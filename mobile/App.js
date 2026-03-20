import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
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
import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { SafeAreaView } from 'react-native-safe-area-context';
import { API_BASE_URL } from './src/config';

const STORAGE_CART = '@grab_basket/cart_v8';
const STORAGE_FAVORITES = '@grab_basket/favorites_v5';
const STORAGE_RECENT_STORES = '@grab_basket/recent_stores_v6';
const STORAGE_RECENT_SEARCHES = '@grab_basket/recent_searches_v5';
const STORAGE_ORDER_HISTORY = '@grab_basket/order_history_v3';

const FREE_DELIVERY_THRESHOLD = 199;
const PLATFORM_FEE = 6;

const COLORS = {
  bg: '#f5f6f8',
  card: '#ffffff',
  text: '#111827',
  muted: '#6b7280',
  subtle: '#9ca3af',
  border: '#e5e7eb',
  green: '#0f9d58',
  greenDark: '#07693b',
  purple: '#6d28d9',
  purpleDark: '#4f1bb0',
  purpleSoft: '#8b5cf6',
  purpleText: '#efe9ff',
  blueDark: '#082a73',
  blue: '#0b3d91',
  blueSoft: '#163d9c',
  yellow: '#ffcd00',
  yellowSoft: '#ffd84d',
  orange: '#ff7a00',
  deepGreen: '#0f4d32',
  deepNavy: '#081a4b',
  black: '#0f172a',
};

const TOP_SERVICES = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline', emoji: '🍔' },
  { key: 'warehouse', label: 'Warehouse', icon: 'basket-outline', emoji: '🛒', badge: '5 mins' },
  { key: 'eatout', label: 'Eatout', icon: 'restaurant-outline', emoji: '🍽️' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline', emoji: '🪩' },
];

const FOOD_HIGHLIGHTS = [
  { key: 'binge', title: 'Binge worthy deals', badge: 'Up to 60% OFF & more', emoji: '🎉' },
  { key: 'eatright', title: 'EatRight', badge: 'Win up to ₹300 free cash', emoji: '🥗' },
  { key: 'awards', title: 'Restaurant awards', badge: 'Best rated around you', emoji: '🏅' },
];

const WAREHOUSE_FILTERS = [
  { key: 'all', label: 'All', icon: 'grid-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxxsaver', label: 'Maxxsaver', icon: 'pricetags-outline' },
  { key: 'festival', label: 'Festival', icon: 'moon-outline' },
  { key: 'ready', label: 'Quick picks', icon: 'flash-outline' },
];

const WAREHOUSE_BANNERS = [
  { key: 'iftar', title: 'Iftar snacks & drinks', emoji: '🥤' },
  { key: 'biryani', title: 'Biryani & feasting corner', emoji: '🍛' },
  { key: 'dates', title: 'Dates, dry fruits & desserts', emoji: '🌰' },
  { key: 'gifts', title: 'Gift-ready packs', emoji: '🎁' },
];

const EATOUT_HIGHLIGHTS = [
  { key: 'big', title: 'Flat 50% OFF', subtitle: 'Selected restaurants near you', emoji: '🧡' },
  { key: 'hall', title: 'GIRF Hall of Fame', subtitle: 'Top discount champions', emoji: '🏆' },
  { key: 'family', title: 'Family-friendly spots', subtitle: 'Comfort dining picks', emoji: '🍽️' },
  { key: 'cafe', title: 'Cafes & quick bites', subtitle: 'Low-commitment plans', emoji: '☕' },
  { key: 'freebie', title: 'Exciting freebies', subtitle: 'Desserts and add-ons', emoji: '🍰' },
];

const SCENE_CATEGORIES = [
  { key: 'music', title: 'Music', emoji: '🎶' },
  { key: 'comedy', title: 'Comedy', emoji: '😂' },
  { key: 'kids', title: 'Kids', emoji: '🧒' },
  { key: 'gaming', title: 'Gaming', emoji: '🎮' },
  { key: 'wellness', title: 'Wellness', emoji: '🧘' },
  { key: 'art', title: 'Art', emoji: '🎨' },
  { key: 'food', title: 'Food pop-ups', emoji: '🍜' },
  { key: 'workshop', title: 'Workshops', emoji: '🛠️' },
];

const FOOD_CATEGORIES = [
  { key: 'south', emoji: '🍛', title: 'South Indian' },
  { key: 'biryani', emoji: '🍗', title: 'Biryani' },
  { key: 'cakes', emoji: '🎂', title: 'Cakes' },
  { key: 'burgers', emoji: '🍔', title: 'Burgers' },
  { key: 'healthy', emoji: '🥗', title: 'Healthy' },
  { key: 'juice', emoji: '🧃', title: 'Juices' },
  { key: 'late-night', emoji: '🌙', title: 'Late night' },
  { key: 'breakfast', emoji: '🥞', title: 'Breakfast' },
];

const WAREHOUSE_CATEGORIES = [
  { key: 'veg', emoji: '🥬', title: 'Vegetables' },
  { key: 'fruit', emoji: '🍎', title: 'Fruits' },
  { key: 'dairy', emoji: '🥛', title: 'Dairy' },
  { key: 'bakery', emoji: '🍞', title: 'Bakery' },
  { key: 'snacks', emoji: '🍫', title: 'Snacks' },
  { key: 'drinks', emoji: '🥤', title: 'Drinks' },
  { key: 'beauty', emoji: '🧴', title: 'Beauty' },
  { key: 'home', emoji: '🧼', title: 'Home care' },
];

const EATOUT_CATEGORIES = [
  { key: 'family', emoji: '👨‍👩‍👧‍👦', title: 'Family dining' },
  { key: 'rooftop', emoji: '🌃', title: 'Rooftop' },
  { key: 'cafe', emoji: '☕', title: 'Cafe dates' },
  { key: 'premium', emoji: '🥂', title: 'Premium dining' },
  { key: 'brunch', emoji: '🍳', title: 'Sunday brunch' },
  { key: 'buffet', emoji: '🍽️', title: 'Buffet' },
  { key: 'cashback', emoji: '💸', title: 'Cashback' },
  { key: 'newhot', emoji: '🔥', title: 'New & hot' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room at Break N Chill',
    venue: 'Chittethukara · 20 MAR',
    price: 299,
    badge: 'Stress buster',
    accent: '#2a0b10',
    emoji: '💥',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Throwing Workshop',
    venue: 'Kadavanthra · 22 MAR',
    price: 1000,
    badge: 'Hands-on',
    accent: '#4b3328',
    emoji: '🏺',
  },
  {
    id: 'scene-3',
    title: 'Stand-up Comedy Night',
    venue: 'Kakkanad · 23 MAR',
    price: 499,
    badge: 'Top rated',
    accent: '#14213d',
    emoji: '🎤',
  },
  {
    id: 'scene-4',
    title: 'Kids Creative Lab',
    venue: 'Panampilly · 29 MAR',
    price: 399,
    badge: 'Family pick',
    accent: '#3c1d64',
    emoji: '🎨',
  },
];

