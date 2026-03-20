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
  Platform,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import * as TrackingTransparency from 'expo-tracking-transparency';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { API_BASE_URL, ADS_CONFIG, META_CONFIG } from './src/config';

const STORAGE_CART = '@grab_basket/cart_v7';
const STORAGE_FAVORITES = '@grab_basket/favorites_v4';
const STORAGE_RECENT_STORES = '@grab_basket/recent_stores_v5';
const STORAGE_RECENT_SEARCHES = '@grab_basket/recent_searches_v4';
const STORAGE_ORDER_HISTORY = '@grab_basket/order_history_v2';

const FREE_DELIVERY_THRESHOLD = 199;
const PLATFORM_FEE = 6;

const COLORS = {
  bg: '#f5f6f8',
  card: '#ffffff',
  text: '#101828',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#eaecf0',
  green900: '#065f46',
  green800: '#0b7a5a',
  green700: '#12906d',
  green100: '#d1fae5',
  yellow100: '#fff3c4',
  pink100: '#ffd7ec',
  blue100: '#dbeafe',
  purple100: '#ede9fe',
  orange100: '#ffedd5',
  dark950: '#020617',
  dark900: '#09111f',
  dark800: '#111827',
  dark700: '#1f2937',
};

const TOP_SERVICES = [
  { key: 'food', icon: 'restaurant-outline', label: 'Food' },
  { key: 'instamart', icon: 'bag-handle-outline', label: 'Instamart' },
  { key: 'dineout', icon: 'wine-outline', label: 'Dineout' },
  { key: 'events', icon: 'sparkles-outline', label: 'Events' },
];

const HOME_SHORTCUTS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxx', label: 'Max Saver', icon: 'pricetags-outline' },
  { key: 'late', label: 'Late night', icon: 'moon-outline' },
  { key: 'quick', label: 'Quick bites', icon: 'flash-outline' },
];

const STORE_FILTERS = ['All', 'Open now', 'Closest', 'A-Z'];
const EVENT_FILTERS = ['All', 'Today', 'This Week', 'This Weekend', 'Next Weekend'];
const PAST_ORDER_FILTERS = ['All', 'Food', 'Instamart'];

const CATEGORY_GRID = [
  { key: 'veg', emoji: '🥬', title: 'Vegetables' },
  { key: 'fruit', emoji: '🍎', title: 'Fruits' },
  { key: 'dairy', emoji: '🥛', title: 'Dairy' },
  { key: 'bakery', emoji: '🍞', title: 'Bakery' },
  { key: 'snacks', emoji: '🍫', title: 'Snacks' },
  { key: 'drinks', emoji: '🥤', title: 'Drinks' },
  { key: 'beauty', emoji: '🧴', title: 'Beauty' },
  { key: 'home', emoji: '🧼', title: 'Home Care' },
];

const FOOD_PROMOS = [
  { key: 'food-1', title: 'Up to 60% off', subtitle: 'Restaurants near you', emoji: '🍕', tone: COLORS.yellow100 },
  { key: 'food-2', title: 'Late night cravings', subtitle: 'Open now', emoji: '🌙', tone: COLORS.pink100 },
  { key: 'food-3', title: 'Dessert drop', subtitle: 'Sweet ends to the day', emoji: '🍰', tone: COLORS.purple100 },
];

const DINEOUT_PROMOS = [
  { key: 'dine-1', title: 'Flat 50% off', subtitle: 'On selected table bookings', emoji: '🎉', tone: COLORS.yellow100 },
  { key: 'dine-2', title: 'Date night picks', subtitle: 'Mood-first curation', emoji: '🥂', tone: COLORS.purple100 },
  { key: 'dine-3', title: 'Family tables', subtitle: 'Kid-friendly comfort', emoji: '🍽️', tone: COLORS.green100 },
];

const EVENT_CATEGORIES = [
  { key: 'music', emoji: '🎶', title: 'Music' },
  { key: 'workshop', emoji: '🛠️', title: 'Workshops' },
  { key: 'comedy', emoji: '😂', title: 'Comedy' },
  { key: 'kids', emoji: '🧒', title: 'Kids' },
  { key: 'wellness', emoji: '🧘', title: 'Wellness' },
  { key: 'gaming', emoji: '🎮', title: 'Gaming' },
  { key: 'art', emoji: '🎨', title: 'Art' },
  { key: 'foodie', emoji: '🍜', title: 'Foodie' },
];

const EVENT_HERO_BANNERS = [
  {
    key: 'crazy-deal',
    eyebrow: 'LIMITED TIME OFFER',
    title: 'CRAZZY DEAL',
    subtitle: 'Up to 20% off on select events!',
  },
  {
    key: 'weekender',
    eyebrow: 'WEEKEND DROP',
    title: 'PLAN SOMETHING FUN',
    subtitle: 'Fresh local picks for your group.',
  },
];

const EVENT_DATA = [
  {
    id: 'event-1',
    title: 'Rage Room at Break N Chill',
    venue: 'Break N Chill · Chittethukara',
    price: 299,
    date: '20 MAR',
    bucket: 'Today',
    category: 'gaming',
    accent: '#2a0b10',
    badge: 'Stress buster',
    emoji: '💥',
  },
  {
    id: 'event-2',
    title: 'Pottery Wheel Throwing Workshop',
    venue: 'Soil to Soul Ceramics · Kadavanthra',
    price: 1000,
    date: '20 MAR',
    bucket: 'This Week',
    category: 'workshop',
    accent: '#4b3328',
    badge: 'Hands-on',
    emoji: '🏺',
  },
  {
    id: 'event-3',
    title: 'Kimchi Culture Pop-up',
    venue: 'Skei Presents · Kochi',
    price: 699,
    date: '22 MAR',
    bucket: 'This Weekend',
    category: 'foodie',
    accent: '#5a1017',
    badge: 'Limited seats',
    emoji: '🍜',
  },
  {
    id: 'event-4',
    title: 'Stand-up Comedy Night',
    venue: 'Laugh Club · Kakkanad',
    price: 499,
    date: '23 MAR',
    bucket: 'This Weekend',
    category: 'comedy',
    accent: '#14213d',
    badge: 'Top rated',
    emoji: '🎤',
  },
  {
    id: 'event-5',
    title: 'Kids Creative Lab',
    venue: 'Mini Makers · Panampilly',
    price: 399,
    date: '29 MAR',
    bucket: 'Next Weekend',
    category: 'kids',
    accent: '#3c1d64',
    badge: 'Family pick',
    emoji: '🎨',
  },
];

const ACCOUNT_SHORTCUTS = [
  { key: 'address', icon: 'location-outline', label: 'Saved Address' },
  { key: 'payment', icon: 'card-outline', label: 'Payment Modes' },
  { key: 'refunds', icon: 'reload-outline', label: 'Refunds' },
  { key: 'wallet', icon: 'wallet-outline', label: 'Wallet' },
];

const ACCOUNT_ROWS = [
  { icon: 'ticket-outline', label: 'My Vouchers' },
  { icon: 'receipt-outline', label: 'Statements' },
  { icon: 'bookmark-outline', label: 'Wishlists' },
  { icon: 'heart-outline', label: 'Favourites' },
  { icon: 'help-circle-outline', label: 'Support' },
  { icon: 'shield-checkmark-outline', label: 'Privacy & consent' },
];

const STORE_TONES = ['#d1fae5', '#fef3c7', '#dbeafe', '#fce7f3', '#ede9fe', '#dcfce7'];

const FALLBACK_HOME_DEALS = [
  { key: 'deal-1', name: 'Amul Curd', price: 35, brand: 'Daily essential', emoji: '🥛' },
  { key: 'deal-2', name: 'Cadbury Dairy Milk', price: 20, brand: 'Quick sweet bite', emoji: '🍫' },
  { key: 'deal-3', name: 'Kissan Jam', price: 49, brand: 'Breakfast saver', emoji: '🍓' },
  { key: 'deal-4', name: 'Classic Chips', price: 20, brand: 'Impulse add-on', emoji: '🥔' },
];

