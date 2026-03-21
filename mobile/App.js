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
import { API_CONFIG_ERROR, API_TIMEOUT_MS, buildApiUrl } from './src/config';

const STORAGE_CART = '@grab_basket/cart_v11';
const STORAGE_FAVORITES = '@grab_basket/favorites_v8';
const STORAGE_RECENT_STORES = '@grab_basket/recent_stores_v9';
const STORAGE_RECENT_SEARCHES = '@grab_basket/recent_searches_v8';
const STORAGE_ORDER_HISTORY = '@grab_basket/order_history_v6';
const STORAGE_AUTH_TOKEN = '@grab_basket/auth_token_v1';
const STORAGE_AUTH_EMAIL = '@grab_basket/auth_email_v1';
const STORAGE_AUTH_ROLE = '@grab_basket/auth_role_v1';
const STORAGE_SELECTED_ADDRESS_ID = '@grab_basket/selected_address_id_v1';

const FREE_DELIVERY_THRESHOLD = 199;
const PLATFORM_FEE = 0;
const MAX_RECENT = 8;
const MAX_ORDERS = 50;
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
  dangerSoft: '#FCE9E9',

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

function formatOrderTime(dateInput = new Date()) {
  try {
    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
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
    fresh: vendors.filter((vendor) =>
      /(fruit|vegetable|fresh|dairy|farm|grocery|greens)/i.test(
        `${vendor?.name || ''} ${vendor?.description || ''}`
      )
    ),
    maxxsaver: vendors.filter((vendor) =>
      /(save|mart|basket|daily|essentials|value)/i.test(
        `${vendor?.name || ''} ${vendor?.description || ''}`
      )
    ),
    festival: vendors.filter((vendor) =>
      /(dates|dry|dessert|sweet|gift|biryani|festival|ramzan)/i.test(
        `${vendor?.name || ''} ${vendor?.description || ''}`
      )
    ),
    ready: vendors.filter((vendor) =>
      /(ready|instant|coffee|tea|bakery|juice|quick)/i.test(
        `${vendor?.name || ''} ${vendor?.description || ''}`
      )
    ),
  };
}

function isValidCart(value) {
  return Boolean(
    value && typeof value === 'object' && !Array.isArray(value) && typeof value.items === 'object'
  );
}

function prettifyStatus(status = '') {
  const raw = String(status || '').trim();
  if (!raw) return 'Created';
  return raw
    .replace(/_/g, ' ')
    .toLowerCase()
    .replace(/\b\w/g, (match) => match.toUpperCase());
}

function inferServiceFromContent(vendor, items = []) {
  const bag = `${vendor?.name || ''} ${vendor?.description || ''} ${items
    .map((item) => item?.name || item?.name_snapshot || '')
    .join(' ')}`.toLowerCase();

  if (/(mart|basket|grocery|milk|curd|bread|atta|snack)/.test(bag)) return 'warehouse';
  if (/(table|booking|reservation|cafe|brewery|dine)/.test(bag)) return 'eatout';
  if (/(scene|event|ticket|show|workshop|comedy|gig|entry)/.test(bag)) return 'scenes';
  return 'food';
}

function formatAddressShort(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city].filter(Boolean).join(' · ');
}

function normalizeAddress(item) {
  if (!item) return null;
  return {
    id: item.id,
    label: item.label || 'Address',
    line1: item.line1 || '',
    line2: item.line2 || '',
    city: item.city || '',
    pincode: item.pincode || '',
    lat: Number(item.lat ?? 0),
    lng: Number(item.lng ?? 0),
    is_default: Boolean(item.is_default),
  };
}