const FALLBACK_HOME_DEALS = [
  { key: 'deal-1', id: 'deal-1', vendor_id: 'fallback', name: 'Amul Curd', price: 35, brand: 'Daily essential', emoji: '🥛' },
  { key: 'deal-2', id: 'deal-2', vendor_id: 'fallback', name: 'Cadbury Dairy Milk', price: 20, brand: 'Quick sweet bite', emoji: '🍫' },
  { key: 'deal-3', id: 'deal-3', vendor_id: 'fallback', name: 'Kissan Jam', price: 49, brand: 'Breakfast saver', emoji: '🍓' },
  { key: 'deal-4', id: 'deal-4', vendor_id: 'fallback', name: 'Classic Chips', price: 20, brand: 'Impulse add-on', emoji: '🥔' },
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
    id: 'mock-warehouse-1',
    service: 'warehouse',
    vendorName: 'Warehouse Daily',
    location: 'Great Orchard',
    items: [{ name: 'Curd', qty: 1 }, { name: 'Cadbury Dairy Milk', qty: 1 }],
    orderedAt: 'Mar 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
];

const PAST_ORDER_FILTERS = ['All', 'Food', 'Warehouse'];

const SERVICE_THEMES = {
  food: {
    hero: COLORS.purple,
    heroAccent: COLORS.purpleSoft,
    headline: 'Valliachans Place',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: "Search for 'EatRight'",
    searchAction: 'VEG',
    searchActionIcon: 'leaf-outline',
    locationHint: 'Delivering to',
  },
  warehouse: {
    hero: COLORS.blueDark,
    heroAccent: COLORS.blue,
    headline: '5 mins',
    address: 'To Valliachans Place: 12b, Great Orchard / Tower 1',
    searchPlaceholder: 'Search for Dryfruits',
    searchAction: '',
    searchActionIcon: 'bookmark-outline',
    locationHint: '',
  },
  eatout: {
    hero: COLORS.purpleDark,
    heroAccent: COLORS.purpleSoft,
    headline: 'Valliachans Place',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for cuisines',
    searchAction: '',
    searchActionIcon: 'search-outline',
    locationHint: '',
    topStrip: 'Earn flat 10% Dinecash on every bill payment',
  },
  scenes: {
    hero: '#1d1144',
    heroAccent: '#3b2a77',
    headline: 'Tonight in Kochi',
    address: 'Music, comedy, workshops and more',
    searchPlaceholder: 'Search for events or experiences',
    searchAction: '',
    searchActionIcon: 'sparkles-outline',
    locationHint: '',
  },
};

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

function mapLegacyService(value) {
  const service = normalizeText(value);
  if (service === 'instamart') return 'warehouse';
  if (service === 'dineout') return 'eatout';
  if (service === 'events') return 'scenes';
  return service || 'food';
}

function serviceLabel(value) {
  const service = mapLegacyService(value);
  if (service === 'warehouse') return 'Warehouse';
  if (service === 'eatout') return 'Eatout';
  if (service === 'scenes') return 'Scenes';
  return 'Food';
}

function estimateEta(vendor, service = 'food') {
  if (service === 'eatout') return 'Table in 10-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
    return '30-45 mins';
  }
  return service === 'warehouse' ? '5-15 mins' : '23 mins';
}

function getDeliveryFeeAmount(vendor) {
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 0;
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return 19;
  return 29;
}

function getDeliveryFeeLabel(vendor, service = 'food') {
  if (service === 'eatout') return 'Extra bank offers';
  const amount = getDeliveryFeeAmount(vendor);
  return amount === 0 ? 'Free delivery' : `${money(amount)} delivery`;
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getStoreOfferLabel(vendor, service = 'food') {
  const warehouseOffers = ['₹9 deal', '40% OFF', 'Daily price drop', 'Best brands'];
  const foodOffers = ['40% OFF', '60% OFF', 'Items at ₹79', 'Flat 25% OFF'];
  const eatoutOffers = ['Flat 50% OFF', 'Free dessert', 'Bank cashback', 'Extra 15% OFF'];
  const offers = service === 'warehouse' ? warehouseOffers : service === 'eatout' ? eatoutOffers : foodOffers;
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
  if (/(coffee|tea)/.test(value)) return '☕';
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
    maxxsaver: vendors.filter((vendor) => /(save|mart|basket|daily|essentials|value)/i.test(`${vendor.name} ${vendor.description}`)),
    festival: vendors.filter((vendor) => /(dates|dry|dessert|sweet|gift|biryani|festival)/i.test(`${vendor.name} ${vendor.description}`)),
    ready: vendors.filter((vendor) => /(ready|instant|coffee|tea|bakery|juice)/i.test(`${vendor.name} ${vendor.description}`)),
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
  if (filter === 'Closest') {
    return cloned.sort((a, b) => (a.distance_km ?? Number.MAX_SAFE_INTEGER) - (b.distance_km ?? Number.MAX_SAFE_INTEGER));
  }
  if (filter === 'A-Z') {
    return cloned.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
  }
  return cloned;
}

function findVendorById(list, id) {
  return list.find((item) => String(item.id) === String(id)) || null;
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
  const [activeService, setActiveService] = useState('food');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
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

  const theme = SERVICE_THEMES[activeService] || SERVICE_THEMES.food;

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
        if (savedOrders) {
          const parsed = JSON.parse(savedOrders).map((item) => ({
            ...item,
            service: mapLegacyService(item.service),
          }));
          setOrderHistory(parsed);
        }
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

  const suggestionPool = useMemo(
    () =>
      dedupeStrings([
        ...recentSearches,
        ...(homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS).map((item) => item.name),
        ...vendors.map((item) => item.name),
        ...FOOD_CATEGORIES.map((item) => item.title),
        ...WAREHOUSE_CATEGORIES.map((item) => item.title),
        ...EATOUT_CATEGORIES.map((item) => item.title),
      ]).slice(0, 12),
    [recentSearches, homeDeals, vendors]
  );

  const pastOrders = useMemo(() => {
    const items = orderHistory.length > 0 ? orderHistory : MOCK_PAST_ORDERS;
    if (pastOrderFilter === 'All') return items;
    return items.filter((item) => mapLegacyService(item.service) === pastOrderFilter.toLowerCase());
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

    const service = activeService === 'warehouse' ? 'warehouse' : activeService === 'eatout' ? 'eatout' : activeService === 'scenes' ? 'food' : 'food';
    const order = {
      id: `local-${Date.now()}`,
      service,
      vendorId: cartVendor?.id || null,
      vendorName: cartVendor?.name || 'Your store',
      location: cartVendor?.address || 'Saved address',
      items: cartItems.map((item) => ({ name: item.name, qty: item.qty })),
      orderedAt: formatOrderTime(new Date()),
      total: cartTotal,
      status: service === 'eatout' ? 'Booked' : 'Delivered',
    };

    setOrderHistory((current) => [order, ...current].slice(0, 12));
    clearCart();
    Alert.alert(
      service === 'eatout' ? 'Demo reservation saved' : 'Demo order placed',
      service === 'eatout'
        ? 'Saved locally so the Eatout flow feels closer to a booking app.'
        : 'Saved locally so Reorder now feels much closer to a real app.'
    );
    return true;
  }, [activeService, cartItems, cartVendor, cartTotal, clearCart]);

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
    suggestionPool,
    pastOrders,
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

function SectionHeader({ title, subtitle, actionLabel, onPressAction, light = false }) {
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
      <ActivityIndicator size="large" color={light ? '#ffffff' : COLORS.green} />
      <Text style={[styles.loadingText, light && { color: '#ffffff' }]}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, text, light = false }) {
  return (
    <View style={[styles.emptyCard, light && styles.emptyCardDark]}>
      <Text style={[styles.emptyTitle, light && styles.emptyTitleDark]}>{title}</Text>
      <Text style={[styles.emptyText, light && styles.emptyTextDark]}>{text}</Text>
    </View>
  );
}

function MetaPill({ text, dark = false, accent = false }) {
  return (
    <View style={[styles.metaPill, dark && styles.metaPillDark, accent && styles.metaPillAccent]}>
      <Text style={[styles.metaPillText, dark && styles.metaPillTextDark, accent && styles.metaPillTextAccent]}>{text}</Text>
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

function ServicePill({ item, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.servicePill, active && styles.servicePillActive]}
      onPress={onPress}>
      <Text style={styles.serviceEmoji}>{item.emoji}</Text>
      <View>
        {item.badge && active ? <Text style={styles.serviceBadge}>{item.badge}</Text> : null}
        <Text style={styles.servicePillText}>{item.label}</Text>
      </View>
    </TouchableOpacity>
  );
}

function HeroSearchBar({ placeholder, value, onChangeText, onSubmit, actionLabel, actionIcon }) {
  return (
    <View style={styles.heroSearchWrap}>
      <View style={styles.heroSearch}>
        <Ionicons name="search-outline" size={22} color={COLORS.muted} />
        <TextInput
          style={styles.heroSearchInput}
          placeholder={placeholder}
          placeholderTextColor={COLORS.subtle}
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
        />
        <Ionicons name={actionLabel ? 'mic-outline' : actionIcon || 'search-outline'} size={20} color={COLORS.muted} />
      </View>
      <TouchableOpacity activeOpacity={0.92} style={styles.heroActionChip}>
        {actionLabel ? <Text style={styles.heroActionChipText}>{actionLabel}</Text> : <Ionicons name={actionIcon || 'bookmark-outline'} size={20} color="#ffffff" />}
        {actionLabel ? <Ionicons name={actionIcon || 'leaf-outline'} size={18} color="#ffffff" /> : null}
      </TouchableOpacity>
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
    theme,
  } = useGrabBasket();

  const isWarehouse = activeService === 'warehouse';
  const isEatout = activeService === 'eatout';
  const isScenes = activeService === 'scenes';

  return (
    <View style={[styles.heroShell, { backgroundColor: theme.hero }]}>
      {theme.topStrip ? (
        <View style={styles.topStrip}>
          <Ionicons name="cash-outline" size={18} color="#d1fae5" />
          <Text style={styles.topStripText}>{theme.topStrip}</Text>
        </View>
      ) : null}

      <View style={styles.heroHeaderRow}>
        <View style={{ flex: 1 }}>
          <Text style={styles.heroTitle}>{theme.headline}</Text>
          {theme.locationHint ? <Text style={styles.heroTinyLabel}>{theme.locationHint}</Text> : null}
          <TouchableOpacity activeOpacity={0.9} style={styles.heroAddressRow}>
            <Text style={styles.heroAddress} numberOfLines={1}>{theme.address}</Text>
            <Ionicons name="chevron-down" size={16} color="#ffffff" />
          </TouchableOpacity>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.profileCircle}>
          <Ionicons name="person-outline" size={22} color="#ffffff" />
        </TouchableOpacity>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceRow}>
        {TOP_SERVICES.map((item) => (
          <ServicePill
            key={item.key}
            item={item}
            active={activeService === item.key}
            onPress={() => setActiveService(item.key)}
          />
        ))}
      </ScrollView>

      <HeroSearchBar
        placeholder={theme.searchPlaceholder}
        value={homeSearch}
        onChangeText={setHomeSearch}
        onSubmit={() => rememberSearch(homeSearch)}
        actionLabel={theme.searchAction}
        actionIcon={theme.searchActionIcon}
      />

      {isWarehouse ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.inlineFilterRow}>
          {WAREHOUSE_FILTERS.map((item) => {
            const active = activeShortcut === item.key;
            return (
              <TouchableOpacity
                key={item.key}
                activeOpacity={0.92}
                style={[styles.inlineFilterChip, active && styles.inlineFilterChipActive]}
                onPress={() => setActiveShortcut(item.key)}>
                <Ionicons name={item.icon} size={16} color={active ? COLORS.blueDark : '#ffffff'} />
                <Text style={[styles.inlineFilterText, active && styles.inlineFilterTextActive]}>{item.label}</Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      ) : null}

      {isScenes ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sceneChipRow}>
          {SCENE_CATEGORIES.slice(0, 6).map((item) => (
            <View key={item.key} style={styles.sceneChip}>
              <Text style={styles.sceneChipEmoji}>{item.emoji}</Text>
              <Text style={styles.sceneChipText}>{item.title}</Text>
            </View>
          ))}
        </ScrollView>
      ) : null}

      {isEatout ? <View style={styles.eatoutGlow} /> : null}
    </View>
  );
}

