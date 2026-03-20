import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Alert,
  SafeAreaView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { API_BASE_URL } from './src/config';

import HomeScreen from './src/screens/HomeScreen';
import ExploreScreen from './src/screens/ExploreScreen';
import ReorderScreen from './src/screens/ReorderScreen';
import AccountScreen from './src/screens/AccountScreen';
import VendorDetailsScreen from './src/screens/VendorDetailsScreen';
import CartScreen from './src/screens/CartScreen';

const STORAGE_CART = '@grab_basket/cart_v6';
const STORAGE_FAVORITES = '@grab_basket/favorites_v3';
const STORAGE_RECENT_STORES = '@grab_basket/recent_stores_v4';
const STORAGE_RECENT_SEARCHES = '@grab_basket/recent_searches_v3';
const STORAGE_ORDER_HISTORY = '@grab_basket/order_history_v1';

const FREE_DELIVERY_THRESHOLD = 199;
const PLATFORM_FEE = 6;

const COLORS = {
  green900: '#075b49',
  green800: '#0b7a5a',
  green700: '#0f8a6a',
  green100: '#dcfce7',
  green050: '#edfdf4',
  bg: '#f4f5f7',
  card: '#ffffff',
  text: '#111827',
  muted: '#6b7280',
  subtle: '#9ca3af',
  border: '#e5e7eb',
  pink: '#f7a8d5',
  pinkSoft: '#ffd7ec',
  yellowSoft: '#fff3c4',
  blueSoft: '#dbeafe',
  purpleSoft: '#ede9fe',
  purple900: '#4c1d95',
  purple800: '#5b21b6',
  purple700: '#6d28d9',
  purple100: '#f3e8ff',
  dark900: '#020617',
  dark800: '#111827',
  dark700: '#1f2937',
};

const TOP_SERVICES = [
  { key: 'food', icon: 'restaurant-outline', label: 'Food' },
  { key: 'instamart', icon: 'bag-handle-outline', label: 'Instamart' },
  { key: 'dineout', icon: 'wine-outline', label: 'Dineout' },
  { key: 'scenes', icon: 'color-wand-outline', label: 'Scenes' },
];

const HOME_SHORTCUTS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxx', label: 'Maxxsaver', icon: 'pricetags-outline' },
  { key: 'ramzan', label: 'Ramzan', icon: 'moon-outline' },
  { key: 'exam', label: 'Exam Ready', icon: 'school-outline' },
];

const STORE_FILTERS = ['All', 'Open now', 'Closest', 'A-Z'];

const FESTIVAL_TILES = [
  { key: 'navratri', title: 'Chaitra\nNavratri', emoji: '🪔' },
  { key: 'eid', title: 'Eid-Ul-Fitr', emoji: '🌙' },
  { key: 'ugadi', title: 'Ugadi', emoji: '🥭' },
  { key: 'gangaur', title: 'Gangaur', emoji: '🌼' },
];

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

const FOOD_PROMO_TILES = [
  {
    key: 'food-deal',
    title: 'Binge worthy deals',
    subtitle: 'Up to 60% off & more',
    emoji: '🍕',
    tone: '#fde68a',
  },
  {
    key: 'eatright',
    title: 'EatRight',
    subtitle: 'Win up to ₹300 free cash',
    emoji: '🥗',
    tone: '#fbcfe8',
  },
  {
    key: 'awards',
    title: 'Restaurant awards',
    subtitle: 'Vote, share and win',
    emoji: '🏆',
    tone: '#fef08a',
  },
];

const FOOD_DISCOVERY = ['Restaurants near me', 'Pre-book offers', 'Late night', 'Cafe desserts'];

