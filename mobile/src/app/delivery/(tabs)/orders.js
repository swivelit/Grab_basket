import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  DeviceEventEmitter,
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
import { useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { BrandPalette, createShadow } from '@/constants/theme';

import { useGrabBasket } from '../../../../App';
import InlineErrorCard from '../../../components/inline-error-card';
import InlineNoticeCard from '../../../components/inline-notice-card';
import { getErrorMessage, requestJson } from '../../../lib/api-client';
import { APP_ENV } from '../../../config';
import { captureEvent } from '../../../lib/telemetry';
import { FEATURE_FLAGS } from '../../../constants/feature-flags';
import { ANALYTICS_EVENTS, ANALYTICS_TAXONOMY_VERSION } from '../../../constants/analytics-taxonomy';

const COLORS = {
  page: BrandPalette.page,
  surface: BrandPalette.surface,
  surfaceAlt: BrandPalette.surfaceAlt,
  line: BrandPalette.line,
  border: BrandPalette.border,
  text: BrandPalette.text,
  muted: BrandPalette.textMuted,
  subtle: BrandPalette.textSubtle,
  brand: BrandPalette.primary,
  brandSoft: BrandPalette.primarySoft,
  success: BrandPalette.success,
  successSoft: BrandPalette.successSoft,
  warning: BrandPalette.warning,
  warningSoft: BrandPalette.warningSoft,
  info: BrandPalette.info,
  infoSoft: BrandPalette.infoSoft,
  danger: BrandPalette.danger,
  dangerSoft: BrandPalette.dangerSoft,
  black: BrandPalette.ink,
  white: BrandPalette.white,
};

const ACTIVE_STATUSES = ['ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP', 'PICKED_UP'];
const CACHE_KEY = '@grab_basket/delivery_orders_query_cache_v1';
const STALE_TIME_MS = 60 * 1000;
const CACHE_TIME_MS = 20 * 60 * 1000;
const DEBOUNCE_MS = 280;

const FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'active', label: 'Active', icon: 'bicycle-outline' },
  { key: 'pickup', label: 'Picked up', icon: 'bag-check-outline' },
  { key: 'delivered', label: 'Delivered', icon: 'checkmark-circle-outline' },
  { key: 'cod', label: 'COD', icon: 'cash-outline' },
  { key: 'closed', label: 'Closed', icon: 'archive-outline' },
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

