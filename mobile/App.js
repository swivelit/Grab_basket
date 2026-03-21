import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  API_CONFIG_ERROR,
  API_TIMEOUT_MS,
  APP_CONFIG,
  buildApiUrl,
} from './src/config';

const STORAGE_CART = '@grab_basket/cart_v10';
const STORAGE_FAVORITES = '@grab_basket/favorites_v7';
const STORAGE_RECENT_STORES = '@grab_basket/recent_stores_v8';
const STORAGE_RECENT_SEARCHES = '@grab_basket/recent_searches_v7';
const STORAGE_ORDER_HISTORY = '@grab_basket/order_history_v5';

const FREE_DELIVERY_THRESHOLD = 199;
const PLATFORM_FEE = 6;
const MAX_RECENT = 8;
const MAX_ORDERS = 16;
const NETWORK_TIMEOUT_MS =
  Number.isFinite(Number(API_TIMEOUT_MS)) && Number(API_TIMEOUT_MS) > 0
    ? Number(API_TIMEOUT_MS)
    : 15000;

const COLORS = {
  bg: '#FFF9F3',
  card: '#FFFFFF',
  cardAlt: '#FFF6EC',
  text: '#2F241C',
  muted: '#756354',
  subtle: '#A18C7B',
  border: '#F2DDC7',
  line: '#F4E6D7',

  peach50: '#FFF7EE',
  peach100: '#FFF0DE',
  peach200: '#FFE5B4',
  peach300: '#FFD8AA',
  peach400: '#F4BC92',
  peach500: '#E8956E',
  peach600: '#D97651',

  success: '#2E8B57',
  successSoft: '#EAF7EF',
  successBorder: '#CBEBD7',

  blue: '#4C7BC8',
  blueSoft: '#EEF4FF',
  purple: '#8B6CCF',
  purpleSoft: '#F3EEFF',
  yellowSoft: '#FFF6DB',
  danger: '#D45454',

  black: '#2B211A',
  darkSurface: '#16110D',
  darkSurfaceAlt: '#231B14',
  darkBorder: '#413226',
  darkMuted: '#DCC5AF',
  darkText: '#FFF7F0',
};

const SERVICE_ACCENT = {
  food: { primary: COLORS.peach600, soft: COLORS.peach50, dark: false },
  warehouse: { primary: COLORS.peach600, soft: COLORS.peach50, dark: false },
  eatout: { primary: COLORS.peach600, soft: COLORS.peach50, dark: false },
  scenes: { primary: '#F0AA81', soft: '#2D2219', dark: true },
};

const REORDER_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'food', label: 'Food' },
  { key: 'warehouse', label: 'Instamart' },
  { key: 'eatout', label: 'Dineout' },
  { key: 'scenes', label: 'Scenes' },
];