const DINEOUT_PROMO_TILES = [
  {
    key: 'flat50',
    title: 'Flat 50% off',
    subtitle: 'On table bookings',
    emoji: '🎉',
    tone: '#fde68a',
  },
  {
    key: 'girf',
    title: 'GIRF Hall of Fame',
    subtitle: 'Best dineout picks',
    emoji: '🏆',
    tone: '#ede9fe',
  },
  {
    key: 'family',
    title: 'Family-friendly spots',
    subtitle: 'Comfortable and kid-friendly',
    emoji: '🍽️',
    tone: '#dcfce7',
  },
  {
    key: 'cafes',
    title: 'Cafes & quick bites',
    subtitle: 'Coffee, snacks and desserts',
    emoji: '☕',
    tone: '#dbeafe',
  },
];

const DINEOUT_DISCOVERY = [
  'Restaurants near me',
  'Pre-book offers',
  'Quick bites',
  'Premium dining',
  'Rooftop',
];

const SCENE_FILTERS = ['All', 'Today', 'This Week', 'This Weekend', 'Next Weekend'];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room at Break N Chill',
    venue: 'Break N Chill · Chittethukara',
    price: 299,
    date: '20 MAR',
    bucket: 'Today',
    emoji: '💥',
    tone: '#2b0b16',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Throwing Workshop',
    venue: 'Soil to Soul Ceramics · Kadavanthra',
    price: 1000,
    date: '20 MAR',
    bucket: 'This Week',
    emoji: '🏺',
    tone: '#3a2c25',
  },
  {
    id: 'scene-3',
    title: 'Kimchi Culture',
    venue: 'Skei Presents · Kochi',
    price: 699,
    date: '22 MAR',
    bucket: 'This Weekend',
    emoji: '🎎',
    tone: '#5f1015',
  },
  {
    id: 'scene-4',
    title: 'Stand-up Comedy Night',
    venue: 'Laugh Club · Kakkanad',
    price: 499,
    date: '23 MAR',
    bucket: 'This Weekend',
    emoji: '🎤',
    tone: '#1e293b',
  },
  {
    id: 'scene-5',
    title: 'Kids Creative Lab',
    venue: 'Mini Makers · Panampilly',
    price: 399,
    date: '29 MAR',
    bucket: 'Next Weekend',
    emoji: '🎨',
    tone: '#3b1f65',
  },
];

const ACCOUNT_SHORTCUTS = [
  { key: 'address', icon: 'location-outline', label: 'Saved\nAddress' },
  { key: 'payment', icon: 'card-outline', label: 'Payment\nModes' },
  { key: 'refunds', icon: 'reload-outline', label: 'My\nRefunds' },
  { key: 'wallet', icon: 'wallet-outline', label: 'Swiggy\nMoney' },
];

const ACCOUNT_ROWS = [
  { icon: 'ticket-outline', label: 'My Vouchers' },
  { icon: 'receipt-outline', label: 'Account Statements' },
  { icon: 'train-outline', label: 'Order Food on Train' },
  { icon: 'briefcase-outline', label: 'Corporate Rewards' },
  { icon: 'school-outline', label: 'Student Rewards' },
  { icon: 'bookmark-outline', label: 'My Instamart Wishlist' },
  { icon: 'heart-outline', label: 'Favourites' },
  { icon: 'sparkles-outline', label: 'Partner Rewards' },
  { icon: 'call-outline', label: 'Allow restaurants to contact you' },
];

const STORE_TONES = ['#d9f99d', '#fde68a', '#bfdbfe', '#fbcfe8', '#c7d2fe', '#a7f3d0'];

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
    items: [
      { name: 'Curd', qty: 1 },
      { name: 'Cadbury Dairy Milk', qty: 1 },
    ],
    orderedAt: 'Mar 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
];

