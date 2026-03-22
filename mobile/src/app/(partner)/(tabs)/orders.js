import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../../../App';
import { buildApiUrl } from '../../../config';

const COLORS = {
  page: '#FFF9F3',
  surface: '#FFFFFF',
  surfaceAlt: '#FFF6EC',
  line: '#F3E0CD',
  border: '#F0D9C3',
  text: '#2F241C',
  muted: '#7A6758',
  subtle: '#A18B79',
  brand: '#D97651',
  brandSoft: '#FFF0E7',
  success: '#1F8F5F',
  successSoft: '#EAF8F0',
  warning: '#C57B12',
  warningSoft: '#FFF6DE',
  info: '#2C69C9',
  infoSoft: '#EBF2FF',
  danger: '#D45454',
  dangerSoft: '#FDECEC',
  black: '#241A14',
};

const SELLER_PENDING_STATUSES = ['CREATED'];
const SELLER_PREP_STATUSES = ['ACCEPTED_BY_SELLER', 'ASSIGNED_TO_PARTNER'];
const SELLER_READY_STATUSES = ['READY_FOR_PICKUP'];

const CACHE_KEY = '@grab_basket/partner_orders_query_cache_v1';
const STALE_TIME_MS = 60 * 1000;
const CACHE_TIME_MS = 20 * 60 * 1000;
const DEBOUNCE_MS = 280;

const FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'new', label: 'New', icon: 'sparkles-outline' },
  { key: 'preparing', label: 'Preparing', icon: 'flame-outline' },
  { key: 'ready', label: 'Ready', icon: 'cube-outline' },
  { key: 'closed', label: 'Closed', icon: 'archive-outline' },
  { key: 'cod', label: 'COD', icon: 'cash-outline' },
];

const SORT_OPTIONS = [
  { key: 'relevance', label: 'Relevance' },
  { key: 'latest', label: 'Latest' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
];

let memoryCache = {};
let cacheHydrated = false;
let cacheHydrationPromise = null;

function safeJsonParse(raw, fallback = null) {
  try {
    return JSON.parse(raw);
  } catch {
    return fallback;
  }
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

function buildQueryString(params = {}) {
  const pairs = Object.entries(params)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);

  return pairs.length ? `?${pairs.join('&')}` : '';
}

async function request(path, token, { method = 'GET', body, query } = {}) {
  const response = await fetch(`${buildApiUrl(path)}${buildQueryString(query)}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body,
  });

  const raw = await response.text();
  const payload = safeJsonParse(raw, {});

  if (!response.ok) {
    const message =
      (typeof payload?.detail === 'string' && payload.detail) ||
      payload?.error?.message ||
      `Request failed with status ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    throw error;
  }

  return payload;
}

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function formatStatus(status = '') {
  return (
    String(status || '')
      .replace(/_/g, ' ')
      .toLowerCase()
      .replace(/\b\w/g, (char) => char.toUpperCase()) || 'Unknown'
  );
}

function formatDateTime(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';

  return date.toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function summarizeOrder(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  if (!items.length) return 'No items added yet';
  const first = items[0];
  const extra = Math.max(0, items.length - 1);
  return `${Number(first?.qty || 1)} x ${first?.name_snapshot || 'Item'}${extra ? ` +${extra} more` : ''}`;
}

function getLatestEvent(order) {
  const events = Array.isArray(order?.events) ? order.events : [];
  return events.length ? events[events.length - 1] : null;
}

function getStatusTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value.includes('DELIVERED')) {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle-outline' };
  }
  if (value.includes('CANCEL') || value.includes('REJECT')) {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'close-circle-outline' };
  }
  if (value.includes('READY') || value.includes('PICK') || value.includes('ASSIGNED')) {
    return { bg: COLORS.infoSoft, text: COLORS.info, icon: 'bicycle-outline' };
  }
  return { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'time-outline' };
}

function getOrderTimestamp(order) {
  const latest = getLatestEvent(order);
  const raw = latest?.created_at || order?.updated_at || order?.created_at;
  const parsed = raw ? new Date(raw).getTime() : 0;
  return Number.isFinite(parsed) ? parsed : 0;
}