const FALLBACK_HOME_DEALS = [
  { id: 'deal-1', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Fresh Curd', price: 35, brand: 'Everyday essential' },
  { id: 'deal-2', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Milk Chocolate', price: 20, brand: 'Quick sweet bite' },
  { id: 'deal-3', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Fruit Jam', price: 49, brand: 'Breakfast saver' },
  { id: 'deal-4', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Classic Chips', price: 20, brand: 'Impulse add-on' },
];

const FALLBACK_ORDER_HISTORY = [
  {
    id: 'fallback-food-1',
    service: 'food',
    vendorName: 'Bakeryt',
    location: 'Manali Rd',
    items: [{ name: 'Chocolate Truffle', qty: 1 }],
    orderedAt: 'February 11, 5:18 PM',
    total: 511,
    status: 'Delivered',
  },
  {
    id: 'fallback-food-2',
    service: 'food',
    vendorName: 'Sweet Truth - Cake and Desserts',
    location: 'Manali Rd',
    items: [{ name: 'Red Velvet Jar', qty: 2 }],
    orderedAt: 'February 9, 7:10 PM',
    total: 298,
    status: 'Delivered',
  },
  {
    id: 'fallback-warehouse-1',
    service: 'warehouse',
    vendorName: 'Daily Basket',
    location: 'Great Orchard',
    items: [{ name: 'Curd', qty: 1 }, { name: 'Milk Chocolate', qty: 1 }],
    orderedAt: 'March 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
  {
    id: 'fallback-eatout-1',
    service: 'eatout',
    vendorName: 'Cafe Papaya',
    location: 'Kakkanad',
    items: [{ name: 'Table for 2', qty: 1 }],
    orderedAt: 'March 20, 8:15 PM',
    total: 799,
    status: 'Booked',
  },
];

const USE_DEMO_CONTENT = APP_CONFIG.isDevelopment;

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function normalizeErrorMessage(error, fallback = 'Something went wrong') {
  if (error instanceof Error && error.message) return error.message;
  if (typeof error === 'string' && error.trim()) return error.trim();
  return fallback;
}

function safeJsonParse(raw) {
  try {
    return raw ? JSON.parse(raw) : null;
  } catch {
    return raw;
  }
}

function extractErrorMessage(data, fallback = 'Request failed') {
  if (data && typeof data === 'object') {
    if (typeof data.detail === 'string' && data.detail.trim()) return data.detail.trim();
    if (typeof data.message === 'string' && data.message.trim()) return data.message.trim();
    if (data.error && typeof data.error.message === 'string' && data.error.message.trim()) {
      return data.error.message.trim();
    }
  }

  if (typeof data === 'string' && data.trim()) return data.trim();
  return fallback;
}

function mapLegacyService(value) {
  const service = normalizeText(value);
  if (service === 'instamart') return 'warehouse';
  if (service === 'dineout') return 'eatout';
  return service || 'food';
}

function getServiceLabel(service = '') {
  const normalized = mapLegacyService(service);
  if (normalized === 'warehouse') return 'Instamart';
  if (normalized === 'eatout') return 'Dineout';
  if (normalized === 'scenes') return 'Scenes';
  return 'Food';
}

function findVendorById(vendors = [], vendorId) {
  return vendors.find((vendor) => String(vendor?.id) === String(vendorId)) || null;
}

function estimateEta(vendor, service = 'food') {
  const normalized = mapLegacyService(service);
  if (normalized === 'warehouse') return '5-15 mins';
  if (normalized === 'eatout') return 'Table in 10-15 mins';
  if (normalized === 'scenes') return 'Instant confirmation';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getDeliveryFeeAmount(vendor) {
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 0;
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return 19;
  return 29;
}

function buildVendorQuery(search = '', filter = 'All') {
  const params = new URLSearchParams();
  const q = String(search || '').trim();
  if (q) params.set('q', q);
  if (filter === 'Open now') params.set('open_only', 'true');
  params.set('limit', '50');
  return `/vendors?${params.toString()}`;
}

function sortVendors(vendors = [], filter = 'All') {
  const list = [...vendors];

  if (filter === 'Closest') {
    return list.sort(
      (a, b) =>
        (a?.distance_km ?? Number.MAX_SAFE_INTEGER) -
        (b?.distance_km ?? Number.MAX_SAFE_INTEGER)
    );
  }

  if (filter === 'A-Z') {
    return list.sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || '')));
  }

  return list;
}

function dedupeStrings(values = []) {
  const seen = new Set();
  const output = [];

  values.forEach((value) => {
    const raw = String(value || '').trim();
    const key = normalizeText(raw);
    if (!raw || seen.has(key)) return;
    seen.add(key);
    output.push(raw);
  });

  return output;
}

function formatOrderTime(date = new Date()) {
  try {
    return date.toLocaleString('en-IN', {
      month: 'long',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  } catch {
    return 'Just now';
  }
}

function createShortcutBuckets(vendors = []) {
  return {
    fresh: vendors.filter((vendor) => /(fruit|vegetable|fresh|dairy|farm|grocery|greens)/i.test(`${vendor?.name || ''} ${vendor?.description || ''}`)),
    maxxsaver: vendors.filter((vendor) => /(save|mart|basket|daily|essentials|value)/i.test(`${vendor?.name || ''} ${vendor?.description || ''}`)),
    festival: vendors.filter((vendor) => /(dates|dry|dessert|sweet|gift|biryani|festival|ramzan)/i.test(`${vendor?.name || ''} ${vendor?.description || ''}`)),
    ready: vendors.filter((vendor) => /(ready|instant|coffee|tea|bakery|juice|quick)/i.test(`${vendor?.name || ''} ${vendor?.description || ''}`)),
  };
}

function isValidCart(value) {
  return Boolean(value && typeof value === 'object' && !Array.isArray(value) && typeof value.items === 'object');
}

async function apiRequest(path, options = {}) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort();
  }, NETWORK_TIMEOUT_MS);

  try {
    const response = await fetch(buildApiUrl(path), {
      method: options.method || 'GET',
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers || {}),
      },
      body: options.body,
      signal: controller.signal,
    });

    const raw = await response.text();
    const data = safeJsonParse(raw);

    if (!response.ok) {
      throw new Error(
        extractErrorMessage(data, `Request failed with status ${response.status}`)
      );
    }

    return data;
  } catch (error) {
    if (error?.name === 'AbortError') {
      throw new Error(`Request timed out after ${Math.round(NETWORK_TIMEOUT_MS / 1000)}s`);
    }

    if (API_CONFIG_ERROR) {
      throw new Error(API_CONFIG_ERROR);
    }

    throw new Error(normalizeErrorMessage(error, 'Network request failed'));
  } finally {
    clearTimeout(timeoutId);
  }
}

const GrabBasketContext = createContext(null);

export function useGrabBasket() {
  const value = useContext(GrabBasketContext);
  if (!value) throw new Error('useGrabBasket must be used inside GrabBasketProvider');
  return value;
}