const SERVICE_THEMES = {
  instamart: {
    hero: COLORS.green800,
    heroAccent: COLORS.green700,
    heroPill: 'rgba(255,255,255,0.16)',
    headline: '23 mins',
    headlineType: 'eta',
    address: 'To Valliachans Place: 12b, Great Orchard...',
    searchPlaceholder: 'Search for Sunscreen',
    bodyDark: false,
  },
  food: {
    hero: COLORS.purple700,
    heroAccent: '#7c3aed',
    heroPill: 'rgba(255,255,255,0.16)',
    headline: 'Valliachans Place',
    headlineType: 'title',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: "Search for 'EatRight'",
    bodyDark: false,
  },
  dineout: {
    hero: COLORS.purple800,
    heroAccent: COLORS.purple700,
    heroPill: 'rgba(255,255,255,0.16)',
    headline: 'Valliachans Place',
    headlineType: 'title',
    address: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for cuisines',
    bodyDark: false,
  },
  scenes: {
    hero: COLORS.dark900,
    heroAccent: COLORS.dark800,
    heroPill: 'rgba(255,255,255,0.10)',
    headline: '12b, Great Orchard',
    headlineType: 'lightTitle',
    address: 'Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search experiences',
    bodyDark: true,
  },
};

const EXPLORE_TILES = {
  instamart: CATEGORY_GRID,
  food: [
    { key: 'south', emoji: '🍛', title: 'South Indian' },
    { key: 'biryani', emoji: '🍗', title: 'Biryani' },
    { key: 'cakes', emoji: '🎂', title: 'Cakes' },
    { key: 'burgers', emoji: '🍔', title: 'Burgers' },
    { key: 'healthy', emoji: '🥗', title: 'Healthy' },
    { key: 'juice', emoji: '🧃', title: 'Juices' },
    { key: 'late-night', emoji: '🌙', title: 'Late night' },
    { key: 'breakfast', emoji: '🥞', title: 'Breakfast' },
  ],
  dineout: [
    { key: 'family', emoji: '👨‍👩‍👧‍👦', title: 'Family dining' },
    { key: 'rooftop', emoji: '🌃', title: 'Rooftop' },
    { key: 'cafe', emoji: '☕', title: 'Cafe dates' },
    { key: 'premium', emoji: '🥂', title: 'Premium dining' },
    { key: 'brunch', emoji: '🍳', title: 'Sunday brunch' },
    { key: 'buffet', emoji: '🍽️', title: 'Buffet' },
    { key: 'cashback', emoji: '💸', title: 'Cashback' },
    { key: 'newhot', emoji: '🔥', title: 'New & hot' },
  ],
  scenes: [
    { key: 'today', emoji: '🌈', title: "Today's vibe" },
    { key: 'weekend', emoji: '🎉', title: 'Weekend mood' },
    { key: 'week', emoji: '🗓️', title: "This week's drops" },
    { key: 'next', emoji: '✨', title: 'Next weekend tea' },
    { key: 'music', emoji: '🎶', title: 'Music' },
    { key: 'kids', emoji: '🧒', title: 'Kids' },
    { key: 'workshop', emoji: '🛠️', title: 'Workshops' },
    { key: 'comedy', emoji: '😂', title: 'Comedy' },
  ],
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
  const result = [];

  values.forEach((value) => {
    const raw = String(value || '').trim();
    const key = normalizeText(raw);
    if (!raw || seen.has(key)) return;
    seen.add(key);
    result.push(raw);
  });

  return result;
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

function getStoreTone(seed = 0) {
  return STORE_TONES[seed % STORE_TONES.length];
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getStoreStatusLabel(vendor) {
  const eta = estimateEta(vendor);
  if (eta === '15-20 mins') return 'FAST';
  if (getDeliveryFeeAmount(vendor) === 0) return 'FREE';
  return vendor?.open_now ? 'OPEN' : 'STORE';
}

function getStoreOfferLabel(vendor) {
  const offers = ['40% OFF', '60% OFF', 'ITEMS AT ₹79', 'FLAT 25% OFF'];
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return offers[seed % offers.length];
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
      return cloned.sort((a, b) => {
        const av = a.distance_km ?? Number.MAX_SAFE_INTEGER;
        const bv = b.distance_km ?? Number.MAX_SAFE_INTEGER;
        return av - bv;
      });
    case 'A-Z':
      return cloned.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    default:
      return cloned;
  }
}

function createKeywordMap(vendors = []) {
  return {
    fresh: vendors.filter((vendor) =>
      /(fruit|vegetable|fresh|dairy|farm|grocery|greens)/i.test(`${vendor.name} ${vendor.description}`)
    ),
    maxx: vendors.filter((vendor) =>
      /(save|mart|basket|daily|essentials|value)/i.test(`${vendor.name} ${vendor.description}`)
    ),
    ramzan: vendors.filter((vendor) =>
      /(dates|dry|juice|drink|sweet|iftar|festival)/i.test(`${vendor.name} ${vendor.description}`)
    ),
    exam: vendors.filter((vendor) =>
      /(snack|drink|coffee|tea|quick|ready|instant)/i.test(`${vendor.name} ${vendor.description}`)
    ),
  };
}

function pickEmoji(name = '') {
  const value = String(name || '').toLowerCase();
  if (/(curd|milk|paneer|yogurt|dairy)/.test(value)) return '🥛';
  if (/(chip|snack|nacho|lays|cracker)/.test(value)) return '🥔';
  if (/(jam|fruit|berry|strawberry)/.test(value)) return '🍓';
  if (/(chocolate|candy|bar|cocoa)/.test(value)) return '🍫';
  if (/(bread|toast|bun|bakery)/.test(value)) return '🍞';
  if (/(drink|juice|cola|soda|water)/.test(value)) return '🥤';
  if (/(vegetable|tomato|onion|potato)/.test(value)) return '🥬';
  if (/(rice|dal|flour|atta)/.test(value)) return '🍚';
  if (/(beauty|cream|soap|shampoo|sunscreen)/.test(value)) return '🧴';
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

async function apiRequest(path) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
    },
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

export default function App() {
  const [activeTab, setActiveTab] = useState('home');
  const [activeService, setActiveService] = useState('instamart');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
  const [sceneFilter, setSceneFilter] = useState('All');
  const [pastOrderFilter, setPastOrderFilter] = useState('All');

  const [vendors, setVendors] = useState([]);
  const [vendorsLoading, setVendorsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [homeDeals, setHomeDeals] = useState([]);
  const [homeDealsLoading, setHomeDealsLoading] = useState(false);

  const [selectedVendor, setSelectedVendor] = useState(null);
  const [productSearch, setProductSearch] = useState('');
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);

  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [favorites, setFavorites] = useState({});
  const [recentStoreIds, setRecentStoreIds] = useState([]);
  const [recentSearches, setRecentSearches] = useState([]);
  const [orderHistory, setOrderHistory] = useState([]);
  const [showCart, setShowCart] = useState(false);

  const theme = SERVICE_THEMES[activeService];

  useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        const stored = await AsyncStorage.multiGet([
          STORAGE_CART,
          STORAGE_FAVORITES,
          STORAGE_RECENT_STORES,
          STORAGE_RECENT_SEARCHES,
          STORAGE_ORDER_HISTORY,
        ]);

        if (!mounted) return;

        const savedCart = stored[0]?.[1];
        const savedFavorites = stored[1]?.[1];
        const savedRecentStores = stored[2]?.[1];
        const savedRecentSearches = stored[3]?.[1];
        const savedOrders = stored[4]?.[1];

        if (savedCart) setCart(JSON.parse(savedCart));
        if (savedFavorites) setFavorites(JSON.parse(savedFavorites));
        if (savedRecentStores) setRecentStoreIds(JSON.parse(savedRecentStores));
        if (savedRecentSearches) setRecentSearches(JSON.parse(savedRecentSearches));
        if (savedOrders) setOrderHistory(JSON.parse(savedOrders));
      } catch {
        // Guest mode persistence can fail silently.
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
      [value, ...current.filter((item) => normalizeText(item) !== normalizeText(value))].slice(0, 8)
    );
  }, []);

  const loadVendors = useCallback(
    async ({ pullToRefresh = false } = {}) => {
      try {
        if (pullToRefresh) {
          setRefreshing(true);
        } else {
          setVendorsLoading(true);
        }

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
    const timer = setTimeout(() => {
      loadVendors();
    }, 250);

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
        .flatMap(({ vendor, products: vendorProducts }) =>
          vendorProducts
            .filter((item) => item.is_available !== false)
            .map((item) => ({
              ...item,
              key: `${vendor.id}-${item.id}`,
              vendorName: vendor.name,
              vendorDescription: vendor.description,
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
    if (vendors.length > 0) {
      loadHomeDeals(vendors);
    } else {
      setHomeDeals([]);
    }
  }, [vendors, loadHomeDeals]);

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      setProductsLoading(true);

      const params = new URLSearchParams();
      if (String(searchValue || '').trim()) params.set('q', String(searchValue).trim());
      params.set('limit', '200');

      const query = params.toString();
      const data = await apiRequest(`/vendors/${vendor.id}/products${query ? `?${query}` : ''}`);
      setProducts(Array.isArray(data) ? data : []);
    } catch (error) {
      setProducts([]);
      Alert.alert('Could not load products', error.message);
    } finally {
      setProductsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!selectedVendor) return undefined;

    const timer = setTimeout(() => {
      loadProducts(selectedVendor, productSearch);
    }, 250);

    return () => clearTimeout(timer);
  }, [selectedVendor, productSearch, loadProducts]);

  const cartItems = useMemo(() => Object.values(cart.items), [cart]);

  const cartCount = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item.qty || 0), 0),
    [cartItems]
  );

  const cartSubtotal = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 0), 0),
    [cartItems]
  );

  const cartVendor = useMemo(() => {
    if (!cart.vendorId) return null;
    return vendors.find((item) => item.id === cart.vendorId) || selectedVendor || null;
  }, [cart.vendorId, vendors, selectedVendor]);

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

  const recentVendors = useMemo(
    () =>
      recentStoreIds
        .map((id) => vendors.find((vendor) => vendor.id === id))
        .filter(Boolean),
    [recentStoreIds, vendors]
  );

  const favoriteVendors = useMemo(
    () => vendors.filter((vendor) => favorites[vendor.id]),
    [vendors, favorites]
  );

  const suggestionPool = useMemo(
    () =>
      dedupeStrings([
        ...recentSearches,
        ...(homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS).map((item) => item.name),
        ...vendors.map((item) => item.name),
        ...CATEGORY_GRID.map((item) => item.title),
        'Sunscreen',
      ]).slice(0, 10),
    [recentSearches, homeDeals, vendors]
  );

  const vendorSearchChips = useMemo(
    () => dedupeStrings(products.map((item) => item.name)).slice(0, 8),
    [products]
  );

  const bestSellerProducts = useMemo(() => products.slice(0, 6), [products]);

  const sceneEvents = useMemo(() => {
    if (sceneFilter === 'All') return SCENE_EVENTS;
    return SCENE_EVENTS.filter((event) => event.bucket === sceneFilter);
  }, [sceneFilter]);

  const pastOrders = useMemo(() => {
    const items = orderHistory.length > 0 ? orderHistory : MOCK_PAST_ORDERS;
    if (pastOrderFilter === 'All') return items;
    return items.filter((item) => item.service === pastOrderFilter.toLowerCase());
  }, [orderHistory, pastOrderFilter]);

  const toggleFavorite = useCallback((vendorId) => {
    setFavorites((current) => ({
      ...current,
      [vendorId]: !current[vendorId],
    }));
  }, []);

  const rememberStore = useCallback((vendorId) => {
    setRecentStoreIds((current) => [vendorId, ...current.filter((id) => id !== vendorId)].slice(0, 8));
  }, []);

  const applyHomeSearch = useCallback(
    (term) => {
      setHomeSearch(term);
      rememberSearch(term);
      setActiveTab('home');
    },
    [rememberSearch]
  );

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
      if (cart.vendorId && cart.vendorId !== product.vendor_id) {
        Alert.alert(
          'Replace cart?',
          'Only one store can stay active in the basket. Replace the current basket with this item?',
          [
            { text: 'Cancel', style: 'cancel' },
            { text: 'Replace', style: 'destructive', onPress: () => replaceCartWith(product) },
          ]
        );
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

      if (nextQty <= 0) {
        delete nextItems[product.id];
      } else {
        nextItems[product.id] = {
          ...existing,
          qty: nextQty,
        };
      }

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

  const openVendor = useCallback(
    async (vendor) => {
      rememberStore(vendor.id);
      setSelectedVendor(vendor);
      setProductSearch('');
      setProducts([]);
      await loadProducts(vendor, '');
    },
    [loadProducts, rememberStore]
  );

  const placeDemoOrder = useCallback(() => {
    if (cartItems.length === 0) {
      Alert.alert('Cart is empty', 'Add some products first.');
      return;
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
    setShowCart(false);
    setActiveTab('reorder');

    Alert.alert(
      'Demo order placed',
      'Saved locally so your Reorder and Account screens now feel much closer to a real flow.'
    );
  }, [activeService, cartItems, cartTotal, cartVendor, clearCart]);

  const isHomeRoot = !selectedVendor && !showCart && activeTab === 'home';

  const deliveryStripText =
    cartSubtotal <= 0
      ? `FREE DELIVERY on orders above ${money(FREE_DELIVERY_THRESHOLD)}`
      : freeDeliveryRemaining > 0
        ? `Add ${money(freeDeliveryRemaining)} more for FREE DELIVERY`
        : 'FREE DELIVERY unlocked';

  const exploreLabel =
    activeService === 'instamart'
      ? 'Categories'
      : activeService === 'dineout'
        ? 'My corner'
        : activeService === 'scenes'
          ? 'Buckets'
          : 'Explore';

  const helpers = {
    money,
    initials,
    normalizeText,
    dedupeStrings,
    estimateEta,
    getDeliveryFeeAmount,
    getDeliveryFeeLabel,
    getStoreTone,
    getVendorRating,
    getStoreStatusLabel,
    getStoreOfferLabel,
    getOfferLabel,
    formatOrderTime,
    pickEmoji,
  };

  const constants = {
    COLORS,
    TOP_SERVICES,
    HOME_SHORTCUTS,
    STORE_FILTERS,
    FESTIVAL_TILES,
    CATEGORY_GRID,
    FOOD_PROMO_TILES,
    FOOD_DISCOVERY,
    DINEOUT_PROMO_TILES,
    DINEOUT_DISCOVERY,
    SCENE_FILTERS,
    SCENE_EVENTS,
    ACCOUNT_SHORTCUTS,
    ACCOUNT_ROWS,
    STORE_TONES,
    FALLBACK_HOME_DEALS,
    MOCK_PAST_ORDERS,
    SERVICE_THEMES,
    EXPLORE_TILES,
    FREE_DELIVERY_THRESHOLD,
    PLATFORM_FEE,
  };

  const state = {
    activeTab,
    activeService,
    activeShortcut,
    homeSearch,
    storeFilter,
    sceneFilter,
    pastOrderFilter,
    vendors,
    vendorsLoading,
    refreshing,
    homeDeals,
    homeDealsLoading,
    selectedVendor,
    productSearch,
    products,
    productsLoading,
    cart,
    favorites,
    recentStoreIds,
    recentSearches,
    orderHistory,
    showCart,
    theme,
    cartItems,
    cartCount,
    cartSubtotal,
    cartVendor,
    deliveryFeeAmount,
    platformFeeAmount,
    cartTotal,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    featuredVendors,
    recentVendors,
    favoriteVendors,
    suggestionPool,
    vendorSearchChips,
    bestSellerProducts,
    sceneEvents,
    pastOrders,
    deliveryStripText,
    exploreLabel,
    isHomeRoot,
  };

  const actions = {
    setActiveTab,
    setActiveService,
    setActiveShortcut,
    setHomeSearch,
    setStoreFilter,
    setSceneFilter,
    setPastOrderFilter,
    setSelectedVendor,
    setProductSearch,
    setShowCart,
    setCart,
    setFavorites,
    setRecentStoreIds,
    setRecentSearches,
    setOrderHistory,
    loadVendors,
    loadProducts,
    rememberSearch,
    toggleFavorite,
    applyHomeSearch,
    addToCart,
    updateQty,
    clearCart,
    openVendor,
    placeDemoOrder,
  };

  const screenProps = {
    state,
    actions,
    helpers,
    constants,
  };

  const renderActiveScreen = () => {
    if (selectedVendor) {
      return <VendorDetailsScreen {...screenProps} />;
    }

    if (showCart) {
      return <CartScreen {...screenProps} />;
    }

    switch (activeTab) {
      case 'explore':
        return <ExploreScreen {...screenProps} />;
      case 'reorder':
        return <ReorderScreen {...screenProps} />;
      case 'account':
        return <AccountScreen {...screenProps} />;
      case 'home':
      default:
        return <HomeScreen {...screenProps} />;
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, isHomeRoot && { backgroundColor: theme.hero }]}>
      <StatusBar
        barStyle={isHomeRoot ? 'light-content' : 'dark-content'}
        backgroundColor={isHomeRoot ? theme.hero : COLORS.bg}
      />

      {renderActiveScreen()}

      {!selectedVendor && !showCart ? (
        <>
          {activeTab === 'home' ? (
            <View style={styles.rootOverlayArea} pointerEvents="box-none">
              {cartCount > 0 ? (
                <TouchableOpacity style={styles.rootCartBar} onPress={() => setShowCart(true)} activeOpacity={0.92}>
                  <View>
                    <Text style={styles.rootCartBarTitle}>View cart</Text>
                    <Text style={styles.rootCartBarSubtitle}>
                      {cartCount} items · {money(cartTotal)}
                    </Text>
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
          ) : null}

          <View style={styles.bottomTabBar}>
            <BottomTab
              icon="home-outline"
              label="Home"
              active={activeTab === 'home'}
              onPress={() => setActiveTab('home')}
            />
            <BottomTab
              icon="grid-outline"
              label={exploreLabel}
              active={activeTab === 'explore'}
              onPress={() => setActiveTab('explore')}
            />
            <BottomTab
              icon="reload-outline"
              label="Reorder"
              active={activeTab === 'reorder'}
              onPress={() => setActiveTab('reorder')}
            />
            <BottomTab
              icon="person-outline"
              label="Account"
              active={activeTab === 'account'}
              onPress={() => setActiveTab('account')}
            />
          </View>
        </>
      ) : null}
    </SafeAreaView>
  );
}

function BottomTab({ icon, label, active, onPress }) {
  return (
    <TouchableOpacity style={styles.bottomTab} onPress={onPress} activeOpacity={0.9}>
      <Ionicons name={icon} size={20} color={active ? COLORS.text : COLORS.muted} />
      <Text style={[styles.bottomTabLabel, active && styles.bottomTabLabelActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  rootOverlayArea: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 78,
    alignItems: 'center',
    paddingHorizontal: 16,
  },
  rootCartBar: {
    width: '100%',
    backgroundColor: COLORS.green800,
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
    shadowColor: '#000000',
    shadowOpacity: 0.16,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
  },
  rootCartBarTitle: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '900',
  },
  rootCartBarSubtitle: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 13,
    marginTop: 4,
  },
  deliveryStrip: {
    backgroundColor: '#dff8f0',
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  deliveryStripText: {
    color: COLORS.green900,
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 0.3,
    textAlign: 'center',
  },
  bottomTabBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: '#ffffff',
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    flexDirection: 'row',
    paddingTop: 8,
    paddingBottom: 14,
  },
  bottomTab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  bottomTabLabel: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  bottomTabLabelActive: {
    color: COLORS.text,
  },
});