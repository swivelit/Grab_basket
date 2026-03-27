import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Switch,
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

const CACHE_KEY = '@grab_basket/partner_catalog_query_cache_v1';
const STALE_TIME_MS = 60 * 1000;
const CACHE_TIME_MS = 20 * 60 * 1000;
const DEBOUNCE_MS = 280;

const FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'available', label: 'Available', icon: 'checkmark-circle-outline' },
  { key: 'outofstock', label: 'Out of stock', icon: 'alert-circle-outline' },
  { key: 'lowstock', label: 'Low stock', icon: 'warning-outline' },
  { key: 'paused', label: 'Paused', icon: 'pause-circle-outline' },
  { key: 'under199', label: 'Under ₹199', icon: 'pricetag-outline' },
  { key: 'premium', label: 'Premium', icon: 'diamond-outline' },
];

const SORT_OPTIONS = [
  { key: 'relevance', label: 'Relevance' },
  { key: 'name', label: 'Name' },
  { key: 'price', label: 'Price' },
  { key: 'stock', label: 'Stock' },
  { key: 'availability', label: 'Availability' },
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

function getStatusTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value.includes('DELIVERED') || value.includes('AVAILABLE')) {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle-outline' };
  }
  if (value.includes('UNAVAILABLE') || value.includes('PAUSE')) {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'pause-circle-outline' };
  }
  return { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'time-outline' };
}

function getStockQty(product) {
  const value = Number(product?.stock_qty ?? 0);
  return Number.isFinite(value) ? Math.max(0, Math.trunc(value)) : 0;
}

function getMaxQtyPerOrder(product) {
  const value = Number(product?.max_qty_per_order ?? 20);
  return Number.isFinite(value) ? Math.max(1, Math.trunc(value)) : 20;
}

function getInventoryState(product) {
  const isAvailable = product?.is_available !== false;
  const stockQty = getStockQty(product);

  if (!isAvailable && stockQty <= 0) {
    return {
      key: 'outofstock',
      label: 'Out of stock',
      helper: 'Customers cannot order this item until you restock or enable it again.',
      tone: { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'alert-circle-outline' },
    };
  }

  if (!isAvailable) {
    return {
      key: 'paused',
      label: `Paused · ${stockQty} left`,
      helper: 'Inventory exists, but ordering is paused for this item.',
      tone: { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'pause-circle-outline' },
    };
  }

  if (stockQty <= 0) {
    return {
      key: 'untracked',
      label: 'Inventory not tracked',
      helper: 'This item stays orderable until you manually pause it.',
      tone: { bg: COLORS.infoSoft, text: COLORS.info, icon: 'layers-outline' },
    };
  }

  if (stockQty <= 5) {
    return {
      key: 'lowstock',
      label: `Low stock · ${stockQty} left`,
      helper: 'Restock soon to avoid failed accepts when multiple orders land together.',
      tone: { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'warning-outline' },
    };
  }

  return {
    key: 'available',
    label: `${stockQty} in stock`,
    helper: 'Tracked inventory is healthy for this item right now.',
    tone: { bg: COLORS.successSoft, text: COLORS.success, icon: 'cube-outline' },
  };
}

function createEmptyProductForm() {
  return {
    id: null,
    name: '',
    description: '',
    price: '',
    stock_qty: '',
    max_qty_per_order: '20',
    is_available: true,
  };
}

function normalizeIntegerInput(value) {
  return String(value || '').replace(/[^0-9]/g, '');
}