export function GrabBasketProvider({ children }) {
  const [activeService, setActiveService] = useState('food');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
  const [pastOrderFilter, setPastOrderFilter] = useState('all');

  const [vendors, setVendors] = useState([]);
  const [vendorsLoading, setVendorsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [homeDeals, setHomeDeals] = useState([]);
  const [homeDealsLoading, setHomeDealsLoading] = useState(false);

  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [favorites, setFavorites] = useState({});
  const [recentStoreIds, setRecentStoreIds] = useState([]);
  const [recentSearches, setRecentSearches] = useState([]);
  const [orderHistory, setOrderHistory] = useState([]);

  const vendorRequestIdRef = useRef(0);
  const dealsRequestIdRef = useRef(0);
  const configAlertShownRef = useRef(false);
  const vendorErrorAlertRef = useRef('');
  const productsErrorAlertRef = useRef('');

  useEffect(() => {
    if (!API_CONFIG_ERROR || configAlertShownRef.current) return;
    configAlertShownRef.current = true;
    Alert.alert('Configuration issue', API_CONFIG_ERROR);
  }, []);

  useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        const values = await AsyncStorage.multiGet([
          STORAGE_CART,
          STORAGE_FAVORITES,
          STORAGE_RECENT_STORES,
          STORAGE_RECENT_SEARCHES,
          STORAGE_ORDER_HISTORY,
        ]);

        if (!mounted) return;

        const nextCart = values[0]?.[1];
        const nextFavorites = values[1]?.[1];
        const nextStores = values[2]?.[1];
        const nextSearches = values[3]?.[1];
        const nextOrders = values[4]?.[1];

        if (nextCart) {
          try {
            const parsed = JSON.parse(nextCart);
            if (isValidCart(parsed)) setCart(parsed);
          } catch {}
        }

        if (nextFavorites) {
          try {
            const parsed = JSON.parse(nextFavorites);
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
              setFavorites(parsed);
            }
          } catch {}
        }

        if (nextStores) {
          try {
            const parsed = JSON.parse(nextStores);
            if (Array.isArray(parsed)) {
              setRecentStoreIds(parsed.slice(0, MAX_RECENT));
            }
          } catch {}
        }

        if (nextSearches) {
          try {
            const parsed = JSON.parse(nextSearches);
            if (Array.isArray(parsed)) {
              setRecentSearches(dedupeStrings(parsed).slice(0, MAX_RECENT));
            }
          } catch {}
        }

        if (nextOrders) {
          try {
            const parsed = JSON.parse(nextOrders);
            if (Array.isArray(parsed)) {
              setOrderHistory(
                parsed
                  .map((item) => ({
                    ...item,
                    service: mapLegacyService(item?.service),
                  }))
                  .slice(0, MAX_ORDERS)
              );
            }
          } catch {}
        }
      } catch {
        // ignore invalid local cache
      } finally {
        if (mounted) {
          setVendorsLoading(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_CART, JSON.stringify(cart)).catch(() => {});
  }, [cart]);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_FAVORITES, JSON.stringify(favorites)).catch(() => {});
  }, [favorites]);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_RECENT_STORES, JSON.stringify(recentStoreIds)).catch(() => {});
  }, [recentStoreIds]);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_RECENT_SEARCHES, JSON.stringify(recentSearches)).catch(() => {});
  }, [recentSearches]);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_ORDER_HISTORY, JSON.stringify(orderHistory)).catch(() => {});
  }, [orderHistory]);

  const rememberSearch = useCallback((term) => {
    const value = String(term || '').trim();
    if (!value) return;

    setRecentSearches((current) =>
      [value, ...current.filter((item) => normalizeText(item) !== normalizeText(value))].slice(
        0,
        MAX_RECENT
      )
    );
  }, []);

  const rememberStore = useCallback((vendorId) => {
    if (vendorId == null) return;

    setRecentStoreIds((current) =>
      [vendorId, ...current.filter((item) => String(item) !== String(vendorId))].slice(0, MAX_RECENT)
    );
  }, []);

  const loadVendors = useCallback(
    async ({ pullToRefresh = false } = {}) => {
      const requestId = ++vendorRequestIdRef.current;

      try {
        if (pullToRefresh) setRefreshing(true);
        else setVendorsLoading(true);

        const data = await apiRequest(buildVendorQuery(homeSearch, storeFilter));

        if (requestId !== vendorRequestIdRef.current) return;

        const parsed = Array.isArray(data) ? data : [];
        setVendors(sortVendors(parsed, storeFilter));
      } catch (error) {
        if (requestId !== vendorRequestIdRef.current) return;

        setVendors([]);
        const message = normalizeErrorMessage(error, 'Could not load stores');

        if (vendorErrorAlertRef.current !== message) {
          vendorErrorAlertRef.current = message;
          Alert.alert('Could not load stores', message);
        }
      } finally {
        if (requestId === vendorRequestIdRef.current) {
          setVendorsLoading(false);
          setRefreshing(false);
        }
      }
    },
    [homeSearch, storeFilter]
  );

  useEffect(() => {
    const timer = setTimeout(() => {
      loadVendors();
    }, 220);

    return () => clearTimeout(timer);
  }, [loadVendors]);

  const loadHomeDeals = useCallback(async (vendorList) => {
    const requestId = ++dealsRequestIdRef.current;
    const topVendors = vendorList.slice(0, 4);

    if (topVendors.length === 0) {
      setHomeDeals([]);
      setHomeDealsLoading(false);
      return;
    }

    try {
      setHomeDealsLoading(true);

      const groups = await Promise.all(
        topVendors.map(async (vendor) => {
          try {
            const data = await apiRequest(`/vendors/${vendor.id}/products?limit=12`);
            return { vendor, products: Array.isArray(data) ? data : [] };
          } catch {
            return { vendor, products: [] };
          }
        })
      );

      if (requestId !== dealsRequestIdRef.current) return;

      const curated = groups
        .flatMap(({ vendor, products }) =>
          products
            .filter((item) => item?.is_available !== false)
            .map((item) => ({
              ...item,
              vendorName: vendor?.name,
              vendor_id: item?.vendor_id ?? vendor?.id,
              key: `${vendor?.id}-${item?.id}`,
            }))
        )
        .sort((a, b) => Number(a?.price || 0) - Number(b?.price || 0))
        .slice(0, 8);

      setHomeDeals(curated);
    } finally {
      if (requestId === dealsRequestIdRef.current) {
        setHomeDealsLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    if (vendors.length > 0) loadHomeDeals(vendors);
    else {
      setHomeDeals([]);
      setHomeDealsLoading(false);
    }
  }, [vendors, loadHomeDeals]);

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      const params = new URLSearchParams();
      const q = String(searchValue || '').trim();

      if (q) params.set('q', q);
      params.set('limit', '200');

      const query = params.toString();
      const data = await apiRequest(`/vendors/${vendor.id}/products${query ? `?${query}` : ''}`);

      return Array.isArray(data) ? data : [];
    } catch (error) {
      const message = normalizeErrorMessage(error, 'Could not load products');

      if (productsErrorAlertRef.current !== message) {
        productsErrorAlertRef.current = message;
        Alert.alert('Could not load products', message);
      }

      return [];
    }
  }, []);

  const keywordMap = useMemo(() => createShortcutBuckets(vendors), [vendors]);

  const shortcutFilteredVendors = useMemo(() => {
    if (activeService !== 'warehouse' || activeShortcut === 'all') return vendors;
    const bucket = keywordMap[activeShortcut] || [];
    return bucket.length > 0 ? bucket : vendors;
  }, [activeService, activeShortcut, keywordMap, vendors]);

  const featuredVendors = useMemo(() => shortcutFilteredVendors.slice(0, 8), [shortcutFilteredVendors]);

  const recentVendors = useMemo(
    () => recentStoreIds.map((id) => findVendorById(vendors, id)).filter(Boolean),
    [recentStoreIds, vendors]
  );

  const suggestionPool = useMemo(
    () =>
      dedupeStrings([
        ...recentSearches,
        ...vendors.map((vendor) => vendor?.name),
        ...(homeDeals.length > 0
          ? homeDeals
          : USE_DEMO_CONTENT
            ? FALLBACK_HOME_DEALS
            : []).map((item) => item?.name),
      ]).slice(0, 12),
    [recentSearches, vendors, homeDeals]
  );

  const cartItems = useMemo(() => Object.values(cart.items || {}), [cart]);

  const cartCount = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item?.qty || 0), 0),
    [cartItems]
  );

  const cartSubtotal = useMemo(
    () =>
      cartItems.reduce(
        (sum, item) => sum + Number(item?.price || 0) * Number(item?.qty || 0),
        0
      ),
    [cartItems]
  );

  const cartVendor = useMemo(
    () => (cart?.vendorId ? findVendorById(vendors, cart.vendorId) : null),
    [vendors, cart?.vendorId]
  );

  const deliveryFeeAmount = cartCount > 0 ? getDeliveryFeeAmount(cartVendor) : 0;
  const platformFeeAmount = cartCount > 0 ? PLATFORM_FEE : 0;
  const cartTotal = cartSubtotal + deliveryFeeAmount + platformFeeAmount;
  const freeDeliveryRemaining = Math.max(0, FREE_DELIVERY_THRESHOLD - cartSubtotal);
  const freeDeliveryProgress = Math.min(1, cartSubtotal / FREE_DELIVERY_THRESHOLD);

  const toggleFavorite = useCallback((vendorId) => {
    setFavorites((current) => ({
      ...current,
      [vendorId]: !current?.[vendorId],
    }));
  }, []);

  const replaceCartWith = useCallback((product) => {
    const itemKey = String(product?.id);

    setCart({
      vendorId: product?.vendor_id,
      items: {
        [itemKey]: {
          ...product,
          qty: 1,
        },
      },
    });
  }, []);

  const addToCart = useCallback(
    (product) => {
      if (!product?.id) return;

      if (cart?.vendorId && String(cart.vendorId) !== String(product?.vendor_id)) {
        Alert.alert(
          'Replace basket?',
          'Only one store can stay active in the basket. Replace the current basket with this item?',
          [
            { text: 'Cancel', style: 'cancel' },
            { text: 'Replace', style: 'destructive', onPress: () => replaceCartWith(product) },
          ]
        );
        return;
      }

      const itemKey = String(product.id);

      setCart((current) => {
        const existing = current?.items?.[itemKey];

        return {
          vendorId: product?.vendor_id,
          items: {
            ...(current?.items || {}),
            [itemKey]: {
              ...(existing || product),
              qty: existing ? existing.qty + 1 : 1,
            },
          },
        };
      });
    },
    [cart?.vendorId, replaceCartWith]
  );

  const updateQty = useCallback((product, delta) => {
    const itemKey = String(product?.id);
    if (!itemKey) return;

    setCart((current) => {
      const existing = current?.items?.[itemKey];
      if (!existing) return current;

      const nextQty = existing.qty + delta;
      const nextItems = { ...(current?.items || {}) };

      if (nextQty <= 0) delete nextItems[itemKey];
      else nextItems[itemKey] = { ...existing, qty: nextQty };

      const hasItems = Object.keys(nextItems).length > 0;

      return {
        vendorId: hasItems ? current.vendorId : null,
        items: nextItems,
      };
    });
  }, []);

  const clearCart = useCallback(() => {
    setCart({ vendorId: null, items: {} });
  }, []);

  const placeDemoOrder = useCallback(() => {
    if (!USE_DEMO_CONTENT) {
      Alert.alert(
        'Checkout not connected',
        'Production checkout is not wired yet. Remove demo ordering and connect real payments/orders before release.'
      );
      return false;
    }

    if (cartItems.length === 0) {
      Alert.alert('Basket is empty', 'Add some items first.');
      return false;
    }

    const normalizedService = mapLegacyService(activeService);
    const vendorName =
      cartVendor?.name ||
      cartItems[0]?.vendorName ||
      cartItems[0]?.vendor_name ||
      'GrabBasket Store';

    const order = {
      id: `local-${Date.now()}`,
      service: normalizedService,
      vendorId: cartVendor?.id ?? cart?.vendorId ?? null,
      vendorName,
      location: cartVendor?.address || 'Saved address',
      items: cartItems.map((item) => ({
        name: item?.name,
        qty: item?.qty,
      })),
      orderedAt: formatOrderTime(new Date()),
      total: cartTotal,
      status:
        normalizedService === 'eatout' || normalizedService === 'scenes'
          ? 'Booked'
          : 'Delivered',
    };

    setOrderHistory((current) => [order, ...current].slice(0, MAX_ORDERS));
    clearCart();

    Alert.alert(
      normalizedService === 'eatout' || normalizedService === 'scenes'
        ? 'Demo booking saved'
        : 'Demo order placed',
      'Saved locally so reorder and account flows work while backend checkout is still being wired.'
    );

    return true;
  }, [activeService, cart, cartItems, cartTotal, cartVendor, clearCart]);

  const pastOrders = useMemo(() => {
    const source =
      orderHistory.length > 0
        ? orderHistory
        : USE_DEMO_CONTENT
          ? FALLBACK_ORDER_HISTORY
          : [];

    if (pastOrderFilter === 'all') return source;
    return source.filter((item) => mapLegacyService(item?.service) === pastOrderFilter);
  }, [orderHistory, pastOrderFilter]);

  const value = {
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    storeFilter,
    setStoreFilter,
    pastOrderFilter,
    setPastOrderFilter,
    vendors,
    vendorsLoading,
    refreshing,
    loadVendors,
    homeDeals,
    homeDealsLoading,
    loadProducts,
    favorites,
    toggleFavorite,
    recentStoreIds,
    recentSearches,
    orderHistory,
    featuredVendors,
    recentVendors,
    suggestionPool,
    cart,
    cartItems,
    cartCount,
    cartSubtotal,
    cartVendor,
    deliveryFeeAmount,
    platformFeeAmount,
    cartTotal,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    rememberStore,
    rememberSearch,
    addToCart,
    updateQty,
    clearCart,
    placeDemoOrder,
    pastOrders,
  };

  return <GrabBasketContext.Provider value={value}>{children}</GrabBasketContext.Provider>;
}