function RestaurantSnapshotCard({ vendor, service = 'food', favorite, onOpen, onToggleFavorite }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.snapshotCard} onPress={onOpen}>
      <View style={[styles.snapshotImage, { backgroundColor: service === 'eatout' ? '#5c3a17' : '#2b0f34' }]}>
        <View style={styles.snapshotBadgeWrap}>
          <Text style={styles.snapshotOffer}>{getStoreOfferLabel(vendor, service)}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.snapshotHeart} onPress={onToggleFavorite}>
          <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color="#ffffff" />
        </TouchableOpacity>
        <Text style={styles.snapshotAvatar}>{initials(vendor.name)}</Text>
      </View>
      <Text style={styles.snapshotTitle} numberOfLines={1}>{vendor.name}</Text>
      <Text style={styles.snapshotMeta} numberOfLines={1}>{estimateEta(vendor, service)} • {getVendorRating(vendor)} ★</Text>
    </TouchableOpacity>
  );
}

function VendorListCard({ vendor, service = 'food', favorite, onOpen, onToggleFavorite }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.vendorCard} onPress={onOpen}>
      <View style={[styles.vendorThumb, { backgroundColor: service === 'warehouse' ? '#dbeafe' : service === 'eatout' ? '#fef3c7' : '#ede9fe' }]}>
        <Text style={styles.vendorThumbText}>{initials(vendor.name)}</Text>
      </View>
      <View style={{ flex: 1 }}>
        <View style={styles.vendorTopRow}>
          <Text style={styles.vendorName} numberOfLines={1}>{vendor.name}</Text>
          <TouchableOpacity activeOpacity={0.9} onPress={onToggleFavorite}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={favorite ? '#ef4444' : COLORS.text} />
          </TouchableOpacity>
        </View>
        <Text style={styles.vendorDesc} numberOfLines={2}>{vendor.description || vendor.address || 'Curated local merchant'}</Text>
        <View style={styles.vendorMetaRow}>
          <MetaPill text={getStoreOfferLabel(vendor, service)} accent />
          <MetaPill text={estimateEta(vendor, service)} />
          <MetaPill text={getDeliveryFeeLabel(vendor, service)} />
          <MetaPill text={`${getVendorRating(vendor)} ★`} />
        </View>
      </View>
    </TouchableOpacity>
  );
}

function VendorRail({ service = 'food' }) {
  const { featuredVendors, favorites, toggleFavorite, vendorsLoading } = useGrabBasket();
  const openVendor = useOpenVendor();

  if (vendorsLoading) return <LoadingBlock label="Loading stores..." />;
  if (featuredVendors.length === 0) return <EmptyState title="No stores yet" text="Your vendor feed is empty right now." />;

  return featuredVendors.map((vendor) => (
    <VendorListCard
      key={vendor.id}
      vendor={vendor}
      service={service}
      favorite={Boolean(favorites[vendor.id])}
      onOpen={() => openVendor(vendor)}
      onToggleFavorite={() => toggleFavorite(vendor.id)}
    />
  ));
}