function orderSearchText(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  return [
    `order ${order?.id ?? ''}`,
    order?.status,
    order?.payment_method,
    order?.payment_status,
    order?.vendor_id,
    order?.partner_id,
    summarizeOrder(order),
    items.map((item) => item?.name_snapshot || item?.name || '').join(' '),
    getLatestEvent(order)?.note,
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function getOrderScore(order, search) {
  const query = normalizeText(search);
  if (!query) return 1;

  let score = 0;
  if (String(order?.id ?? '').includes(query)) score += 100;
  if (normalizeText(order?.status).includes(query)) score += 50;
  if (normalizeText(order?.payment_method).includes(query)) score += 16;
  if (normalizeText(summarizeOrder(order)).includes(query)) score += 60;
  if (orderSearchText(order).includes(query)) score += 10;
  return score;
}

function matchesFilter(order, filterKey) {
  const status = String(order?.status || '').toUpperCase();
  const paymentMethod = String(order?.payment_method || '').toUpperCase();
  const isClosed = ![...SELLER_PENDING_STATUSES, ...SELLER_PREP_STATUSES, ...SELLER_READY_STATUSES].includes(status);

  switch (filterKey) {
    case 'new':
      return SELLER_PENDING_STATUSES.includes(status);
    case 'preparing':
      return SELLER_PREP_STATUSES.includes(status);
    case 'ready':
      return SELLER_READY_STATUSES.includes(status);
    case 'closed':
      return isClosed;
    case 'cod':
      return paymentMethod === 'COD';
    default:
      return true;
  }
}

function sortOrders(list = [], sortBy = 'relevance') {
  return [...list].sort((a, b) => {
    if (sortBy === 'latest') return getOrderTimestamp(b) - getOrderTimestamp(a);
    if (sortBy === 'amount') return Number(b?.total_amount || 0) - Number(a?.total_amount || 0);
    if (sortBy === 'status') return String(a?.status || '').localeCompare(String(b?.status || ''));
    return (b.__score || 0) - (a.__score || 0) || getOrderTimestamp(b) - getOrderTimestamp(a);
  });
}

function computeQueryData(orders = [], search, filterKey, sortBy) {
  const query = normalizeText(search);
  const items = sortOrders(
    (orders || [])
      .map((order) => {
        const score = getOrderScore(order, query);
        const passesSearch = !query || score > 0 || orderSearchText(order).includes(query);
        const passesFilter = matchesFilter(order, filterKey);
        if (!passesSearch || !passesFilter) return null;
        return { ...order, __score: score };
      })
      .filter(Boolean),
    sortBy
  );

  return { items, totalMatches: items.length, empty: items.length === 0 };
}

function buildSourceSignature(orders = []) {
  return (orders || [])
    .slice(0, 40)
    .map((order) => `${order?.id}-${order?.status}-${getOrderTimestamp(order)}`)
    .join('|');
}

function buildQueryKey({ search, filterKey, sortBy, sourceSignature }) {
  return ['partner-orders', normalizeText(search), filterKey, sortBy, sourceSignature].join('::');
}

async function hydrateCache() {
  if (cacheHydrated) return memoryCache;
  if (cacheHydrationPromise) return cacheHydrationPromise;

  cacheHydrationPromise = AsyncStorage.getItem(CACHE_KEY)
    .then((raw) => {
      memoryCache = safeJsonParse(raw, {}) || {};
      cacheHydrated = true;
      return memoryCache;
    })
    .catch(() => {
      memoryCache = {};
      cacheHydrated = true;
      return memoryCache;
    })
    .finally(() => {
      cacheHydrationPromise = null;
    });

  return cacheHydrationPromise;
}

function getCacheEntry(key) {
  const entry = memoryCache?.[key];
  if (!entry?.updatedAt) return null;
  if (Date.now() - Number(entry.updatedAt) > CACHE_TIME_MS) {
    delete memoryCache[key];
    return null;
  }
  return entry;
}

async function writeCacheEntry(key, data) {
  await hydrateCache();
  const now = Date.now();
  const next = { ...memoryCache, [key]: { data, updatedAt: now } };

  Object.keys(next).forEach((cacheKey) => {
    if (now - Number(next[cacheKey]?.updatedAt || 0) > CACHE_TIME_MS) {
      delete next[cacheKey];
    }
  });

  memoryCache = next;
  AsyncStorage.setItem(CACHE_KEY, JSON.stringify(next)).catch(() => {});
}

function formatCacheAge(timestamp) {
  if (!timestamp) return 'No cache yet';
  const age = Date.now() - Number(timestamp || 0);
  if (age < 15000) return 'Updated just now';
  if (age < 60000) return 'Updated < 1 min ago';
  if (age < 3600000) return `Updated ${Math.round(age / 60000)} mins ago`;
  return 'Updated earlier';
}

function useDebouncedValue(value, delay = DEBOUNCE_MS) {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}

function useOrderQuery({ orders, search, filterKey, sortBy }) {
  const sourceSignature = useMemo(() => buildSourceSignature(orders), [orders]);
  const queryKey = useMemo(
    () => buildQueryKey({ search, filterKey, sortBy, sourceSignature }),
    [search, filterKey, sortBy, sourceSignature]
  );

  const [state, setState] = useState({
    data: { items: [], totalMatches: 0, empty: false },
    isLoading: true,
    isFetching: false,
    isFromCache: false,
    updatedAt: 0,
  });

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      const cached = getCacheEntry(queryKey);
      const fresh = cached && Date.now() - Number(cached.updatedAt || 0) <= STALE_TIME_MS;

      if (cached?.data) {
        setState({
          data: cached.data,
          isLoading: false,
          isFetching: !fresh,
          isFromCache: true,
          updatedAt: Number(cached.updatedAt || 0),
        });
      } else {
        setState((current) => ({ ...current, isLoading: true, isFetching: true }));
      }

      await hydrateCache();
      if (cancelled) return;

      const hydrated = getCacheEntry(queryKey);
      const hydratedFresh = hydrated && Date.now() - Number(hydrated.updatedAt || 0) <= STALE_TIME_MS;
      if (hydrated?.data) {
        setState({
          data: hydrated.data,
          isLoading: false,
          isFetching: !hydratedFresh,
          isFromCache: true,
          updatedAt: Number(hydrated.updatedAt || 0),
        });
      }

      const next = computeQueryData(orders, search, filterKey, sortBy);
      await writeCacheEntry(queryKey, next);
      if (cancelled) return;

      setState({
        data: next,
        isLoading: false,
        isFetching: false,
        isFromCache: false,
        updatedAt: Date.now(),
      });
    };

    run().catch(() => {
      if (cancelled) return;
      const next = computeQueryData(orders, search, filterKey, sortBy);
      setState({
        data: next,
        isLoading: false,
        isFetching: false,
        isFromCache: false,
        updatedAt: Date.now(),
      });
    });

    return () => {
      cancelled = true;
    };
  }, [orders, queryKey, search, filterKey, sortBy]);

  return state;
}