async function request(path, token, { method = 'GET', body, query } = {}) {
  return requestJson(path, {
    method,
    token,
    query,
    body: typeof body === 'string' ? JSON.parse(body) : body,
  });
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
  if (value.includes('PICK') || value.includes('READY') || value.includes('ASSIGNED')) {
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

  switch (filterKey) {
    case 'active':
      return ACTIVE_STATUSES.includes(status);
    case 'pickup':
      return status === 'PICKED_UP';
    case 'delivered':
      return status === 'DELIVERED';
    case 'cod':
      return paymentMethod === 'COD';
    case 'closed':
      return !ACTIVE_STATUSES.includes(status);
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
  return ['delivery-orders', normalizeText(search), filterKey, sortBy, sourceSignature].join('::');
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
      ? { bg: COLORS.success, text: COLORS.white, border: COLORS.success }
      : tone === 'danger'
        ? { bg: COLORS.white, text: COLORS.danger, border: COLORS.dangerSoft }
        : tone === 'muted'
          ? { bg: COLORS.white, text: COLORS.text, border: COLORS.border }
          : { bg: COLORS.brand, text: COLORS.white, border: COLORS.brand };

  return (
    <TouchableOpacity
      activeOpacity={0.9}
      disabled={disabled}
      onPress={onPress}
      style={[
        styles.primaryButton,
        { backgroundColor: disabled ? COLORS.border : palette.bg, borderColor: disabled ? COLORS.border : palette.border },
      ]}>
      {icon ? <Ionicons name={icon} size={16} color={disabled ? COLORS.subtle : palette.text} /> : null}
      <Text style={[styles.primaryButtonText, { color: disabled ? COLORS.subtle : palette.text }]}>{label}</Text>
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
        <Ionicons name="options-outline" size={18} color={filtersOpen ? COLORS.white : COLORS.brand} />
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
      <Ionicons name={icon} size={15} color={active ? COLORS.white : COLORS.muted} />
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

export default function DeliveryOrdersScreen() {
  const { authToken, sessionReady, isAuthenticated, logout, appVariantName } = useGrabBasket();
  const tabBarHeight = useBottomTabBarHeight();
  const params = useLocalSearchParams();
  const highlightOrderId = String(params?.highlightOrderId || params?.orderId || '').trim();

  const [orders, setOrders] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingAction, setLoadingAction] = useState(false);
  const [search, setSearch] = useState('');
  const [filterKey, setFilterKey] = useState('all');
  const [sortBy, setSortBy] = useState('relevance');
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [inlineError, setInlineError] = useState('');
  const [inlineNotice, setInlineNotice] = useState(null);

  const debouncedSearch = useDebouncedValue(search);

  const showNotice = useCallback((title, message, tone = 'success') => {
    setInlineNotice({ title, message, tone });
  }, []);

  const clearNotice = useCallback(() => {
    setInlineNotice(null);
  }, []);

  const showError = useCallback((message, fallback = 'Please try again.') => {
    setInlineError(getErrorMessage(message, fallback));
  }, []);

  const clearError = useCallback(() => {
    setInlineError('');
  }, []);

  const loadData = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) return;
      try {
        if (!silent) setRefreshing(true);
        const response = await request('/partner/orders', authToken, { query: { limit: 100 } });
        setOrders(Array.isArray(response) ? response : []);
        clearError();
      } catch (error) {
        showError(error, `${appVariantName} could not load delivery orders.`);
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        if (!silent) setRefreshing(false);
      }
    },
    [appVariantName, authToken, clearError, logout, showError]
  );

  useEffect(() => {
    if (!sessionReady || !isAuthenticated || !authToken) return;
    loadData({ silent: false }).catch(() => {});
  }, [authToken, isAuthenticated, loadData, sessionReady]);

  useEffect(() => {
    if (!highlightOrderId) return;
    setSearch(highlightOrderId);
    setFilterKey('all');
    setSortBy('latest');
    setFiltersOpen(true);
  }, [highlightOrderId]);

  useEffect(() => {
    const syncSub = DeviceEventEmitter.addListener('grab_basket:orders_sync_requested', (payload) => {
      const targetVariant = String(payload?.app_variant || '').trim().toLowerCase();
      if (targetVariant && targetVariant !== 'delivery') return;
      loadData({ silent: true }).catch(() => {});
    });

    const openSub = DeviceEventEmitter.addListener('grab_basket:push_order_open_requested', (payload) => {
      const targetVariant = String(payload?.app_variant || '').trim().toLowerCase();
      const nextOrderId = String(payload?.order_id || '').trim();
      if (targetVariant && targetVariant !== 'delivery') return;
      if (!nextOrderId) return;
      setSearch(nextOrderId);
      setFilterKey('all');
      setSortBy('latest');
      setFiltersOpen(true);
      showNotice('Live update', `Showing order #${nextOrderId} from the latest notification.`, 'success');
      loadData({ silent: true }).catch(() => {});
    });

    return () => {
      syncSub.remove();
      openSub.remove();
    };
  }, [loadData, showNotice]);

  const refresh = useCallback(() => loadData({ silent: false }), [loadData]);

  const runAction = useCallback(
    async (work, successMessage) => {
      try {
        setLoadingAction(true);
        await work();
        clearError();
        if (successMessage) {
          showNotice('Done', successMessage, 'success');
        }
        await loadData({ silent: true });
      } catch (error) {
        showError(error, 'Please try again.');
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        setLoadingAction(false);
      }
    },
    [clearError, loadData, logout, showError, showNotice]
  );

  const pickup = useCallback(
    (orderId) => {
      captureEvent(ANALYTICS_EVENTS.deliveryOrderPickupTapped, {
        taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
        order_id: String(orderId || ''),
        app_env: APP_ENV,
      });
      runAction(async () => {
        await request(`/partner/orders/${orderId}/pickup`, authToken, { method: 'POST' });
      }, `Order #${orderId} marked as picked up.`);
    },
    [authToken, runAction]
  );

  const deliver = useCallback(
    (orderId) => {
      captureEvent(ANALYTICS_EVENTS.deliveryOrderDeliverTapped, {
        taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
        order_id: String(orderId || ''),
        app_env: APP_ENV,
      });
      runAction(async () => {
        await request(`/partner/orders/${orderId}/deliver`, authToken, { method: 'POST' });
      }, `Order #${orderId} marked as delivered.`);
    },
    [authToken, runAction]
  );

  const queryState = useOrderQuery({ orders, search: debouncedSearch, filterKey, sortBy });

  const activeOrders = useMemo(
    () => queryState.data.items.filter((item) => ACTIVE_STATUSES.includes(String(item.status || '').toUpperCase())),
    [queryState.data.items]
  );

  const completedOrders = useMemo(
    () => queryState.data.items.filter((item) => !ACTIVE_STATUSES.includes(String(item.status || '').toUpperCase())),
    [queryState.data.items]
  );
  const customerImpact = useMemo(() => {
    const delivered = completedOrders.filter((item) => String(item?.status || '').toUpperCase() === 'DELIVERED').length;
    const active = activeOrders.length;
    const pendingPickup = activeOrders.filter((item) =>
      ['ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP'].includes(String(item?.status || '').toUpperCase())
    ).length;

    return { delivered, active, pendingPickup };
  }, [activeOrders, completedOrders]);

  const isQueryActive = Boolean(normalizeText(debouncedSearch)) || filterKey !== 'all' || sortBy !== 'relevance';
  const activeControlCount = Number(filterKey !== 'all') + Number(sortBy !== 'relevance');
  const activeFilterDef = FILTERS.find((item) => item.key === filterKey) || FILTERS[0];
  const activeSortDef = SORT_OPTIONS.find((item) => item.key === sortBy) || SORT_OPTIONS[0];

  useEffect(() => {
    captureEvent(ANALYTICS_EVENTS.deliveryOrdersScreenViewed, {
      taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
      app_env: APP_ENV,
      staged_rollout: FEATURE_FLAGS.stagedRollout,
    });
  }, []);

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
          <Text style={styles.centerSubtitle}>Use the delivery account flow before opening rider orders.</Text>
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
        <View style={styles.feedbackStack}>
          <InlineErrorCard
            title={`${appVariantName} sync issue`}
            message={inlineError}
            onRetry={refresh}
            onDismiss={clearError}
          />
          <InlineNoticeCard
            title={inlineNotice?.title || 'Updated'}
            message={inlineNotice?.message || ''}
            tone={inlineNotice?.tone || 'success'}
            onDismiss={clearNotice}
          />
        </View>
        <SectionCard
          title="Search delivery queue"
          subtitle="Search by order id, item names, status, or payment mode. Cached results stay visible while the list refreshes.">
          <QuerySearchBar
            value={search}
            onChangeText={setSearch}
            onClear={() => setSearch('')}
            onToggleFilters={() => setFiltersOpen((current) => !current)}
            filtersOpen={filtersOpen}
            filterCount={activeControlCount}
            placeholder="Search order id, item, COD, delivered"
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

        <SectionCard
          title="Customer trust signals"
          subtitle="Delivery reliability drives ratings, review tone, refunds, and repeat intent on the customer app.">
          <View style={styles.trustStatsRow}>
            <View style={styles.trustStatCard}>
              <Text style={styles.trustStatValue}>{customerImpact.active}</Text>
              <Text style={styles.trustStatLabel}>Active trips</Text>
            </View>
            <View style={styles.trustStatCard}>
              <Text style={styles.trustStatValue}>{customerImpact.pendingPickup}</Text>
              <Text style={styles.trustStatLabel}>Awaiting pickup</Text>
            </View>
            <View style={styles.trustStatCard}>
              <Text style={styles.trustStatValue}>{customerImpact.delivered}</Text>
              <Text style={styles.trustStatLabel}>Delivered today</Text>
            </View>
          </View>
          <Text style={styles.trustCopy}>
            Fast pickup confirmations and clear delivery completion reduce support tickets and refund pressure.
          </Text>
        </SectionCard>

        <SectionCard
          title="Release governance"
          subtitle="Rollout controls protect production while validating trust metrics in staging first.">
          <View style={styles.governanceWrap}>
            <Text style={styles.governanceText}>Environment: {String(APP_ENV || 'development').toUpperCase()}</Text>
            <Text style={styles.governanceText}>Staged rollout: {FEATURE_FLAGS.stagedRollout ? 'Enabled' : 'Disabled'}</Text>
            <Text style={styles.governanceText}>Crash monitoring: {FEATURE_FLAGS.crashMonitoring ? 'Enabled' : 'Disabled'}</Text>
            <Text style={styles.governanceText}>Rollout control: {FEATURE_FLAGS.rolloutControl ? 'Enabled' : 'Disabled'}</Text>
          </View>
        </SectionCard>

        <SectionCard title="Assigned orders" subtitle="Fast rider actions matter more than decorative screens.">
          {queryState.isLoading ? (
            <EmptyState icon="refresh-outline" title="Loading delivery queue" subtitle="Preparing cached search results for the rider app." />
          ) : activeOrders.length ? (
            activeOrders.map((order) => {
              const isPickedUp = String(order.status || '').toUpperCase() === 'PICKED_UP';
              return (
                <OrderCard
                  key={order.id}
                  order={order}
                  actions={[
                    <PrimaryButton
                      key={isPickedUp ? 'deliver' : 'pickup'}
                      label={isPickedUp ? 'Complete delivery' : 'Confirm pickup'}
                      icon={isPickedUp ? 'checkmark-circle-outline' : 'bag-check-outline'}
                      onPress={() => (isPickedUp ? deliver(order.id) : pickup(order.id))}
                      disabled={loadingAction}
                      tone={isPickedUp ? 'success' : 'brand'}
                    />,
                  ]}
                />
              );
            })
          ) : (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'time-outline'}
              title={isQueryActive ? 'No active matches' : 'Nothing assigned'}
              subtitle={isQueryActive ? 'Try a broader search term or reset the active filters.' : 'Your dispatch queue is empty right now.'}
            />
          )}
        </SectionCard>

        <SectionCard title="Recent completed / closed orders" subtitle="Delivered and cancelled orders stay visible here so the rider can verify history.">
          {!queryState.isLoading && completedOrders.length ? (
            completedOrders.slice(0, 12).map((order) => <OrderCard key={order.id} order={order} />)
          ) : !queryState.isLoading ? (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'checkmark-done-outline'}
              title={isQueryActive ? 'No completed matches' : 'No completed history yet'}
              subtitle={isQueryActive ? 'The current search and filters do not match any completed orders.' : 'Delivered or closed orders will appear here.'}
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
  feedbackStack: { gap: 12, marginBottom: 14 },
  card: {
    backgroundColor: COLORS.surface,
    borderRadius: 24,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 14,
  },
  cardHeader: { gap: 4, marginBottom: 14 },
  cardTitle: { fontSize: 17, fontWeight: '800', color: COLORS.text },
  cardSubtitle: { fontSize: 13, lineHeight: 19, color: COLORS.muted },
  trustStatsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  trustStatCard: {
    flex: 1,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.surfaceAlt,
    paddingVertical: 12,
    paddingHorizontal: 10,
    alignItems: 'center',
  },
  trustStatValue: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  trustStatLabel: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 11,
    fontWeight: '700',
  },
  trustCopy: {
    marginTop: 10,
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 18,
  },
  governanceWrap: {
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 12,
    paddingVertical: 10,
    gap: 6,
  },
  governanceText: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 18,
  },
  querySearchBar: {
    minHeight: 54,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  querySearchInput: { flex: 1, fontSize: 14, color: COLORS.text, fontWeight: '600' },
  queryIconButton: { width: 26, height: 26, borderRadius: 13, alignItems: 'center', justifyContent: 'center' },
  queryFilterButton: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.brandSoft,
    position: 'relative',
  },
  queryFilterButtonActive: { backgroundColor: COLORS.brand },
  queryFilterBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: COLORS.black,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  queryFilterBadgeText: { color: COLORS.white, fontSize: 10, fontWeight: '800' },
  queryMetaBanner: {
    marginTop: 12,
    minHeight: 42,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  queryMetaLeft: { flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 },
  queryMetaText: { flex: 1, fontSize: 12, fontWeight: '800', color: COLORS.text },
  queryMetaTime: { fontSize: 11, fontWeight: '700', color: COLORS.muted },
  queryControlsPanel: {
    marginTop: 12,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.surface,
    padding: 12,
  },
  queryPanelTitle: { fontSize: 12, fontWeight: '800', color: COLORS.text, marginBottom: 10 },
  queryChipRow: { gap: 10, paddingBottom: 8 },
  queryChip: {
    minHeight: 36,
    paddingHorizontal: 12,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  queryChipActive: { backgroundColor: COLORS.brand, borderColor: COLORS.brand },
  queryChipText: { fontSize: 12, fontWeight: '800', color: COLORS.text },
  queryChipTextActive: { color: COLORS.white },
  querySortChip: {
    minHeight: 34,
    paddingHorizontal: 12,
    borderRadius: 17,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    justifyContent: 'center',
  },
  querySortChipActive: { backgroundColor: COLORS.info, borderColor: COLORS.info },
  querySortChipText: { fontSize: 12, fontWeight: '800', color: COLORS.text },
  querySortChipTextActive: { color: COLORS.white },
  querySummaryCard: {
    marginTop: 12,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.surfaceAlt,
    padding: 12,
    gap: 10,
  },
  querySummaryRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 },
  querySummaryTitle: { flex: 1, fontSize: 15, fontWeight: '800', color: COLORS.text },
  querySummaryAction: { fontSize: 12, fontWeight: '800', color: COLORS.brand },
  querySummaryChipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  querySummaryChip: {
    minHeight: 30,
    paddingHorizontal: 10,
    borderRadius: 15,
    backgroundColor: COLORS.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  querySummaryChipText: { fontSize: 11, fontWeight: '800', color: COLORS.text },
  pill: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  pillText: { fontSize: 12, fontWeight: '700' },
  primaryButton: {
    minHeight: 42,
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  primaryButtonText: { fontSize: 13, fontWeight: '800' },
  orderCard: {
    borderRadius: 18,
    padding: 14,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
    marginBottom: 10,
  },
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
