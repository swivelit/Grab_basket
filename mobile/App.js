import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
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
import { API_BASE_URL } from './src/config';

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
        // Ignore guest-mode boot persistence issues.
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

  const toggleFavorite = (vendorId) => {
    setFavorites((current) => ({
      ...current,
      [vendorId]: !current[vendorId],
    }));
  };

  const rememberStore = (vendorId) => {
    setRecentStoreIds((current) => [vendorId, ...current.filter((id) => id !== vendorId)].slice(0, 8));
  };

  const applyHomeSearch = (term) => {
    setHomeSearch(term);
    rememberSearch(term);
    setActiveTab('home');
  };

  const replaceCartWith = (product) => {
    setCart({
      vendorId: product.vendor_id,
      items: {
        [product.id]: {
          ...product,
          qty: 1,
        },
      },
    });
  };

  const addToCart = (product) => {
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
  };

  const updateQty = (product, delta) => {
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
  };

  const clearCart = () => {
    setCart({ vendorId: null, items: {} });
  };

  const openVendor = async (vendor) => {
    rememberStore(vendor.id);
    setSelectedVendor(vendor);
    setProductSearch('');
    setProducts([]);
    await loadProducts(vendor, '');
  };

  const placeDemoOrder = () => {
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
      'Saved locally so your Reorder and Account screens now feel much closer to a real Swiggy flow.'
    );
  };

  const InstamartServiceSection = () => (
    <View style={styles.bodySurface}>
      <View style={styles.everydayBanner}>
        <View style={styles.everydayBadge}>
          <Text style={styles.everydayPrice}>₹9</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.everydayTitle}>everyday</Text>
          <Text style={styles.everydayText}>Shop for ₹199 to get one item at ₹9</Text>
        </View>
      </View>

      <SectionHeader title="Under ₹99" subtitle="Fast add-ons and daily essentials." />
      {homeDealsLoading ? <LoadingBlock label="Loading quick picks..." /> : null}

      <View style={styles.quickGrid}>
        {(homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS).map((item) => (
          <QuickDealCard
            key={item.key || item.id}
            item={item}
            qty={item.id ? cart.items[item.id]?.qty || 0 : 0}
            onAdd={item.id ? () => addToCart(item) : undefined}
            onRemove={item.id ? () => updateQty(item, -1) : undefined}
          />
        ))}
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.suggestionRail}>
        {suggestionPool.map((item) => (
          <TouchableOpacity key={item} style={styles.suggestionChip} onPress={() => applyHomeSearch(item)}>
            <Ionicons name="search-outline" size={14} color={COLORS.green800} />
            <Text style={styles.suggestionChipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        {STORE_FILTERS.map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.filterChip, storeFilter === item && styles.filterChipActive]}
            onPress={() => setStoreFilter(item)}>
            <Text style={[styles.filterChipText, storeFilter === item && styles.filterChipTextActive]}>
              {item}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <SectionHeader title="Popular categories" subtitle="Daily-use aisles that feel familiar and easy to scan." />

      <View style={styles.categoryGrid}>
        {CATEGORY_GRID.map((item) => (
          <View key={item.key} style={styles.categoryTile}>
            <Text style={styles.categoryEmoji}>{item.emoji}</Text>
            <Text style={styles.categoryTitle}>{item.title}</Text>
          </View>
        ))}
      </View>

      {recentVendors.length > 0 ? (
        <>
          <SectionHeader title="Recently opened" subtitle="Bring users back to the stores they already trust." />
          {recentVendors.slice(0, 2).map((vendor, index) => (
            <StoreCard
              key={`recent-${vendor.id}`}
              vendor={vendor}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              tone={getStoreTone(index + 1)}
            />
          ))}
        </>
      ) : null}

      <SectionHeader title="Featured stores" subtitle={`${featuredVendors.length} stores shown.`} />

      {vendorsLoading ? (
        <LoadingBlock label="Loading stores..." />
      ) : featuredVendors.length === 0 ? (
        <EmptyState title="No stores found" text="Seed vendors and products in the backend and this feed will fill out." />
      ) : (
        featuredVendors.map((vendor, index) => (
          <StoreCard
            key={vendor.id}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
            tone={getStoreTone(index + 3)}
          />
        ))
      )}

      {favoriteVendors.length > 0 ? (
        <>
          <SectionHeader title="Saved stores" subtitle="Useful even before authentication is added back." />
          {favoriteVendors.slice(0, 2).map((vendor, index) => (
            <StoreCard
              key={`favorite-${vendor.id}`}
              vendor={vendor}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              tone={getStoreTone(index + 5)}
            />
          ))}
        </>
      ) : null}
    </View>
  );

  const FoodServiceSection = () => (
    <View style={styles.bodySurface}>
      <View style={[styles.foodHeroBanner, { backgroundColor: theme.hero }]}>
        <Text style={styles.foodHeroEyebrow}>CRAVE</Text>
        <Text style={styles.foodHeroTitle}>Up to 60% off & more</Text>
        <Text style={styles.foodHeroText}>Sharper merchandising, stronger visual hierarchy, more like the reference.</Text>
      </View>

      <View style={styles.tileGrid}>
        {FOOD_PROMO_TILES.map((item) => (
          <FeatureTile key={item.key} item={item} />
        ))}
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        {FOOD_DISCOVERY.map((item) => (
          <TouchableOpacity key={item} style={styles.filterChip} onPress={() => applyHomeSearch(item)}>
            <Text style={styles.filterChipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <SectionHeader title="Top rated near you" subtitle="Store cards now feel closer to Swiggy's food discovery blocks." />

      {vendorsLoading ? (
        <LoadingBlock label="Loading restaurants..." />
      ) : featuredVendors.length === 0 ? (
        <EmptyState title="No restaurants yet" text="Add more food vendors on the backend to make this feed feel full." />
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.miniRail}>
          {featuredVendors.map((vendor, index) => (
            <MiniStoreCard
              key={`food-${vendor.id}`}
              vendor={vendor}
              tone={getStoreTone(index)}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
            />
          ))}
        </ScrollView>
      )}

      <SectionHeader title="Crave under ₹99" subtitle="Great for quick wins and high-conversion add-to-cart behavior." />

      <View style={styles.quickGrid}>
        {(homeDeals.length > 0 ? homeDeals : FALLBACK_HOME_DEALS).map((item) => (
          <QuickDealCard
            key={`food-${item.key || item.id}`}
            item={item}
            qty={item.id ? cart.items[item.id]?.qty || 0 : 0}
            onAdd={item.id ? () => addToCart(item) : undefined}
            onRemove={item.id ? () => updateQty(item, -1) : undefined}
          />
        ))}
      </View>
    </View>
  );

  const DineoutServiceSection = () => (
    <View style={styles.bodySurface}>
      <View style={styles.dineoutBanner}>
        <View style={{ flex: 1 }}>
          <Text style={styles.dineoutBannerTitle}>Flat 25% OFF</Text>
          <Text style={styles.dineoutBannerText}>+10% cashback with dineout style campaigns</Text>
        </View>
        <Text style={styles.dineoutBannerEmoji}>🥂</Text>
      </View>

      <SectionHeader title="In the spotlight" subtitle="Curated booking hooks and campaign-led discovery." />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.miniRail}>
        {DINEOUT_PROMO_TILES.map((item) => (
          <FeatureTile key={item.key} item={item} compact />
        ))}
      </ScrollView>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        {DINEOUT_DISCOVERY.map((item) => (
          <TouchableOpacity key={item} style={styles.filterChip} onPress={() => applyHomeSearch(item)}>
            <Text style={styles.filterChipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <SectionHeader title="Popular picks" subtitle="You can later swap these with real dineout inventory and booking slots." />

      {vendorsLoading ? (
        <LoadingBlock label="Loading popular picks..." />
      ) : featuredVendors.length === 0 ? (
        <EmptyState title="No dineout picks yet" text="Use the same vendor base now, then split food vs dineout inventory later." />
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.miniRail}>
          {featuredVendors.map((vendor, index) => (
            <MiniStoreCard
              key={`dineout-${vendor.id}`}
              vendor={vendor}
              tone={getStoreTone(index + 2)}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              dineout
            />
          ))}
        </ScrollView>
      )}
    </View>
  );

  const ScenesServiceSection = () => (
    <View style={styles.bodySurfaceDark}>
      <SectionHeader
        title="When is the plan?"
        subtitle="The structure now matches the reference better: themed buckets, featured cards and event blocks."
        light
      />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.miniRail}>
        {["Today’s vibe", 'Weekend mood', "This week’s drops", 'Next weekend tea'].map((item, index) => (
          <View key={item} style={[styles.bucketPill, { backgroundColor: ['#7e1e6f', '#164e63', '#5b3f93', '#6b4a1f'][index] }]}>
            <Text style={styles.bucketPillText}>{item}</Text>
          </View>
        ))}
      </ScrollView>

      <SectionHeader title="All scenes" subtitle={`${sceneEvents.length} events`} light />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        {SCENE_FILTERS.map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.darkFilterChip, sceneFilter === item && styles.darkFilterChipActive]}
            onPress={() => setSceneFilter(item)}>
            <Text style={[styles.darkFilterChipText, sceneFilter === item && styles.darkFilterChipTextActive]}>
              {item}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <View style={styles.sceneGrid}>
        {sceneEvents.map((item) => (
          <SceneEventCard key={item.id} item={item} />
        ))}
      </View>
    </View>
  );

  const HomeHeroSection = () => {
    const isEta = theme.headlineType === 'eta';

    return (
      <View style={[styles.heroShell, { backgroundColor: theme.hero }]}>
        <View style={styles.heroTopRow}>
          <View style={{ flex: 1 }}>
            <Text style={isEta ? styles.heroEta : theme.bodyDark ? styles.heroTitleLight : styles.heroTitle}>
              {theme.headline}
            </Text>
            <TouchableOpacity activeOpacity={0.88} style={styles.addressRow}>
              <Text style={styles.addressText} numberOfLines={1}>
                {theme.address}
              </Text>
              <Ionicons name="chevron-down" size={16} color="#d1fae5" />
            </TouchableOpacity>
          </View>
          <TouchableOpacity style={[styles.profileButton, { backgroundColor: theme.heroPill }]} activeOpacity={0.9}>
            <Ionicons name="person-outline" size={22} color="#ffffff" />
          </TouchableOpacity>
        </View>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceRail}>
          {TOP_SERVICES.map((item) => {
            const active = activeService === item.key;
            return (
              <TouchableOpacity
                key={item.key}
                activeOpacity={0.9}
                style={[
                  styles.serviceCard,
                  active && { backgroundColor: theme.heroAccent, borderColor: 'rgba(255,255,255,0.24)' },
                ]}
                onPress={() => {
                  setActiveService(item.key);
                  setActiveTab('home');
                }}>
                <View style={[styles.serviceIconWrap, active && styles.serviceIconWrapActive]}>
                  <Ionicons name={item.icon} size={22} color={active ? theme.hero : '#ffffff'} />
                </View>
                <Text style={[styles.serviceLabel, active && styles.serviceLabelActive]}>{item.label}</Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        <View style={styles.heroSearchRow}>
          <View style={styles.searchBoxHero}>
            <Ionicons name="search-outline" size={20} color={COLORS.muted} />
            <TextInput
              style={styles.searchInput}
              placeholder={theme.searchPlaceholder}
              placeholderTextColor={COLORS.subtle}
              value={homeSearch}
              onChangeText={setHomeSearch}
              onSubmitEditing={() => rememberSearch(homeSearch)}
            />
            <Ionicons name={activeService === 'food' ? 'mic-outline' : 'receipt-outline'} size={20} color={COLORS.muted} />
          </View>
          <TouchableOpacity style={[styles.bookmarkButton, { backgroundColor: theme.heroPill }]} activeOpacity={0.9}>
            <Ionicons name="bookmark-outline" size={22} color="#ffffff" />
          </TouchableOpacity>
        </View>

        {activeService === 'instamart' ? (
          <>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.shortcutRail}>
              {HOME_SHORTCUTS.map((item) => {
                const active = activeShortcut === item.key;
                return (
                  <TouchableOpacity
                    key={item.key}
                    style={styles.shortcutItem}
                    activeOpacity={0.92}
                    onPress={() => setActiveShortcut(item.key)}>
                    <View style={[styles.shortcutIconWrap, active && styles.shortcutIconWrapActive]}>
                      <Ionicons name={item.icon} size={16} color={active ? '#ffffff' : '#d1fae5'} />
                    </View>
                    <Text style={[styles.shortcutLabel, active && styles.shortcutLabelActive]}>{item.label}</Text>
                    <View style={[styles.shortcutUnderline, !active && styles.shortcutUnderlineHidden]} />
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            <View style={styles.celebrationWrap}>
              <Text style={styles.celebrationEyebrow}>SEASON OF</Text>
              <Text style={styles.celebrationTitle}>CELEBRATION</Text>
            </View>

            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.festivalRail}>
              {FESTIVAL_TILES.map((item) => (
                <View key={item.key} style={styles.festivalCard}>
                  <Text style={styles.festivalTitle}>{item.title}</Text>
                  <Text style={styles.festivalEmoji}>{item.emoji}</Text>
                </View>
              ))}
            </ScrollView>
          </>
        ) : null}
      </View>
    );
  };

  const HomeScreen = () => (
    <View style={styles.screen}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[
          styles.homeScrollContent,
          activeService === 'scenes' && { backgroundColor: COLORS.dark900 },
        ]}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadVendors({ pullToRefresh: true })}
            tintColor={theme.bodyDark ? '#ffffff' : '#ffffff'}
          />
        }>
        <HomeHeroSection />

        {activeService === 'instamart' && <InstamartServiceSection />}
        {activeService === 'food' && <FoodServiceSection />}
        {activeService === 'dineout' && <DineoutServiceSection />}
        {activeService === 'scenes' && <ScenesServiceSection />}
      </ScrollView>
    </View>
  );

  const ExploreScreen = () => {
    const tiles = EXPLORE_TILES[activeService] || CATEGORY_GRID;
    const title =
      activeService === 'instamart'
        ? 'Categories'
        : activeService === 'dineout'
          ? 'My corner'
          : activeService === 'scenes'
            ? 'Buckets'
            : 'Explore';

    return (
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[
          styles.pageContent,
          activeService === 'scenes' && { backgroundColor: COLORS.dark900 },
        ]}>
        <SectionHeader
          title={title}
          subtitle={
            activeService === 'instamart'
              ? 'Fast aisles, clear hierarchy, and easier browsing.'
              : activeService === 'food'
                ? 'Cuisine-led discovery that feels closer to food delivery patterns.'
                : activeService === 'dineout'
                  ? 'Mood-led dineout discovery for bookings and offers.'
                  : 'Experience buckets and time-based discovery.'
          }
          light={activeService === 'scenes'}
        />

        <View style={styles.categoryGridLarge}>
          {tiles.map((item, index) => (
            <TouchableOpacity
              key={item.key}
              activeOpacity={0.92}
              style={[
                styles.categoryLargeTile,
                { backgroundColor: activeService === 'scenes' ? COLORS.dark700 : getStoreTone(index) },
              ]}>
              <Text style={styles.categoryLargeEmoji}>{item.emoji}</Text>
              <Text style={[styles.categoryLargeTitle, activeService === 'scenes' && styles.categoryLargeTitleLight]}>
                {item.title}
              </Text>
              <Text style={[styles.categoryLargeHint, activeService === 'scenes' && styles.categoryLargeHintLight]}>
                Tap to browse
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>
    );
  };

  const ReorderScreen = () => (
    <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Reorder" subtitle="Local order history makes this useful even before auth is wired." />

      {cartCount === 0 ? (
        <EmptyState title="No active basket yet" text="Open a store and add products. Demo orders placed from cart will show up here." />
      ) : (
        <View style={styles.panelCard}>
          <Text style={styles.panelTitle}>Current basket snapshot</Text>
          <Text style={styles.panelText}>{cartVendor?.name || 'Current store'}</Text>
          <Text style={styles.panelSubText}>{cartCount} items · {money(cartTotal)}</Text>
          <TouchableOpacity style={styles.primaryButton} onPress={() => setShowCart(true)}>
            <Text style={styles.primaryButtonText}>Open cart</Text>
          </TouchableOpacity>
        </View>
      )}

      <View style={styles.segmentWrap}>
        {['All', 'Food', 'Instamart'].map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.segmentButton, pastOrderFilter === item && styles.segmentButtonActive]}
            onPress={() => setPastOrderFilter(item)}>
            <Text style={[styles.segmentButtonText, pastOrderFilter === item && styles.segmentButtonTextActive]}>
              {item}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {pastOrders.length === 0 ? (
        <EmptyState title="No past orders yet" text="Place one demo order from cart and this section will become much stronger." />
      ) : (
        pastOrders.map((order) => (
          <PastOrderCard key={order.id} order={order} />
        ))
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
                <Text style={styles.simpleListMeta}>
                  {estimateEta(vendor)} · {getDeliveryFeeLabel(vendor)}
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
            </TouchableOpacity>
          ))}
        </View>
      ) : null}
    </ScrollView>
  );

  const AccountScreen = () => (
    <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
      <View style={styles.accountHeaderCard}>
        <View>
          <Text style={styles.accountName}>Guest</Text>
          <Text style={styles.accountPhone}>+91 - 0000000000</Text>
          <Text style={styles.accountEmail}>guest@grabbasket.app</Text>
        </View>
        <TouchableOpacity style={styles.helpButton}>
          <Text style={styles.helpButtonText}>Help</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.oneCard}>
        <View>
          <View style={styles.oneBadge}>
            <Text style={styles.oneBadgeText}>ACTIVE</Text>
          </View>
          <Text style={styles.oneTitle}>₹35 saved in 36 days</Text>
          <Text style={styles.oneSubText}>Explore all membership benefits</Text>
        </View>
        <Ionicons name="chevron-down" size={20} color={COLORS.subtle} />
      </View>

      <View style={styles.accountQuickGrid}>
        {ACCOUNT_SHORTCUTS.map((item) => (
          <View key={item.key} style={styles.accountQuickCard}>
            <Ionicons name={item.icon} size={20} color={COLORS.text} />
            <Text style={styles.accountQuickText}>{item.label}</Text>
          </View>
        ))}
      </View>

      <View style={styles.accountListCard}>
        {ACCOUNT_ROWS.map((item, index) => (
          <AccountListRow key={item.label} item={item} first={index === 0} />
        ))}
      </View>

      <SectionHeader title="Past orders" subtitle="Closer to the account screenshot, but still guest-mode friendly." />

      <View style={styles.segmentWrap}>
        {['All', 'Food', 'Instamart'].map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.segmentButton, pastOrderFilter === item && styles.segmentButtonActive]}
            onPress={() => setPastOrderFilter(item)}>
            <Text style={[styles.segmentButtonText, pastOrderFilter === item && styles.segmentButtonTextActive]}>
              {item}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {pastOrders.slice(0, 3).map((order) => (
        <PastOrderCard key={`account-${order.id}`} order={order} compact />
      ))}
    </ScrollView>
  );

  const VendorDetailsScreen = () => (
    <View style={styles.screen}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => setSelectedVendor(null)}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.innerHeaderSubtitle}>
            {estimateEta(selectedVendor)} · {selectedVendor?.open_now ? 'Open now' : 'Store details'}
          </Text>
        </View>
        <TouchableOpacity style={styles.iconButton} onPress={() => toggleFavorite(selectedVendor.id)}>
          <Ionicons name={favorites[selectedVendor.id] ? 'heart' : 'heart-outline'} size={18} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContentWithFloat}>
        <View style={styles.vendorHeroCard}>
          <View style={styles.vendorHeroTopRow}>
            <View style={styles.vendorInitialBadge}>
              <Text style={styles.vendorInitialBadgeText}>{initials(selectedVendor?.name || '')}</Text>
            </View>
            <View style={styles.ratingPill}>
              <Ionicons name="star" size={12} color="#14532d" />
              <Text style={styles.ratingPillText}>{getVendorRating(selectedVendor)}</Text>
            </View>
          </View>

          <Text style={styles.vendorHeroTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.vendorHeroText}>
            {selectedVendor?.description || selectedVendor?.address || 'Quick grocery and essentials store'}
          </Text>

          <View style={styles.vendorBadgeRow}>
            <MetaBadge text={selectedVendor?.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={`ETA ${estimateEta(selectedVendor)}`} />
            <MetaBadge text={getDeliveryFeeLabel(selectedVendor)} />
            {selectedVendor?.distance_km != null ? <MetaBadge text={`${selectedVendor.distance_km.toFixed(1)} km`} /> : null}
          </View>
        </View>

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

        {vendorSearchChips.length > 0 ? (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.suggestionRail}>
            {vendorSearchChips.map((item) => (
              <TouchableOpacity key={item} style={styles.suggestionChip} onPress={() => setProductSearch(item)}>
                <Ionicons name="search-outline" size={14} color={COLORS.green800} />
                <Text style={styles.suggestionChipText}>{item}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        ) : null}

        {productsLoading ? <LoadingBlock label="Loading products..." /> : null}

        {!productsLoading && bestSellerProducts.length > 0 ? (
          <>
            <SectionHeader title="Bestsellers" subtitle="High-conversion block that Swiggy-style stores need." />
            {bestSellerProducts.map((product) => (
              <ProductCard
                key={`best-${product.id}`}
                product={product}
                featured
                qty={cart.items[product.id]?.qty || 0}
                onAdd={() => addToCart(product)}
                onRemove={() => updateQty(product, -1)}
              />
            ))}
          </>
        ) : null}

        <SectionHeader title="All items" subtitle="Fetched from /vendors/{id}/products." />
        {!productsLoading && products.length === 0 ? (
          <EmptyState title="No products yet" text="Add products from the seller side and this page will start looking complete." />
        ) : (
          products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              qty={cart.items[product.id]?.qty || 0}
              onAdd={() => addToCart(product)}
              onRemove={() => updateQty(product, -1)}
            />
          ))
        )}
      </ScrollView>

      {cartCount > 0 && cart.vendorId === selectedVendor?.id ? (
        <TouchableOpacity style={styles.floatingCart} onPress={() => setShowCart(true)}>
          <View>
            <Text style={styles.floatingCartTitle}>View cart</Text>
            <Text style={styles.floatingCartSubtitle}>{cartCount} items · {money(cartTotal)}</Text>
          </View>
          <Ionicons name="arrow-forward" size={20} color="#ffffff" />
        </TouchableOpacity>
      ) : null}
    </View>
  );

  const CartScreen = () => (
    <View style={styles.screen}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => setShowCart(false)}>
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
            <FreeDeliveryCard
              subtotal={cartSubtotal}
              remaining={freeDeliveryRemaining}
              progress={freeDeliveryProgress}
            />

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
              <SummaryRow label="Subtotal" value={money(cartSubtotal)} />
              <SummaryRow label="Delivery fee" value={deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)} />
              <SummaryRow label="Platform fee" value={money(platformFeeAmount)} />
              <View style={styles.summaryDivider} />
              <SummaryRow label="Total" value={money(cartTotal)} strong />
            </View>

            <TouchableOpacity style={styles.primaryButton} onPress={placeDemoOrder}>
              <Text style={styles.primaryButtonText}>Place demo order</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.secondaryButton} onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear cart</Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </View>
  );

  const ActiveScreen = () => {
    if (selectedVendor) return <VendorDetailsScreen />;
    if (showCart) return <CartScreen />;

    switch (activeTab) {
      case 'explore':
        return <ExploreScreen />;
      case 'reorder':
        return <ReorderScreen />;
      case 'account':
        return <AccountScreen />;
      case 'home':
      default:
        return <HomeScreen />;
    }
  };

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

  return (
    <SafeAreaView style={[styles.safeArea, isHomeRoot && { backgroundColor: theme.hero }]}>
      <StatusBar
        barStyle={isHomeRoot ? 'light-content' : 'dark-content'}
        backgroundColor={isHomeRoot ? theme.hero : COLORS.bg}
      />
      <ActiveScreen />

      {!selectedVendor && !showCart ? (
        <>
          {activeTab === 'home' ? (
            <View style={styles.rootOverlayArea} pointerEvents="box-none">
              {cartCount > 0 ? (
                <TouchableOpacity style={styles.rootCartBar} onPress={() => setShowCart(true)} activeOpacity={0.92}>
                  <View>
                    <Text style={styles.rootCartBarTitle}>View cart</Text>
                    <Text style={styles.rootCartBarSubtitle}>{cartCount} items · {money(cartTotal)}</Text>
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

function SectionHeader({ title, subtitle, light = false }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
      {subtitle ? <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text> : null}
    </View>
  );
}

function LoadingBlock({ label }) {
  return (
    <View style={styles.loadingWrap}>
      <ActivityIndicator size="large" color={COLORS.green800} />
      <Text style={styles.loadingText}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, text }) {
  return (
    <View style={styles.emptyCard}>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptyText}>{text}</Text>
    </View>
  );
}

function MetaBadge({ text }) {
  return (
    <View style={styles.metaBadge}>
      <Text style={styles.metaBadgeText}>{text}</Text>
    </View>
  );
}

function FreeDeliveryCard({ subtotal, remaining, progress }) {
  return (
    <View style={styles.freeDeliveryCard}>
      <View style={styles.freeDeliveryHeader}>
        <Ionicons name="bicycle-outline" size={18} color={COLORS.green800} />
        <Text style={styles.freeDeliveryTitle}>Free delivery progress</Text>
      </View>
      <Text style={styles.freeDeliveryText}>
        {subtotal <= 0
          ? `Add items worth ${money(FREE_DELIVERY_THRESHOLD)} to unlock free delivery.`
          : remaining > 0
            ? `Add ${money(remaining)} more to unlock free delivery.`
            : 'Free delivery unlocked for this basket.'}
      </Text>
      <View style={styles.progressTrack}>
        <View style={[styles.progressFill, { width: progress === 0 ? '0%' : `${Math.max(8, progress * 100)}%` }]} />
      </View>
    </View>
  );
}

function QuickDealCard({ item, qty = 0, onAdd, onRemove }) {
  return (
    <View style={styles.quickDealCard}>
      <View style={styles.quickDealTopRow}>
        <Text style={styles.quickDealOffer}>{getOfferLabel(item)}</Text>
        {qty > 0 && onAdd && onRemove ? (
          <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} compact />
        ) : (
          <TouchableOpacity style={styles.quickDealSelect} activeOpacity={0.9} onPress={onAdd}>
            <Text style={styles.quickDealSelectText}>{onAdd ? 'ADD' : 'Preview'}</Text>
          </TouchableOpacity>
        )}
      </View>
      <View style={styles.quickDealImageMock}>
        <Text style={styles.quickDealEmoji}>{item.emoji || pickEmoji(item.name)}</Text>
      </View>
      <Text style={styles.quickDealTitle} numberOfLines={1}>{item.name}</Text>
      <Text style={styles.quickDealSubtitle} numberOfLines={1}>{item.vendorName || item.brand}</Text>
      <View style={styles.quickDealBottomRow}>
        <Text style={styles.quickDealPrice}>{money(item.price)}</Text>
        <Text style={styles.quickDealMeta}>{item.vendorDistance != null ? `${item.vendorDistance.toFixed(1)} km` : 'Fast'}</Text>
      </View>
    </View>
  );
}

function FeatureTile({ item, compact = false }) {
  return (
    <View style={[styles.featureTile, compact && styles.featureTileCompact, { backgroundColor: item.tone }]}>
      <Text style={styles.featureTileEmoji}>{item.emoji}</Text>
      <Text style={styles.featureTileTitle}>{item.title}</Text>
      <Text style={styles.featureTileSubtitle}>{item.subtitle}</Text>
    </View>
  );
}

function MiniStoreCard({ vendor, onOpen, onToggleFavorite, favorite, tone, dineout = false }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.miniStoreCard} onPress={onOpen}>
      <View style={[styles.miniStoreHero, { backgroundColor: tone }]}>
        <View style={styles.miniStoreTopRow}>
          <View style={styles.miniOfferBadge}>
            <Text style={styles.miniOfferBadgeText}>{dineout ? 'UP TO 25% OFF' : getStoreOfferLabel(vendor)}</Text>
          </View>
          <TouchableOpacity onPress={onToggleFavorite} style={styles.favoriteButtonMini}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={COLORS.text} />
          </TouchableOpacity>
        </View>
        <View style={styles.miniAvatar}>
          <Text style={styles.miniAvatarText}>{initials(vendor.name)}</Text>
        </View>
      </View>
      <View style={styles.miniStoreContent}>
        <Text style={styles.miniStoreName} numberOfLines={1}>{vendor.name}</Text>
        <Text style={styles.miniStoreMeta} numberOfLines={1}>
          {dineout ? `${getVendorRating(vendor)} ★ · Booking friendly` : `${estimateEta(vendor)} · ${getDeliveryFeeLabel(vendor)}`}
        </Text>
      </View>
    </TouchableOpacity>
  );
}

function SceneEventCard({ item }) {
  return (
    <View style={[styles.sceneCard, { backgroundColor: item.tone }]}>
      <View style={styles.sceneCardTop}>
        <View style={styles.sceneDateBadge}>
          <Text style={styles.sceneDateText}>{item.date}</Text>
        </View>
        <Text style={styles.sceneEmoji}>{item.emoji}</Text>
      </View>
      <View style={styles.scenePricePill}>
        <Text style={styles.scenePricePillText}>Starts at {money(item.price)}</Text>
      </View>
      <Text style={styles.sceneTitle}>{item.title}</Text>
      <Text style={styles.sceneVenue}>{item.venue}</Text>
      <Text style={styles.sceneBucket}>{item.bucket}</Text>
    </View>
  );
}

function PastOrderCard({ order, compact = false }) {
  const firstItem = order.items?.[0];
  const itemLine = firstItem
    ? `${firstItem.qty || 1} x ${firstItem.name}${order.items.length > 1 ? ` +${order.items.length - 1} more` : ''}`
    : 'Order';

  return (
    <View style={[styles.pastOrderCard, compact && styles.pastOrderCardCompact]}>
      <View style={styles.pastOrderHeader}>
        <View style={styles.pastOrderThumb}>
          <Text style={styles.pastOrderThumbText}>{initials(order.vendorName)}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <View style={styles.pastOrderTitleRow}>
            <Text style={styles.pastOrderTitle} numberOfLines={1}>{order.vendorName}</Text>
            <Text style={styles.pastOrderStatus}>{order.status}</Text>
          </View>
          <Text style={styles.pastOrderLocation} numberOfLines={1}>{order.location}</Text>
        </View>
      </View>

      <Text style={styles.pastOrderItemLine}>{itemLine}</Text>
      <Text style={styles.pastOrderMeta}>
        Ordered: {order.orderedAt} · Bill Total: {money(order.total)}
      </Text>

      {!compact ? (
        <TouchableOpacity style={styles.reorderButton}>
          <Text style={styles.reorderButtonText}>REORDER</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function AccountListRow({ item, first }) {
  return (
    <View style={[styles.accountListRow, !first && styles.accountListRowBorder]}>
      <View style={styles.accountListIcon}>
        <Ionicons name={item.icon} size={18} color={COLORS.text} />
      </View>
      <Text style={styles.accountListLabel}>{item.label}</Text>
      <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
    </View>
  );
}

function StoreCard({ vendor, onOpen, onToggleFavorite, favorite, tone }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.storeCard} onPress={onOpen}>
      <View style={[styles.storeHero, { backgroundColor: tone }]}>
        <View style={styles.storeHeroBadgeRow}>
          <View style={styles.storePromoBadge}>
            <Text style={styles.storePromoBadgeText}>{estimateEta(vendor)}</Text>
          </View>
          <TouchableOpacity onPress={onToggleFavorite} style={styles.favoriteButton}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={COLORS.text} />
          </TouchableOpacity>
        </View>
        <View style={styles.storeHeroCircle}>
          <Text style={styles.storeHeroCircleText}>{initials(vendor.name)}</Text>
        </View>
      </View>

      <View style={styles.storeContent}>
        <View style={styles.storeTitleRow}>
          <Text style={styles.storeName} numberOfLines={1}>{vendor.name}</Text>
          <View style={styles.ratingPillSmall}>
            <Ionicons name="star" size={10} color="#14532d" />
            <Text style={styles.ratingPillText}>{getVendorRating(vendor)}</Text>
          </View>
        </View>
        <Text style={styles.storeDescription} numberOfLines={2}>
          {vendor.description || vendor.address || 'Daily essentials and groceries'}
        </Text>
        <View style={styles.vendorBadgeRow}>
          <MetaBadge text={vendor.open_now ? 'Open now' : 'Store'} />
          <MetaBadge text={`ETA ${estimateEta(vendor)}`} />
          <MetaBadge text={getDeliveryFeeLabel(vendor)} />
          {vendor.distance_km != null ? <MetaBadge text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
        </View>
        <View style={styles.storeCardBottom}>
          <Text style={styles.storeAddress} numberOfLines={1}>{vendor.address || 'Tap to browse the store'}</Text>
          <View style={styles.browsePill}>
            <Text style={styles.browsePillText}>Browse</Text>
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function ProductCard({ product, qty, onAdd, onRemove, featured = false }) {
  return (
    <View style={styles.productCard}>
      <View style={{ flex: 1, paddingRight: 12 }}>
        <View style={styles.productHeaderRow}>
          {featured ? <Text style={styles.featuredLabel}>BESTSELLER</Text> : <Text style={styles.featuredLabelMuted}>{getOfferLabel(product).toUpperCase()}</Text>}
          <View style={styles.ratingPillSmall}>
            <Ionicons name="star" size={10} color="#14532d" />
            <Text style={styles.ratingPillText}>4.4</Text>
          </View>
        </View>
        <Text style={styles.productTitle}>{product.name}</Text>
        <Text style={styles.productDescription}>{product.description || 'Store product'}</Text>
        <View style={styles.productMetaRow}>
          <Text style={styles.productPrice}>{money(product.price)}</Text>
          <Text style={styles.productMetaDot}>•</Text>
          <Text style={styles.productMetaText}>{qty > 0 ? `${qty} in basket` : 'Available now'}</Text>
        </View>
      </View>
      <View style={styles.productRightBlock}>
        <View style={styles.productImageMock}>
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

function QtyStepper({ qty, onAdd, onRemove, compact = false }) {
  return (
    <View style={[styles.qtyStepper, compact && styles.qtyStepperCompact]}>
      <TouchableOpacity style={styles.qtyAction} onPress={onRemove}>
        <Ionicons name="remove" size={16} color={COLORS.text} />
      </TouchableOpacity>
      <Text style={styles.qtyText}>{qty}</Text>
      <TouchableOpacity style={styles.qtyAction} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

function SummaryRow({ label, value, strong = false }) {
  return (
    <View style={styles.summaryRow}>
      <Text style={[styles.summaryLabel, strong && styles.summaryLabelStrong]}>{label}</Text>
      <Text style={[styles.summaryValue, strong && styles.summaryValueStrong]}>{value}</Text>
    </View>
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
  screen: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  homeScrollContent: {
    paddingBottom: 172,
  },
  pageContent: {
    padding: 16,
    paddingBottom: 110,
    gap: 16,
  },
  pageContentWithFloat: {
    padding: 16,
    paddingBottom: 120,
    gap: 16,
  },
  heroShell: {
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 22,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  heroEta: {
    color: '#ffffff',
    fontSize: 40,
    fontWeight: '900',
    lineHeight: 44,
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
    lineHeight: 34,
  },
  heroTitleLight: {
    color: '#ffffff',
    fontSize: 26,
    fontWeight: '900',
    lineHeight: 32,
  },
  addressRow: {
    marginTop: 6,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  addressText: {
    flex: 1,
    color: 'rgba(255,255,255,0.92)',
    fontSize: 15,
    fontWeight: '700',
  },
  profileButton: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
  },
  serviceRail: {
    gap: 10,
    paddingTop: 18,
  },
  serviceCard: {
    width: 92,
    minHeight: 88,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.10)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.10)',
  },
  serviceIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceIconWrapActive: {
    backgroundColor: '#ffffff',
  },
  serviceLabel: {
    marginTop: 8,
    color: 'rgba(255,255,255,0.84)',
    fontWeight: '700',
    fontSize: 14,
  },
  serviceLabelActive: {
    color: '#ffffff',
  },
  heroSearchRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 18,
  },
  searchBoxHero: {
    flex: 1,
    minHeight: 56,
    borderRadius: 16,
    backgroundColor: '#ffffff',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    gap: 10,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 16,
  },
  bookmarkButton: {
    width: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
  },
  shortcutRail: {
    gap: 18,
    paddingTop: 16,
    paddingBottom: 4,
  },
  shortcutItem: {
    alignItems: 'center',
    minWidth: 60,
  },
  shortcutIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  shortcutIconWrapActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
  },
  shortcutLabel: {
    marginTop: 8,
    color: '#d1fae5',
    fontWeight: '700',
    fontSize: 12,
  },
  shortcutLabelActive: {
    color: '#ffffff',
  },
  shortcutUnderline: {
    height: 3,
    width: 20,
    marginTop: 8,
    borderRadius: 999,
    backgroundColor: '#ffffff',
  },
  shortcutUnderlineHidden: {
    opacity: 0,
  },
  celebrationWrap: {
    alignItems: 'center',
    marginTop: 18,
  },
  celebrationEyebrow: {
    color: '#d8ffe9',
    fontSize: 13,
    letterSpacing: 3,
    fontWeight: '700',
  },
  celebrationTitle: {
    color: COLORS.pinkSoft,
    fontSize: 30,
    fontWeight: '900',
    marginTop: 6,
  },
  festivalRail: {
    gap: 12,
    paddingTop: 14,
  },
  festivalCard: {
    width: 128,
    borderRadius: 22,
    backgroundColor: COLORS.pink,
    paddingHorizontal: 12,
    paddingVertical: 14,
    justifyContent: 'space-between',
  },
  festivalTitle: {
    color: '#3c2546',
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 21,
    minHeight: 46,
  },
  festivalEmoji: {
    fontSize: 34,
    textAlign: 'right',
    marginTop: 18,
  },
  bodySurface: {
    padding: 16,
    gap: 18,
    backgroundColor: COLORS.bg,
  },
  bodySurfaceDark: {
    padding: 16,
    gap: 18,
    backgroundColor: COLORS.dark900,
  },
  everydayBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 18,
    shadowColor: '#000000',
    shadowOpacity: 0.06,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 6 },
    elevation: 2,
  },
  everydayBadge: {
    width: 64,
    height: 64,
    borderRadius: 18,
    backgroundColor: '#eef2ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  everydayPrice: {
    color: '#4338ca',
    fontSize: 28,
    fontWeight: '900',
  },
  everydayTitle: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  everydayText: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '600',
    marginTop: 4,
    lineHeight: 20,
  },
  foodHeroBanner: {
    borderRadius: 28,
    padding: 22,
  },
  foodHeroEyebrow: {
    color: '#fef3c7',
    fontSize: 15,
    fontWeight: '900',
    letterSpacing: 1,
  },
  foodHeroTitle: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
    marginTop: 8,
  },
  foodHeroText: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  dineoutBanner: {
    backgroundColor: '#166534',
    borderRadius: 24,
    padding: 20,
    flexDirection: 'row',
    alignItems: 'center',
  },
  dineoutBannerTitle: {
    color: '#ffffff',
    fontSize: 26,
    fontWeight: '900',
  },
  dineoutBannerText: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
  dineoutBannerEmoji: {
    fontSize: 46,
    marginLeft: 12,
  },
  sectionHeader: {
    gap: 6,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
  },
  sectionTitleLight: {
    color: '#ffffff',
  },
  sectionSubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  sectionSubtitleLight: {
    color: 'rgba(255,255,255,0.72)',
  },
  tileGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  featureTile: {
    width: '48%',
    minHeight: 126,
    borderRadius: 22,
    padding: 16,
    justifyContent: 'space-between',
  },
  featureTileCompact: {
    width: 188,
  },
  featureTileEmoji: {
    fontSize: 28,
  },
  featureTileTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  featureTileSubtitle: {
    color: COLORS.text,
    opacity: 0.7,
    fontSize: 13,
    lineHeight: 18,
    marginTop: 6,
  },
  quickGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  quickDealCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 22,
    padding: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  quickDealTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  quickDealOffer: {
    backgroundColor: COLORS.yellowSoft,
    color: '#92400e',
    fontSize: 11,
    fontWeight: '800',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
  },
  quickDealSelect: {
    backgroundColor: COLORS.green100,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  quickDealSelectText: {
    color: COLORS.green900,
    fontSize: 12,
    fontWeight: '900',
  },
  quickDealImageMock: {
    height: 90,
    borderRadius: 18,
    backgroundColor: COLORS.blueSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 14,
  },
  quickDealEmoji: {
    fontSize: 40,
  },
  quickDealTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  quickDealSubtitle: {
    color: COLORS.muted,
    fontSize: 12,
    marginTop: 4,
  },
  quickDealBottomRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
  },
  quickDealPrice: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  quickDealMeta: {
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '700',
  },
  suggestionRail: {
    gap: 10,
  },
  suggestionChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  suggestionChipText: {
    color: COLORS.green800,
    fontWeight: '700',
    fontSize: 13,
  },
  filterRail: {
    gap: 10,
  },
  filterChip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  filterChipActive: {
    backgroundColor: COLORS.green050,
    borderColor: '#bbf7d0',
  },
  filterChipText: {
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  filterChipTextActive: {
    color: COLORS.green900,
  },
  darkFilterChip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: COLORS.dark700,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  darkFilterChipActive: {
    backgroundColor: '#ffffff',
  },
  darkFilterChipText: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 13,
    fontWeight: '700',
  },
  darkFilterChipTextActive: {
    color: COLORS.dark900,
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  categoryTile: {
    width: '22%',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    paddingVertical: 14,
    paddingHorizontal: 10,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  categoryEmoji: {
    fontSize: 26,
  },
  categoryTitle: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
    textAlign: 'center',
    marginTop: 8,
  },
  categoryGridLarge: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  categoryLargeTile: {
    width: '48%',
    borderRadius: 24,
    padding: 18,
    minHeight: 134,
    justifyContent: 'space-between',
  },
  categoryLargeEmoji: {
    fontSize: 34,
  },
  categoryLargeTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  categoryLargeTitleLight: {
    color: '#ffffff',
  },
  categoryLargeHint: {
    color: COLORS.text,
    opacity: 0.7,
    fontSize: 13,
    fontWeight: '700',
  },
  categoryLargeHintLight: {
    color: 'rgba(255,255,255,0.7)',
  },
  miniRail: {
    gap: 12,
  },
  miniStoreCard: {
    width: 170,
    backgroundColor: '#ffffff',
    borderRadius: 22,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  miniStoreHero: {
    padding: 12,
  },
  miniStoreTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  miniOfferBadge: {
    backgroundColor: 'rgba(255,255,255,0.76)',
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },
  miniOfferBadgeText: {
    color: COLORS.text,
    fontSize: 10,
    fontWeight: '900',
  },
  favoriteButtonMini: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.76)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  miniAvatar: {
    width: 62,
    height: 62,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.78)',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
  },
  miniAvatarText: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
  },
  miniStoreContent: {
    padding: 12,
  },
  miniStoreName: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },
  miniStoreMeta: {
    color: COLORS.muted,
    fontSize: 12,
    marginTop: 6,
    lineHeight: 17,
  },
  bucketPill: {
    minWidth: 128,
    height: 92,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 16,
  },
  bucketPillText: {
    color: '#ffffff',
    fontSize: 17,
    fontWeight: '900',
    textAlign: 'center',
  },
  sceneGrid: {
    gap: 12,
  },
  sceneCard: {
    borderRadius: 24,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.06)',
  },
  sceneCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  sceneDateBadge: {
    backgroundColor: 'rgba(255,255,255,0.10)',
    borderRadius: 14,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  sceneDateText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
  },
  sceneEmoji: {
    fontSize: 28,
  },
  scenePricePill: {
    marginTop: 14,
    alignSelf: 'flex-start',
    backgroundColor: '#ec4899',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 999,
  },
  scenePricePillText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
  },
  sceneTitle: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '900',
    marginTop: 14,
  },
  sceneVenue: {
    color: 'rgba(255,255,255,0.74)',
    fontSize: 13,
    lineHeight: 18,
    marginTop: 6,
  },
  sceneBucket: {
    color: '#fbcfe8',
    fontSize: 12,
    fontWeight: '800',
    marginTop: 12,
  },
  storeCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  storeHero: {
    padding: 16,
  },
  storeHeroBadgeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  storePromoBadge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.7)',
  },
  storePromoBadgeText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '900',
  },
  favoriteButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.7)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeHeroCircle: {
    width: 74,
    height: 74,
    borderRadius: 37,
    backgroundColor: 'rgba(255,255,255,0.82)',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
  },
  storeHeroCircleText: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
  },
  storeContent: {
    padding: 16,
    gap: 10,
  },
  storeTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  storeName: {
    flex: 1,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  storeDescription: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  vendorBadgeRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  metaBadge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: '#f3f4f6',
  },
  metaBadgeText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  storeCardBottom: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  storeAddress: {
    flex: 1,
    color: COLORS.subtle,
    fontSize: 13,
  },
  browsePill: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: COLORS.green050,
  },
  browsePillText: {
    color: COLORS.green900,
    fontSize: 12,
    fontWeight: '900',
  },
  ratingPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#dcfce7',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  ratingPillSmall: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#dcfce7',
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },
  ratingPillText: {
    color: '#14532d',
    fontSize: 12,
    fontWeight: '900',
  },
  loadingWrap: {
    paddingVertical: 24,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  loadingText: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '700',
  },
  emptyCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  emptyText: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  panelCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  panelTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    marginBottom: 12,
  },
  panelText: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '800',
  },
  panelSubText: {
    color: COLORS.muted,
    fontSize: 14,
    marginTop: 4,
    marginBottom: 16,
  },
  simpleListRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
  },
  simpleListIcon: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: COLORS.purpleSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  simpleListIconText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  simpleListTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  simpleListMeta: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 3,
  },
  accountHeaderCard: {
    backgroundColor: '#d9465f',
    borderRadius: 28,
    padding: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  accountName: {
    color: '#ffffff',
    fontSize: 32,
    fontWeight: '900',
  },
  accountPhone: {
    color: 'rgba(255,255,255,0.92)',
    fontSize: 18,
    marginTop: 10,
  },
  accountEmail: {
    color: 'rgba(255,255,255,0.92)',
    fontSize: 16,
    marginTop: 4,
  },
  helpButton: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  helpButtonText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 14,
  },
  oneCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  oneBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#dcfce7',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
    marginBottom: 10,
  },
  oneBadgeText: {
    color: '#166534',
    fontSize: 11,
    fontWeight: '900',
  },
  oneTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
  },
  oneSubText: {
    color: COLORS.muted,
    fontSize: 14,
    marginTop: 4,
  },
  accountQuickGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  accountQuickCard: {
    width: '22%',
    minHeight: 102,
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 10,
    paddingVertical: 14,
    justifyContent: 'space-between',
  },
  accountQuickText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 16,
    marginTop: 10,
  },
  accountListCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  accountListRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    gap: 12,
  },
  accountListRowBorder: {
    borderTopWidth: 1,
    borderTopColor: '#eef0f3',
  },
  accountListIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  accountListLabel: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  segmentWrap: {
    backgroundColor: '#eef0f3',
    borderRadius: 18,
    padding: 4,
    flexDirection: 'row',
  },
  segmentButton: {
    flex: 1,
    borderRadius: 14,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  segmentButtonActive: {
    backgroundColor: '#111827',
  },
  segmentButtonText: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '800',
  },
  segmentButtonTextActive: {
    color: '#ffffff',
  },
  pastOrderCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  pastOrderCardCompact: {
    paddingBottom: 14,
  },
  pastOrderHeader: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'center',
  },
  pastOrderThumb: {
    width: 54,
    height: 54,
    borderRadius: 16,
    backgroundColor: COLORS.yellowSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pastOrderThumbText: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  pastOrderTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  pastOrderTitle: {
    flex: 1,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  pastOrderStatus: {
    color: '#16a34a',
    fontSize: 13,
    fontWeight: '900',
  },
  pastOrderLocation: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },
  pastOrderItemLine: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 14,
  },
  pastOrderMeta: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 8,
  },
  reorderButton: {
    marginTop: 16,
    backgroundColor: '#fde7df',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  reorderButtonText: {
    color: '#ea580c',
    fontSize: 14,
    fontWeight: '900',
  },
  innerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 10,
    backgroundColor: COLORS.bg,
  },
  iconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  innerHeaderTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  innerHeaderSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 2,
  },
  vendorHeroCard: {
    backgroundColor: '#ffffff',
    borderRadius: 26,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  vendorHeroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  vendorInitialBadge: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: COLORS.purpleSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorInitialBadgeText: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  vendorHeroTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
  },
  vendorHeroText: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
    marginBottom: 14,
  },
  searchBoxPlain: {
    minHeight: 56,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  productCard: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
  },
  productHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 8,
  },
  featuredLabel: {
    color: '#065f46',
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.6,
  },
  featuredLabelMuted: {
    color: COLORS.subtle,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.6,
  },
  productTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
  },
  productDescription: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    marginTop: 6,
  },
  productMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 12,
  },
  productPrice: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  productMetaDot: {
    color: COLORS.subtle,
    fontSize: 14,
  },
  productMetaText: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  productRightBlock: {
    alignItems: 'center',
    gap: 10,
  },
  productImageMock: {
    width: 88,
    height: 88,
    borderRadius: 20,
    backgroundColor: COLORS.blueSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  productEmoji: {
    fontSize: 40,
  },
  addButton: {
    minWidth: 78,
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 14,
    backgroundColor: COLORS.green100,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  addButtonText: {
    color: COLORS.green900,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyStepper: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    minWidth: 94,
    borderRadius: 14,
    backgroundColor: COLORS.green100,
    paddingHorizontal: 6,
    paddingVertical: 4,
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  qtyStepperCompact: {
    minWidth: 82,
  },
  qtyAction: {
    width: 28,
    height: 28,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    color: COLORS.green900,
    fontSize: 14,
    fontWeight: '900',
  },
  floatingCart: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 16,
    backgroundColor: COLORS.green800,
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#000000',
    shadowOpacity: 0.16,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
  },
  floatingCartTitle: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '900',
  },
  floatingCartSubtitle: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 13,
    marginTop: 4,
  },
  freeDeliveryCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  freeDeliveryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  freeDeliveryTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
  },
  freeDeliveryText: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  progressTrack: {
    marginTop: 14,
    height: 10,
    borderRadius: 999,
    backgroundColor: '#e5e7eb',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
    backgroundColor: COLORS.green800,
  },
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#eef0f3',
  },
  cartLineTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  cartLineMeta: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  summaryLabel: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '700',
  },
  summaryLabelStrong: {
    color: COLORS.text,
    fontWeight: '900',
  },
  summaryValue: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  summaryValueStrong: {
    fontSize: 18,
    fontWeight: '900',
  },
  summaryDivider: {
    height: 1,
    backgroundColor: '#eef0f3',
    marginVertical: 8,
  },
  primaryButton: {
    backgroundColor: COLORS.green800,
    borderRadius: 18,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '900',
  },
  secondaryButton: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  secondaryButtonText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
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