function FoodServiceSection() {
  const { featuredVendors, favorites, toggleFavorite } = useGrabBasket();
  const openVendor = useOpenVendor();

  return (
    <View style={styles.pageSection}>
      <View style={styles.foodHeroBanner}>
        <View style={{ flex: 1 }}>
          <Text style={styles.foodHeroBannerTitle}>CRAVE</Text>
          <Text style={styles.foodHeroBannerSub}>UP TO 60% OFF & MORE</Text>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.foodHeroBannerButton}>
          <Text style={styles.foodHeroBannerButtonText}>ORDER NOW</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.highlightRow}>
        {FOOD_HIGHLIGHTS.map((item) => (
          <View key={item.key} style={styles.highlightCard}>
            <Text style={styles.highlightEmoji}>{item.emoji}</Text>
            <Text style={styles.highlightTitle}>{item.title}</Text>
            <Text style={styles.highlightBadge}>{item.badge}</Text>
          </View>
        ))}
      </View>

      <SectionHeader title="Top rated near you" subtitle="Closer to the Swiggy discovery rhythm with visual first restaurant cards." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {featuredVendors.map((vendor) => (
          <RestaurantSnapshotCard
            key={vendor.id}
            vendor={vendor}
            service="food"
            favorite={Boolean(favorites[vendor.id])}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))}
      </ScrollView>

      <SectionHeader title="Restaurants to order from" subtitle="Backend vendors still power the list, but the layout now feels much closer to a production food feed." />
      <VendorRail service="food" />
    </View>
  );
}