function SectionCard({ title, subtitle, children }) {
  return (
    <View style={styles.card}>
      {title || subtitle ? (
        <View style={styles.cardHeader}>
          {title ? <Text style={styles.cardTitle}>{title}</Text> : null}
          {subtitle ? <Text style={styles.cardSubtitle}>{subtitle}</Text> : null}
        </View>
      ) : null}
      {children}
    </View>
  );
}

function Pill({ text, tone }) {
  return (
    <View style={[styles.pill, { backgroundColor: tone.bg }]}>
      <Ionicons name={tone.icon} size={14} color={tone.text} />
      <Text style={[styles.pillText, { color: tone.text }]}>{text}</Text>
    </View>
  );
}

function MetaLine({ icon, label }) {
  return (
    <View style={styles.metaLine}>
      <Ionicons name={icon} size={15} color={COLORS.subtle} />
      <Text style={styles.metaLineText}>{label}</Text>
    </View>
  );
}

function PrimaryButton({ label, icon, onPress, disabled = false, tone = 'brand' }) {
  const palette =
    tone === 'success'
      ? { bg: COLORS.success, text: '#FFFFFF', border: COLORS.success }
      : tone === 'danger'
        ? { bg: '#FFFFFF', text: COLORS.danger, border: '#F3C6C6' }
        : tone === 'muted'
          ? { bg: '#FFFFFF', text: COLORS.text, border: COLORS.border }
          : { bg: COLORS.brand, text: '#FFFFFF', border: COLORS.brand };

  return (
    <TouchableOpacity
      activeOpacity={0.9}
      disabled={disabled}
      onPress={onPress}
      style={[
        styles.primaryButton,
        { backgroundColor: disabled ? '#E8DED5' : palette.bg, borderColor: disabled ? '#E8DED5' : palette.border },
      ]}>
      {icon ? <Ionicons name={icon} size={16} color={disabled ? '#907E70' : palette.text} /> : null}
      <Text style={[styles.primaryButtonText, { color: disabled ? '#907E70' : palette.text }]}>{label}</Text>
    </TouchableOpacity>
  );
}