function normalizeOrderRecord(order, { vendors = [], addresses = [], serviceHint = '' } = {}) {
  if (!order) return null;

  const vendorId = order.vendor_id ?? order.vendorId ?? null;
  const vendor = findVendorById(vendors, vendorId);
  const items = Array.isArray(order.items)
    ? order.items.map((item) => ({
        name: item?.name_snapshot || item?.name || 'Item',
        qty: Number(item?.qty || 1),
      }))
    : [];

  const addressId = order.delivery_address_id ?? order.deliveryAddressId ?? null;
  const address = addresses.find((item) => String(item?.id) === String(addressId)) || null;
  const rawService = normalizeText(order.service);
  const service = rawService
    ? mapLegacyService(rawService)
    : serviceHint
      ? mapLegacyService(serviceHint)
      : inferServiceFromContent(vendor, items);

  return {
    id: String(order.id || Date.now()),
    service,
    vendorId,
    vendorName: order.vendorName || vendor?.name || `Store #${vendorId || 'NA'}`,
    location:
      order.location ||
      vendor?.address ||
      formatAddressShort(address) ||
      'Saved address',
    items,
    orderedAt: order.orderedAt || formatOrderTime(order.created_at || new Date()),
    total:
      Number(order.total_amount ?? order.total ?? order.subtotal_amount ?? 0) +
      Number(order.platform_fee ?? 0),
    status: prettifyStatus(order.status || 'CREATED'),
    paymentMethod: order.payment_method || order.paymentMethod || 'COD',
    paymentStatus: prettifyStatus(order.payment_status || order.paymentStatus || 'PENDING'),
    deliveryAddressId: addressId,
    createdAt: order.created_at || null,
  };
}