function WarehouseDealCard({ item, qty = 0, onAdd, onRemove }) {
  return (
    <View style={styles.warehouseDealCard}>
      <Text style={styles.warehouseDealBadge}>₹9 everyday</Text>
      <View style={styles.warehouseDealIconWrap}>
        <Text style={styles.warehouseDealIcon}>{item.emoji || pickEmoji(item.name)}</Text>
      </View>
      <Text style={styles.warehouseDealTitle} numberOfLines={1}>{item.name}</Text>
      <Text style={styles.warehouseDealMeta} numberOfLines={1}>{item.vendorName || item.brand}</Text>
      <View style={styles.warehouseDealFooter}>
        <Text style={styles.warehouseDealPrice}>{money(item.price)}</Text>
        {qty > 0 ? (
          <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} />
        ) : (
          <TouchableOpacity activeOpacity={0.92} style={styles.selectButton} onPress={onAdd}>
            <Text style={styles.selectButtonText}>Select</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function WarehouseServiceSection() {
  const { homeDeals, homeDealsLoading, cart, addToCart, updateQty } = useGrabBasket();
  const deals = homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS;

  return (
    <View style={styles.pageSection}>
      <View style={styles.warehouseBanner}>
        <Text style={styles.warehouseBannerTitle}>Ramzan Mubarak</Text>
        <Text style={styles.warehouseBannerSub}>Festival-led merchandising, curated clusters and a faster quick-commerce feel.</Text>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {WAREHOUSE_BANNERS.map((item) => (
          <View key={item.key} style={styles.warehouseMiniBanner}>
            <Text style={styles.warehouseMiniBannerEmoji}>{item.emoji}</Text>
            <Text style={styles.warehouseMiniBannerTitle}>{item.title}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.infoStripCard}>
        <Text style={styles.infoStripText}>Explore 28 varieties of dates sourced from 12 countries.</Text>
        <Ionicons name="chevron-forward" size={18} color="#ffffff" />
      </View>

      <View style={styles.warehouseDealsPanel}>
        <View style={styles.warehouseDealsHeader}>
          <Text style={styles.warehouseDealsTitle}>₹9 everyday</Text>
          <Text style={styles.warehouseDealsSub}>Shop smart and unlock impulse-friendly add-ons.</Text>
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
          {(homeDealsLoading ? FALLBACK_HOME_DEALS : deals).map((item) => (
            <WarehouseDealCard
              key={item.key || item.id}
              item={item}
              qty={cart.items[item.id]?.qty || 0}
              onAdd={() => addToCart(item)}
              onRemove={() => updateQty(item, -1)}
            />
          ))}
        </ScrollView>
      </View>

      <SectionHeader title="Stores near you" subtitle="Warehouse now reads like quick-commerce instead of a generic product list." />
      <VendorRail service="warehouse" />
    </View>
  );
}

function EatoutServiceSection() {
  const { featuredVendors, favorites, toggleFavorite } = useGrabBasket();
  const openVendor = useOpenVendor();

  return (
    <View style={styles.pageSection}>
      <View style={styles.eatoutHeroCard}>
        <View style={{ flex: 1 }}>
          <Text style={styles.eatoutHeroTitle}>PARTY{`\n`}FULL</Text>
          <Text style={styles.eatoutHeroSub}>Discovery-first dining, sharper offer cards and clearer booking intent.</Text>
        </View>
        <View style={styles.eatoutHeroAvatarWrap}>
          <Text style={styles.eatoutHeroAvatar}>😎</Text>
        </View>
      </View>

      <View style={styles.eatoutOfferGrid}>
        <View style={styles.eatoutBigOffer}>
          <Text style={styles.eatoutBigOfferEmoji}>{EATOUT_HIGHLIGHTS[0].emoji}</Text>
          <Text style={styles.eatoutBigOfferTitle}>{EATOUT_HIGHLIGHTS[0].title}</Text>
          <Text style={styles.eatoutBigOfferSub}>{EATOUT_HIGHLIGHTS[0].subtitle}</Text>
        </View>
        <View style={styles.eatoutSmallOfferCol}>
          {EATOUT_HIGHLIGHTS.slice(1).map((item) => (
            <View key={item.key} style={styles.eatoutSmallOffer}>
              <View style={{ flex: 1 }}>
                <Text style={styles.eatoutSmallOfferTitle}>{item.title}</Text>
                <Text style={styles.eatoutSmallOfferSub}>{item.subtitle}</Text>
              </View>
              <Text style={styles.eatoutSmallOfferEmoji}>{item.emoji}</Text>
            </View>
          ))}
        </View>
      </View>

      <View style={styles.cashbackStrip}>
        <Text style={styles.cashbackStripText}>Get FLAT ₹75 cashback with Mobikwik Wallet</Text>
      </View>

      <SectionHeader title="Book a table" subtitle="Offer-led place cards now feel closer to an actual dineout marketplace." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {featuredVendors.map((vendor) => (
          <RestaurantSnapshotCard
            key={vendor.id}
            vendor={vendor}
            service="eatout"
            favorite={Boolean(favorites[vendor.id])}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))}
      </ScrollView>

      <SectionHeader title="Popular places" subtitle="Until a dedicated bookings API lands, the shared vendor feed is styled to feel like Eatout." />
      <VendorRail service="eatout" />
    </View>
  );
}

function ScenesServiceSection() {
  return (
    <View style={[styles.pageSection, { backgroundColor: '#150c33' }]}>
      <View style={styles.sceneHeroCard}>
        <Text style={styles.sceneHeroEyebrow}>Weekend drop</Text>
        <Text style={styles.sceneHeroTitle}>PLAN SOMETHING FUN</Text>
        <Text style={styles.sceneHeroSub}>Scenes is your placeholder for events, workshops and local moments.</Text>
      </View>

      <SectionHeader title="Featured this week" subtitle="Keep this lighter until you wire real scenes data." light />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {SCENE_EVENTS.map((item) => (
          <View key={item.id} style={[styles.sceneEventCard, { backgroundColor: item.accent }]}>
            <Text style={styles.sceneEventEmoji}>{item.emoji}</Text>
            <MetaPill text={item.badge} dark />
            <Text style={styles.sceneEventPrice}>Starts at {money(item.price)}</Text>
            <Text style={styles.sceneEventTitle}>{item.title}</Text>
            <Text style={styles.sceneEventVenue}>{item.venue}</Text>
          </View>
        ))}
      </ScrollView>

      <SectionHeader title="Browse by vibe" subtitle="A clean placeholder until scenes gets its own backend model." light />
      <View style={styles.categoryGrid}>
        {SCENE_CATEGORIES.map((item) => (
          <View key={item.key} style={styles.sceneCategoryTile}>
            <Text style={styles.categoryEmoji}>{item.emoji}</Text>
            <Text style={styles.sceneCategoryText}>{item.title}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

export function HomeScreen() {
  const { activeService, refreshing, loadVendors, cartCount, cartTotal, cartSubtotal, freeDeliveryRemaining } = useGrabBasket();
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const isScenes = activeService === 'scenes';

  const deliveryStripText =
    cartSubtotal <= 0
      ? `FREE DELIVERY on orders above ${money(FREE_DELIVERY_THRESHOLD)}`
      : freeDeliveryRemaining > 0
        ? `Add ${money(freeDeliveryRemaining)} more for FREE DELIVERY`
        : 'FREE DELIVERY unlocked';

  return (
    <SafeAreaView style={[styles.safeArea, isScenes && { backgroundColor: '#150c33' }]}>
      <StatusBar barStyle="light-content" backgroundColor={SERVICE_THEMES[activeService].hero} />
      <View style={[styles.screen, isScenes && { backgroundColor: '#150c33' }]}>
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={[styles.scrollContent, { paddingBottom: tabBarHeight + 120 }, isScenes && { backgroundColor: '#150c33' }]}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadVendors({ pullToRefresh: true })} tintColor={isScenes ? '#ffffff' : COLORS.green} />}>
          <HomeHeroSection />
          {activeService === 'food' && <FoodServiceSection />}
          {activeService === 'warehouse' && <WarehouseServiceSection />}
          {activeService === 'eatout' && <EatoutServiceSection />}
          {activeService === 'scenes' && <ScenesServiceSection />}
        </ScrollView>

        <View style={[styles.overlayArea, { bottom: tabBarHeight + 12 }]}>
          {cartCount > 0 ? (
            <TouchableOpacity activeOpacity={0.94} style={styles.floatingCartBar} onPress={() => router.push('/cart')}>
              <View>
                <Text style={styles.floatingCartTitle}>{activeService === 'eatout' ? 'Continue booking' : 'View cart'}</Text>
                <Text style={styles.floatingCartText}>{cartCount} items · {money(cartTotal)}</Text>
              </View>
              <Ionicons name="arrow-forward" size={20} color="#ffffff" />
            </TouchableOpacity>
          ) : null}

          {activeService === 'warehouse' ? (
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
  const isScenes = activeService === 'scenes';

  const tiles =
    activeService === 'warehouse'
      ? WAREHOUSE_CATEGORIES
      : activeService === 'food'
        ? FOOD_CATEGORIES
        : activeService === 'eatout'
          ? EATOUT_CATEGORIES
          : SCENE_CATEGORIES;

  const title =
    activeService === 'warehouse'
      ? 'Warehouse categories'
      : activeService === 'food'
        ? 'Food discovery'
        : activeService === 'eatout'
          ? 'Eatout collections'
          : 'Scenes';

  const subtitle =
    activeService === 'warehouse'
      ? 'High-frequency aisles, clearer grouping and lower decision load.'
      : activeService === 'food'
        ? 'Cuisine-led discovery tuned for delivery journeys.'
        : activeService === 'eatout'
          ? 'Mood-led collections for bookings and bill offers.'
          : 'Experiences, gigs and local plans.';

  return (
    <SafeAreaView style={[styles.safeArea, isScenes && { backgroundColor: '#150c33' }]}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={[styles.pageContent, isScenes && { backgroundColor: '#150c33' }]}>
        <SectionHeader title={title} subtitle={subtitle} light={isScenes} />
        <View style={styles.categoryGrid}>
          {tiles.map((item, index) => (
            <View
              key={item.key}
              style={[
                styles.categoryTile,
                isScenes
                  ? styles.sceneCategoryTile
                  : { backgroundColor: index % 4 === 0 ? '#ede9fe' : index % 4 === 1 ? '#dbeafe' : index % 4 === 2 ? '#dcfce7' : '#fef3c7' },
              ]}>
              <Text style={styles.categoryEmoji}>{item.emoji}</Text>
              <Text style={[styles.categoryTitle, isScenes && styles.sceneCategoryText]}>{item.title}</Text>
            </View>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

function PastOrderCard({ order }) {
  const firstItem = order.items?.[0];
  const itemLine = firstItem
    ? `${firstItem.qty || 1} x ${firstItem.name}${order.items.length > 1 ? ` +${order.items.length - 1} more` : ''}`
    : 'Order';

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderTop}>
        <View style={[styles.orderThumb, { backgroundColor: mapLegacyService(order.service) === 'warehouse' ? '#dbeafe' : '#ede9fe' }]}>
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
      <View style={styles.orderLabelRow}>
        <MetaPill text={serviceLabel(order.service)} accent />
      </View>
    </View>
  );
}

export function ReorderScreen() {
  const { cartCount, cartVendor, cartTotal, pastOrderFilter, setPastOrderFilter, pastOrders, recentVendors } = useGrabBasket();
  const router = useRouter();
  const openVendor = useOpenVendor();

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <SectionHeader title="Reorder" subtitle="This now supports both food and warehouse history with cleaner service labels." />

        {cartCount === 0 ? (
          <EmptyState title="No active basket yet" text="Open a store and add products. Demo orders placed from cart will show up here." />
        ) : (
          <View style={styles.panelCard}>
            <Text style={styles.panelTitle}>Current basket snapshot</Text>
            <Text style={styles.panelText}>{cartVendor?.name || 'Current store'}</Text>
            <Text style={styles.panelSubText}>{cartCount} items · {money(cartTotal)}</Text>
            <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={() => router.push('/cart')}>
              <Text style={styles.primaryButtonText}>Open cart</Text>
            </TouchableOpacity>
          </View>
        )}

        <View style={styles.segmentWrap}>
          {PAST_ORDER_FILTERS.map((item) => (
            <TouchableOpacity
              key={item}
              activeOpacity={0.92}
              style={[styles.segmentButton, pastOrderFilter === item && styles.segmentButtonActive]}
              onPress={() => setPastOrderFilter(item)}>
              <Text style={[styles.segmentButtonText, pastOrderFilter === item && styles.segmentButtonTextActive]}>{item}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {pastOrders.length === 0 ? (
          <EmptyState title="No past orders yet" text="Place one demo order from cart and this section gets stronger instantly." />
        ) : (
          pastOrders.map((order) => <PastOrderCard key={order.id} order={order} />)
        )}

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

export function VendorDetailsScreen() {
  const { vendorId } = useLocalSearchParams();
  const router = useRouter();
  const {
    vendors,
    vendorsLoading,
    activeService,
    favorites,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    cart,
    cartCount,
    cartTotal,
    loadProducts,
    addToCart,
    updateQty,
  } = useGrabBasket();

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
          <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={() => router.replace('/')}>
            <Text style={styles.primaryButtonText}>Back to home</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.innerHeader}>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{vendor.name}</Text>
          <Text style={styles.innerHeaderSubtitle}>{estimateEta(vendor, activeService)} · {vendor?.open_now ? 'Open now' : 'Store details'}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => toggleFavorite(vendor.id)}>
          <Ionicons name={favorites[vendor.id] ? 'heart' : 'heart-outline'} size={18} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContentWithFloat}>
        <View style={styles.vendorHeroCard}>
          <View style={styles.vendorHeroTop}>
            <View style={[styles.vendorInitialBadge, { backgroundColor: activeService === 'warehouse' ? '#dbeafe' : activeService === 'eatout' ? '#fef3c7' : '#ede9fe' }]}>
              <Text style={styles.vendorInitialBadgeText}>{initials(vendor.name)}</Text>
            </View>
            <MetaPill text={`${getVendorRating(vendor)} ★`} accent />
          </View>
          <Text style={styles.vendorHeroTitle}>{vendor.name}</Text>
          <Text style={styles.vendorHeroText}>{vendor.description || vendor.address || 'Curated local merchant'}</Text>
          <View style={styles.vendorMetaRow}>
            <MetaPill text={activeService === 'eatout' ? 'Reserve ready' : vendor?.open_now ? 'Open now' : 'Store'} />
            <MetaPill text={`ETA ${estimateEta(vendor, activeService)}`} />
            <MetaPill text={getDeliveryFeeLabel(vendor, activeService)} />
            {vendor?.distance_km != null ? <MetaPill text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
          </View>
        </View>

        <View style={styles.searchBoxPlain}>
          <Ionicons name="search-outline" size={20} color={COLORS.muted} />
          <TextInput
            style={styles.searchInput}
            placeholder={activeService === 'eatout' ? 'Search dishes or offers' : 'Search inside store'}
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
            <View key={product.id} style={styles.productCard}>
              <View style={{ flex: 1 }}>
                <Text style={styles.productBadge}>{getOfferLabel(product)}</Text>
                <Text style={styles.productTitle}>{product.name}</Text>
                <Text style={styles.productDesc}>{product.description || 'Store product'}</Text>
                <Text style={styles.productPrice}>{money(product.price)}</Text>
              </View>
              <View style={styles.productActionCol}>
                <View style={styles.productIconWrap}>
                  <Text style={styles.productIcon}>{pickEmoji(product.name)}</Text>
                </View>
                {cart.items[product.id]?.qty > 0 ? (
                  <QtyStepper qty={cart.items[product.id]?.qty || 0} onAdd={() => addToCart(product)} onRemove={() => updateQty(product, -1)} />
                ) : (
                  <TouchableOpacity activeOpacity={0.92} style={styles.addButton} onPress={() => addToCart(product)}>
                    <Text style={styles.addButtonText}>{activeService === 'eatout' ? 'BOOK' : 'ADD'}</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          ))
        )}
      </ScrollView>

      {cartCount > 0 && String(cart.vendorId) === String(vendor.id) ? (
        <TouchableOpacity activeOpacity={0.94} style={styles.floatingCartCta} onPress={() => router.push('/cart')}>
          <View>
            <Text style={styles.floatingCartTitle}>{activeService === 'eatout' ? 'Continue booking' : 'View cart'}</Text>
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

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.innerHeader}>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{activeService === 'eatout' ? 'Booking' : 'Cart'}</Text>
          <Text style={styles.innerHeaderSubtitle}>{cartVendor?.name || 'Your basket'}</Text>
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        {cartItems.length === 0 ? (
          <EmptyState title="Your cart is empty" text="Add products from a single store and they will appear here." />
        ) : (
          <>
            {activeService !== 'eatout' ? (
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
            ) : null}

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
              <Text style={styles.panelTitle}>{activeService === 'eatout' ? 'Booking details' : 'Bill details'}</Text>
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Subtotal</Text><Text style={styles.summaryValue}>{money(cartSubtotal)}</Text></View>
              {activeService !== 'eatout' ? <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Delivery fee</Text><Text style={styles.summaryValue}>{deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}</Text></View> : null}
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>{activeService === 'eatout' ? 'Platform fee' : 'Platform fee'}</Text><Text style={styles.summaryValue}>{money(platformFeeAmount)}</Text></View>
              <View style={styles.summaryDivider} />
              <View style={styles.summaryRow}><Text style={styles.summaryLabelStrong}>Total</Text><Text style={styles.summaryValueStrong}>{money(cartTotal)}</Text></View>
            </View>

            <TouchableOpacity
              activeOpacity={0.92}
              style={styles.primaryButton}
              onPress={() => {
                const ok = placeDemoOrder();
                if (ok) router.replace('/reorder');
              }}>
              <Text style={styles.primaryButtonText}>{activeService === 'eatout' ? 'Confirm demo booking' : 'Place demo order'}</Text>
            </TouchableOpacity>
            <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear cart</Text>
            </TouchableOpacity>
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

  heroShell: {
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 18,
  },
  topStrip: {
    marginBottom: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 14,
    backgroundColor: 'rgba(16, 185, 129, 0.22)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  topStripText: { color: '#ecfdf5', fontSize: 13, fontWeight: '800', flex: 1 },
  heroHeaderRow: { flexDirection: 'row', gap: 12, alignItems: 'center' },
  heroTitle: { color: '#ffffff', fontSize: 28, fontWeight: '900' },
  heroTinyLabel: { color: 'rgba(255,255,255,0.74)', fontSize: 12, fontWeight: '700', marginTop: 2 },
  heroAddressRow: { marginTop: 4, flexDirection: 'row', alignItems: 'center', gap: 6 },
  heroAddress: { color: '#ffffff', fontSize: 14, fontWeight: '600', flex: 1 },
  profileCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceRow: { gap: 12, paddingTop: 18, paddingBottom: 14 },
  servicePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.10)',
    minWidth: 104,
  },
  servicePillActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderColor: 'rgba(255,255,255,0.34)',
  },
  serviceEmoji: { fontSize: 24 },
  serviceBadge: { color: '#bfdbfe', fontSize: 11, fontWeight: '900' },
  servicePillText: { color: '#ffffff', fontSize: 15, fontWeight: '800' },
  heroSearchWrap: { flexDirection: 'row', gap: 10, alignItems: 'center' },
  heroSearch: {
    flex: 1,
    height: 56,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  heroSearchInput: { flex: 1, fontSize: 17, color: COLORS.text, fontWeight: '600' },
  heroActionChip: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.20)',
  },
  heroActionChipText: { color: '#ffffff', fontSize: 12, fontWeight: '900' },
  inlineFilterRow: { gap: 10, paddingTop: 14 },
  inlineFilterChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.20)',
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  inlineFilterChipActive: { backgroundColor: '#ffffff' },
  inlineFilterText: { color: '#ffffff', fontSize: 13, fontWeight: '800' },
  inlineFilterTextActive: { color: COLORS.blueDark },
  sceneChipRow: { gap: 10, paddingTop: 14 },
  sceneChip: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.10)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  sceneChipEmoji: { fontSize: 16 },
  sceneChipText: { color: '#ffffff', fontSize: 13, fontWeight: '800' },
  eatoutGlow: {
    marginTop: 12,
    height: 10,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.08)',
  },

  sectionHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: 12 },
  sectionTitle: { color: COLORS.text, fontSize: 22, fontWeight: '900' },
  sectionTitleLight: { color: '#ffffff' },
  sectionSubtitle: { color: COLORS.muted, fontSize: 13, lineHeight: 19, marginTop: 4 },
  sectionSubtitleLight: { color: 'rgba(255,255,255,0.72)' },
  sectionAction: { color: COLORS.green, fontSize: 13, fontWeight: '900', marginTop: 6 },
  sectionActionLight: { color: '#c4b5fd' },
  loadingWrap: { paddingVertical: 32, alignItems: 'center', gap: 10 },
  loadingText: { color: COLORS.muted, fontSize: 14, fontWeight: '700' },
  emptyCard: {
    borderRadius: 22,
    padding: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    gap: 8,
  },
  emptyCardDark: { backgroundColor: 'rgba(255,255,255,0.06)', borderColor: 'rgba(255,255,255,0.08)' },
  emptyTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  emptyTitleDark: { color: '#ffffff' },
  emptyText: { color: COLORS.muted, fontSize: 13, lineHeight: 20 },
  emptyTextDark: { color: 'rgba(255,255,255,0.72)' },
  metaPill: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: '#f3f4f6',
  },
  metaPillDark: { backgroundColor: 'rgba(255,255,255,0.10)' },
  metaPillAccent: { backgroundColor: '#ecfdf5' },
  metaPillText: { color: COLORS.text, fontSize: 11, fontWeight: '900' },
  metaPillTextDark: { color: '#ffffff' },
  metaPillTextAccent: { color: COLORS.greenDark },

  foodHeroBanner: {
    borderRadius: 26,
    backgroundColor: COLORS.purple,
    padding: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  foodHeroBannerTitle: { color: COLORS.yellow, fontSize: 42, fontWeight: '900' },
  foodHeroBannerSub: { color: '#ffffff', fontSize: 15, fontWeight: '800', marginTop: 6 },
  foodHeroBannerButton: {
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderRadius: 16,
    backgroundColor: 'rgba(255,205,0,0.18)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.22)',
  },
  foodHeroBannerButtonText: { color: COLORS.yellow, fontSize: 13, fontWeight: '900' },
  highlightRow: { flexDirection: 'row', gap: 12 },
  highlightCard: {
    flex: 1,
    minHeight: 148,
    borderRadius: 22,
    backgroundColor: COLORS.yellow,
    padding: 14,
    justifyContent: 'space-between',
  },
  highlightEmoji: { fontSize: 26 },
  highlightTitle: { color: COLORS.purpleDark, fontSize: 16, fontWeight: '900' },
  highlightBadge: { color: '#7c2d12', fontSize: 12, fontWeight: '800', lineHeight: 17 },
  horizontalRail: { gap: 12 },
  snapshotCard: { width: 156 },
  snapshotImage: {
    height: 132,
    borderRadius: 24,
    padding: 12,
    justifyContent: 'space-between',
  },
  snapshotBadgeWrap: { alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: 'rgba(255,255,255,0.16)' },
  snapshotOffer: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  snapshotHeart: { position: 'absolute', top: 10, right: 10 },
  snapshotAvatar: { color: '#ffffff', fontSize: 36, fontWeight: '900', alignSelf: 'center', marginTop: 12 },
  snapshotTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900', marginTop: 10 },
  snapshotMeta: { color: COLORS.muted, fontSize: 12, fontWeight: '700', marginTop: 4 },

  vendorCard: {
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    flexDirection: 'row',
    gap: 12,
  },
  vendorThumb: {
    width: 72,
    height: 72,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorThumbText: { color: COLORS.text, fontSize: 22, fontWeight: '900' },
  vendorTopRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  vendorName: { flex: 1, color: COLORS.text, fontSize: 17, fontWeight: '900' },
  vendorDesc: { color: COLORS.muted, fontSize: 13, lineHeight: 18, marginTop: 4 },
  vendorMetaRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 10 },

  warehouseBanner: {
    borderRadius: 26,
    backgroundColor: COLORS.blueDark,
    padding: 20,
  },
  warehouseBannerTitle: { color: '#ffffff', fontSize: 34, fontWeight: '900', textTransform: 'uppercase' },
  warehouseBannerSub: { color: '#dbeafe', fontSize: 14, lineHeight: 20, marginTop: 8 },
  warehouseMiniBanner: {
    width: 152,
    minHeight: 122,
    borderRadius: 22,
    backgroundColor: COLORS.blue,
    padding: 14,
    justifyContent: 'space-between',
  },
  warehouseMiniBannerEmoji: { fontSize: 28 },
  warehouseMiniBannerTitle: { color: '#ffffff', fontSize: 15, fontWeight: '800', lineHeight: 21 },
  infoStripCard: {
    backgroundColor: COLORS.blueDark,
    borderRadius: 18,
    paddingHorizontal: 16,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  infoStripText: { color: '#ffffff', fontSize: 13, fontWeight: '800', flex: 1 },
  warehouseDealsPanel: {
    borderRadius: 24,
    backgroundColor: '#eef2ff',
    paddingVertical: 16,
  },
  warehouseDealsHeader: { paddingHorizontal: 16, marginBottom: 8 },
  warehouseDealsTitle: { color: COLORS.blueDark, fontSize: 28, fontWeight: '900' },
  warehouseDealsSub: { color: COLORS.muted, fontSize: 13, lineHeight: 19, marginTop: 4 },
  warehouseDealCard: {
    width: 182,
    borderRadius: 22,
    backgroundColor: '#ffffff',
    padding: 14,
    borderWidth: 1,
    borderColor: '#dbeafe',
  },
  warehouseDealBadge: { color: COLORS.blueDark, fontSize: 12, fontWeight: '900' },
  warehouseDealIconWrap: {
    marginTop: 12,
    width: 68,
    height: 68,
    borderRadius: 18,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  warehouseDealIcon: { fontSize: 28 },
  warehouseDealTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900', marginTop: 12 },
  warehouseDealMeta: { color: COLORS.muted, fontSize: 12, marginTop: 4 },
  warehouseDealFooter: { marginTop: 14, gap: 10 },
  warehouseDealPrice: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  selectButton: {
    height: 38,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#bfdbfe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  selectButtonText: { color: COLORS.blueDark, fontSize: 13, fontWeight: '900' },

  eatoutHeroCard: {
    borderRadius: 26,
    backgroundColor: '#24104d',
    padding: 20,
    flexDirection: 'row',
    gap: 16,
    alignItems: 'center',
  },
  eatoutHeroTitle: { color: '#ffffff', fontSize: 42, fontWeight: '900', lineHeight: 42 },
  eatoutHeroSub: { color: '#ddd6fe', fontSize: 14, lineHeight: 20, marginTop: 10 },
  eatoutHeroAvatarWrap: {
    width: 92,
    height: 92,
    borderRadius: 24,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  eatoutHeroAvatar: { fontSize: 44 },
  eatoutOfferGrid: { flexDirection: 'row', gap: 12 },
  eatoutBigOffer: {
    flex: 1,
    minHeight: 214,
    borderRadius: 24,
    backgroundColor: COLORS.yellow,
    padding: 16,
    justifyContent: 'space-between',
  },
  eatoutBigOfferEmoji: { fontSize: 36 },
  eatoutBigOfferTitle: { color: COLORS.black, fontSize: 26, fontWeight: '900' },
  eatoutBigOfferSub: { color: '#78350f', fontSize: 13, lineHeight: 18 },
  eatoutSmallOfferCol: { flex: 1, gap: 10 },
  eatoutSmallOffer: {
    minHeight: 100,
    borderRadius: 20,
    backgroundColor: COLORS.yellow,
    padding: 14,
    flexDirection: 'row',
    gap: 10,
  },
  eatoutSmallOfferTitle: { color: COLORS.black, fontSize: 14, fontWeight: '900' },
  eatoutSmallOfferSub: { color: '#78350f', fontSize: 11, lineHeight: 16, marginTop: 4 },
  eatoutSmallOfferEmoji: { fontSize: 28 },
  cashbackStrip: {
    borderRadius: 16,
    paddingVertical: 12,
    paddingHorizontal: 14,
    backgroundColor: '#1f2937',
  },
  cashbackStripText: { color: '#ffffff', fontSize: 13, fontWeight: '800', textAlign: 'center' },

  sceneHeroCard: { borderRadius: 26, backgroundColor: '#24104d', padding: 20 },
  sceneHeroEyebrow: { color: '#c4b5fd', fontSize: 12, fontWeight: '900', textTransform: 'uppercase' },
  sceneHeroTitle: { color: '#ffffff', fontSize: 28, fontWeight: '900', marginTop: 8 },
  sceneHeroSub: { color: 'rgba(255,255,255,0.72)', fontSize: 14, lineHeight: 20, marginTop: 8 },
  sceneEventCard: {
    width: 214,
    borderRadius: 24,
    padding: 16,
    justifyContent: 'space-between',
    minHeight: 260,
  },
  sceneEventEmoji: { fontSize: 34, marginBottom: 10 },
  sceneEventPrice: { color: '#ffffff', fontSize: 13, fontWeight: '900', marginTop: 12 },
  sceneEventTitle: { color: '#ffffff', fontSize: 18, fontWeight: '900', lineHeight: 24, marginTop: 8 },
  sceneEventVenue: { color: 'rgba(255,255,255,0.74)', fontSize: 13, lineHeight: 18, marginTop: 6 },
  sceneCategoryTile: {
    width: '47%',
    minHeight: 116,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    padding: 16,
    justifyContent: 'space-between',
  },
  sceneCategoryText: { color: '#ffffff', fontSize: 16, fontWeight: '900' },

  categoryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  categoryTile: {
    width: '47%',
    minHeight: 116,
    borderRadius: 22,
    padding: 16,
    justifyContent: 'space-between',
  },
  categoryEmoji: { fontSize: 30 },
  categoryTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900' },

  overlayArea: { position: 'absolute', left: 16, right: 16, gap: 10 },
  floatingCartBar: {
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    backgroundColor: COLORS.green,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  floatingCartTitle: { color: '#ffffff', fontSize: 15, fontWeight: '900' },
  floatingCartText: { color: '#d1fae5', fontSize: 13, fontWeight: '700', marginTop: 2 },
  deliveryStrip: { borderRadius: 16, paddingHorizontal: 14, paddingVertical: 12, backgroundColor: '#052e23' },
  deliveryStripText: { color: '#d1fae5', fontSize: 12, fontWeight: '900', textAlign: 'center' },

  panelCard: {
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    gap: 12,
  },
  panelTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  panelText: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  panelSubText: { color: COLORS.muted, fontSize: 13, lineHeight: 18 },
  segmentWrap: { flexDirection: 'row', gap: 10 },
  segmentButton: {
    flex: 1,
    borderRadius: 999,
    paddingVertical: 12,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
  },
  segmentButtonActive: { backgroundColor: COLORS.green, borderColor: COLORS.green },
  segmentButtonText: { color: COLORS.text, fontSize: 13, fontWeight: '900' },
  segmentButtonTextActive: { color: '#ffffff' },
  orderCard: { borderRadius: 22, padding: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: COLORS.card, gap: 10 },
  orderTop: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  orderThumb: { width: 46, height: 46, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  orderThumbText: { color: COLORS.text, fontWeight: '900' },
  orderTitle: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  orderMeta: { color: COLORS.muted, fontSize: 13 },
  orderStatus: { color: COLORS.greenDark, fontSize: 12, fontWeight: '900' },
  orderLine: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  orderLabelRow: { flexDirection: 'row' },

  simpleListRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10 },
  simpleListIcon: { width: 40, height: 40, borderRadius: 14, backgroundColor: COLORS.bg, alignItems: 'center', justifyContent: 'center' },
  simpleListIconText: { color: COLORS.text, fontWeight: '900' },
  simpleListTitle: { color: COLORS.text, fontSize: 15, fontWeight: '800', flex: 1 },
  simpleListMeta: { color: COLORS.muted, fontSize: 12, marginTop: 2 },

  innerHeader: {
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    backgroundColor: COLORS.bg,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  innerHeaderTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  innerHeaderSubtitle: { color: COLORS.muted, fontSize: 12, marginTop: 4 },
  vendorHeroCard: { borderRadius: 24, padding: 18, backgroundColor: COLORS.card, borderWidth: 1, borderColor: COLORS.border },
  vendorHeroTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  vendorInitialBadge: { width: 64, height: 64, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  vendorInitialBadgeText: { color: COLORS.text, fontSize: 22, fontWeight: '900' },
  vendorHeroTitle: { color: COLORS.text, fontSize: 22, fontWeight: '900', marginTop: 14 },
  vendorHeroText: { color: COLORS.muted, fontSize: 14, lineHeight: 20, marginTop: 6 },
  searchBoxPlain: {
    height: 52,
    borderRadius: 20,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  searchInput: { flex: 1, color: COLORS.text, fontSize: 15, fontWeight: '600' },
  productCard: {
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    flexDirection: 'row',
    gap: 16,
  },
  productBadge: { color: COLORS.greenDark, fontSize: 11, fontWeight: '900' },
  productTitle: { color: COLORS.text, fontSize: 17, fontWeight: '900', marginTop: 8 },
  productDesc: { color: COLORS.muted, fontSize: 13, lineHeight: 18, marginTop: 6 },
  productPrice: { color: COLORS.text, fontSize: 17, fontWeight: '900', marginTop: 12 },
  productActionCol: { justifyContent: 'space-between', alignItems: 'flex-end' },
  productIconWrap: {
    width: 76,
    height: 76,
    borderRadius: 22,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  productIcon: { fontSize: 30 },
  addButton: {
    minWidth: 86,
    height: 40,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#86efac',
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButtonText: { color: COLORS.greenDark, fontSize: 13, fontWeight: '900' },
  qtyStepper: {
    height: 40,
    minWidth: 92,
    borderRadius: 12,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 6,
  },
  qtyButton: { width: 28, height: 28, borderRadius: 8, alignItems: 'center', justifyContent: 'center', backgroundColor: '#f3f4f6' },
  qtyText: { color: COLORS.text, fontSize: 14, fontWeight: '900' },

  floatingCartCta: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 16,
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    backgroundColor: COLORS.green,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  progressTrack: { height: 10, borderRadius: 999, overflow: 'hidden', backgroundColor: COLORS.bg, marginTop: 6 },
  progressFill: { height: 10, borderRadius: 999, backgroundColor: COLORS.green },
  cartLine: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10 },
  cartLineTitle: { color: COLORS.text, fontSize: 15, fontWeight: '800' },
  cartLineMeta: { color: COLORS.muted, fontSize: 12, marginTop: 4 },
  summaryRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  summaryLabel: { color: COLORS.muted, fontSize: 14 },
  summaryValue: { color: COLORS.text, fontSize: 14, fontWeight: '800' },
  summaryDivider: { height: 1, backgroundColor: COLORS.border },
  summaryLabelStrong: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  summaryValueStrong: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  primaryButton: { height: 50, borderRadius: 16, backgroundColor: COLORS.green, alignItems: 'center', justifyContent: 'center' },
  primaryButtonText: { color: '#ffffff', fontSize: 15, fontWeight: '900' },
  secondaryButton: { height: 50, borderRadius: 16, borderWidth: 1, borderColor: COLORS.border, backgroundColor: '#ffffff', alignItems: 'center', justifyContent: 'center' },
  secondaryButtonText: { color: COLORS.text, fontSize: 15, fontWeight: '900' },
});