function EmptyState({ icon = 'search-outline', title, subtitle }) {
  return (
    <View style={styles.emptyState}>
      <View style={styles.emptyIconWrap}>
        <Ionicons name={icon} size={22} color={COLORS.brand} />
      </View>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

function Timeline({ events = [] }) {
  if (!events.length) {
    return (
      <View style={styles.timelineEmpty}>
        <Text style={styles.timelineEmptyTitle}>No timeline events yet</Text>
        <Text style={styles.timelineEmptySubtitle}>This order will become easier to audit once more lifecycle updates are stored.</Text>
      </View>
    );
  }

  return (
    <View style={styles.timelineWrap}>
      {events.map((event, index) => {
        const tone = getStatusTone(event?.status);
        const isLast = index === events.length - 1;

        return (
          <View key={`${event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.text }]} />
              {!isLast ? <View style={styles.timelineLine} /> : null}
            </View>
            <View style={styles.timelineBody}>
              <Text style={styles.timelineTitle}>{formatStatus(event?.status)}</Text>
              <Text style={styles.timelineMeta}>{formatDateTime(event?.created_at)}</Text>
              {event?.note ? <Text style={styles.timelineNote}>{event.note}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}

function OrderCard({ order, actions = [] }) {
  const [expanded, setExpanded] = useState(false);
  const tone = getStatusTone(order?.status);
  const latestEvent = getLatestEvent(order);
  const hasTimeline = Array.isArray(order?.events) && order.events.length > 0;

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderTopRow}>
        <View style={styles.orderMetaWrap}>
          <Text style={styles.orderTitle}>Order #{order?.id}</Text>
          <Text style={styles.orderSubtitle}>{summarizeOrder(order)}</Text>
        </View>
        <Pill text={formatStatus(order?.status)} tone={tone} />
      </View>

      <View style={styles.metaList}>
        <MetaLine icon="bag-handle-outline" label={`Vendor #${order?.vendor_id || '—'}`} />
        <MetaLine icon="wallet-outline" label={`${money(order?.total_amount)} · ${String(order?.payment_method || '').toUpperCase()}`} />
        <MetaLine icon="card-outline" label={`Payment ${formatStatus(order?.payment_status || 'PENDING')}`} />
        <MetaLine icon="time-outline" label={latestEvent ? `${formatStatus(latestEvent.status)} · ${formatDateTime(latestEvent.created_at)}` : 'Timeline not available yet'} />
        {order?.partner_id ? <MetaLine icon="person-outline" label={`Partner #${order.partner_id}`} /> : null}
      </View>

      {actions.length ? <View style={styles.buttonRow}>{actions}</View> : null}

      {hasTimeline ? (
        <TouchableOpacity activeOpacity={0.9} style={styles.expandRow} onPress={() => setExpanded((current) => !current)}>
          <Ionicons name={expanded ? 'chevron-up-outline' : 'chevron-down-outline'} size={16} color={COLORS.muted} />
          <Text style={styles.expandRowText}>{expanded ? 'Hide timeline' : 'Show timeline'}</Text>
        </TouchableOpacity>
      ) : null}

      {expanded ? <Timeline events={order?.events || []} /> : null}
    </View>
  );
}

function QuerySearchBar({ value, onChangeText, onClear, onToggleFilters, filtersOpen, filterCount, placeholder }) {
  return (
    <View style={styles.querySearchBar}>
      <Ionicons name="search-outline" size={18} color={COLORS.muted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.subtle}
        style={styles.querySearchInput}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
      />
      {value ? (
        <TouchableOpacity activeOpacity={0.9} onPress={onClear} style={styles.queryIconButton}>
          <Ionicons name="close-outline" size={17} color={COLORS.muted} />
        </TouchableOpacity>
      ) : null}
      <TouchableOpacity
        activeOpacity={0.9}
        onPress={onToggleFilters}
        style={[styles.queryFilterButton, filtersOpen && styles.queryFilterButtonActive]}>
        <Ionicons name="options-outline" size={18} color={filtersOpen ? '#FFFFFF' : COLORS.brand} />
        {filterCount > 0 ? (
          <View style={styles.queryFilterBadge}>
            <Text style={styles.queryFilterBadgeText}>{filterCount}</Text>
          </View>
        ) : null}
      </TouchableOpacity>
    </View>
  );
}

function QueryMetaBanner({ isFetching, isFromCache, updatedAt }) {
  const title = isFetching
    ? isFromCache
      ? 'Showing cached results while refreshing'
      : 'Refreshing results'
    : isFromCache
      ? 'Showing cached results'
      : 'Showing fresh results';

  return (
    <View style={styles.queryMetaBanner}>
      <View style={styles.queryMetaLeft}>
        {isFetching ? <ActivityIndicator size="small" color={COLORS.brand} /> : <Ionicons name={isFromCache ? 'cloud-outline' : 'sparkles-outline'} size={16} color={COLORS.brand} />}
        <Text style={styles.queryMetaText}>{title}</Text>
      </View>
      <Text style={styles.queryMetaTime}>{formatCacheAge(updatedAt)}</Text>
    </View>
  );
}

function QueryChip({ label, icon, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.9} onPress={onPress} style={[styles.queryChip, active && styles.queryChipActive]}>
      <Ionicons name={icon} size={15} color={active ? '#FFFFFF' : COLORS.muted} />
      <Text style={[styles.queryChipText, active && styles.queryChipTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function QuerySortChip({ label, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.9} onPress={onPress} style={[styles.querySortChip, active && styles.querySortChipActive]}>
      <Text style={[styles.querySortChipText, active && styles.querySortChipTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function QueryControlsPanel({ filterKey, setFilterKey, sortBy, setSortBy }) {
  return (
    <View style={styles.queryControlsPanel}>
      <Text style={styles.queryPanelTitle}>Quick filters</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.queryChipRow}>
        {FILTERS.map((item) => (
          <QueryChip key={item.key} label={item.label} icon={item.icon} active={filterKey === item.key} onPress={() => setFilterKey(item.key)} />
        ))}
      </ScrollView>

      <Text style={styles.queryPanelTitle}>Sort by</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.queryChipRow}>
        {SORT_OPTIONS.map((item) => (
          <QuerySortChip key={item.key} label={item.label} active={sortBy === item.key} onPress={() => setSortBy(item.key)} />
        ))}
      </ScrollView>
    </View>
  );
}

function QuerySummaryCard({ totalMatches, search, filterLabel, sortLabel, onReset }) {
  return (
    <View style={styles.querySummaryCard}>
      <View style={styles.querySummaryRow}>
        <Text style={styles.querySummaryTitle}>{totalMatches} result{totalMatches === 1 ? '' : 's'} found</Text>
        <TouchableOpacity activeOpacity={0.9} onPress={onReset}>
          <Text style={styles.querySummaryAction}>Reset</Text>
        </TouchableOpacity>
      </View>
      <View style={styles.querySummaryChipRow}>
        {search ? <View style={styles.querySummaryChip}><Text style={styles.querySummaryChipText}>Search: “{search}”</Text></View> : null}
        {filterLabel && filterLabel !== 'All' ? <View style={styles.querySummaryChip}><Text style={styles.querySummaryChipText}>Filter: {filterLabel}</Text></View> : null}
        {sortLabel && sortLabel !== 'Relevance' ? <View style={styles.querySummaryChip}><Text style={styles.querySummaryChipText}>Sort: {sortLabel}</Text></View> : null}
      </View>
    </View>
  );
}

export default function PartnerOrdersScreen() {
  const { authToken, sessionReady, isAuthenticated, logout, appVariantName } = useGrabBasket();
  const tabBarHeight = useBottomTabBarHeight();

  const [orders, setOrders] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingAction, setLoadingAction] = useState(false);
  const [search, setSearch] = useState('');
  const [filterKey, setFilterKey] = useState('all');
  const [sortBy, setSortBy] = useState('relevance');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const debouncedSearch = useDebouncedValue(search);

  const loadData = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) return;
      try {
        if (!silent) setRefreshing(true);
        const response = await request('/seller/orders', authToken).catch((error) => {
          if (error?.status === 404) return [];
          throw error;
        });
        setOrders(Array.isArray(response) ? response : []);
      } catch (error) {
        Alert.alert(`${appVariantName} sync failed`, error?.message || 'Could not load seller orders.');
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        if (!silent) setRefreshing(false);
      }
    },
    [appVariantName, authToken, logout]
  );

  useEffect(() => {
    if (!sessionReady || !isAuthenticated || !authToken) return;
    loadData({ silent: false }).catch(() => {});
  }, [authToken, isAuthenticated, loadData, sessionReady]);

  const refresh = useCallback(() => loadData({ silent: false }), [loadData]);

  const runAction = useCallback(
    async (work, successMessage) => {
      try {
        setLoadingAction(true);
        await work();
        if (successMessage) Alert.alert('Done', successMessage);
        await loadData({ silent: true });
      } catch (error) {
        Alert.alert('Action failed', error?.message || 'Please try again.');
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        setLoadingAction(false);
      }
    },
    [loadData, logout]
  );

  const acceptOrder = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/seller/orders/${orderId}/accept`, authToken, { method: 'POST' });
      }, `Order #${orderId} accepted.`);
    },
    [authToken, runAction]
  );

  const rejectOrder = useCallback(
    (orderId) => {
      Alert.alert('Reject order', `Reject order #${orderId}?`, [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Reject',
          style: 'destructive',
          onPress: () =>
            runAction(async () => {
              await request(`/seller/orders/${orderId}/reject`, authToken, {
                method: 'POST',
                query: { reason: 'Rejected from seller app' },
              });
            }, `Order #${orderId} rejected.`),
        },
      ]);
    },
    [authToken, runAction]
  );

  const readyOrder = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/seller/orders/${orderId}/ready`, authToken, { method: 'POST' });
      }, `Order #${orderId} is ready for pickup.`);
    },
    [authToken, runAction]
  );

  const queryState = useOrderQuery({ orders, search: debouncedSearch, filterKey, sortBy });

  const created = useMemo(
    () => queryState.data.items.filter((item) => SELLER_PENDING_STATUSES.includes(String(item.status || '').toUpperCase())),
    [queryState.data.items]
  );
  const preparing = useMemo(
    () => queryState.data.items.filter((item) => SELLER_PREP_STATUSES.includes(String(item.status || '').toUpperCase())),
    [queryState.data.items]
  );
  const ready = useMemo(
    () => queryState.data.items.filter((item) => SELLER_READY_STATUSES.includes(String(item.status || '').toUpperCase())),
    [queryState.data.items]
  );
  const closed = useMemo(
    () =>
      queryState.data.items.filter(
        (item) => ![...SELLER_PENDING_STATUSES, ...SELLER_PREP_STATUSES, ...SELLER_READY_STATUSES].includes(String(item.status || '').toUpperCase())
      ),
    [queryState.data.items]
  );

  const isQueryActive = Boolean(normalizeText(debouncedSearch)) || filterKey !== 'all' || sortBy !== 'relevance';
  const activeControlCount = Number(filterKey !== 'all') + Number(sortBy !== 'relevance');
  const activeFilterDef = FILTERS.find((item) => item.key === filterKey) || FILTERS[0];
  const activeSortDef = SORT_OPTIONS.find((item) => item.key === sortBy) || SORT_OPTIONS[0];

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.centerState}>
          <ActivityIndicator color={COLORS.brand} />
          <Text style={styles.centerTitle}>Preparing {appVariantName}</Text>
          <Text style={styles.centerSubtitle}>Loading the latest authenticated state.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.centerState}>
          <Ionicons name="lock-closed-outline" size={28} color={COLORS.brand} />
          <Text style={styles.centerTitle}>Sign in required</Text>
          <Text style={styles.centerSubtitle}>Use the seller account flow before opening order operations.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingHorizontal: 16, paddingTop: 12, paddingBottom: tabBarHeight + 20 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} />}>
        <SectionCard title="Search seller orders" subtitle="Find orders by id, item names, status, or payment mode. Cached results stay visible while the queue refreshes.">
          <QuerySearchBar
            value={search}
            onChangeText={setSearch}
            onClear={() => setSearch('')}
            onToggleFilters={() => setFiltersOpen((current) => !current)}
            filtersOpen={filtersOpen}
            filterCount={activeControlCount}
            placeholder="Search order id, item, preparing, COD"
          />

          <QueryMetaBanner isFetching={queryState.isFetching} isFromCache={queryState.isFromCache} updatedAt={queryState.updatedAt} />

          {filtersOpen ? <QueryControlsPanel filterKey={filterKey} setFilterKey={setFilterKey} sortBy={sortBy} setSortBy={setSortBy} /> : null}

          {isQueryActive ? (
            <QuerySummaryCard
              totalMatches={queryState.data.totalMatches}
              search={debouncedSearch}
              filterLabel={activeFilterDef?.label}
              sortLabel={activeSortDef?.label}
              onReset={() => {
                setSearch('');
                setFilterKey('all');
                setSortBy('relevance');
              }}
            />
          ) : null}
        </SectionCard>

        <SectionCard title="New orders" subtitle="Accept or reject the queue quickly.">
          {queryState.isLoading ? (
            <EmptyState icon="refresh-outline" title="Loading seller queue" subtitle="Preparing cached order search results for the partner app." />
          ) : created.length ? (
            created.map((order) => (
              <OrderCard
                key={order.id}
                order={order}
                actions={[
                  <PrimaryButton key="accept" label="Accept" icon="checkmark-outline" onPress={() => acceptOrder(order.id)} disabled={loadingAction} tone="success" />,
                  <PrimaryButton key="reject" label="Reject" icon="close-outline" onPress={() => rejectOrder(order.id)} disabled={loadingAction} tone="danger" />,
                ]}
              />
            ))
          ) : (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'sparkles-outline'}
              title={isQueryActive ? 'No new-order matches' : 'No new orders'}
              subtitle={isQueryActive ? 'Try a broader search term or reset the active filters.' : 'Fresh customer orders will appear here.'}
            />
          )}
        </SectionCard>

        <SectionCard title="Preparing orders" subtitle="Advance accepted orders to pickup readiness.">
          {!queryState.isLoading && preparing.length ? (
            preparing.map((order) => (
              <OrderCard
                key={order.id}
                order={order}
                actions={[
                  <PrimaryButton key="ready" label="Ready for pickup" icon="cube-outline" onPress={() => readyOrder(order.id)} disabled={loadingAction} tone="brand" />,
                ]}
              />
            ))
          ) : !queryState.isLoading ? (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'restaurant-outline'}
              title={isQueryActive ? 'No prep matches' : 'Nothing in preparation'}
              subtitle={isQueryActive ? 'The current filters do not match any preparing orders.' : 'Accepted orders will move here.'}
            />
          ) : null}
        </SectionCard>

        <SectionCard title="Ready / closed orders" subtitle="Useful for kitchen visibility and dispatch verification.">
          {!queryState.isLoading && [...ready, ...closed].length ? (
            [...ready, ...closed].slice(0, 12).map((order) => <OrderCard key={order.id} order={order} />)
          ) : !queryState.isLoading ? (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'checkmark-done-outline'}
              title={isQueryActive ? 'No ready or closed matches' : 'No history yet'}
              subtitle={isQueryActive ? 'The current search and filter setup does not match any ready or closed orders.' : 'Once orders move forward, they will show here.'}
            />
          ) : null}
        </SectionCard>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: COLORS.page },
  centerState: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 28, gap: 8 },
  centerTitle: { fontSize: 18, fontWeight: '800', color: COLORS.text },
  centerSubtitle: { fontSize: 13, lineHeight: 19, textAlign: 'center', color: COLORS.muted },
  card: { backgroundColor: COLORS.surface, borderRadius: 24, padding: 16, borderWidth: 1, borderColor: COLORS.border, marginBottom: 14 },
  cardHeader: { gap: 4, marginBottom: 14 },
  cardTitle: { fontSize: 17, fontWeight: '800', color: COLORS.text },
  cardSubtitle: { fontSize: 13, lineHeight: 19, color: COLORS.muted },
  querySearchBar: { minHeight: 54, borderRadius: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.surfaceAlt, paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center', gap: 10 },
  querySearchInput: { flex: 1, fontSize: 14, color: COLORS.text, fontWeight: '600' },
  queryIconButton: { width: 26, height: 26, borderRadius: 13, alignItems: 'center', justifyContent: 'center' },
  queryFilterButton: { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.brandSoft, position: 'relative' },
  queryFilterButtonActive: { backgroundColor: COLORS.brand },
  queryFilterBadge: { position: 'absolute', top: -4, right: -4, minWidth: 16, height: 16, borderRadius: 8, backgroundColor: COLORS.black, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4 },
  queryFilterBadgeText: { color: '#FFFFFF', fontSize: 10, fontWeight: '800' },
  queryMetaBanner: { marginTop: 12, minHeight: 42, borderRadius: 14, borderWidth: 1, borderColor: COLORS.line, backgroundColor: COLORS.surfaceAlt, paddingHorizontal: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 },
  queryMetaLeft: { flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 },
  queryMetaText: { flex: 1, fontSize: 12, fontWeight: '800', color: COLORS.text },
  queryMetaTime: { fontSize: 11, fontWeight: '700', color: COLORS.muted },
  queryControlsPanel: { marginTop: 12, borderRadius: 16, borderWidth: 1, borderColor: COLORS.line, backgroundColor: '#FFFDFC', padding: 12 },
  queryPanelTitle: { fontSize: 12, fontWeight: '800', color: COLORS.text, marginBottom: 10 },
  queryChipRow: { gap: 10, paddingBottom: 8 },
  queryChip: { minHeight: 36, paddingHorizontal: 12, borderRadius: 18, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.surface, flexDirection: 'row', alignItems: 'center', gap: 7 },
  queryChipActive: { backgroundColor: COLORS.brand, borderColor: COLORS.brand },
  queryChipText: { fontSize: 12, fontWeight: '800', color: COLORS.text },
  queryChipTextActive: { color: '#FFFFFF' },
  querySortChip: { minHeight: 34, paddingHorizontal: 12, borderRadius: 17, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.surface, justifyContent: 'center' },
  querySortChipActive: { backgroundColor: COLORS.info, borderColor: COLORS.info },
  querySortChipText: { fontSize: 12, fontWeight: '800', color: COLORS.text },
  querySortChipTextActive: { color: '#FFFFFF' },
  querySummaryCard: { marginTop: 12, borderRadius: 16, borderWidth: 1, borderColor: COLORS.line, backgroundColor: COLORS.surfaceAlt, padding: 12, gap: 10 },
  querySummaryRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 },
  querySummaryTitle: { flex: 1, fontSize: 15, fontWeight: '800', color: COLORS.text },
  querySummaryAction: { fontSize: 12, fontWeight: '800', color: COLORS.brand },
  querySummaryChipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  querySummaryChip: { minHeight: 30, paddingHorizontal: 10, borderRadius: 15, backgroundColor: COLORS.surface, alignItems: 'center', justifyContent: 'center' },
  querySummaryChipText: { fontSize: 11, fontWeight: '800', color: COLORS.text },
  pill: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  pillText: { fontSize: 12, fontWeight: '700' },
  primaryButton: { minHeight: 42, borderRadius: 14, paddingHorizontal: 14, borderWidth: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 },
  primaryButtonText: { fontSize: 13, fontWeight: '800' },
  orderCard: { borderRadius: 18, padding: 14, backgroundColor: COLORS.surfaceAlt, borderWidth: 1, borderColor: COLORS.line, marginBottom: 10 },
  orderTopRow: { flexDirection: 'row', gap: 10, justifyContent: 'space-between', alignItems: 'flex-start' },
  orderMetaWrap: { flex: 1, gap: 4, paddingRight: 8 },
  orderTitle: { fontSize: 15, fontWeight: '800', color: COLORS.text },
  orderSubtitle: { fontSize: 13, lineHeight: 19, color: COLORS.muted },
  metaList: { gap: 9, marginTop: 12 },
  metaLine: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  metaLineText: { flex: 1, fontSize: 13, lineHeight: 19, color: COLORS.muted },
  buttonRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 14 },
  expandRow: { marginTop: 12, alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 4 },
  expandRowText: { fontSize: 12, fontWeight: '700', color: COLORS.muted },
  timelineWrap: { marginTop: 14, paddingTop: 12, borderTopWidth: 1, borderTopColor: COLORS.line },
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, minHeight: 58 },
  timelineRail: { width: 18, alignItems: 'center' },
  timelineDot: { width: 10, height: 10, borderRadius: 5, marginTop: 5 },
  timelineLine: { width: 2, flex: 1, marginTop: 6, backgroundColor: COLORS.line },
  timelineBody: { flex: 1, paddingBottom: 12 },
  timelineTitle: { fontSize: 13, fontWeight: '800', color: COLORS.text },
  timelineMeta: { marginTop: 2, fontSize: 12, color: COLORS.muted },
  timelineNote: { marginTop: 4, fontSize: 12, lineHeight: 18, color: COLORS.text },
  timelineEmpty: { marginTop: 14, borderRadius: 14, backgroundColor: COLORS.surface, borderWidth: 1, borderColor: COLORS.line, padding: 14 },
  timelineEmptyTitle: { fontSize: 13, fontWeight: '800', color: COLORS.text },
  timelineEmptySubtitle: { marginTop: 4, fontSize: 12, lineHeight: 18, color: COLORS.muted },
  emptyState: { alignItems: 'center', gap: 8, paddingVertical: 22 },
  emptyIconWrap: { width: 42, height: 42, borderRadius: 21, backgroundColor: COLORS.brandSoft, alignItems: 'center', justifyContent: 'center' },
  emptyTitle: { fontSize: 15, fontWeight: '800', color: COLORS.text },
  emptySubtitle: { fontSize: 13, lineHeight: 19, textAlign: 'center', color: COLORS.muted },
});