const MOCK_PAST_ORDERS = [
  {
    id: 'mock-food-1',
    service: 'food',
    vendorName: 'Bakeryt',
    location: 'Manali Rd',
    items: [{ name: 'Chocolate Truffle', qty: 1 }],
    orderedAt: 'Feb 11, 5:18 PM',
    total: 511,
    status: 'Delivered',
  },
  {
    id: 'mock-food-2',
    service: 'food',
    vendorName: 'Sweet Truth - Cake & Desserts',
    location: 'Manali Rd',
    items: [{ name: 'Red Velvet Jar', qty: 2 }],
    orderedAt: 'Feb 09, 7:10 PM',
    total: 298,
    status: 'Delivered',
  },
  {
    id: 'mock-insta-1',
    service: 'instamart',
    vendorName: 'Instamart Daily',
    location: 'Great Orchard',
    items: [{ name: 'Curd', qty: 1 }, { name: 'Cadbury Dairy Milk', qty: 1 }],
    orderedAt: 'Mar 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
];

const SERVICE_THEMES = {
  instamart: {
    hero: COLORS.green800,
    heroAccent: COLORS.green700,
    heroPill: 'rgba(255,255,255,0.14)',
    headline: '23 mins',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for milk, chips, fruits...',
    bodyDark: false,
  },
  food: {
    hero: '#6d28d9',
    heroAccent: '#7c3aed',
    heroPill: 'rgba(255,255,255,0.14)',
    headline: 'Valliachans Place',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for biryani, pizza, desserts...',
    bodyDark: false,
  },
  dineout: {
    hero: '#5b21b6',
    heroAccent: '#6d28d9',
    heroPill: 'rgba(255,255,255,0.14)',
    headline: 'Valliachans Place',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for cuisines, cafes, offers...',
    bodyDark: false,
  },
  events: {
    hero: COLORS.dark950,
    heroAccent: COLORS.dark700,
    heroPill: 'rgba(255,255,255,0.10)',
    headline: '12b, Great Orchard',
    address: 'Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search events, workshops, comedy...',
    bodyDark: true,
  },
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
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

function getStoreTone(seed = 0) {
  return STORE_TONES[seed % STORE_TONES.length];
}

function estimateEta(vendor) {
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
    return '30-45 mins';
  }
  return '23 mins';
}

function getDeliveryFeeAmount(vendor) {
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 0;
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return 19;
  return 29;
}

function getDeliveryFeeLabel(vendor) {
  const amount = getDeliveryFeeAmount(vendor);
  return amount === 0 ? 'Free delivery' : `${money(amount)} delivery`;
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getStoreOfferLabel(vendor) {
  const offers = ['40% OFF', '60% OFF', 'ITEMS AT ₹79', 'FLAT 25% OFF'];
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return offers[seed % offers.length];
}

function pickEmoji(name = '') {
  const value = String(name || '').toLowerCase();
  if (/(curd|milk|paneer|dairy|yogurt)/.test(value)) return '🥛';
  if (/(chip|snack|cracker)/.test(value)) return '🥔';
  if (/(jam|berry|fruit)/.test(value)) return '🍓';
  if (/(chocolate|candy|bar)/.test(value)) return '🍫';
  if (/(bread|toast|bun|bakery)/.test(value)) return '🍞';
  if (/(drink|juice|cola|water)/.test(value)) return '🥤';
  if (/(vegetable|tomato|onion|potato)/.test(value)) return '🥬';
  if (/(rice|dal|flour|atta)/.test(value)) return '🍚';
  if (/(beauty|cream|soap|shampoo|sunscreen)/.test(value)) return '🧴';
  if (/(pizza|burger|biryani|sandwich|meal)/.test(value)) return '🍔';
  return '🛒';
}

function getOfferLabel(product) {
  const price = Number(product?.price || 0);
  if (price <= 25) return 'Low price';
  if (price <= 60) return 'Value buy';
  return 'Popular';
}

function formatOrderTime(date = new Date()) {
  try {
    return date.toLocaleString('en-IN', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  } catch {
    return 'Just now';
  }
}

function createKeywordMap(vendors = []) {
  return {
    fresh: vendors.filter((vendor) => /(fruit|vegetable|fresh|dairy|farm|grocery|greens)/i.test(`${vendor.name} ${vendor.description}`)),
    maxx: vendors.filter((vendor) => /(save|mart|basket|daily|essentials|value)/i.test(`${vendor.name} ${vendor.description}`)),
    late: vendors.filter((vendor) => /(open|quick|snack|dessert|night)/i.test(`${vendor.name} ${vendor.description}`)),
    quick: vendors.filter((vendor) => /(ready|instant|coffee|tea|bakery|juice)/i.test(`${vendor.name} ${vendor.description}`)),
  };
}

function buildVendorQuery(search, filter) {
  const params = new URLSearchParams();
  if (String(search || '').trim()) params.set('q', String(search).trim());
  if (filter === 'Open now') params.set('open_only', 'true');
  params.set('limit', '50');
  return `/vendors?${params.toString()}`;
}

function sortVendors(list, filter) {
  const cloned = [...list];
  switch (filter) {
    case 'Closest':
      return cloned.sort((a, b) => (a.distance_km ?? Number.MAX_SAFE_INTEGER) - (b.distance_km ?? Number.MAX_SAFE_INTEGER));
    case 'A-Z':
      return cloned.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    default:
      return cloned;
  }
}

function findVendorById(list, id) {
  return list.find((item) => String(item.id) === String(id)) || null;
}

function getMetaSdk() {
  try {
    return require('react-native-fbsdk-next');
  } catch {
    return null;
  }
}

async function initializeMetaSdk() {
  const sdk = getMetaSdk();
  if (!sdk?.Settings) return false;

  try {
    sdk.Settings.initializeSDK?.();
    sdk.Settings.setAutoLogAppEventsEnabled?.(true);
    sdk.Settings.setAdvertiserIDCollectionEnabled?.(true);

    if (Platform.OS === 'ios') {
      const permission = await TrackingTransparency.requestTrackingPermissionsAsync();
      sdk.Settings.setAdvertiserTrackingEnabled?.(permission.status === 'granted');
    }

    return true;
  } catch {
    return false;
  }
}

function logMetaEvent(name, params = {}) {
  const sdk = getMetaSdk();
  try {
    sdk?.AppEventsLogger?.logEvent?.(name, undefined, params);
  } catch {
    // no-op in Expo Go or before native build is ready
  }
}

async function apiRequest(path) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: { Accept: 'application/json' },
  });

  const raw = await response.text();
  let data = null;

  try {
    data = raw ? JSON.parse(raw) : null;
  } catch {
    data = raw;
  }

  if (!response.ok) {
    const message =
      (data && typeof data === 'object' && (data.detail || data?.error?.message)) ||
      (typeof data === 'string' && data) ||
      'Request failed';
    throw new Error(message);
  }

  return data;
}

const GrabBasketContext = createContext(null);

export function useGrabBasket() {
  const value = useContext(GrabBasketContext);
  if (!value) throw new Error('useGrabBasket must be used inside GrabBasketProvider');
  return value;
}