function getOrderTone(service = '') {
  const normalized = mapLegacyService(service);

  if (normalized === 'warehouse') {
    return {
      badgeBg: COLORS.blueSoft,
      badgeColor: COLORS.blue,
      actionBg: COLORS.blueSoft,
      actionColor: COLORS.blue,
    };
  }

  if (normalized === 'eatout') {
    return {
      badgeBg: COLORS.yellowSoft,
      badgeColor: COLORS.peach600,
      actionBg: COLORS.yellowSoft,
      actionColor: COLORS.peach600,
    };
  }

  if (normalized === 'scenes') {
    return {
      badgeBg: COLORS.purpleSoft,
      badgeColor: COLORS.purple,
      actionBg: COLORS.purpleSoft,
      actionColor: COLORS.purple,
    };
  }

  return {
    badgeBg: COLORS.peach50,
    badgeColor: COLORS.peach600,
    actionBg: COLORS.peach50,
    actionColor: COLORS.peach600,
  };
}

function getStatusColor(status = '') {
  const value = normalizeText(status);
  if (value === 'booked') return COLORS.peach600;
  if (value.includes('cancel')) return COLORS.danger;
  return COLORS.success;
}

function buildOrderSummary(order) {
  const first = order?.items?.[0];
  if (!first) return 'Order';

  const extra = Math.max(0, (order?.items?.length || 0) - 1);
  return `${first?.qty || 1} x ${first?.name}${extra > 0 ? ` +${extra} more` : ''}`;
}