function productSearchText(product) {
  const inventory = getInventoryState(product);
  return [
    product?.name,
    product?.description,
    product?.price,
    product?.is_available ? 'available' : 'unavailable',
    inventory?.label,
    inventory?.helper,
    getStockQty(product),
    getMaxQtyPerOrder(product),
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function getProductScore(product, search) {
  const query = normalizeText(search);
  if (!query) return 1;

  let score = 0;
  if (normalizeText(product?.name).includes(query)) score += 90;
  if (normalizeText(product?.description).includes(query)) score += 32;
  if (String(product?.price ?? '').includes(query)) score += 12;
  if (productSearchText(product).includes(query)) score += 8;
  return score;
}

function matchesFilter(product, filterKey) {
  const price = Number(product?.price || 0);
  const inventory = getInventoryState(product);

  switch (filterKey) {
    case 'available':
      return inventory.key === 'available' || inventory.key === 'untracked' || inventory.key === 'lowstock';
    case 'outofstock':
      return inventory.key === 'outofstock';
    case 'lowstock':
      return inventory.key === 'lowstock';
    case 'paused':
      return inventory.key === 'paused';
    case 'under199':
      return price > 0 && price <= 199;
    case 'premium':
      return price >= 300;
    default:
      return true;
  }
}

function sortProducts(list = [], sortBy = 'relevance') {
  return [...list].sort((a, b) => {
    if (sortBy === 'name') return String(a?.name || '').localeCompare(String(b?.name || ''));
    if (sortBy === 'price') return Number(a?.price || 0) - Number(b?.price || 0);
    if (sortBy === 'stock') return getStockQty(b) - getStockQty(a);
    if (sortBy === 'availability') return Number(Boolean(b?.is_available)) - Number(Boolean(a?.is_available));
    return (b.__score || 0) - (a.__score || 0) || String(a?.name || '').localeCompare(String(b?.name || ''));
  });
}

function computeQueryData(products = [], search, filterKey, sortBy) {
  const query = normalizeText(search);
  const items = sortProducts(
    (products || [])
      .map((product) => {
        const score = getProductScore(product, query);
        const passesSearch = !query || score > 0 || productSearchText(product).includes(query);
        const passesFilter = matchesFilter(product, filterKey);
        if (!passesSearch || !passesFilter) return null;
        return { ...product, __score: score };
      })
      .filter(Boolean),
    sortBy
  );

  return { items, totalMatches: items.length, empty: items.length === 0 };
}

function buildSourceSignature(products = []) {
  return (products || [])
    .slice(0, 40)
    .map((product) => `${product?.id}-${product?.is_available}-${product?.price}-${product?.stock_qty}-${product?.max_qty_per_order}-${product?.name}`)
    .join('|');
}

function buildQueryKey({ search, filterKey, sortBy, sourceSignature }) {
  return ['partner-catalog', normalizeText(search), filterKey, sortBy, sourceSignature].join('::');
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

function useCatalogQuery({ products, search, filterKey, sortBy }) {
  const sourceSignature = useMemo(() => buildSourceSignature(products), [products]);
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

      const next = computeQueryData(products, search, filterKey, sortBy);
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
      const next = computeQueryData(products, search, filterKey, sortBy);
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
  }, [products, queryKey, search, filterKey, sortBy]);

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

function PrimaryButton({ label, icon, onPress, disabled = false, tone = 'brand' }) {
  const palette =
    tone === 'danger'
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

function TextField({ label, value, onChangeText, placeholder, keyboardType = 'default', multiline = false }) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.subtle}
        keyboardType={keyboardType}
        multiline={multiline}
        style={[styles.input, multiline && styles.inputMultiline]}
      />
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

export default function PartnerCatalogScreen() {
  const { authToken, sessionReady, isAuthenticated, logout, appVariantName } = useGrabBasket();
  const tabBarHeight = useBottomTabBarHeight();

  const [products, setProducts] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingAction, setLoadingAction] = useState(false);
  const [search, setSearch] = useState('');
  const [filterKey, setFilterKey] = useState('all');
  const [sortBy, setSortBy] = useState('relevance');
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [productForm, setProductForm] = useState(createEmptyProductForm());

  const debouncedSearch = useDebouncedValue(search);

  const loadData = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) return;
      try {
        if (!silent) setRefreshing(true);
        const response = await request('/seller/products', authToken).catch((error) => {
          if (error?.status === 404) return [];
          throw error;
        });
        setProducts(Array.isArray(response) ? response : []);
      } catch (error) {
        Alert.alert(`${appVariantName} sync failed`, error?.message || 'Could not load catalog.');
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

  const saveProduct = useCallback(() => {
    if (!productForm.name.trim()) {
      Alert.alert('Item name required', 'Enter a menu item name first.');
      return;
    }

    const price = Number(productForm.price);
    if (!Number.isFinite(price) || price <= 0) {
      Alert.alert('Invalid price', 'Enter a valid product price.');
      return;
    }

    const stockQtyValue = normalizeIntegerInput(productForm.stock_qty);
    const maxQtyValue = normalizeIntegerInput(productForm.max_qty_per_order);
    const stockQty = stockQtyValue === '' ? 0 : Number(stockQtyValue);
    const maxQtyPerOrder = maxQtyValue === '' ? 20 : Number(maxQtyValue);

    if (!Number.isInteger(stockQty) || stockQty < 0) {
      Alert.alert('Invalid stock', 'Tracked stock must be a whole number of units.');
      return;
    }

    if (!Number.isInteger(maxQtyPerOrder) || maxQtyPerOrder <= 0) {
      Alert.alert('Invalid order limit', 'Max quantity per order must be at least 1.');
      return;
    }

    const payload = {
      name: productForm.name.trim(),
      description: productForm.description.trim(),
      price,
      stock_qty: stockQty,
      max_qty_per_order: maxQtyPerOrder,
      is_available: Boolean(productForm.is_available),
    };

    runAction(async () => {
      if (productForm.id) {
        await request(`/seller/products/${productForm.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        await request('/seller/products', authToken, {
          method: 'POST',
          body: JSON.stringify(payload),
        });
      }
      setProductForm(createEmptyProductForm());
    }, productForm.id ? 'Menu item updated.' : 'Menu item added.');
  }, [authToken, productForm, runAction]);

  const toggleProductAvailability = useCallback(
    (product) => {
      runAction(async () => {
        await request(`/seller/products/${product.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify({ is_available: !product.is_available }),
        });
      }, `${product.name} is now ${product.is_available ? 'paused' : 'available'}.`);
    },
    [authToken, runAction]
  );

  const adjustTrackedStock = useCallback(
    (product, delta, { enableIfPositive = false, successMessage } = {}) => {
      const nextStock = Math.max(0, getStockQty(product) + delta);
      const nextPayload = { stock_qty: nextStock };

      if (enableIfPositive && nextStock > 0) {
        nextPayload.is_available = true;
      }
      if (nextStock === 0 && delta < 0) {
        nextPayload.is_available = false;
      }

      runAction(async () => {
        await request(`/seller/products/${product.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify(nextPayload),
        });
      }, successMessage || `${product.name} stock updated.`);
    },
    [authToken, runAction]
  );

  const markProductSoldOut = useCallback(
    (product) => {
      runAction(async () => {
        await request(`/seller/products/${product.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify({ stock_qty: 0, is_available: false }),
        });
      }, `${product.name} marked out of stock.`);
    },
    [authToken, runAction]
  );

  const startEditProduct = useCallback((product) => {
    setProductForm({
      id: product.id,
      name: product.name || '',
      description: product.description || '',
      price: product.price != null ? String(product.price) : '',
      stock_qty: product?.stock_qty != null ? String(product.stock_qty) : '',
      max_qty_per_order: product?.max_qty_per_order != null ? String(product.max_qty_per_order) : '20',
      is_available: product.is_available !== false,
    });
  }, []);

  const deleteProduct = useCallback(
    (productId) => {
      Alert.alert('Delete item', 'This will remove the product from your catalog.', [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: () =>
            runAction(async () => {
              await request(`/seller/products/${productId}`, authToken, { method: 'DELETE' });
            }, 'Product removed.'),
        },
      ]);
    },
    [authToken, runAction]
  );

  const queryState = useCatalogQuery({ products, search: debouncedSearch, filterKey, sortBy });

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
          <Text style={styles.centerSubtitle}>Use the seller account flow before opening catalog management.</Text>
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
        <SectionCard title={productForm.id ? 'Edit menu item' : 'Add menu item'} subtitle="Swiggy-level seller apps need menu control, availability control, and real stock visibility.">
          <TextField label="Item name" value={productForm.name} onChangeText={(value) => setProductForm((current) => ({ ...current, name: value }))} placeholder="Paneer Tikka Wrap" />
          <TextField label="Description" value={productForm.description} onChangeText={(value) => setProductForm((current) => ({ ...current, description: value }))} placeholder="Short item description" multiline />
          <TextField label="Price" value={productForm.price} onChangeText={(value) => setProductForm((current) => ({ ...current, price: value }))} placeholder="249" keyboardType="decimal-pad" />
          <TextField
            label="Tracked stock units"
            value={productForm.stock_qty}
            onChangeText={(value) => setProductForm((current) => ({ ...current, stock_qty: normalizeIntegerInput(value) }))}
            placeholder="0"
            keyboardType="number-pad"
          />
          <Text style={styles.helperNote}>0 keeps inventory tracking off. To show a true sold-out state, keep this at 0 and switch the item to unavailable, or use the quick Sold out action below.</Text>
          <TextField
            label="Max quantity per order"
            value={productForm.max_qty_per_order}
            onChangeText={(value) => setProductForm((current) => ({ ...current, max_qty_per_order: normalizeIntegerInput(value) }))}
            placeholder="20"
            keyboardType="number-pad"
          />

          <View style={styles.preferenceRow}>
            <View style={styles.flexOne}>
              <Text style={styles.preferenceLabel}>Available for ordering</Text>
              <Text style={styles.helperNote}>Turn this off to pause the item even if stock is still left.</Text>
            </View>
            <Switch
              value={Boolean(productForm.is_available)}
              onValueChange={(value) => setProductForm((current) => ({ ...current, is_available: value }))}
              trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
              thumbColor={productForm.is_available ? COLORS.brand : '#FFFFFF'}
            />
          </View>

          <View style={styles.buttonRow}>
            <PrimaryButton label={productForm.id ? 'Update item' : 'Add item'} icon="save-outline" onPress={saveProduct} disabled={loadingAction} tone="brand" />
            {productForm.id ? <PrimaryButton label="Cancel edit" icon="close-outline" onPress={() => setProductForm(createEmptyProductForm())} disabled={loadingAction} tone="muted" /> : null}
          </View>
        </SectionCard>

        <SectionCard title="Search catalog" subtitle="Search menu items instantly, apply quick filters, and keep cached results visible while the catalog refreshes.">
          <QuerySearchBar
            value={search}
            onChangeText={setSearch}
            onClear={() => setSearch('')}
            onToggleFilters={() => setFiltersOpen((current) => !current)}
            filtersOpen={filtersOpen}
            filterCount={activeControlCount}
            placeholder="Search item name, description, price"
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

        <SectionCard title="Catalog" subtitle="You now have working CRUD, stock-aware status states, and fast availability toggles.">
          {queryState.isLoading ? (
            <EmptyState icon="refresh-outline" title="Loading catalog" subtitle="Preparing cached catalog results for the seller app." />
          ) : queryState.data.items.length ? (
            queryState.data.items.map((product) => {
              const inventoryState = getInventoryState(product);
              const stockQty = getStockQty(product);
              const maxQtyPerOrder = getMaxQtyPerOrder(product);
              const showRestock = inventoryState.key === 'outofstock';
              const showSoldOut = product?.is_available !== false || stockQty > 0;

              return (
                <View key={product.id} style={styles.catalogCard}>
                  <View style={styles.catalogTopRow}>
                    <View style={styles.flexOne}>
                      <Text style={styles.catalogName}>{product.name}</Text>
                      <Text style={styles.catalogDescription}>{product.description || 'No description added'}</Text>
                    </View>
                    <View style={styles.catalogPillRow}>
                      <Pill text={product.is_available ? 'Available' : 'Unavailable'} tone={getStatusTone(product.is_available ? 'AVAILABLE' : 'UNAVAILABLE')} />
                      <Pill text={inventoryState.label} tone={inventoryState.tone} />
                    </View>
                  </View>

                  <View style={styles.catalogFooter}>
                    <Text style={styles.catalogPrice}>{money(product.price)}</Text>
                    <View style={styles.inventoryMetaCard}>
                      <Text style={styles.inventoryMetaTitle}>Inventory state</Text>
                      <Text style={styles.inventoryMetaText}>{inventoryState.helper}</Text>
                      <View style={styles.inventoryMetaRow}>
                        <Text style={styles.inventoryMetaChip}>Tracked stock: {stockQty}</Text>
                        <Text style={styles.inventoryMetaChip}>Max per order: {maxQtyPerOrder}</Text>
                      </View>
                    </View>

                    <View style={styles.buttonRow}>
                      <PrimaryButton label="Edit" icon="create-outline" onPress={() => startEditProduct(product)} disabled={loadingAction} tone="muted" />
                      <PrimaryButton label={product.is_available ? 'Pause' : 'Enable'} icon={product.is_available ? 'pause-outline' : 'play-outline'} onPress={() => toggleProductAvailability(product)} disabled={loadingAction} tone="brand" />
                      {showRestock ? (
                        <PrimaryButton
                          label="Restock +10"
                          icon="add-circle-outline"
                          onPress={() => adjustTrackedStock(product, 10, { enableIfPositive: true, successMessage: `${product.name} restocked by 10 units.` })}
                          disabled={loadingAction}
                          tone="brand"
                        />
                      ) : null}
                      {stockQty > 0 ? (
                        <PrimaryButton
                          label="+5 stock"
                          icon="cube-outline"
                          onPress={() => adjustTrackedStock(product, 5, { successMessage: `${product.name} stock increased by 5.` })}
                          disabled={loadingAction}
                          tone="muted"
                        />
                      ) : null}
                      {showSoldOut ? (
                        <PrimaryButton label="Sold out" icon="remove-circle-outline" onPress={() => markProductSoldOut(product)} disabled={loadingAction} tone="danger" />
                      ) : null}
                      <PrimaryButton label="Delete" icon="trash-outline" onPress={() => deleteProduct(product.id)} disabled={loadingAction} tone="danger" />
                    </View>
                  </View>
                </View>
              );
            })
          ) : (
            <EmptyState
              icon={isQueryActive ? 'search-outline' : 'restaurant-outline'}
              title={isQueryActive ? 'No catalog matches' : 'No menu yet'}
              subtitle={isQueryActive ? 'Try a broader keyword or reset the active catalog filters.' : 'Add your first product above and it will appear here.'}
            />
          )}
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
  fieldWrap: { marginBottom: 12 },
  fieldLabel: { fontSize: 12, fontWeight: '700', color: COLORS.muted, marginBottom: 6 },
  helperNote: { fontSize: 12, lineHeight: 18, color: COLORS.subtle, marginTop: -2, marginBottom: 8 },
  input: {
    minHeight: 46,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 14,
    color: COLORS.text,
    fontSize: 14,
  },
  inputMultiline: { minHeight: 88, textAlignVertical: 'top', paddingTop: 12 },
  preferenceRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 14, paddingVertical: 4 },
  preferenceLabel: { fontSize: 14, fontWeight: '700', color: COLORS.text },
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
  primaryButton: { minHeight: 42, borderRadius: 14, paddingHorizontal: 14, borderWidth: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 },
  primaryButtonText: { fontSize: 13, fontWeight: '800' },
  pill: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  pillText: { fontSize: 12, fontWeight: '700' },
  emptyState: { alignItems: 'center', gap: 8, paddingVertical: 22 },
  emptyIconWrap: { width: 42, height: 42, borderRadius: 21, backgroundColor: COLORS.brandSoft, alignItems: 'center', justifyContent: 'center' },
  emptyTitle: { fontSize: 15, fontWeight: '800', color: COLORS.text },
  emptySubtitle: { fontSize: 13, lineHeight: 19, textAlign: 'center', color: COLORS.muted },
  catalogCard: { borderRadius: 18, padding: 14, backgroundColor: COLORS.surfaceAlt, borderWidth: 1, borderColor: COLORS.line, marginBottom: 10, gap: 12 },
  catalogTopRow: { flexDirection: 'row', gap: 10, alignItems: 'flex-start', justifyContent: 'space-between' },
  catalogName: { fontSize: 15, fontWeight: '800', color: COLORS.text },
  catalogPillRow: { alignItems: 'flex-end', gap: 8 },
  catalogDescription: { fontSize: 13, lineHeight: 19, color: COLORS.muted, marginTop: 4 },
  catalogFooter: { gap: 12 },
  inventoryMetaCard: { borderRadius: 14, borderWidth: 1, borderColor: COLORS.line, backgroundColor: COLORS.surface, padding: 12, gap: 8 },
  inventoryMetaTitle: { fontSize: 12, fontWeight: '800', color: COLORS.text },
  inventoryMetaText: { fontSize: 12, lineHeight: 18, color: COLORS.muted },
  inventoryMetaRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  inventoryMetaChip: { fontSize: 11, fontWeight: '800', color: COLORS.text, backgroundColor: COLORS.surfaceAlt, paddingHorizontal: 10, paddingVertical: 7, borderRadius: 999 },
  catalogPrice: { fontSize: 16, fontWeight: '800', color: COLORS.text },
  buttonRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 14 },
  flexOne: { flex: 1 },
});