export function GrabBasketProvider({ children }) {
  const [activeService, setActiveService] = useState('instamart');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
  const [eventFilter, setEventFilter] = useState('All');
  const [pastOrderFilter, setPastOrderFilter] = useState('All');

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
  const [metaReady, setMetaReady] = useState(false);

  const theme = SERVICE_THEMES[activeService];

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

        const savedCart = values[0]?.[1];
        const savedFavorites = values[1]?.[1];
        const savedRecentStores = values[2]?.[1];
        const savedRecentSearches = values[3]?.[1];
        const savedOrders = values[4]?.[1];

        if (savedCart) setCart(JSON.parse(savedCart));
        if (savedFavorites) setFavorites(JSON.parse(savedFavorites));
        if (savedRecentStores) setRecentStoreIds(JSON.parse(savedRecentStores));
        if (savedRecentSearches) setRecentSearches(JSON.parse(savedRecentSearches));
        if (savedOrders) setOrderHistory(JSON.parse(savedOrders));
      } catch {
        // ignore bad local state
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

  useEffect(() => {
    initializeMetaSdk().then((ready) => setMetaReady(ready));
  }, []);

  useEffect(() => {
    logMetaEvent('grabbasket_service_view', { service: activeService });
  }, [activeService]);

  const rememberSearch = useCallback((term) => {
    const value = String(term || '').trim();
    if (!value) return;

    setRecentSearches((current) => [value, ...current.filter((item) => normalizeText(item) !== normalizeText(value))].slice(0, 8));
  }, []);

  const loadVendors = useCallback(
    async ({ pullToRefresh = false } = {}) => {
      try {
        if (pullToRefresh) setRefreshing(true);
        else setVendorsLoading(true);

        const data = await apiRequest(buildVendorQuery(homeSearch, storeFilter));
        const parsed = Array.isArray(data) ? data : [];
        setVendors(sortVendors(parsed, storeFilter));
      } catch (error) {
        setVendors([]);
        Alert.alert('Could not load stores', error.message);
      } finally {
        setVendorsLoading(false);
        setRefreshing(false);
      }
    },
    [homeSearch, storeFilter]
  );

  useEffect(() => {
    const timer = setTimeout(() => loadVendors(), 250);
    return () => clearTimeout(timer);
  }, [loadVendors]);

  const loadHomeDeals = useCallback(async (vendorList) => {
    const topVendors = vendorList.slice(0, 4);
    if (topVendors.length === 0) {
      setHomeDeals([]);
      return;
    }

    try {
      setHomeDealsLoading(true);
      const groups = await Promise.all(
        topVendors.map(async (vendor) => {
          try {
            const data = await apiRequest(`/vendors/${vendor.id}/products?limit=10`);
            return { vendor, products: Array.isArray(data) ? data : [] };
          } catch {
            return { vendor, products: [] };
          }
        })
      );

      const curated = groups
        .flatMap(({ vendor, products }) =>
          products
            .filter((item) => item.is_available !== false)
            .map((item) => ({
              ...item,
              key: `${vendor.id}-${item.id}`,
              vendorName: vendor.name,
              vendorDistance: vendor.distance_km,
              emoji: pickEmoji(item.name),
            }))
        )
        .sort((a, b) => Number(a.price || 0) - Number(b.price || 0))
        .slice(0, 4);

      setHomeDeals(curated);
    } finally {
      setHomeDealsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (vendors.length > 0) loadHomeDeals(vendors);
    else setHomeDeals([]);
  }, [vendors, loadHomeDeals]);

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      const params = new URLSearchParams();
      if (String(searchValue || '').trim()) params.set('q', String(searchValue).trim());
      params.set('limit', '200');
      const query = params.toString();
      const data = await apiRequest(`/vendors/${vendor.id}/products${query ? `?${query}` : ''}`);
      return Array.isArray(data) ? data : [];
    } catch (error) {
      Alert.alert('Could not load products', error.message);
      return [];
    }
  }, []);

  const cartItems = useMemo(() => Object.values(cart.items), [cart]);
  const cartCount = useMemo(() => cartItems.reduce((sum, item) => sum + Number(item.qty || 0), 0), [cartItems]);
  const cartSubtotal = useMemo(() => cartItems.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 0), 0), [cartItems]);
  const cartVendor = useMemo(() => (cart.vendorId ? findVendorById(vendors, cart.vendorId) : null), [vendors, cart.vendorId]);

  const deliveryFeeAmount = cartCount > 0 ? getDeliveryFeeAmount(cartVendor) : 0;
  const platformFeeAmount = cartCount > 0 ? PLATFORM_FEE : 0;
  const cartTotal = cartSubtotal + deliveryFeeAmount + platformFeeAmount;
  const freeDeliveryRemaining = Math.max(0, FREE_DELIVERY_THRESHOLD - cartSubtotal);
  const freeDeliveryProgress = Math.min(1, cartSubtotal / FREE_DELIVERY_THRESHOLD);

  const keywordMap = useMemo(() => createKeywordMap(vendors), [vendors]);
  const shortcutFilteredVendors = useMemo(() => {
    if (activeShortcut === 'all') return vendors;
    const filtered = keywordMap[activeShortcut] || [];
    return filtered.length > 0 ? filtered : vendors;
  }, [activeShortcut, keywordMap, vendors]);

  const featuredVendors = useMemo(() => shortcutFilteredVendors.slice(0, 6), [shortcutFilteredVendors]);
  const recentVendors = useMemo(() => recentStoreIds.map((id) => findVendorById(vendors, id)).filter(Boolean), [recentStoreIds, vendors]);
  const favoriteVendors = useMemo(() => vendors.filter((vendor) => favorites[vendor.id]), [vendors, favorites]);

  const suggestionPool = useMemo(
    () =>
      dedupeStrings([
        ...recentSearches,
        ...(homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS).map((item) => item.name),
        ...vendors.map((item) => item.name),
        ...CATEGORY_GRID.map((item) => item.title),
        ...EVENT_CATEGORIES.map((item) => item.title),
      ]).slice(0, 10),
    [recentSearches, homeDeals, vendors]
  );

  const filteredEvents = useMemo(() => {
    const byTime = eventFilter === 'All' ? EVENT_DATA : EVENT_DATA.filter((event) => event.bucket === eventFilter);
    const term = normalizeText(homeSearch);
    if (!term || activeService !== 'events') return byTime;
    return byTime.filter((event) => normalizeText(`${event.title} ${event.venue} ${event.category}`).includes(term));
  }, [eventFilter, homeSearch, activeService]);

  const pastOrders = useMemo(() => {
    const items = orderHistory.length > 0 ? orderHistory : MOCK_PAST_ORDERS;
    if (pastOrderFilter === 'All') return items;
    return items.filter((item) => item.service === pastOrderFilter.toLowerCase());
  }, [orderHistory, pastOrderFilter]);

  const toggleFavorite = useCallback((vendorId) => {
    setFavorites((current) => ({ ...current, [vendorId]: !current[vendorId] }));
  }, []);

  const rememberStore = useCallback((vendorId) => {
    setRecentStoreIds((current) => [vendorId, ...current.filter((id) => id !== vendorId)].slice(0, 8));
  }, []);

  const replaceCartWith = useCallback((product) => {
    setCart({
      vendorId: product.vendor_id,
      items: {
        [product.id]: {
          ...product,
          qty: 1,
        },
      },
    });
  }, []);

  const addToCart = useCallback(
    (product) => {
      if (cart.vendorId && String(cart.vendorId) !== String(product.vendor_id)) {
        Alert.alert('Replace cart?', 'Only one store can stay active in the basket. Replace the current basket with this item?', [
          { text: 'Cancel', style: 'cancel' },
          { text: 'Replace', style: 'destructive', onPress: () => replaceCartWith(product) },
        ]);
        return;
      }

      setCart((current) => {
        const existing = current.items[product.id];
        return {
          vendorId: product.vendor_id,
          items: {
            ...current.items,
            [product.id]: {
              ...(existing || product),
              qty: existing ? existing.qty + 1 : 1,
            },
          },
        };
      });

      logMetaEvent('grabbasket_add_to_cart', {
        product_name: product.name,
        vendor_id: String(product.vendor_id || ''),
        price: Number(product.price || 0),
      });
    },
    [cart.vendorId, replaceCartWith]
  );

  const updateQty = useCallback((product, delta) => {
    setCart((current) => {
      const existing = current.items[product.id];
      if (!existing) return current;

      const nextQty = existing.qty + delta;
      const nextItems = { ...current.items };

      if (nextQty <= 0) delete nextItems[product.id];
      else nextItems[product.id] = { ...existing, qty: nextQty };

      const hasItems = Object.keys(nextItems).length > 0;
      return { vendorId: hasItems ? current.vendorId : null, items: nextItems };
    });
  }, []);

  const clearCart = useCallback(() => setCart({ vendorId: null, items: {} }), []);

  const placeDemoOrder = useCallback(() => {
    if (cartItems.length === 0) {
      Alert.alert('Cart is empty', 'Add some products first.');
      return false;
    }

    const service = activeService === 'instamart' ? 'instamart' : 'food';
    const order = {
      id: `local-${Date.now()}`,
      service,
      vendorId: cartVendor?.id || null,
      vendorName: cartVendor?.name || 'Your store',
      location: cartVendor?.address || 'Saved address',
      items: cartItems.map((item) => ({ name: item.name, qty: item.qty })),
      orderedAt: formatOrderTime(new Date()),
      total: cartTotal,
      status: 'Delivered',
    };

    setOrderHistory((current) => [order, ...current].slice(0, 12));
    clearCart();
    logMetaEvent('grabbasket_order_placed', {
      service,
      item_count: cartItems.length,
      total_value: cartTotal,
    });

    Alert.alert('Demo order placed', 'Saved locally so Reorder and Account now feel much closer to a real app.');
    return true;
  }, [cartItems, activeService, cartVendor, cartTotal, clearCart]);

  const value = {
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    storeFilter,
    setStoreFilter,
    eventFilter,
    setEventFilter,
    pastOrderFilter,
    setPastOrderFilter,
    vendors,
    vendorsLoading,
    refreshing,
    loadVendors,
    homeDeals,
    homeDealsLoading,
    loadProducts,
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
    favorites,
    recentStoreIds,
    recentSearches,
    orderHistory,
    theme,
    featuredVendors,
    recentVendors,
    favoriteVendors,
    suggestionPool,
    filteredEvents,
    pastOrders,
    metaReady,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    addToCart,
    updateQty,
    clearCart,
    placeDemoOrder,
  };

  return <GrabBasketContext.Provider value={value}>{children}</GrabBasketContext.Provider>;
}

function useOpenVendor() {
  const router = useRouter();
  const { rememberStore } = useGrabBasket();

  return useCallback(
    (vendor) => {
      rememberStore(vendor.id);
      router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
    },
    [rememberStore, router]
  );
}