function mergeOrderCollections(primary = [], secondary = []) {
  const map = new Map();

  [...primary, ...secondary].forEach((item) => {
    if (!item?.id) return;
    map.set(String(item.id), item);
  });

  return Array.from(map.values()).sort((a, b) => {
    const aTime = new Date(a.createdAt || a.orderedAt || 0).getTime();
    const bTime = new Date(b.createdAt || b.orderedAt || 0).getTime();
    return bTime - aTime;
  });
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
      throw new Error(extractErrorMessage(data, `Request failed with status ${response.status}`));
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

  const [sessionReady, setSessionReady] = useState(false);
  const [authToken, setAuthToken] = useState('');
  const [authEmail, setAuthEmail] = useState('');
  const [authRole, setAuthRole] = useState('');
  const [profile, setProfile] = useState(null);
  const [authLoading, setAuthLoading] = useState(false);
  const [addresses, setAddresses] = useState([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState('');
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [placingOrder, setPlacingOrder] = useState(false);

  const vendorRequestIdRef = useRef(0);
  const dealsRequestIdRef = useRef(0);
  const configAlertShownRef = useRef(false);
  const vendorErrorAlertRef = useRef('');
  const productsErrorAlertRef = useRef('');

  const isAuthenticated = Boolean(authToken);

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
          STORAGE_AUTH_TOKEN,
          STORAGE_AUTH_EMAIL,
          STORAGE_AUTH_ROLE,
          STORAGE_SELECTED_ADDRESS_ID,
        ]);

        if (!mounted) return;

        const nextCart = values[0]?.[1];
        const nextFavorites = values[1]?.[1];
        const nextStores = values[2]?.[1];
        const nextSearches = values[3]?.[1];
        const nextOrders = values[4]?.[1];
        const nextAuthToken = values[5]?.[1];
        const nextAuthEmail = values[6]?.[1];
        const nextAuthRole = values[7]?.[1];
        const nextSelectedAddressId = values[8]?.[1];

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
              setOrderHistory(parsed.slice(0, MAX_ORDERS));
            }
          } catch {}
        }

        if (nextAuthToken) setAuthToken(String(nextAuthToken));
        if (nextAuthEmail) setAuthEmail(String(nextAuthEmail));
        if (nextAuthRole) setAuthRole(String(nextAuthRole));
        if (nextSelectedAddressId) setSelectedAddressId(String(nextSelectedAddressId));
      } catch {
        // ignore invalid local cache
      } finally {
        if (mounted) {
          setSessionReady(true);
          setVendorsLoading(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    if (!sessionReady) return;
    AsyncStorage.setItem(STORAGE_CART, JSON.stringify(cart)).catch(() => {});
  }, [cart, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;
    AsyncStorage.setItem(STORAGE_FAVORITES, JSON.stringify(favorites)).catch(() => {});
  }, [favorites, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;
    AsyncStorage.setItem(STORAGE_RECENT_STORES, JSON.stringify(recentStoreIds)).catch(() => {});
  }, [recentStoreIds, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;
    AsyncStorage.setItem(STORAGE_RECENT_SEARCHES, JSON.stringify(recentSearches)).catch(() => {});
  }, [recentSearches, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;
    AsyncStorage.setItem(STORAGE_ORDER_HISTORY, JSON.stringify(orderHistory)).catch(() => {});
  }, [orderHistory, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;

    if (authToken) {
      AsyncStorage.multiSet([
        [STORAGE_AUTH_TOKEN, authToken],
        [STORAGE_AUTH_EMAIL, authEmail || ''],
        [STORAGE_AUTH_ROLE, authRole || ''],
      ]).catch(() => {});
      return;
    }

    AsyncStorage.multiRemove([
      STORAGE_AUTH_TOKEN,
      STORAGE_AUTH_EMAIL,
      STORAGE_AUTH_ROLE,
    ]).catch(() => {});
  }, [authEmail, authRole, authToken, sessionReady]);

  useEffect(() => {
    if (!sessionReady) return;
    if (selectedAddressId) {
      AsyncStorage.setItem(STORAGE_SELECTED_ADDRESS_ID, String(selectedAddressId)).catch(() => {});
      return;
    }
    AsyncStorage.removeItem(STORAGE_SELECTED_ADDRESS_ID).catch(() => {});
  }, [selectedAddressId, sessionReady]);

  const authorizedRequest = useCallback(
    async (path, options = {}) => {
      if (!authToken) {
        throw new Error('Sign in is required for this action.');
      }

      return apiRequest(path, {
        ...options,
        headers: {
          ...(options.headers || {}),
          Authorization: `Bearer ${authToken}`,
        },
      });
    },
    [authToken]
  );

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
            .filter((item) => item?.is_available)
            .slice(0, 3)
            .map((item) => ({
              ...item,
              vendorName: vendor?.name,
              brand: vendor?.description || vendor?.address || 'Top pick',
            }))
        )
        .slice(0, 8);

      setHomeDeals(curated);
    } finally {
      if (requestId === dealsRequestIdRef.current) {
        setHomeDealsLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    loadHomeDeals(vendors);
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

  const refreshProfile = useCallback(async () => {
    if (!authToken) {
      setProfile(null);
      return null;
    }

    const data = await authorizedRequest('/me/profile');
    setProfile(data || null);
    return data || null;
  }, [authToken, authorizedRequest]);

  const loadAddresses = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) {
        setAddresses([]);
        return [];
      }

      try {
        if (!silent) setAddressesLoading(true);
        const data = await authorizedRequest('/me/addresses');
        const parsed = Array.isArray(data)
          ? data.map(normalizeAddress).filter(Boolean)
          : [];
        setAddresses(parsed);
        return parsed;
      } catch (error) {
        if (!silent) {
          Alert.alert('Could not load addresses', normalizeErrorMessage(error));
        }
        return [];
      } finally {
        if (!silent) setAddressesLoading(false);
      }
    },
    [authToken, authorizedRequest]
  );

  const loadOrders = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) {
        setOrderHistory([]);
        return [];
      }

      try {
        if (!silent) setOrdersLoading(true);
        const data = await authorizedRequest('/orders/me');
        const parsed = Array.isArray(data)
          ? data
              .map((item) => normalizeOrderRecord(item, { vendors, addresses }))
              .filter(Boolean)
          : [];
        setOrderHistory((current) => mergeOrderCollections(parsed, current).slice(0, MAX_ORDERS));
        return parsed;
      } catch (error) {
        if (!silent) {
          Alert.alert('Could not load orders', normalizeErrorMessage(error));
        }
        return [];
      } finally {
        if (!silent) setOrdersLoading(false);
      }
    },
    [addresses, authToken, authorizedRequest, vendors]
  );

  useEffect(() => {
    if (!sessionReady) return;

    if (!authToken) {
      setProfile(null);
      setAddresses([]);
      setSelectedAddressId('');
      return;
    }

    refreshProfile().catch(() => {});
    loadAddresses({ silent: true }).catch(() => {});
  }, [authToken, loadAddresses, refreshProfile, sessionReady]);

  useEffect(() => {
    if (!sessionReady || !authToken) return;
    loadOrders({ silent: true }).catch(() => {});
  }, [authToken, vendors, addresses, loadOrders, sessionReady]);

  useEffect(() => {
    if (!addresses.length) {
      if (selectedAddressId) setSelectedAddressId('');
      return;
    }

    const stillExists = addresses.some((item) => String(item.id) === String(selectedAddressId));
    if (stillExists) return;

    const next = addresses.find((item) => item.is_default) || addresses[0];
    setSelectedAddressId(next ? String(next.id) : '');
  }, [addresses, selectedAddressId]);

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
        ...homeDeals.map((item) => item?.name),
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

  const defaultAddress = useMemo(() => {
    if (!addresses.length) return null;
    return (
      addresses.find((item) => String(item.id) === String(selectedAddressId)) ||
      addresses.find((item) => item.is_default) ||
      addresses[0]
    );
  }, [addresses, selectedAddressId]);

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

  const login = useCallback(
    async ({ email, password }) => {
      setAuthLoading(true);
      try {
        const payload = {
          email: String(email || '').trim().toLowerCase(),
          password: String(password || ''),
        };

        const data = await apiRequest('/auth/login', {
          method: 'POST',
          body: JSON.stringify(payload),
        });

        const nextRole = String(data?.role || '').toUpperCase();
        if (nextRole && nextRole !== 'CUSTOMER') {
          throw new Error('This mobile app currently supports customer accounts only.');
        }

        setAuthToken(String(data?.access_token || ''));
        setAuthEmail(payload.email);
        setAuthRole(nextRole || 'CUSTOMER');
        return true;
      } catch (error) {
        Alert.alert('Could not sign in', normalizeErrorMessage(error));
        return false;
      } finally {
        setAuthLoading(false);
      }
    },
    []
  );

  const register = useCallback(
    async ({ email, password }) => {
      setAuthLoading(true);
      try {
        const payload = {
          email: String(email || '').trim().toLowerCase(),
          password: String(password || ''),
          role: 'CUSTOMER',
        };

        const data = await apiRequest('/auth/register', {
          method: 'POST',
          body: JSON.stringify(payload),
        });

        setAuthToken(String(data?.access_token || ''));
        setAuthEmail(payload.email);
        setAuthRole(String(data?.role || 'CUSTOMER').toUpperCase());
        return true;
      } catch (error) {
        Alert.alert('Could not create account', normalizeErrorMessage(error));
        return false;
      } finally {
        setAuthLoading(false);
      }
    },
    []
  );

  const logout = useCallback(() => {
    setAuthToken('');
    setAuthEmail('');
    setAuthRole('');
    setProfile(null);
    setAddresses([]);
    setOrderHistory([]);
    setSelectedAddressId('');
    clearCart();
  }, [clearCart]);

  const createAddress = useCallback(
    async (payload) => {
      if (!authToken) {
        Alert.alert('Sign in required', 'Sign in before adding a delivery address.');
        return null;
      }

      try {
        setAddressesLoading(true);
        const body = {
          label: String(payload?.label || 'Home').trim() || 'Home',
          line1: String(payload?.line1 || '').trim(),
          line2: String(payload?.line2 || '').trim(),
          city: String(payload?.city || '').trim(),
          pincode: String(payload?.pincode || '').trim(),
          lat: Number(payload?.lat),
          lng: Number(payload?.lng),
          is_default: Boolean(payload?.is_default),
        };

        if (!body.line1) throw new Error('Address line 1 is required.');
        if (!Number.isFinite(body.lat) || !Number.isFinite(body.lng)) {
          throw new Error('Latitude and longitude are required.');
        }

        const data = await authorizedRequest('/me/addresses', {
          method: 'POST',
          body: JSON.stringify(body),
        });

        const next = normalizeAddress(data);
        if (!next) return null;

        setAddresses((current) => {
          const rest = body.is_default
            ? current.map((item) => ({ ...item, is_default: false }))
            : current;
          return [next, ...rest.filter((item) => String(item.id) !== String(next.id))];
        });
        setSelectedAddressId(String(next.id));
        return next;
      } catch (error) {
        Alert.alert('Could not save address', normalizeErrorMessage(error));
        return null;
      } finally {
        setAddressesLoading(false);
      }
    },
    [authToken, authorizedRequest]
  );

  const setDefaultAddress = useCallback(
    async (addressId) => {
      if (!authToken) return false;

      try {
        await authorizedRequest(`/me/addresses/${addressId}/default`, {
          method: 'POST',
        });
        setAddresses((current) =>
          current.map((item) => ({
            ...item,
            is_default: String(item.id) === String(addressId),
          }))
        );
        setSelectedAddressId(String(addressId));
        return true;
      } catch (error) {
        Alert.alert('Could not update address', normalizeErrorMessage(error));
        return false;
      }
    },
    [authToken, authorizedRequest]
  );

  const placeOrder = useCallback(
    async ({ paymentMethod = 'COD' } = {}) => {
      if (cartItems.length === 0) {
        Alert.alert('Basket is empty', 'Add some items first.');
        return false;
      }

      if (!authToken) {
        Alert.alert('Sign in required', 'Sign in from the Account tab before placing an order.');
        return false;
      }

      const normalizedService = mapLegacyService(activeService);
      const needsDeliveryAddress = normalizedService === 'food' || normalizedService === 'warehouse';
      const deliveryAddressId = needsDeliveryAddress ? defaultAddress?.id || null : defaultAddress?.id || null;

      if (needsDeliveryAddress && !deliveryAddressId) {
        Alert.alert(
          'Add delivery address',
          'Add a delivery address from the Account tab before placing this order.'
        );
        return false;
      }

      if (!cartVendor?.id && !cart?.vendorId) {
        Alert.alert('Store unavailable', 'We could not resolve the store for this basket.');
        return false;
      }

      try {
        setPlacingOrder(true);

        const payload = {
          vendor_id: Number(cartVendor?.id ?? cart?.vendorId),
          items: cartItems.map((item) => ({
            product_id: Number(item.id),
            qty: Number(item.qty || 1),
          })),
          payment_method: String(paymentMethod || 'COD').toUpperCase(),
          ...(deliveryAddressId ? { delivery_address_id: Number(deliveryAddressId) } : {}),
        };

        const response = await authorizedRequest('/orders', {
          method: 'POST',
          body: JSON.stringify(payload),
        });

        const normalizedOrder = normalizeOrderRecord(response, {
          vendors,
          addresses,
          serviceHint: normalizedService,
        });

        if (normalizedOrder) {
          setOrderHistory((current) => mergeOrderCollections([normalizedOrder], current).slice(0, MAX_ORDERS));
        }

        clearCart();

        Alert.alert(
          normalizedService === 'eatout' || normalizedService === 'scenes'
            ? 'Booking confirmed'
            : 'Order placed',
          payload.payment_method === 'UPI' && response?.payment_ref
            ? `Order created successfully. Payment reference: ${response.payment_ref}`
            : 'Your order has been created successfully.'
        );

        return true;
      } catch (error) {
        Alert.alert('Could not place order', normalizeErrorMessage(error));
        return false;
      } finally {
        setPlacingOrder(false);
      }
    },
    [
      activeService,
      addresses,
      authToken,
      authorizedRequest,
      cart,
      cartItems,
      cartVendor,
      clearCart,
      defaultAddress,
      vendors,
    ]
  );

  const pastOrders = useMemo(() => {
    if (pastOrderFilter === 'all') return orderHistory;
    return orderHistory.filter((item) => mapLegacyService(item?.service) === pastOrderFilter);
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
    placeDemoOrder: placeOrder,
    placeOrder,
    pastOrders,
    sessionReady,
    isAuthenticated,
    authToken,
    authEmail,
    authRole,
    profile,
    authLoading,
    login,
    register,
    logout,
    addresses,
    addressesLoading,
    selectedAddressId,
    setSelectedAddressId,
    defaultAddress,
    createAddress,
    setDefaultAddress,
    loadAddresses,
    loadOrders,
    ordersLoading,
    placingOrder,
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
  if (value.includes('cancel')) return COLORS.danger;
  if (value.includes('deliver')) return COLORS.success;
  if (value.includes('ready') || value.includes('pick')) return COLORS.blue;
  return COLORS.peach600;
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
    vendors.find((vendor) => normalizeText(vendor?.name) === normalizeText(order?.vendorName)) ||
    null
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
  const { cartCount, cartVendor, cartTotal, recentVendors, vendors, orderHistory, ordersLoading } =
    useGrabBasket();

  const [activeFilter, setActiveFilter] = useState('all');

  const visibleOrders = useMemo(() => {
    if (activeFilter === 'all') return orderHistory;
    return orderHistory.filter((item) => mapLegacyService(item?.service) === activeFilter);
  }, [activeFilter, orderHistory]);

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
      'This order is in your account history, but the vendor is not in the current store feed yet.'
    );
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[styles.screenContent, { paddingBottom: tabBarHeight + 26 }]}>
        <View style={styles.screenHero}>
          <Text style={styles.screenHeroEyebrow}>ORDERS</Text>
          <Text style={styles.screenHeroTitle}>Your recent orders and bookings.</Text>
          <Text style={styles.screenHeroSubtitle}>
            Live backend orders appear here once the user signs in and places an order.
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

        {ordersLoading ? (
          <LoadingState label="Refreshing your orders..." />
        ) : visibleOrders.length > 0 ? (
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
            subtitle="Orders placed from the cart will appear here after checkout succeeds."
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
      <Text style={styles.qtyValue}>{qty}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

function PaymentMethodPill({ label, value, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.paymentPill, active && styles.paymentPillActive]}
      onPress={() => onPress(value)}>
      <Text style={[styles.paymentPillText, active && styles.paymentPillTextActive]}>{label}</Text>
    </TouchableOpacity>
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
    placeOrder,
    isAuthenticated,
    defaultAddress,
    placingOrder,
  } = useGrabBasket();

  const [paymentMethod, setPaymentMethod] = useState('COD');

  const accent = SERVICE_ACCENT[activeService] || SERVICE_ACCENT.food;
  const isBooking = activeService === 'eatout' || activeService === 'scenes';

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
            {!isAuthenticated ? (
              <View style={styles.noticeCard}>
                <View style={styles.noticeIcon}>
                  <Ionicons name="lock-closed-outline" size={18} color={COLORS.peach600} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.noticeTitle}>Sign in before checkout</Text>
                  <Text style={styles.noticeSubtitle}>
                    Use the Account tab to sign in or create a customer account.
                  </Text>
                </View>
                <TouchableOpacity
                  activeOpacity={0.92}
                  style={styles.noticeAction}
                  onPress={() => router.push('/account')}>
                  <Text style={styles.noticeActionText}>Open Account</Text>
                </TouchableOpacity>
              </View>
            ) : null}

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

            {!isBooking ? (
              <View style={styles.billCard}>
                <Text style={styles.billCardTitle}>Delivery address</Text>
                {defaultAddress ? (
                  <>
                    <Text style={styles.billCardSubtitle}>{formatAddressShort(defaultAddress)}</Text>
                    <Text style={styles.helperText}>
                      Lat {Number(defaultAddress.lat).toFixed(4)} · Lng {Number(defaultAddress.lng).toFixed(4)}
                    </Text>
                  </>
                ) : (
                  <Text style={styles.billCardSubtitle}>
                    No delivery address selected yet. Add one from Account to place a food or grocery order.
                  </Text>
                )}
                <TouchableOpacity
                  activeOpacity={0.92}
                  style={styles.inlineGhostButton}
                  onPress={() => router.push('/account')}>
                  <Text style={styles.inlineGhostButtonText}>Manage address</Text>
                </TouchableOpacity>
              </View>
            ) : null}

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>Payment method</Text>
              <View style={styles.paymentRow}>
                <PaymentMethodPill
                  label="Cash on delivery"
                  value="COD"
                  active={paymentMethod === 'COD'}
                  onPress={setPaymentMethod}
                />
                <PaymentMethodPill
                  label="UPI"
                  value="UPI"
                  active={paymentMethod === 'UPI'}
                  onPress={setPaymentMethod}
                />
              </View>
              <Text style={styles.helperText}>
                Gateway integration is still the next backend step. This patch uses your current API contract.
              </Text>
            </View>

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
                  <Text style={styles.summaryLabel}>Estimated delivery fee</Text>
                  <Text style={styles.summaryValue}>
                    {deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}
                  </Text>
                </View>
              ) : null}

              {platformFeeAmount > 0 ? (
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Platform fee</Text>
                  <Text style={styles.summaryValue}>{money(platformFeeAmount)}</Text>
                </View>
              ) : null}

              <View style={styles.summaryDivider} />

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabelStrong}>Estimated total</Text>
                <Text style={styles.summaryValueStrong}>{money(cartTotal)}</Text>
              </View>
            </View>

            <TouchableOpacity
              activeOpacity={placingOrder ? 1 : 0.92}
              disabled={placingOrder}
              style={[
                styles.primaryButton,
                {
                  backgroundColor: accent.dark ? COLORS.black : accent.primary,
                  opacity: placingOrder ? 0.75 : 1,
                },
              ]}
              onPress={async () => {
                const ok = await placeOrder({ paymentMethod });
                if (ok) router.replace('/reorder');
              }}>
              {placingOrder ? <ActivityIndicator color="#FFFFFF" /> : null}
              <Text style={styles.primaryButtonText}>
                {placingOrder
                  ? 'Placing order...'
                  : isBooking
                    ? 'Confirm booking'
                    : 'Place order'}
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
    padding: 22,
    marginBottom: 18,
  },
  screenHeroEyebrow: {
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1.2,
    color: COLORS.peach600,
    marginBottom: 8,
  },
  screenHeroTitle: {
    fontSize: 24,
    lineHeight: 30,
    fontWeight: '900',
    color: COLORS.text,
  },
  screenHeroSubtitle: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 21,
    color: COLORS.muted,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 8,
    gap: 12,
  },
  iconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  topBarTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: COLORS.text,
  },
  topBarSubtitle: {
    marginTop: 2,
    fontSize: 13,
    color: COLORS.muted,
  },
  billCard: {
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 24,
    padding: 18,
    marginBottom: 14,
  },
  billCardTitle: {
    fontSize: 17,
    fontWeight: '900',
    color: COLORS.text,
  },
  billCardSubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 20,
    color: COLORS.muted,
  },
  progressTrack: {
    height: 10,
    borderRadius: 999,
    backgroundColor: COLORS.line,
    overflow: 'hidden',
    marginTop: 14,
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
  },
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.line,
  },
  cartLineTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  cartLineMeta: {
    marginTop: 4,
    fontSize: 13,
    color: COLORS.muted,
  },
  qtyWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    borderRadius: 14,
    paddingHorizontal: 6,
    height: 40,
  },
  qtyAction: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyValue: {
    minWidth: 24,
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 14,
  },
  summaryLabel: {
    fontSize: 14,
    color: COLORS.muted,
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  summaryDivider: {
    height: 1,
    backgroundColor: COLORS.line,
    marginTop: 16,
  },
  summaryLabelStrong: {
    fontSize: 16,
    fontWeight: '900',
    color: COLORS.text,
  },
  summaryValueStrong: {
    fontSize: 17,
    fontWeight: '900',
    color: COLORS.text,
  },
  primaryButton: {
    minHeight: 54,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 10,
    marginTop: 10,
  },
  primaryButtonText: {
    fontSize: 15,
    fontWeight: '900',
    color: '#FFFFFF',
  },
  secondaryButton: {
    minHeight: 52,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    marginTop: 12,
  },
  secondaryButtonText: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  feedbackCard: {
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    minHeight: 180,
  },
  feedbackTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
    textAlign: 'center',
  },
  feedbackSubtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: COLORS.muted,
    textAlign: 'center',
  },
  orderCard: {
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 24,
    padding: 18,
    marginBottom: 14,
  },
  orderTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  orderThumb: {
    width: 46,
    height: 46,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderThumbText: {
    fontSize: 16,
    fontWeight: '900',
  },
  orderMetaBlock: {
    flex: 1,
    paddingHorizontal: 12,
  },
  orderStoreName: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.text,
  },
  orderStoreLocation: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
  },
  orderStatus: {
    fontSize: 12,
    fontWeight: '900',
  },
  orderTagRow: {
    flexDirection: 'row',
    marginTop: 14,
    marginBottom: 12,
  },
  serviceTag: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  serviceTagText: {
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  orderItemLine: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  orderMetaLine: {
    marginTop: 6,
    fontSize: 13,
    color: COLORS.muted,
  },
  orderPrimaryButton: {
    marginTop: 16,
    minHeight: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 6,
  },
  orderPrimaryButtonText: {
    fontSize: 13,
    fontWeight: '900',
  },
  liveBasketCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.successSoft,
    borderWidth: 1,
    borderColor: COLORS.successBorder,
    borderRadius: 22,
    padding: 16,
    marginBottom: 16,
    gap: 12,
  },
  liveBasketIcon: {
    width: 44,
    height: 44,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
  liveBasketTitle: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.text,
  },
  liveBasketSubtitle: {
    marginTop: 4,
    fontSize: 13,
    color: COLORS.muted,
  },
  segmentWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 16,
  },
  segmentButton: {
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
  },
  segmentButtonActive: {
    backgroundColor: COLORS.peach50,
    borderColor: COLORS.peach300,
  },
  segmentButtonText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.muted,
  },
  segmentButtonTextActive: {
    color: COLORS.peach600,
  },
  sectionHeaderRow: {
    marginTop: 8,
    marginBottom: 12,
  },
  sectionHeaderTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: COLORS.text,
  },
  horizontalRail: {
    paddingBottom: 4,
    gap: 12,
  },
  recentVendorCard: {
    width: 148,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    padding: 16,
  },
  recentVendorAvatar: {
    width: 42,
    height: 42,
    borderRadius: 15,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  recentVendorAvatarText: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.peach600,
  },
  recentVendorName: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  recentVendorMeta: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
  },
  noticeCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.peach300,
    borderRadius: 22,
    padding: 16,
    marginBottom: 14,
    gap: 12,
  },
  noticeIcon: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.peach50,
  },
  noticeTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  noticeSubtitle: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  noticeAction: {
    borderRadius: 12,
    backgroundColor: COLORS.peach600,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  noticeActionText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '900',
  },
  paymentRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 14,
  },
  paymentPill: {
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
  },
  paymentPillActive: {
    borderColor: COLORS.peach300,
    backgroundColor: COLORS.peach50,
  },
  paymentPillText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.muted,
  },
  paymentPillTextActive: {
    color: COLORS.peach600,
  },
  helperText: {
    marginTop: 8,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.subtle,
  },
  inlineGhostButton: {
    alignSelf: 'flex-start',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginTop: 12,
  },
  inlineGhostButtonText: {
    fontSize: 12,
    fontWeight: '900',
    color: COLORS.text,
  },
});