function resolveOrderVendor(order, vendors = []) {
  if (order?.vendorId) {
    const foundById = findVendorById(vendors, order.vendorId);
    if (foundById) return foundById;
  }

  return (
    vendors.find(
      (vendor) => normalizeText(vendor?.name) === normalizeText(order?.vendorName)
    ) || null
  );
}

function EmptyState({ title, subtitle }) {
  return (
    <View style={styles.feedbackCard}>
      <Text style={styles.feedbackTitle}>{title}</Text>
      <Text style={styles.feedbackSubtitle}>{subtitle}</Text>
    </View>
  );
}

function LoadingState({ label = 'Loading...' }) {
  return (
    <View style={styles.feedbackCard}>
      <ActivityIndicator color={COLORS.peach600} />
      <Text style={styles.feedbackTitle}>{label}</Text>
    </View>
  );
}

function ReorderOrderCard({ order, onPress }) {
  const tone = getOrderTone(order?.service);
  const isBooking =
    mapLegacyService(order?.service) === 'eatout' ||
    mapLegacyService(order?.service) === 'scenes';

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderTopRow}>
        <View style={[styles.orderThumb, { backgroundColor: tone.badgeBg }]}>
          <Text style={[styles.orderThumbText, { color: tone.badgeColor }]}>
            {initials(order?.vendorName)}
          </Text>
        </View>

        <View style={styles.orderMetaBlock}>
          <Text style={styles.orderStoreName} numberOfLines={1}>
            {order?.vendorName}
          </Text>
          <Text style={styles.orderStoreLocation} numberOfLines={1}>
            {order?.location}
          </Text>
        </View>

        <Text style={[styles.orderStatus, { color: getStatusColor(order?.status) }]}>
          {order?.status}
        </Text>
      </View>

      <View style={styles.orderTagRow}>
        <View style={[styles.serviceTag, { backgroundColor: tone.badgeBg }]}>
          <Text style={[styles.serviceTagText, { color: tone.badgeColor }]}>
            {getServiceLabel(order?.service)}
          </Text>
        </View>
      </View>

      <Text style={styles.orderItemLine}>{buildOrderSummary(order)}</Text>
      <Text style={styles.orderMetaLine}>
        Ordered: {order?.orderedAt} · Total: {money(order?.total)}
      </Text>

      <TouchableOpacity
        activeOpacity={0.92}
        style={[styles.orderPrimaryButton, { backgroundColor: tone.actionBg }]}
        onPress={onPress}>
        <Text style={[styles.orderPrimaryButtonText, { color: tone.actionColor }]}>
          {isBooking ? 'VIEW AGAIN' : 'REORDER'}
        </Text>
        <Ionicons name="chevron-forward" size={16} color={tone.actionColor} />
      </TouchableOpacity>
    </View>
  );
}

function RecentVendorCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.recentVendorCard} onPress={onPress}>
      <View style={styles.recentVendorAvatar}>
        <Text style={styles.recentVendorAvatarText}>{initials(vendor?.name)}</Text>
      </View>
      <Text style={styles.recentVendorName} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={styles.recentVendorMeta} numberOfLines={1}>
        ★ {getVendorRating(vendor)} · {estimateEta(vendor)}
      </Text>
    </TouchableOpacity>
  );
}

export function ReorderScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const { cartCount, cartVendor, cartTotal, recentVendors, vendors, orderHistory } =
    useGrabBasket();

  const [activeFilter, setActiveFilter] = useState('all');

  const allOrders = useMemo(() => {
    const source =
      orderHistory?.length > 0
        ? orderHistory
        : USE_DEMO_CONTENT
          ? FALLBACK_ORDER_HISTORY
          : [];

    return source.map((item) => ({ ...item, service: mapLegacyService(item?.service) }));
  }, [orderHistory]);

  const visibleOrders = useMemo(() => {
    if (activeFilter === 'all') return allOrders;
    return allOrders.filter((item) => mapLegacyService(item?.service) === activeFilter);
  }, [activeFilter, allOrders]);

  const openOrderVendor = (order) => {
    const vendor = resolveOrderVendor(order, vendors);

    if (vendor) {
      router.push({
        pathname: '/store/[vendorId]',
        params: { vendorId: String(vendor.id) },
      });
      return;
    }

    Alert.alert(
      'Vendor unavailable',
      'This past order is stored, but the vendor is not in the current feed yet.'
    );
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[styles.screenContent, { paddingBottom: tabBarHeight + 26 }]}>
        <View style={styles.screenHero}>
          <Text style={styles.screenHeroEyebrow}>REORDER</Text>
          <Text style={styles.screenHeroTitle}>Past orders that feel useful, not hidden.</Text>
          <Text style={styles.screenHeroSubtitle}>
            This surface now behaves like a real reorder flow instead of a placeholder tab.
          </Text>
        </View>

        {cartCount > 0 ? (
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.liveBasketCard}
            onPress={() => router.push('/cart')}>
            <View style={styles.liveBasketIcon}>
              <Ionicons name="bag-handle-outline" size={20} color={COLORS.success} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.liveBasketTitle}>Active basket</Text>
              <Text style={styles.liveBasketSubtitle}>
                {cartVendor?.name || 'Current store'} · {cartCount} items · {money(cartTotal)}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={COLORS.success} />
          </TouchableOpacity>
        ) : null}

        <View style={styles.segmentWrap}>
          {REORDER_FILTERS.map((item) => {
            const active = activeFilter === item.key;

            return (
              <TouchableOpacity
                key={item.key}
                activeOpacity={0.92}
                style={[styles.segmentButton, active && styles.segmentButtonActive]}
                onPress={() => setActiveFilter(item.key)}>
                <Text style={[styles.segmentButtonText, active && styles.segmentButtonTextActive]}>
                  {item.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {visibleOrders.length > 0 ? (
          visibleOrders.map((order) => (
            <ReorderOrderCard
              key={order.id}
              order={order}
              onPress={() => openOrderVendor(order)}
            />
          ))
        ) : (
          <EmptyState
            title="No past orders yet"
            subtitle="Orders placed from cart will appear here."
          />
        )}

        {recentVendors.length > 0 ? (
          <>
            <View style={styles.sectionHeaderRow}>
              <Text style={styles.sectionHeaderTitle}>Recent stores</Text>
            </View>

            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
              {recentVendors.slice(0, 8).map((vendor) => (
                <RecentVendorCard
                  key={vendor.id}
                  vendor={vendor}
                  onPress={() =>
                    router.push({
                      pathname: '/store/[vendorId]',
                      params: { vendorId: String(vendor.id) },
                    })
                  }
                />
              ))}
            </ScrollView>
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

function QtyControl({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyWrap}>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onRemove}>
        <Ionicons name="remove" size={16} color={COLORS.text} />
      </TouchableOpacity>
      <Text style={styles.qtyText}>{qty}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

export function CartScreen() {
  const router = useRouter();
  const {
    activeService,
    cartItems,
    cartVendor,
    cartSubtotal,
    deliveryFeeAmount,
    platformFeeAmount,
    cartTotal,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    addToCart,
    updateQty,
    clearCart,
    placeDemoOrder,
  } = useGrabBasket();

  const accent = SERVICE_ACCENT[activeService] || SERVICE_ACCENT.food;
  const isBooking = activeService === 'eatout' || activeService === 'scenes';
  const demoMode = USE_DEMO_CONTENT;

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <View style={styles.topBar}>
        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.iconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back" size={22} color={COLORS.text} />
        </TouchableOpacity>

        <View style={{ flex: 1 }}>
          <Text style={styles.topBarTitle}>{isBooking ? 'Booking' : 'Cart'}</Text>
          <Text style={styles.topBarSubtitle}>{cartVendor?.name || 'Your basket'}</Text>
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.screenContent}>
        {cartItems.length === 0 ? (
          <EmptyState
            title="Your basket is empty"
            subtitle="Add items from one store and they will appear here."
          />
        ) : (
          <>
            {!isBooking ? (
              <View style={styles.billCard}>
                <Text style={styles.billCardTitle}>Free delivery progress</Text>
                <Text style={styles.billCardSubtitle}>
                  {freeDeliveryRemaining > 0
                    ? `Add ${money(freeDeliveryRemaining)} more to unlock free delivery.`
                    : 'Free delivery unlocked for this basket.'}
                </Text>

                <View style={styles.progressTrack}>
                  <View
                    style={[
                      styles.progressFill,
                      {
                        width:
                          freeDeliveryProgress === 0
                            ? '0%'
                            : `${Math.max(10, freeDeliveryProgress * 100)}%`,
                        backgroundColor: accent.primary,
                      },
                    ]}
                  />
                </View>
              </View>
            ) : null}

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>{isBooking ? 'Selection' : 'Items in basket'}</Text>
              {cartItems.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineMeta}>{money(item.price)} each</Text>
                  </View>
                  <QtyControl
                    qty={item.qty}
                    onAdd={() => addToCart(item)}
                    onRemove={() => updateQty(item, -1)}
                  />
                </View>
              ))}
            </View>

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>{isBooking ? 'Booking details' : 'Bill details'}</Text>

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Subtotal</Text>
                <Text style={styles.summaryValue}>{money(cartSubtotal)}</Text>
              </View>

              {!isBooking ? (
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Delivery fee</Text>
                  <Text style={styles.summaryValue}>
                    {deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}
                  </Text>
                </View>
              ) : null}

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Platform fee</Text>
                <Text style={styles.summaryValue}>{money(platformFeeAmount)}</Text>
              </View>

              <View style={styles.summaryDivider} />

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabelStrong}>Total</Text>
                <Text style={styles.summaryValueStrong}>{money(cartTotal)}</Text>
              </View>
            </View>

            <TouchableOpacity
              activeOpacity={demoMode ? 0.92 : 1}
              style={[
                styles.primaryButton,
                {
                  backgroundColor: demoMode
                    ? accent.dark
                      ? COLORS.black
                      : accent.primary
                    : COLORS.subtle,
                },
              ]}
              onPress={() => {
                const ok = placeDemoOrder();
                if (ok) router.replace('/reorder');
              }}>
              <Text style={styles.primaryButtonText}>
                {demoMode
                  ? isBooking
                    ? 'Confirm demo booking'
                    : 'Place demo order'
                  : 'Checkout not yet connected'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              activeOpacity={0.92}
              style={styles.secondaryButton}
              onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear basket</Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

export default function AppBridge() {
  return null;
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },

  screenContent: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 28,
  },

  screenHero: {
    borderRadius: 28,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 20,
    marginBottom: 16,
  },

  screenHeroEyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.4,
  },

  screenHeroTitle: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
    lineHeight: 34,
  },

  screenHeroSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },

  liveBasketCard: {
    marginBottom: 16,
    borderRadius: 22,
    backgroundColor: COLORS.successSoft,
    borderWidth: 1,
    borderColor: COLORS.successBorder,
    paddingHorizontal: 16,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },

  liveBasketIcon: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#DCFCE7',
    alignItems: 'center',
    justifyContent: 'center',
  },

  liveBasketTitle: {
    color: COLORS.success,
    fontSize: 15,
    fontWeight: '900',
  },

  liveBasketSubtitle: {
    marginTop: 4,
    color: COLORS.success,
    fontSize: 12,
    fontWeight: '700',
  },

  segmentWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 14,
  },

  segmentButton: {
    borderRadius: 999,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },

  segmentButtonActive: {
    backgroundColor: COLORS.peach600,
    borderColor: COLORS.peach600,
  },

  segmentButtonText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },

  segmentButtonTextActive: {
    color: '#FFFFFF',
  },

  orderCard: {
    marginBottom: 14,
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },

  orderTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },

  orderThumb: {
    width: 56,
    height: 56,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },

  orderThumbText: {
    fontSize: 18,
    fontWeight: '900',
  },

  orderMetaBlock: {
    flex: 1,
    paddingRight: 8,
  },

  orderStoreName: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  orderStoreLocation: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },

  orderStatus: {
    fontSize: 15,
    fontWeight: '900',
  },

  orderTagRow: {
    marginTop: 14,
    marginBottom: 8,
  },

  serviceTag: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },

  serviceTagText: {
    fontSize: 12,
    fontWeight: '900',
  },

  orderItemLine: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },

  orderMetaLine: {
    marginTop: 10,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },

  orderPrimaryButton: {
    marginTop: 16,
    minHeight: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
  },

  orderPrimaryButtonText: {
    fontSize: 15,
    fontWeight: '900',
  },

  sectionHeaderRow: {
    marginTop: 10,
    marginBottom: 12,
  },

  sectionHeaderTitle: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
  },

  horizontalRail: {
    gap: 12,
    paddingBottom: 4,
  },

  recentVendorCard: {
    width: 156,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
  },

  recentVendorAvatar: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },

  recentVendorAvatarText: {
    color: COLORS.peach600,
    fontSize: 18,
    fontWeight: '900',
  },

  recentVendorName: {
    marginTop: 12,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },

  recentVendorMeta: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
  },

  feedbackCard: {
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 28,
    paddingHorizontal: 18,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },

  feedbackTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },

  feedbackSubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    textAlign: 'center',
  },

  topBar: {
    minHeight: 62,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },

  iconButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
  },

  topBarTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  topBarSubtitle: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
  },

  billCard: {
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    marginBottom: 14,
  },

  billCardTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  billCardSubtitle: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },

  progressTrack: {
    marginTop: 14,
    height: 10,
    borderRadius: 999,
    backgroundColor: COLORS.line,
    overflow: 'hidden',
  },

  progressFill: {
    height: '100%',
    borderRadius: 999,
  },

  cartLine: {
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
  },

  cartLineTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },

  cartLineMeta: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
  },

  qtyWrap: {
    minWidth: 92,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.cardAlt,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
  },

  qtyAction: {
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },

  qtyText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },

  summaryRow: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },

  summaryLabel: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '600',
  },

  summaryValue: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },

  summaryDivider: {
    marginTop: 14,
    height: 1,
    backgroundColor: COLORS.border,
  },

  summaryLabelStrong: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },

  summaryValueStrong: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  primaryButton: {
    minHeight: 52,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
  },

  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '900',
  },

  secondaryButton: {
    minHeight: 52,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
  },

  secondaryButtonText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
});