function SectionHeader({ title, subtitle, light = false, actionLabel, onPressAction }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text> : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function LoadingBlock({ label, light = false }) {
  return (
    <View style={styles.loadingWrap}>
      <ActivityIndicator size="large" color={light ? '#ffffff' : COLORS.green800} />
      <Text style={[styles.loadingText, light && { color: '#ffffff' }]}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, text, light = false }) {
  return (
    <View style={[styles.emptyCard, light && styles.emptyCardDark]}>
      <Text style={[styles.emptyTitle, light && styles.sectionTitleLight]}>{title}</Text>
      <Text style={[styles.emptyText, light && styles.sectionSubtitleLight]}>{text}</Text>
    </View>
  );
}

function MetaBadge({ text, dark = false }) {
  return (
    <View style={[styles.badge, dark && styles.badgeDark]}>
      <Text style={[styles.badgeText, dark && styles.badgeTextDark]}>{text}</Text>
    </View>
  );
}

function QtyStepper({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyStepper}>
      <TouchableOpacity style={styles.qtyButton} onPress={onRemove}>
        <Ionicons name="remove" size={16} color={COLORS.text} />
      </TouchableOpacity>
      <Text style={styles.qtyText}>{qty}</Text>
      <TouchableOpacity style={styles.qtyButton} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

function QuickDealCard({ item, qty = 0, onAdd, onRemove }) {
  return (
    <View style={styles.dealCard}>
      <Text style={styles.smallPill}>{getOfferLabel(item)}</Text>
      <Text style={styles.dealEmoji}>{item.emoji || pickEmoji(item.name)}</Text>
      <Text style={styles.dealTitle} numberOfLines={1}>{item.name}</Text>
      <Text style={styles.dealMeta} numberOfLines={1}>{item.vendorName || item.brand}</Text>
      <View style={styles.dealBottom}>
        <Text style={styles.priceText}>{money(item.price)}</Text>
        {qty > 0 && onAdd && onRemove ? (
          <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} />
        ) : (
          <TouchableOpacity style={styles.addTinyButton} onPress={onAdd}>
            <Text style={styles.addTinyButtonText}>{onAdd ? 'ADD' : 'VIEW'}</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function PromoTile({ item }) {
  return (
    <View style={[styles.promoTile, { backgroundColor: item.tone }]}>
      <Text style={styles.promoEmoji}>{item.emoji}</Text>
      <Text style={styles.promoTitle}>{item.title}</Text>
      <Text style={styles.promoSubtitle}>{item.subtitle}</Text>
    </View>
  );
}

function StoreCard({ vendor, favorite, onOpen, onToggleFavorite, dark = false }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={[styles.storeCard, dark && styles.storeCardDark]} onPress={onOpen}>
      <View style={[styles.storeAvatar, { backgroundColor: getStoreTone(Number(vendor?.id || 0)) }]}>
        <Text style={styles.storeAvatarText}>{initials(vendor.name)}</Text>
      </View>

      <View style={styles.storeContent}>
        <View style={styles.storeTitleRow}>
          <Text style={[styles.storeName, dark && styles.storeNameDark]} numberOfLines={1}>{vendor.name}</Text>
          <TouchableOpacity onPress={onToggleFavorite}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={dark ? '#ffffff' : COLORS.text} />
          </TouchableOpacity>
        </View>

        <Text style={[styles.storeDescription, dark && styles.storeDescriptionDark]} numberOfLines={2}>
          {vendor.description || vendor.address || 'Daily essentials and groceries'}
        </Text>

        <View style={styles.badgeRow}>
          <MetaBadge text={estimateEta(vendor)} dark={dark} />
          <MetaBadge text={getDeliveryFeeLabel(vendor)} dark={dark} />
          <MetaBadge text={`${getVendorRating(vendor)} ★`} dark={dark} />
        </View>
      </View>
    </TouchableOpacity>
  );
}

function ProductCard({ product, qty, onAdd, onRemove }) {
  return (
    <View style={styles.productCard}>
      <View style={styles.productLeft}>
        <Text style={styles.smallPill}>{getOfferLabel(product)}</Text>
        <Text style={styles.productTitle}>{product.name}</Text>
        <Text style={styles.productDesc}>{product.description || 'Store product'}</Text>
        <Text style={styles.priceText}>{money(product.price)}</Text>
      </View>

      <View style={styles.productRight}>
        <View style={styles.productEmojiWrap}>
          <Text style={styles.productEmoji}>{pickEmoji(product.name)}</Text>
        </View>
        {qty > 0 ? (
          <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} />
        ) : (
          <TouchableOpacity style={styles.addButton} onPress={onAdd}>
            <Text style={styles.addButtonText}>ADD</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function PastOrderCard({ order, compact = false }) {
  const firstItem = order.items?.[0];
  const itemLine = firstItem
    ? `${firstItem.qty || 1} x ${firstItem.name}${order.items.length > 1 ? ` +${order.items.length - 1} more` : ''}`
    : 'Order';

  return (
    <View style={[styles.orderCard, compact && { marginBottom: 12 }]}>
      <View style={styles.orderTop}>
        <View style={styles.orderThumb}>
          <Text style={styles.orderThumbText}>{initials(order.vendorName)}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.orderTitle}>{order.vendorName}</Text>
          <Text style={styles.orderMeta}>{order.location}</Text>
        </View>
        <Text style={styles.orderStatus}>{order.status}</Text>
      </View>

      <Text style={styles.orderLine}>{itemLine}</Text>
      <Text style={styles.orderMeta}>Ordered: {order.orderedAt} · Bill Total: {money(order.total)}</Text>
    </View>
  );
}

function AdEngineSlot({ slot, dark = false }) {
  const { metaReady } = useGrabBasket();
  const onceRef = useRef(false);

  useEffect(() => {
    if (onceRef.current) return;
    onceRef.current = true;
    logMetaEvent('grabbasket_ad_slot_visible', { slot });
  }, [slot]);

  const placementId = ADS_CONFIG.placements[slot];
  const headline = placementId ? 'Meta placement ready' : 'Sponsored';
  const subtitle = placementId
    ? `Slot ${slot} is wired. Replace this fallback card with your native Audience Network renderer after you add the placement.`
    : `Slot ${slot} is live in the UI. Add a Meta Audience Network placement ID to turn this into a monetized surface.`;

  return (
    <View style={[styles.adCard, dark && styles.adCardDark]}>
      <View style={styles.adHeaderRow}>
        <View>
          <Text style={[styles.adEyebrow, dark && styles.adEyebrowDark]}>{headline}</Text>
          <Text style={[styles.adTitle, dark && styles.adTitleDark]}>Ad engine slot · {slot}</Text>
        </View>
        <MetaBadge text={metaReady ? 'SDK ready' : 'SDK pending'} dark={dark} />
      </View>
      <Text style={[styles.adCopy, dark && styles.adCopyDark]}>{subtitle}</Text>
      <View style={styles.adFooterRow}>
        <Text style={[styles.adFootnote, dark && styles.adFootnoteDark]}>{META_CONFIG.adAccountId ? `Ad account: ${META_CONFIG.adAccountId}` : 'Add a Meta ad account in env'}</Text>
        <TouchableOpacity onPress={() => logMetaEvent('grabbasket_ad_slot_tap', { slot })}>
          <Text style={[styles.adAction, dark && styles.adActionDark]}>Learn more</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

function EventPosterCard({ item }) {
  return (
    <View style={[styles.eventPosterCard, { backgroundColor: item.accent }]}>
      <View style={styles.eventPosterArtwork}>
        <Text style={styles.eventPosterEmoji}>{item.emoji}</Text>
        <View style={styles.eventPosterBadgeWrap}>
          <Text style={styles.eventPosterBadge}>{item.badge}</Text>
        </View>
      </View>

      <View style={styles.eventPosterFooter}>
        <Text style={styles.eventPosterPrice}>Starts at {money(item.price)}</Text>
        <View style={styles.eventPosterMetaRow}>
          <View style={styles.eventDateBox}>
            <Text style={styles.eventDateText}>{item.date.split(' ')[0]}</Text>
            <Text style={styles.eventDateMonth}>{item.date.split(' ')[1]}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.eventPosterTitle} numberOfLines={2}>{item.title}</Text>
            <Text style={styles.eventPosterVenue} numberOfLines={2}>{item.venue}</Text>
          </View>
        </View>
      </View>
    </View>
  );
}

function HomeHeroSection() {
  const {
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    rememberSearch,
    suggestionPool,
    theme,
  } = useGrabBasket();

  const dark = activeService === 'events';
  const headlineStyle = activeService === 'instamart' ? styles.heroEta : [styles.heroTitle, dark && styles.heroTitleDark];

  return (
    <View style={[styles.hero, { backgroundColor: theme.hero }]}>
      <View style={styles.heroTop}>
        <View style={{ flex: 1 }}>
          <Text style={headlineStyle}>{theme.headline}</Text>
          <TouchableOpacity style={styles.addressRow} activeOpacity={0.9}>
            <Text style={[styles.addressText, dark && styles.addressTextDark]} numberOfLines={1}>{theme.address}</Text>
            <Ionicons name="chevron-down" size={16} color={dark ? '#ffffff' : '#d1fae5'} />
          </TouchableOpacity>
        </View>

        <View style={[styles.profileCircle, { backgroundColor: theme.heroPill }]}>
          <Ionicons name="person-outline" size={20} color="#ffffff" />
        </View>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceRow}>
        {TOP_SERVICES.map((item) => {
          const active = activeService === item.key;
          return (
            <TouchableOpacity
              key={item.key}
              style={[styles.servicePill, active && { backgroundColor: theme.heroAccent, borderColor: 'rgba(255,255,255,0.28)' }]}
              onPress={() => setActiveService(item.key)}>
              <Ionicons name={item.icon} size={18} color="#ffffff" />
              <Text style={styles.servicePillText}>{item.label}</Text>
            </TouchableOpacity>
          );
        })}
      </ScrollView>

      <View style={styles.heroSearch}>
        <Ionicons name="search-outline" size={18} color={COLORS.muted} />
        <TextInput
          style={styles.heroSearchInput}
          placeholder={theme.searchPlaceholder}
          placeholderTextColor={COLORS.subtle}
          value={homeSearch}
          onChangeText={setHomeSearch}
          onSubmitEditing={() => rememberSearch(homeSearch)}
        />
        <Ionicons name={activeService === 'food' ? 'mic-outline' : 'receipt-outline'} size={18} color={COLORS.muted} />
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.suggestionRow}>
        {suggestionPool.slice(0, 8).map((item) => (
          <TouchableOpacity key={item} style={[styles.suggestionChip, dark && styles.suggestionChipDark]} onPress={() => { setHomeSearch(item); rememberSearch(item); }}>
            <Text style={[styles.suggestionChipText, dark && styles.suggestionChipTextDark]}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {activeService === 'instamart' ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.shortcutRow}>
          {HOME_SHORTCUTS.map((item) => {
            const active = activeShortcut === item.key;
            return (
              <TouchableOpacity key={item.key} style={[styles.shortcutChip, active && styles.shortcutChipActive]} onPress={() => setActiveShortcut(item.key)}>
                <Ionicons name={item.icon} size={14} color={active ? '#ffffff' : '#d1fae5'} />
                <Text style={[styles.shortcutChipText, active && styles.shortcutChipTextActive]}>{item.label}</Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      ) : null}
    </View>
  );
}

function VendorRail({ dark = false }) {
  const { featuredVendors, favorites, toggleFavorite, vendorsLoading } = useGrabBasket();
  const openVendor = useOpenVendor();

  if (vendorsLoading) return <LoadingBlock label="Loading stores..." light={dark} />;
  if (featuredVendors.length === 0) return <EmptyState title="No stores yet" text="Your vendor feed is empty right now." light={dark} />;

  return featuredVendors.map((vendor) => (
    <StoreCard
      key={vendor.id}
      vendor={vendor}
      favorite={Boolean(favorites[vendor.id])}
      onOpen={() => openVendor(vendor)}
      onToggleFavorite={() => toggleFavorite(vendor.id)}
      dark={dark}
    />
  ));
}

function InstamartServiceSection() {
  const { homeDeals, homeDealsLoading, cart, addToCart, updateQty } = useGrabBasket();
  const deals = homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS;

  return (
    <View style={styles.pageSection}>
      <SectionHeader title="Grab in minutes" subtitle="Fast-pick cards, small decision load, and grocery-first ordering." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalList}>
        {(homeDealsLoading ? FALLBACK_HOME_DEALS : deals).map((item) => (
          <QuickDealCard
            key={item.key || item.id}
            item={item}
            qty={cart.items[item.id]?.qty || 0}
            onAdd={() => addToCart(item)}
            onRemove={() => updateQty(item, -1)}
          />
        ))}
      </ScrollView>

      <AdEngineSlot slot="home_inline" />
      <SectionHeader title="Stores near you" subtitle="The feed is still API-driven, but the layout is much closer to a shipping app." />
      <VendorRail />
    </View>
  );
}

function FoodServiceSection() {
  return (
    <View style={styles.pageSection}>
      <SectionHeader title="Food discovery" subtitle="Promo-led restaurant browsing and clearer hierarchy." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalList}>
        {FOOD_PROMOS.map((item) => <PromoTile key={item.key} item={item} />)}
      </ScrollView>

      <AdEngineSlot slot="food_inline" />
      <SectionHeader title="Restaurants near you" subtitle="Real vendors from your backend continue to power the list." />
      <VendorRail />
    </View>
  );
}

function DineoutServiceSection() {
  return (
    <View style={styles.pageSection}>
      <SectionHeader title="Dineout picks" subtitle="Booking-first mood discovery, offers and premium tables." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalList}>
        {DINEOUT_PROMOS.map((item) => <PromoTile key={item.key} item={item} />)}
      </ScrollView>

      <AdEngineSlot slot="dineout_inline" />
      <SectionHeader title="Popular places" subtitle="Reusing the store feed until you wire a dedicated dineout API." />
      <VendorRail />
    </View>
  );
}

function EventsServiceSection() {
  const { filteredEvents, eventFilter, setEventFilter } = useGrabBasket();

  return (
    <View style={styles.eventsSectionWrap}>
      <View style={styles.eventsHeroBanner}>
        <Text style={styles.eventsHeroGlow}>✦</Text>
        <Text style={styles.eventsHeroTitle}>{EVENT_HERO_BANNERS[0].title}</Text>
        <Text style={styles.eventsHeroSub}>{EVENT_HERO_BANNERS[0].subtitle}</Text>
        <Text style={styles.eventsHeroEyebrow}>{EVENT_HERO_BANNERS[0].eyebrow}</Text>
      </View>

      <AdEngineSlot slot="events_top" dark />

      <SectionHeader
        title="Featured this week"
        subtitle="Horizontal discovery matches the reference direction much more closely now."
        light
        actionLabel="View all"
      />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalList}>
        {filteredEvents.slice(0, 5).map((item) => (
          <View key={item.id} style={styles.eventPosterWrap}>
            <EventPosterCard item={item} />
          </View>
        ))}
      </ScrollView>

      <Text style={styles.eventsQuestion}>WHEN IS THE PLAN?</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRow}>
        {EVENT_FILTERS.map((item) => (
          <TouchableOpacity key={item} style={[styles.filterChipDark, eventFilter === item && styles.filterChipDarkActive]} onPress={() => setEventFilter(item)}>
            <Text style={[styles.filterChipTextDark, eventFilter === item && styles.filterChipTextDarkActive]}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {filteredEvents.length === 0 ? (
        <EmptyState title="No events" text="Try a different time bucket." light />
      ) : (
        filteredEvents.map((item) => (
          <View key={`list-${item.id}`} style={styles.eventListCard}>
            <View style={styles.eventListDateBox}>
              <Text style={styles.eventListDateTop}>{item.date.split(' ')[0]}</Text>
              <Text style={styles.eventListDateBottom}>{item.date.split(' ')[1]}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.eventListTitle}>{item.title}</Text>
              <Text style={styles.eventListVenue}>{item.venue}</Text>
              <View style={styles.badgeRow}>
                <MetaBadge text={item.bucket} dark />
                <MetaBadge text={item.badge} dark />
              </View>
            </View>
            <Text style={styles.eventListPrice}>{money(item.price)}</Text>
          </View>
        ))
      )}

      <AdEngineSlot slot="events_mid" dark />

      <SectionHeader title="Browse by vibe" subtitle="Bucket-led discovery below the fold." light />
      <View style={styles.categoryGrid}>
        {EVENT_CATEGORIES.map((item) => (
          <View key={item.key} style={styles.categoryTileDark}>
            <Text style={styles.categoryEmoji}>{item.emoji}</Text>
            <Text style={styles.categoryTitleDark}>{item.title}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

export function HomeScreen() {
  const { activeService, refreshing, loadVendors, theme, cartCount, cartTotal, cartSubtotal, freeDeliveryRemaining } = useGrabBasket();
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const isEvents = activeService === 'events';

  const deliveryStripText =
    cartSubtotal <= 0
      ? `FREE DELIVERY on orders above ${money(FREE_DELIVERY_THRESHOLD)}`
      : freeDeliveryRemaining > 0
        ? `Add ${money(freeDeliveryRemaining)} more for FREE DELIVERY`
        : 'FREE DELIVERY unlocked';

  return (
    <SafeAreaView style={[styles.safeArea, isEvents && { backgroundColor: COLORS.dark950 }]}>
      <StatusBar barStyle="light-content" backgroundColor={theme.hero} />
      <View style={styles.screen}>
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={[styles.scrollContent, { paddingBottom: tabBarHeight + 120 }, isEvents && { backgroundColor: COLORS.dark950 }]}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadVendors({ pullToRefresh: true })} tintColor={isEvents ? '#ffffff' : COLORS.green800} />}>
          <HomeHeroSection />
          {activeService === 'instamart' && <InstamartServiceSection />}
          {activeService === 'food' && <FoodServiceSection />}
          {activeService === 'dineout' && <DineoutServiceSection />}
          {activeService === 'events' && <EventsServiceSection />}
        </ScrollView>

        <View style={[styles.overlayArea, { bottom: tabBarHeight + 12 }]}>
          {cartCount > 0 ? (
            <TouchableOpacity style={styles.floatingCartBar} onPress={() => router.push('/cart')}>
              <View>
                <Text style={styles.floatingCartTitle}>View cart</Text>
                <Text style={styles.floatingCartText}>{cartCount} items · {money(cartTotal)}</Text>
              </View>
              <Ionicons name="arrow-forward" size={20} color="#ffffff" />
            </TouchableOpacity>
          ) : null}

          {activeService === 'instamart' ? (
            <View style={styles.deliveryStrip}>
              <Text style={styles.deliveryStripText}>{deliveryStripText}</Text>
            </View>
          ) : null}
        </View>
      </View>
    </SafeAreaView>
  );
}

export function ExploreScreen() {
  const { activeService } = useGrabBasket();
  const isEvents = activeService === 'events';

  const tiles = activeService === 'events'
    ? EVENT_CATEGORIES
    : activeService === 'instamart'
      ? CATEGORY_GRID
      : activeService === 'food'
        ? [
            { key: 'south', emoji: '🍛', title: 'South Indian' },
            { key: 'biryani', emoji: '🍗', title: 'Biryani' },
            { key: 'cakes', emoji: '🎂', title: 'Cakes' },
            { key: 'burgers', emoji: '🍔', title: 'Burgers' },
            { key: 'healthy', emoji: '🥗', title: 'Healthy' },
            { key: 'juice', emoji: '🧃', title: 'Juices' },
            { key: 'late-night', emoji: '🌙', title: 'Late night' },
            { key: 'breakfast', emoji: '🥞', title: 'Breakfast' },
          ]
        : [
            { key: 'family', emoji: '👨‍👩‍👧‍👦', title: 'Family dining' },
            { key: 'rooftop', emoji: '🌃', title: 'Rooftop' },
            { key: 'cafe', emoji: '☕', title: 'Cafe dates' },
            { key: 'premium', emoji: '🥂', title: 'Premium dining' },
            { key: 'brunch', emoji: '🍳', title: 'Sunday brunch' },
            { key: 'buffet', emoji: '🍽️', title: 'Buffet' },
            { key: 'cashback', emoji: '💸', title: 'Cashback' },
            { key: 'newhot', emoji: '🔥', title: 'New & hot' },
          ];

  const title = activeService === 'instamart' ? 'Categories' : activeService === 'events' ? 'Event buckets' : 'Explore';
  const subtitle = activeService === 'instamart'
    ? 'Fast aisles and cleaner category discovery.'
    : activeService === 'food'
      ? 'Cuisine-led discovery tuned for delivery journeys.'
      : activeService === 'dineout'
        ? 'Mood-led dineout discovery for bookings and offers.'
        : 'Buckets and vibes for event-led browsing.';

  return (
    <SafeAreaView style={[styles.safeArea, isEvents && { backgroundColor: COLORS.dark950 }]}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={[styles.pageContent, isEvents && { backgroundColor: COLORS.dark950 }]}>
        <SectionHeader title={title} subtitle={subtitle} light={isEvents} />
        <AdEngineSlot slot="explore_inline" dark={isEvents} />
        <View style={styles.categoryGrid}>
          {tiles.map((item, index) => (
            <View key={item.key} style={[styles.categoryTileLarge, isEvents ? styles.categoryTileDark : { backgroundColor: getStoreTone(index) }]}>
              <Text style={styles.categoryEmoji}>{item.emoji}</Text>
              <Text style={[styles.categoryTitle, isEvents && { color: '#ffffff' }]}>{item.title}</Text>
            </View>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

export function ReorderScreen() {
  const { cartCount, cartVendor, cartTotal, pastOrderFilter, setPastOrderFilter, pastOrders, recentVendors } = useGrabBasket();
  const router = useRouter();
  const openVendor = useOpenVendor();

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <SectionHeader title="Reorder" subtitle="Local history is enough to make this useful even before auth is enabled." />

        {cartCount === 0 ? (
          <EmptyState title="No active basket yet" text="Open a store and add products. Demo orders placed from cart will show up here." />
        ) : (
          <View style={styles.panelCard}>
            <Text style={styles.panelTitle}>Current basket snapshot</Text>
            <Text style={styles.panelText}>{cartVendor?.name || 'Current store'}</Text>
            <Text style={styles.panelSubText}>{cartCount} items · {money(cartTotal)}</Text>
            <TouchableOpacity style={styles.primaryButton} onPress={() => router.push('/cart')}>
              <Text style={styles.primaryButtonText}>Open cart</Text>
            </TouchableOpacity>
          </View>
        )}

        <AdEngineSlot slot="reorder_inline" />

        <View style={styles.segmentWrap}>
          {PAST_ORDER_FILTERS.map((item) => (
            <TouchableOpacity key={item} style={[styles.segmentButton, pastOrderFilter === item && styles.segmentButtonActive]} onPress={() => setPastOrderFilter(item)}>
              <Text style={[styles.segmentButtonText, pastOrderFilter === item && styles.segmentButtonTextActive]}>{item}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {pastOrders.length === 0 ? <EmptyState title="No past orders yet" text="Place one demo order from cart and this section gets stronger instantly." /> : pastOrders.map((order) => <PastOrderCard key={order.id} order={order} />)}

        {recentVendors.length > 0 ? (
          <View style={styles.panelCard}>
            <Text style={styles.panelTitle}>Recent stores</Text>
            {recentVendors.slice(0, 4).map((vendor) => (
              <TouchableOpacity key={vendor.id} style={styles.simpleListRow} onPress={() => openVendor(vendor)}>
                <View style={styles.simpleListIcon}>
                  <Text style={styles.simpleListIconText}>{initials(vendor.name)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.simpleListTitle}>{vendor.name}</Text>
                  <Text style={styles.simpleListMeta}>{estimateEta(vendor)} · {getDeliveryFeeLabel(vendor)}</Text>
                </View>
                <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
              </TouchableOpacity>
            ))}
          </View>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

export function AccountScreen() {
  const { favoriteVendors, pastOrders } = useGrabBasket();

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <View style={styles.accountHeaderCard}>
          <View>
            <Text style={styles.accountName}>Guest</Text>
            <Text style={styles.accountSubText}>+91 · 0000000000</Text>
            <Text style={styles.accountSubText}>guest@grabbasket.app</Text>
          </View>
          <TouchableOpacity style={styles.helpButton}>
            <Text style={styles.helpButtonText}>Help</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.oneCard}>
          <View>
            <Text style={styles.smallPill}>ACTIVE</Text>
            <Text style={styles.panelTitle}>₹35 saved in 36 days</Text>
            <Text style={styles.panelSubText}>Replace this with real loyalty logic later.</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
        </View>

        <AdEngineSlot slot="account_inline" />

        <View style={styles.quickGrid}>
          {ACCOUNT_SHORTCUTS.map((item) => (
            <View key={item.key} style={styles.quickCard}>
              <Ionicons name={item.icon} size={20} color={COLORS.text} />
              <Text style={styles.quickCardText}>{item.label}</Text>
            </View>
          ))}
        </View>

        <View style={styles.panelCard}>
          {ACCOUNT_ROWS.map((item) => (
            <View key={item.label} style={styles.simpleListRow}>
              <View style={styles.simpleListIcon}>
                <Ionicons name={item.icon} size={18} color={COLORS.text} />
              </View>
              <Text style={styles.simpleListTitle}>{item.label}</Text>
              <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
            </View>
          ))}
        </View>

        {favoriteVendors.length > 0 ? (
          <View style={styles.panelCard}>
            <Text style={styles.panelTitle}>Favourite stores</Text>
            {favoriteVendors.slice(0, 3).map((vendor) => (
              <View key={vendor.id} style={styles.simpleListRow}>
                <View style={styles.simpleListIcon}>
                  <Text style={styles.simpleListIconText}>{initials(vendor.name)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.simpleListTitle}>{vendor.name}</Text>
                  <Text style={styles.simpleListMeta}>{estimateEta(vendor)} · {getStoreOfferLabel(vendor)}</Text>
                </View>
              </View>
            ))}
          </View>
        ) : null}

        {pastOrders.slice(0, 3).map((order) => <PastOrderCard key={`account-${order.id}`} order={order} compact />)}
      </ScrollView>
    </SafeAreaView>
  );
}

export function VendorDetailsScreen() {
  const { vendorId } = useLocalSearchParams();
  const router = useRouter();
  const { vendors, vendorsLoading, favorites, toggleFavorite, rememberStore, rememberSearch, cart, cartCount, cartTotal, loadProducts, addToCart, updateQty } = useGrabBasket();

  const vendor = useMemo(() => findVendorById(vendors, vendorId), [vendors, vendorId]);
  const [productSearch, setProductSearch] = useState('');
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);

  useEffect(() => {
    if (vendor?.id) rememberStore(vendor.id);
  }, [vendor, rememberStore]);

  useEffect(() => {
    if (!vendor) return undefined;
    const timer = setTimeout(async () => {
      setProductsLoading(true);
      const list = await loadProducts(vendor, productSearch);
      setProducts(list);
      setProductsLoading(false);
    }, 250);
    return () => clearTimeout(timer);
  }, [vendor, productSearch, loadProducts]);

  if (vendorsLoading && !vendor) {
    return <SafeAreaView style={styles.safeArea}><LoadingBlock label="Loading store..." /></SafeAreaView>;
  }

  if (!vendor) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.pageContent}>
          <EmptyState title="Store not found" text="This vendor is missing or not loaded yet." />
          <TouchableOpacity style={styles.primaryButton} onPress={() => router.replace('/')}>
            <Text style={styles.primaryButtonText}>Back to home</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{vendor.name}</Text>
          <Text style={styles.innerHeaderSubtitle}>{estimateEta(vendor)} · {vendor?.open_now ? 'Open now' : 'Store details'}</Text>
        </View>
        <TouchableOpacity style={styles.iconButton} onPress={() => toggleFavorite(vendor.id)}>
          <Ionicons name={favorites[vendor.id] ? 'heart' : 'heart-outline'} size={18} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContentWithFloat}>
        <View style={styles.vendorHeroCard}>
          <View style={styles.vendorHeroTop}>
            <View style={styles.vendorInitialBadge}><Text style={styles.vendorInitialBadgeText}>{initials(vendor.name)}</Text></View>
            <MetaBadge text={`${getVendorRating(vendor)} ★`} />
          </View>
          <Text style={styles.vendorHeroTitle}>{vendor.name}</Text>
          <Text style={styles.vendorHeroText}>{vendor.description || vendor.address || 'Quick grocery and essentials store'}</Text>
          <View style={styles.badgeRow}>
            <MetaBadge text={vendor?.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={`ETA ${estimateEta(vendor)}`} />
            <MetaBadge text={getDeliveryFeeLabel(vendor)} />
            {vendor?.distance_km != null ? <MetaBadge text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
          </View>
        </View>

        <AdEngineSlot slot="store_inline" />

        <View style={styles.searchBoxPlain}>
          <Ionicons name="search-outline" size={20} color={COLORS.muted} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search inside store"
            placeholderTextColor={COLORS.subtle}
            value={productSearch}
            onChangeText={setProductSearch}
            onSubmitEditing={() => rememberSearch(productSearch)}
          />
        </View>

        {productsLoading ? <LoadingBlock label="Loading products..." /> : null}
        {!productsLoading && products.length === 0 ? (
          <EmptyState title="No products yet" text="Add products from the seller side and this page will start looking complete." />
        ) : (
          products.map((product) => (
            <ProductCard key={product.id} product={product} qty={cart.items[product.id]?.qty || 0} onAdd={() => addToCart(product)} onRemove={() => updateQty(product, -1)} />
          ))
        )}
      </ScrollView>

      {cartCount > 0 && String(cart.vendorId) === String(vendor.id) ? (
        <TouchableOpacity style={styles.floatingCartCta} onPress={() => router.push('/cart')}>
          <View>
            <Text style={styles.floatingCartTitle}>View cart</Text>
            <Text style={styles.floatingCartText}>{cartCount} items · {money(cartTotal)}</Text>
          </View>
          <Ionicons name="arrow-forward" size={20} color="#ffffff" />
        </TouchableOpacity>
      ) : null}
    </SafeAreaView>
  );
}

export function CartScreen() {
  const router = useRouter();
  const { cartItems, cartVendor, cartSubtotal, deliveryFeeAmount, platformFeeAmount, cartTotal, freeDeliveryRemaining, freeDeliveryProgress, addToCart, updateQty, clearCart, placeDemoOrder } = useGrabBasket();

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>Cart</Text>
          <Text style={styles.innerHeaderSubtitle}>{cartVendor?.name || 'Your basket'}</Text>
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        {cartItems.length === 0 ? (
          <EmptyState title="Your cart is empty" text="Add products from a single store and they will appear here." />
        ) : (
          <>
            <View style={styles.panelCard}>
              <Text style={styles.panelTitle}>Free delivery progress</Text>
              <Text style={styles.panelSubText}>
                {cartSubtotal <= 0
                  ? `Add items worth ${money(FREE_DELIVERY_THRESHOLD)} to unlock free delivery.`
                  : freeDeliveryRemaining > 0
                    ? `Add ${money(freeDeliveryRemaining)} more to unlock free delivery.`
                    : 'Free delivery unlocked for this basket.'}
              </Text>
              <View style={styles.progressTrack}>
                <View style={[styles.progressFill, { width: freeDeliveryProgress === 0 ? '0%' : `${Math.max(8, freeDeliveryProgress * 100)}%` }]} />
              </View>
            </View>

            <AdEngineSlot slot="cart_inline" />

            <View style={styles.panelCard}>
              {cartItems.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineMeta}>{money(item.price)} each</Text>
                  </View>
                  <QtyStepper qty={item.qty} onAdd={() => addToCart(item)} onRemove={() => updateQty(item, -1)} />
                </View>
              ))}
            </View>

            <View style={styles.panelCard}>
              <Text style={styles.panelTitle}>Bill details</Text>
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Subtotal</Text><Text style={styles.summaryValue}>{money(cartSubtotal)}</Text></View>
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Delivery fee</Text><Text style={styles.summaryValue}>{deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}</Text></View>
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Platform fee</Text><Text style={styles.summaryValue}>{money(platformFeeAmount)}</Text></View>
              <View style={styles.summaryDivider} />
              <View style={styles.summaryRow}><Text style={styles.summaryLabelStrong}>Total</Text><Text style={styles.summaryValueStrong}>{money(cartTotal)}</Text></View>
            </View>

            <TouchableOpacity style={styles.primaryButton} onPress={() => { const ok = placeDemoOrder(); if (ok) router.replace('/reorder'); }}>
              <Text style={styles.primaryButtonText}>Place demo order</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.secondaryButton} onPress={clearCart}><Text style={styles.secondaryButtonText}>Clear cart</Text></TouchableOpacity>
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

export default function LegacyAppModule() {
  return null;
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: COLORS.bg },
  screen: { flex: 1, backgroundColor: COLORS.bg },
  scrollContent: { paddingBottom: 120 },
  pageSection: { padding: 16, gap: 16 },
  pageContent: { padding: 16, paddingBottom: 120, gap: 16 },
  pageContentWithFloat: { padding: 16, paddingBottom: 120, gap: 16 },
  hero: { paddingHorizontal: 16, paddingTop: 12, paddingBottom: 20, borderBottomLeftRadius: 28, borderBottomRightRadius: 28 },
  heroTop: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  heroEta: { color: '#ffffff', fontSize: 28, fontWeight: '900' },
  heroTitle: { color: '#ffffff', fontSize: 24, fontWeight: '900' },
  heroTitleDark: { color: '#ffffff' },
  addressRow: { marginTop: 6, flexDirection: 'row', alignItems: 'center', gap: 4 },
  addressText: { color: '#d1fae5', fontSize: 14, fontWeight: '600', flex: 1 },
  addressTextDark: { color: 'rgba(255,255,255,0.82)' },
  profileCircle: { height: 42, width: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center' },
  serviceRow: { paddingTop: 18, paddingBottom: 10, gap: 12 },
  servicePill: { paddingHorizontal: 16, paddingVertical: 12, borderRadius: 18, borderWidth: 1, borderColor: 'rgba(255,255,255,0.16)', flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: 'rgba(255,255,255,0.05)' },
  servicePillText: { color: '#ffffff', fontSize: 16, fontWeight: '800' },
  heroSearch: { height: 52, borderRadius: 26, backgroundColor: '#ffffff', paddingHorizontal: 16, flexDirection: 'row', alignItems: 'center', gap: 10 },
  heroSearchInput: { flex: 1, color: COLORS.text, fontSize: 15, fontWeight: '600' },
  suggestionRow: { paddingTop: 12, gap: 8 },
  suggestionChip: { borderRadius: 999, paddingHorizontal: 12, paddingVertical: 8, backgroundColor: 'rgba(255,255,255,0.12)' },
  suggestionChipDark: { backgroundColor: 'rgba(255,255,255,0.08)' },
  suggestionChipText: { color: '#d1fae5', fontSize: 12, fontWeight: '700' },
  suggestionChipTextDark: { color: 'rgba(255,255,255,0.85)' },
  shortcutRow: { paddingTop: 14, gap: 8 },
  shortcutChip: { flexDirection: 'row', alignItems: 'center', gap: 6, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 8, backgroundColor: 'rgba(255,255,255,0.08)' },
  shortcutChipActive: { backgroundColor: 'rgba(255,255,255,0.22)' },
  shortcutChipText: { color: '#d1fae5', fontSize: 12, fontWeight: '800' },
  shortcutChipTextActive: { color: '#ffffff' },
  sectionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  sectionTitle: { color: COLORS.text, fontSize: 22, fontWeight: '900' },
  sectionTitleLight: { color: '#ffffff' },
  sectionSubtitle: { color: COLORS.muted, marginTop: 4, fontSize: 13, lineHeight: 18 },
  sectionSubtitleLight: { color: 'rgba(255,255,255,0.74)' },
  sectionAction: { color: COLORS.green800, fontSize: 13, fontWeight: '900' },
  sectionActionLight: { color: '#ffffff' },
  horizontalList: { gap: 12 },
  loadingWrap: { paddingVertical: 40, alignItems: 'center', justifyContent: 'center', gap: 12 },
  loadingText: { color: COLORS.muted, fontWeight: '700' },
  emptyCard: { borderRadius: 20, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border, padding: 18 },
  emptyCardDark: { backgroundColor: COLORS.dark800, borderColor: 'rgba(255,255,255,0.08)' },
  emptyTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  emptyText: { color: COLORS.muted, fontSize: 14, lineHeight: 20, marginTop: 6 },
  badge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6, backgroundColor: COLORS.bg },
  badgeDark: { backgroundColor: 'rgba(255,255,255,0.08)' },
  badgeText: { color: COLORS.text, fontSize: 12, fontWeight: '800' },
  badgeTextDark: { color: '#ffffff' },
  qtyStepper: { minWidth: 96, height: 36, borderRadius: 18, borderWidth: 1, borderColor: COLORS.border, backgroundColor: '#ffffff', paddingHorizontal: 6, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  qtyButton: { width: 28, height: 28, borderRadius: 14, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.bg },
  qtyText: { color: COLORS.text, fontSize: 14, fontWeight: '900' },
  dealCard: { width: 164, borderRadius: 22, padding: 16, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border },
  smallPill: { alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 999, overflow: 'hidden', backgroundColor: COLORS.green100, color: COLORS.green900, fontSize: 11, fontWeight: '900' },
  dealEmoji: { fontSize: 34, marginTop: 18 },
  dealTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900', marginTop: 14 },
  dealMeta: { color: COLORS.muted, fontSize: 13, marginTop: 6 },
  dealBottom: { marginTop: 18, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  priceText: { color: COLORS.text, fontSize: 15, fontWeight: '900' },
  addTinyButton: { minWidth: 58, height: 34, borderRadius: 17, backgroundColor: COLORS.green800, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 12 },
  addTinyButtonText: { color: '#ffffff', fontWeight: '900', fontSize: 12 },
  promoTile: { width: 220, borderRadius: 22, padding: 18 },
  promoEmoji: { fontSize: 30 },
  promoTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900', marginTop: 14 },
  promoSubtitle: { color: COLORS.muted, fontSize: 13, lineHeight: 18, marginTop: 6 },
  storeCard: { flexDirection: 'row', gap: 14, padding: 16, borderRadius: 20, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border },
  storeCardDark: { backgroundColor: COLORS.dark800, borderColor: 'rgba(255,255,255,0.08)' },
  storeAvatar: { width: 60, height: 60, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  storeAvatarText: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  storeContent: { flex: 1 },
  storeTitleRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 8 },
  storeName: { flex: 1, color: COLORS.text, fontSize: 17, fontWeight: '900' },
  storeNameDark: { color: '#ffffff' },
  storeDescription: { color: COLORS.muted, fontSize: 13, lineHeight: 18, marginTop: 6 },
  storeDescriptionDark: { color: 'rgba(255,255,255,0.72)' },
  badgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },
  productCard: { flexDirection: 'row', padding: 16, borderRadius: 20, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.card, gap: 12 },
  productLeft: { flex: 1 },
  productTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900', marginTop: 12 },
  productDesc: { color: COLORS.muted, fontSize: 13, lineHeight: 18, marginTop: 6 },
  productRight: { alignItems: 'flex-end', justifyContent: 'space-between' },
  productEmojiWrap: { width: 86, height: 86, borderRadius: 22, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.bg },
  productEmoji: { fontSize: 36 },
  addButton: { minWidth: 90, height: 38, borderRadius: 19, backgroundColor: COLORS.green800, alignItems: 'center', justifyContent: 'center', marginTop: 12 },
  addButtonText: { color: '#ffffff', fontWeight: '900', fontSize: 13 },
  adCard: { borderRadius: 22, padding: 16, backgroundColor: COLORS.orange100, borderWidth: 1, borderColor: '#fed7aa', gap: 10 },
  adCardDark: { backgroundColor: COLORS.dark800, borderColor: 'rgba(255,255,255,0.08)' },
  adHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  adEyebrow: { color: '#9a3412', fontSize: 11, fontWeight: '900' },
  adEyebrowDark: { color: '#fdba74' },
  adTitle: { color: COLORS.text, fontSize: 17, fontWeight: '900', marginTop: 4 },
  adTitleDark: { color: '#ffffff' },
  adCopy: { color: COLORS.muted, fontSize: 13, lineHeight: 18 },
  adCopyDark: { color: 'rgba(255,255,255,0.72)' },
  adFooterRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  adFootnote: { color: '#c2410c', fontSize: 11, fontWeight: '800', flex: 1 },
  adFootnoteDark: { color: '#fdba74' },
  adAction: { color: COLORS.green900, fontSize: 13, fontWeight: '900' },
  adActionDark: { color: '#ffffff' },
  eventsSectionWrap: { padding: 16, gap: 16, backgroundColor: COLORS.dark950 },
  eventsHeroBanner: { minHeight: 150, borderRadius: 24, padding: 18, backgroundColor: '#14071a', borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)', overflow: 'hidden', justifyContent: 'flex-end' },
  eventsHeroGlow: { position: 'absolute', top: 12, left: 18, color: '#f472b6', fontSize: 54, fontWeight: '900' },
  eventsHeroTitle: { color: '#fb7185', fontSize: 34, fontWeight: '900', letterSpacing: 1 },
  eventsHeroSub: { color: '#ffffff', fontSize: 22, fontWeight: '900', marginTop: 4 },
  eventsHeroEyebrow: { alignSelf: 'flex-start', marginTop: 12, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: 'rgba(255,255,255,0.08)', color: '#fbcfe8', fontSize: 11, fontWeight: '900' },
  eventPosterWrap: { width: 240 },
  eventPosterCard: { borderRadius: 22, overflow: 'hidden', borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  eventPosterArtwork: { height: 188, padding: 16, justifyContent: 'space-between' },
  eventPosterEmoji: { fontSize: 64, textAlign: 'right' },
  eventPosterBadgeWrap: { alignSelf: 'flex-start', backgroundColor: 'rgba(255,255,255,0.12)', borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6 },
  eventPosterBadge: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  eventPosterFooter: { backgroundColor: '#0f172a', padding: 14 },
  eventPosterPrice: { color: '#f472b6', fontSize: 16, fontWeight: '900' },
  eventPosterMetaRow: { flexDirection: 'row', gap: 12, marginTop: 12 },
  eventDateBox: { width: 50, height: 58, borderRadius: 14, backgroundColor: '#111827', alignItems: 'center', justifyContent: 'center' },
  eventDateText: { color: '#ffffff', fontSize: 20, fontWeight: '900' },
  eventDateMonth: { color: 'rgba(255,255,255,0.72)', fontSize: 12, fontWeight: '800' },
  eventPosterTitle: { color: '#ffffff', fontSize: 17, fontWeight: '900' },
  eventPosterVenue: { color: 'rgba(255,255,255,0.72)', fontSize: 13, lineHeight: 18, marginTop: 4 },
  eventsQuestion: { color: '#ffffff', fontSize: 26, fontWeight: '900', letterSpacing: 1.2, marginTop: 8 },
  filterRow: { gap: 10 },
  filterChipDark: { paddingHorizontal: 14, paddingVertical: 10, borderRadius: 999, backgroundColor: COLORS.dark800, borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  filterChipDarkActive: { backgroundColor: '#ffffff' },
  filterChipTextDark: { color: '#ffffff', fontSize: 12, fontWeight: '900' },
  filterChipTextDarkActive: { color: COLORS.dark950 },
  eventListCard: { flexDirection: 'row', gap: 14, alignItems: 'flex-start', padding: 16, borderRadius: 20, backgroundColor: COLORS.dark800, borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  eventListDateBox: { width: 58, height: 66, borderRadius: 16, backgroundColor: '#111827', alignItems: 'center', justifyContent: 'center' },
  eventListDateTop: { color: '#ffffff', fontSize: 22, fontWeight: '900' },
  eventListDateBottom: { color: 'rgba(255,255,255,0.72)', fontSize: 12, fontWeight: '900' },
  eventListTitle: { color: '#ffffff', fontSize: 17, fontWeight: '900' },
  eventListVenue: { color: 'rgba(255,255,255,0.72)', fontSize: 13, lineHeight: 18, marginTop: 4 },
  eventListPrice: { color: '#ffffff', fontWeight: '900', fontSize: 14 },
  categoryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  categoryTileLarge: { width: '47%', minHeight: 120, borderRadius: 22, padding: 16, justifyContent: 'space-between' },
  categoryTileDark: { width: '47%', minHeight: 120, borderRadius: 22, padding: 16, justifyContent: 'space-between', backgroundColor: COLORS.dark800, borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  categoryEmoji: { fontSize: 28 },
  categoryTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  categoryTitleDark: { color: '#ffffff', fontSize: 16, fontWeight: '900' },
  overlayArea: { position: 'absolute', left: 16, right: 16, gap: 10 },
  floatingCartBar: { borderRadius: 22, paddingHorizontal: 18, paddingVertical: 16, backgroundColor: COLORS.green800, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  floatingCartTitle: { color: '#ffffff', fontSize: 15, fontWeight: '900' },
  floatingCartText: { color: '#d1fae5', fontSize: 13, fontWeight: '700', marginTop: 2 },
  deliveryStrip: { borderRadius: 16, paddingHorizontal: 14, paddingVertical: 12, backgroundColor: '#052e23' },
  deliveryStripText: { color: '#d1fae5', fontSize: 12, fontWeight: '900', textAlign: 'center' },
  panelCard: { borderRadius: 22, padding: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.card, gap: 12 },
  panelTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  panelText: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  panelSubText: { color: COLORS.muted, fontSize: 13, lineHeight: 18 },
  segmentWrap: { flexDirection: 'row', gap: 10 },
  segmentButton: { flex: 1, borderRadius: 999, paddingVertical: 12, backgroundColor: '#ffffff', borderWidth: 1, borderColor: COLORS.border, alignItems: 'center' },
  segmentButtonActive: { backgroundColor: COLORS.green800, borderColor: COLORS.green800 },
  segmentButtonText: { color: COLORS.text, fontSize: 13, fontWeight: '900' },
  segmentButtonTextActive: { color: '#ffffff' },
  orderCard: { borderRadius: 22, padding: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.card },
  orderTop: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  orderThumb: { width: 46, height: 46, borderRadius: 14, backgroundColor: COLORS.green100, alignItems: 'center', justifyContent: 'center' },
  orderThumbText: { color: COLORS.green900, fontWeight: '900' },
  orderTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  orderMeta: { color: COLORS.muted, fontSize: 13, marginTop: 4 },
  orderStatus: { color: COLORS.green900, fontSize: 12, fontWeight: '900' },
  orderLine: { color: COLORS.text, fontSize: 14, fontWeight: '700', marginTop: 14 },
  simpleListRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10 },
  simpleListIcon: { width: 40, height: 40, borderRadius: 14, backgroundColor: COLORS.bg, alignItems: 'center', justifyContent: 'center' },
  simpleListIconText: { color: COLORS.text, fontWeight: '900' },
  simpleListTitle: { color: COLORS.text, fontSize: 15, fontWeight: '800', flex: 1 },
  simpleListMeta: { color: COLORS.muted, fontSize: 12, marginTop: 2 },
  accountHeaderCard: { borderRadius: 24, padding: 18, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border, flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  accountName: { color: COLORS.text, fontSize: 22, fontWeight: '900' },
  accountSubText: { color: COLORS.muted, fontSize: 13, marginTop: 4 },
  helpButton: { alignSelf: 'flex-start', borderRadius: 999, paddingHorizontal: 14, paddingVertical: 10, backgroundColor: COLORS.green100 },
  helpButtonText: { color: COLORS.green900, fontSize: 12, fontWeight: '900' },
  oneCard: { borderRadius: 22, padding: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.card, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 12 },
  quickGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  quickCard: { width: '47%', borderRadius: 20, padding: 16, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border, gap: 12 },
  quickCardText: { color: COLORS.text, fontSize: 14, fontWeight: '800' },
  innerHeader: { paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: COLORS.border, backgroundColor: COLORS.bg, flexDirection: 'row', alignItems: 'center', gap: 12 },
  iconButton: { width: 40, height: 40, borderRadius: 14, alignItems: 'center', justifyContent: 'center', backgroundColor: '#ffffff', borderWidth: 1, borderColor: COLORS.border },
  innerHeaderTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  innerHeaderSubtitle: { color: COLORS.muted, fontSize: 12, marginTop: 4 },
  vendorHeroCard: { borderRadius: 24, padding: 18, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border },
  vendorHeroTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  vendorInitialBadge: { width: 64, height: 64, borderRadius: 18, backgroundColor: COLORS.green100, alignItems: 'center', justifyContent: 'center' },
  vendorInitialBadgeText: { color: COLORS.green900, fontSize: 22, fontWeight: '900' },
  vendorHeroTitle: { color: COLORS.text, fontSize: 22, fontWeight: '900', marginTop: 14 },
  vendorHeroText: { color: COLORS.muted, fontSize: 14, lineHeight: 20, marginTop: 6 },
  searchBoxPlain: { height: 52, borderRadius: 20, backgroundColor: '#ffffff', borderWidth: 1, borderColor: COLORS.border, paddingHorizontal: 16, flexDirection: 'row', alignItems: 'center', gap: 10 },
  searchInput: { flex: 1, color: COLORS.text, fontSize: 15, fontWeight: '600' },
  floatingCartCta: { position: 'absolute', left: 16, right: 16, bottom: 16, borderRadius: 22, paddingHorizontal: 18, paddingVertical: 16, backgroundColor: COLORS.green800, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  progressTrack: { height: 10, borderRadius: 999, overflow: 'hidden', backgroundColor: COLORS.bg, marginTop: 6 },
  progressFill: { height: 10, borderRadius: 999, backgroundColor: COLORS.green800 },
  cartLine: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10 },
  cartLineTitle: { color: COLORS.text, fontSize: 15, fontWeight: '800' },
  cartLineMeta: { color: COLORS.muted, fontSize: 12, marginTop: 4 },
  summaryRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  summaryLabel: { color: COLORS.muted, fontSize: 14 },
  summaryValue: { color: COLORS.text, fontSize: 14, fontWeight: '800' },
  summaryDivider: { height: 1, backgroundColor: COLORS.border },
  summaryLabelStrong: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  summaryValueStrong: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  primaryButton: { height: 50, borderRadius: 16, backgroundColor: COLORS.green800, alignItems: 'center', justifyContent: 'center' },
  primaryButtonText: { color: '#ffffff', fontSize: 15, fontWeight: '900' },
  secondaryButton: { height: 50, borderRadius: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: '#ffffff', alignItems: 'center', justifyContent: 'center' },
  secondaryButtonText: { color: COLORS.text, fontSize: 15, fontWeight: '900' },
});