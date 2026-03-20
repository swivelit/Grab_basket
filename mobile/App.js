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
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
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
  { key: 'navratri', title: 'Chaitra Navratri', emoji: '🪔' },
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
  { key: 'food-deal', title: 'Binge worthy deals', subtitle: 'Up to 60% off & more', emoji: '🍕', tone: '#fde68a' },
  { key: 'eatright', title: 'EatRight', subtitle: 'Win up to ₹300 free cash', emoji: '🥗', tone: '#fbcfe8' },
  { key: 'awards', title: 'Restaurant awards', subtitle: 'Vote, share and win', emoji: '🏆', tone: '#fef08a' },
];

const FOOD_DISCOVERY = ['Restaurants near me', 'Pre-book offers', 'Late night', 'Cafe desserts'];

const DINEOUT_PROMO_TILES = [
  { key: 'flat50', title: 'Flat 50% off', subtitle: 'On table bookings', emoji: '🎉', tone: '#fde68a' },
  { key: 'girf', title: 'GIRF Hall of Fame', subtitle: 'Best dineout picks', emoji: '🏆', tone: '#ede9fe' },
  { key: 'family', title: 'Family-friendly spots', subtitle: 'Comfortable and kid-friendly', emoji: '🍽️', tone: '#dcfce7' },
  { key: 'cafes', title: 'Cafes & quick bites', subtitle: 'Coffee, snacks and desserts', emoji: '☕', tone: '#dbeafe' },
];

const DINEOUT_DISCOVERY = ['Restaurants near me', 'Pre-book offers', 'Quick bites', 'Premium dining', 'Rooftop'];

const SCENE_FILTERS = ['All', 'Today', 'This Week', 'This Weekend', 'Next Weekend'];

const SCENE_EVENTS = [
  { id: 'scene-1', title: 'Rage Room at Break N Chill', venue: 'Break N Chill · Chittethukara', price: 299, date: '20 MAR', bucket: 'Today', emoji: '💥', tone: '#2b0b16' },
  { id: 'scene-2', title: 'Pottery Wheel Throwing Workshop', venue: 'Soil to Soul Ceramics · Kadavanthra', price: 1000, date: '20 MAR', bucket: 'This Week', emoji: '🏺', tone: '#3a2c25' },
  { id: 'scene-3', title: 'Kimchi Culture', venue: 'Skei Presents · Kochi', price: 699, date: '22 MAR', bucket: 'This Weekend', emoji: '🎎', tone: '#5f1015' },
  { id: 'scene-4', title: 'Stand-up Comedy Night', venue: 'Laugh Club · Kakkanad', price: 499, date: '23 MAR', bucket: 'This Weekend', emoji: '🎤', tone: '#1e293b' },
  { id: 'scene-5', title: 'Kids Creative Lab', venue: 'Mini Makers · Panampilly', price: 399, date: '29 MAR', bucket: 'Next Weekend', emoji: '🎨', tone: '#3b1f65' },
];

const ACCOUNT_SHORTCUTS = [
  { key: 'address', icon: 'location-outline', label: 'Saved Address' },
  { key: 'payment', icon: 'card-outline', label: 'Payment Modes' },
  { key: 'refunds', icon: 'reload-outline', label: 'My Refunds' },
  { key: 'wallet', icon: 'wallet-outline', label: 'Swiggy Money' },
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

function findVendorById(list, id) {
  return list.find((item) => String(item.id) === String(id)) || null;
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

const GrabBasketContext = createContext(null);

export function useGrabBasket() {
  const value = useContext(GrabBasketContext);

  if (!value) {
    throw new Error('useGrabBasket must be used inside GrabBasketProvider');
  }

  return value;
}

export function GrabBasketProvider({ children }) {
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

  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [favorites, setFavorites] = useState({});
  const [recentStoreIds, setRecentStoreIds] = useState([]);
  const [recentSearches, setRecentSearches] = useState([]);
  const [orderHistory, setOrderHistory] = useState([]);

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
      const params = new URLSearchParams();

      if (String(searchValue || '').trim()) {
        params.set('q', String(searchValue).trim());
      }

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
    return findVendorById(vendors, cart.vendorId);
  }, [vendors, cart.vendorId]);

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
    () => recentStoreIds.map((id) => findVendorById(vendors, id)).filter(Boolean),
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
      if (cart.vendorId && String(cart.vendorId) !== String(product.vendor_id)) {
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

    Alert.alert(
      'Demo order placed',
      'Saved locally so your Reorder and Account screens now feel much closer to a real flow.'
    );

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
    sceneFilter,
    setSceneFilter,
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
    sceneEvents,
    pastOrders,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    applyHomeSearch,
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
      router.push({
        pathname: '/store/[vendorId]',
        params: { vendorId: String(vendor.id) },
      });
    },
    [rememberStore, router]
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
    <TouchableOpacity
      activeOpacity={0.95}
      style={[styles.storeCard, dark && styles.storeCardDark]}
      onPress={onOpen}>
      <View style={[styles.storeAvatar, { backgroundColor: getStoreTone(Number(vendor?.id || 0)) }]}>
        <Text style={styles.storeAvatarText}>{initials(vendor.name)}</Text>
      </View>

      <View style={styles.storeContent}>
        <View style={styles.storeTitleRow}>
          <Text style={[styles.storeName, dark && styles.storeNameDark]} numberOfLines={1}>
            {vendor.name}
          </Text>
          <TouchableOpacity onPress={onToggleFavorite}>
            <Ionicons
              name={favorite ? 'heart' : 'heart-outline'}
              size={18}
              color={dark ? '#ffffff' : COLORS.text}
            />
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
      <Text style={styles.orderMeta}>
        Ordered: {order.orderedAt} · Bill Total: {money(order.total)}
      </Text>
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

  const isEta = theme.headlineType === 'eta';

  return (
    <View style={[styles.hero, { backgroundColor: theme.hero }]}>
      <View style={styles.heroTop}>
        <View style={{ flex: 1 }}>
          <Text style={isEta ? styles.heroEta : styles.heroTitle}>{theme.headline}</Text>
          <TouchableOpacity style={styles.addressRow} activeOpacity={0.9}>
            <Text style={styles.addressText} numberOfLines={1}>{theme.address}</Text>
            <Ionicons name="chevron-down" size={16} color="#d1fae5" />
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
              style={[
                styles.servicePill,
                active && { backgroundColor: theme.heroAccent, borderColor: 'rgba(255,255,255,0.3)' },
              ]}
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
        <Ionicons
          name={activeService === 'food' ? 'mic-outline' : 'receipt-outline'}
          size={18}
          color={COLORS.muted}
        />
      </View>

      {activeService === 'instamart' ? (
        <>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.shortcutRow}>
            {HOME_SHORTCUTS.map((item) => {
              const active = activeShortcut === item.key;
              return (
                <TouchableOpacity
                  key={item.key}
                  style={[styles.shortcutChip, active && styles.shortcutChipActive]}
                  onPress={() => setActiveShortcut(item.key)}>
                  <Ionicons name={item.icon} size={14} color={active ? '#ffffff' : '#d1fae5'} />
                  <Text style={[styles.shortcutChipText, active && styles.shortcutChipTextActive]}>
                    {item.label}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.festivalRow}>
            {FESTIVAL_TILES.map((item) => (
              <View key={item.key} style={styles.festivalCard}>
                <Text style={styles.festivalEmoji}>{item.emoji}</Text>
                <Text style={styles.festivalTitle}>{item.title}</Text>
              </View>
            ))}
          </ScrollView>
        </>
      ) : null}
    </View>
  );
}

function InstamartServiceSection() {
  const {
    homeDealsLoading,
    homeDeals,
    cart,
    addToCart,
    updateQty,
    suggestionPool,
    applyHomeSearch,
    storeFilter,
    setStoreFilter,
    recentVendors,
    featuredVendors,
    favoriteVendors,
    favorites,
    toggleFavorite,
  } = useGrabBasket();

  const openVendor = useOpenVendor();

  return (
    <View style={styles.surface}>
      <View style={styles.bannerCard}>
        <Text style={styles.bannerTitle}>everyday</Text>
        <Text style={styles.bannerText}>Shop for ₹199 to get one item at ₹9</Text>
      </View>

      <SectionHeader title="Under ₹99" subtitle="Fast add-ons and daily essentials." />
      {homeDealsLoading ? <LoadingBlock label="Loading quick picks..." /> : null}

      <View style={styles.dealGrid}>
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

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {suggestionPool.map((item) => (
          <TouchableOpacity key={item} style={styles.chip} onPress={() => applyHomeSearch(item)}>
            <Ionicons name="search-outline" size={14} color={COLORS.green800} />
            <Text style={styles.chipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
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
          <SectionHeader title="Recently opened" subtitle="Bring users back to trusted stores." />
          {recentVendors.slice(0, 2).map((vendor) => (
            <StoreCard
              key={`recent-${vendor.id}`}
              vendor={vendor}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
            />
          ))}
        </>
      ) : null}

      <SectionHeader title="Featured stores" subtitle={`${featuredVendors.length} stores shown.`} />
      {featuredVendors.length === 0 ? (
        <EmptyState title="No stores found" text="Seed vendors and products in the backend and this feed will fill out." />
      ) : (
        featuredVendors.map((vendor) => (
          <StoreCard
            key={vendor.id}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))
      )}

      {favoriteVendors.length > 0 ? (
        <>
          <SectionHeader title="Saved stores" subtitle="Useful even before auth is added back." />
          {favoriteVendors.slice(0, 2).map((vendor) => (
            <StoreCard
              key={`favorite-${vendor.id}`}
              vendor={vendor}
              favorite={!!favorites[vendor.id]}
              onOpen={() => openVendor(vendor)}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
            />
          ))}
        </>
      ) : null}
    </View>
  );
}

function FoodServiceSection() {
  const {
    homeDeals,
    vendorsLoading,
    featuredVendors,
    cart,
    addToCart,
    updateQty,
    favorites,
    toggleFavorite,
    applyHomeSearch,
  } = useGrabBasket();

  const openVendor = useOpenVendor();

  return (
    <View style={styles.surface}>
      <View style={[styles.bannerCard, { backgroundColor: COLORS.purple100 }]}>
        <Text style={styles.bannerTitle}>Up to 60% off & more</Text>
        <Text style={styles.bannerText}>Sharper merchandising, stronger visual hierarchy.</Text>
      </View>

      <View style={styles.promoGrid}>
        {FOOD_PROMO_TILES.map((item) => (
          <PromoTile key={item.key} item={item} />
        ))}
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {FOOD_DISCOVERY.map((item) => (
          <TouchableOpacity key={item} style={styles.filterChip} onPress={() => applyHomeSearch(item)}>
            <Text style={styles.filterChipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <SectionHeader title="Top rated near you" subtitle="Closer to Swiggy-style food discovery blocks." />

      {vendorsLoading ? (
        <LoadingBlock label="Loading restaurants..." />
      ) : featuredVendors.length === 0 ? (
        <EmptyState title="No restaurants yet" text="Add more food vendors on the backend to make this feed feel full." />
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
          {featuredVendors.map((vendor) => (
            <View key={`food-${vendor.id}`} style={styles.railCard}>
              <StoreCard
                vendor={vendor}
                favorite={!!favorites[vendor.id]}
                onOpen={() => openVendor(vendor)}
                onToggleFavorite={() => toggleFavorite(vendor.id)}
              />
            </View>
          ))}
        </ScrollView>
      )}

      <SectionHeader title="Crave under ₹99" subtitle="Great for quick wins and add-to-cart behavior." />
      <View style={styles.dealGrid}>
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
}

function DineoutServiceSection() {
  const { vendorsLoading, featuredVendors, favorites, toggleFavorite, applyHomeSearch } = useGrabBasket();
  const openVendor = useOpenVendor();

  return (
    <View style={styles.surface}>
      <View style={[styles.bannerCard, { backgroundColor: COLORS.yellowSoft }]}>
        <Text style={styles.bannerTitle}>Flat 25% OFF</Text>
        <Text style={styles.bannerText}>+10% cashback with dineout-style campaigns.</Text>
      </View>

      <SectionHeader title="In the spotlight" subtitle="Curated booking hooks and campaign-led discovery." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {DINEOUT_PROMO_TILES.map((item) => (
          <PromoTile key={item.key} item={item} />
        ))}
      </ScrollView>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {DINEOUT_DISCOVERY.map((item) => (
          <TouchableOpacity key={item} style={styles.filterChip} onPress={() => applyHomeSearch(item)}>
            <Text style={styles.filterChipText}>{item}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <SectionHeader title="Popular picks" subtitle="Swap these later with real booking inventory." />
      {vendorsLoading ? (
        <LoadingBlock label="Loading popular picks..." />
      ) : featuredVendors.length === 0 ? (
        <EmptyState title="No dineout picks yet" text="Use the same vendor base now, then split food vs dineout later." />
      ) : (
        featuredVendors.map((vendor) => (
          <StoreCard
            key={`dineout-${vendor.id}`}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))
      )}
    </View>
  );
}

function ScenesServiceSection() {
  const { sceneFilter, setSceneFilter, sceneEvents } = useGrabBasket();

  return (
    <View style={styles.darkSurface}>
      <SectionHeader
        title="When is the plan?"
        subtitle="Buckets, featured cards and event blocks."
        light
      />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {SCENE_FILTERS.map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.filterChipDark, sceneFilter === item && styles.filterChipDarkActive]}
            onPress={() => setSceneFilter(item)}>
            <Text style={[styles.filterChipTextDark, sceneFilter === item && styles.filterChipTextDarkActive]}>
              {item}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {sceneEvents.length === 0 ? (
        <EmptyState title="No scenes" text="Try another bucket." light />
      ) : (
        sceneEvents.map((item) => (
          <View key={item.id} style={[styles.sceneCard, { backgroundColor: item.tone }]}>
            <View style={styles.sceneTop}>
              <Text style={styles.sceneDate}>{item.date}</Text>
              <Text style={styles.sceneEmoji}>{item.emoji}</Text>
            </View>
            <Text style={styles.sceneTitle}>{item.title}</Text>
            <Text style={styles.sceneVenue}>{item.venue}</Text>
            <Text style={styles.scenePrice}>Starts at {money(item.price)}</Text>
          </View>
        ))
      )}
    </View>
  );
}

export function HomeScreen() {
  const {
    activeService,
    refreshing,
    loadVendors,
    theme,
    cartCount,
    cartTotal,
    cartSubtotal,
    freeDeliveryRemaining,
  } = useGrabBasket();

  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const deliveryStripText =
    cartSubtotal <= 0
      ? `FREE DELIVERY on orders above ${money(FREE_DELIVERY_THRESHOLD)}`
      : freeDeliveryRemaining > 0
        ? `Add ${money(freeDeliveryRemaining)} more for FREE DELIVERY`
        : 'FREE DELIVERY unlocked';

  const isScenes = activeService === 'scenes';

  return (
    <SafeAreaView style={[styles.safeArea, isScenes && { backgroundColor: COLORS.dark900 }]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={theme.hero}
      />

      <View style={styles.screen}>
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={[
            styles.scrollContent,
            { paddingBottom: tabBarHeight + 120 },
            isScenes && { backgroundColor: COLORS.dark900 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => loadVendors({ pullToRefresh: true })}
              tintColor="#ffffff"
            />
          }>
          <HomeHeroSection />
          {activeService === 'instamart' && <InstamartServiceSection />}
          {activeService === 'food' && <FoodServiceSection />}
          {activeService === 'dineout' && <DineoutServiceSection />}
          {activeService === 'scenes' && <ScenesServiceSection />}
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
  const router = useRouter();

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
    <SafeAreaView style={[styles.safeArea, activeService === 'scenes' && { backgroundColor: COLORS.dark900 }]}>
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
                ? 'Cuisine-led discovery that feels closer to delivery patterns.'
                : activeService === 'dineout'
                  ? 'Mood-led dineout discovery for bookings and offers.'
                  : 'Experience buckets and time-based discovery.'
          }
          light={activeService === 'scenes'}
        />

        <View style={styles.categoryGrid}>
          {tiles.map((item, index) => (
            <TouchableOpacity
              key={item.key}
              activeOpacity={0.92}
              style={[
                styles.categoryTileLarge,
                {
                  backgroundColor: activeService === 'scenes' ? COLORS.dark700 : getStoreTone(index),
                },
              ]}
              onPress={() => router.push('/')}>
              <Text style={styles.categoryEmoji}>{item.emoji}</Text>
              <Text
                style={[
                  styles.categoryTitle,
                  activeService === 'scenes' && { color: '#ffffff' },
                ]}>
                {item.title}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

export function ReorderScreen() {
  const {
    cartCount,
    cartVendor,
    cartTotal,
    pastOrderFilter,
    setPastOrderFilter,
    pastOrders,
    recentVendors,
  } = useGrabBasket();

  const router = useRouter();
  const openVendor = useOpenVendor();

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <SectionHeader title="Reorder" subtitle="Local order history makes this useful even before auth is wired." />

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
    </SafeAreaView>
  );
}

export function AccountScreen() {
  const { pastOrderFilter, setPastOrderFilter, pastOrders } = useGrabBasket();

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <View style={styles.accountHeaderCard}>
          <View>
            <Text style={styles.accountName}>Guest</Text>
            <Text style={styles.accountSubText}>+91 - 0000000000</Text>
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
            <Text style={styles.panelSubText}>Explore all membership benefits</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
        </View>

        <View style={styles.quickGrid}>
          {ACCOUNT_SHORTCUTS.map((item) => (
            <View key={item.key} style={styles.quickCard}>
              <Ionicons name={item.icon} size={20} color={COLORS.text} />
              <Text style={styles.quickCardText}>{item.label}</Text>
            </View>
          ))}
        </View>

        <View style={styles.accountListCard}>
          {ACCOUNT_ROWS.map((item, index) => (
            <View
              key={item.label}
              style={[styles.accountListRow, index !== 0 && styles.accountListRowBorder]}>
              <Ionicons name={item.icon} size={18} color={COLORS.text} />
              <Text style={styles.accountListLabel}>{item.label}</Text>
              <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
            </View>
          ))}
        </View>

        <SectionHeader title="Past orders" subtitle="Guest-mode friendly order history." />

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
    </SafeAreaView>
  );
}

export function VendorDetailsScreen() {
  const { vendorId } = useLocalSearchParams();
  const router = useRouter();

  const {
    vendors,
    vendorsLoading,
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
    if (vendor?.id) {
      rememberStore(vendor.id);
    }
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
    return (
      <SafeAreaView style={styles.safeArea}>
        <LoadingBlock label="Loading store..." />
      </SafeAreaView>
    );
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
        <TouchableOpacity
          style={styles.iconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>

        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{vendor.name}</Text>
          <Text style={styles.innerHeaderSubtitle}>
            {estimateEta(vendor)} · {vendor?.open_now ? 'Open now' : 'Store details'}
          </Text>
        </View>

        <TouchableOpacity style={styles.iconButton} onPress={() => toggleFavorite(vendor.id)}>
          <Ionicons
            name={favorites[vendor.id] ? 'heart' : 'heart-outline'}
            size={18}
            color={COLORS.text}
          />
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContentWithFloat}>
        <View style={styles.vendorHeroCard}>
          <View style={styles.vendorHeroTop}>
            <View style={styles.vendorInitialBadge}>
              <Text style={styles.vendorInitialBadgeText}>{initials(vendor.name)}</Text>
            </View>
            <MetaBadge text={`${getVendorRating(vendor)} ★`} />
          </View>

          <Text style={styles.vendorHeroTitle}>{vendor.name}</Text>
          <Text style={styles.vendorHeroText}>
            {vendor.description || vendor.address || 'Quick grocery and essentials store'}
          </Text>

          <View style={styles.badgeRow}>
            <MetaBadge text={vendor?.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={`ETA ${estimateEta(vendor)}`} />
            <MetaBadge text={getDeliveryFeeLabel(vendor)} />
            {vendor?.distance_km != null ? <MetaBadge text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
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

        {productsLoading ? <LoadingBlock label="Loading products..." /> : null}

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
  const {
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
        <TouchableOpacity
          style={styles.iconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
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
                <View
                  style={[
                    styles.progressFill,
                    {
                      width:
                        freeDeliveryProgress === 0
                          ? '0%'
                          : `${Math.max(8, freeDeliveryProgress * 100)}%`,
                    },
                  ]}
                />
              </View>
            </View>

            <View style={styles.panelCard}>
              {cartItems.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineMeta}>{money(item.price)} each</Text>
                  </View>
                  <QtyStepper
                    qty={item.qty}
                    onAdd={() => addToCart(item)}
                    onRemove={() => updateQty(item, -1)}
                  />
                </View>
              ))}
            </View>

            <View style={styles.panelCard}>
              <Text style={styles.panelTitle}>Bill details</Text>
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Subtotal</Text>
                <Text style={styles.summaryValue}>{money(cartSubtotal)}</Text>
              </View>
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Delivery fee</Text>
                <Text style={styles.summaryValue}>{deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}</Text>
              </View>
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
              style={styles.primaryButton}
              onPress={() => {
                const ok = placeDemoOrder();
                if (ok) {
                  router.replace('/reorder');
                }
              }}>
              <Text style={styles.primaryButtonText}>Place demo order</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.secondaryButton} onPress={clearCart}>
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
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },

  screen: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },

  scrollContent: {
    paddingBottom: 120,
  },

  pageContent: {
    padding: 16,
    paddingBottom: 120,
    gap: 16,
  },

  pageContentWithFloat: {
    padding: 16,
    paddingBottom: 120,
    gap: 16,
  },

  hero: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 22,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },

  heroTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },

  heroEta: {
    color: '#ffffff',
    fontSize: 38,
    lineHeight: 42,
    fontWeight: '900',
  },

  heroTitle: {
    color: '#ffffff',
    fontSize: 28,
    lineHeight: 32,
    fontWeight: '900',
  },

  addressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 6,
  },

  addressText: {
    flex: 1,
    color: 'rgba(255,255,255,0.92)',
    fontSize: 14,
    fontWeight: '700',
  },

  profileCircle: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
  },

  serviceRow: {
    gap: 10,
    paddingTop: 18,
  },

  servicePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
    backgroundColor: 'rgba(255,255,255,0.10)',
  },

  servicePillText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },

  heroSearch: {
    marginTop: 16,
    minHeight: 54,
    borderRadius: 16,
    backgroundColor: '#ffffff',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
  },

  heroSearchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 16,
  },

  shortcutRow: {
    gap: 10,
    paddingTop: 16,
  },

  shortcutChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.10)',
  },

  shortcutChipActive: {
    backgroundColor: 'rgba(255,255,255,0.24)',
  },

  shortcutChipText: {
    color: '#d1fae5',
    fontWeight: '700',
    fontSize: 12,
  },

  shortcutChipTextActive: {
    color: '#ffffff',
  },

  festivalRow: {
    gap: 10,
    paddingTop: 14,
  },

  festivalCard: {
    width: 120,
    minHeight: 90,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    padding: 14,
    justifyContent: 'space-between',
  },

  festivalEmoji: {
    fontSize: 26,
  },

  festivalTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },

  surface: {
    backgroundColor: COLORS.bg,
    paddingHorizontal: 16,
    paddingTop: 18,
    paddingBottom: 24,
  },

  darkSurface: {
    backgroundColor: COLORS.dark900,
    paddingHorizontal: 16,
    paddingTop: 18,
    paddingBottom: 24,
  },

  sectionHeader: {
    marginBottom: 12,
    marginTop: 4,
  },

  sectionTitle: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '800',
  },

  sectionTitleLight: {
    color: '#ffffff',
  },

  sectionSubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    marginTop: 4,
  },

  sectionSubtitleLight: {
    color: 'rgba(255,255,255,0.78)',
  },

  bannerCard: {
    backgroundColor: COLORS.green100,
    borderRadius: 20,
    padding: 16,
    marginBottom: 16,
  },

  bannerTitle: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },

  bannerText: {
    color: COLORS.muted,
    fontSize: 14,
    marginTop: 6,
  },

  loadingWrap: {
    paddingVertical: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },

  loadingText: {
    marginTop: 10,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '600',
  },

  emptyCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
  },

  emptyCardDark: {
    backgroundColor: COLORS.dark800,
    borderColor: 'rgba(255,255,255,0.08)',
  },

  emptyTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },

  emptyText: {
    color: COLORS.muted,
    fontSize: 14,
    marginTop: 8,
    lineHeight: 20,
  },

  dealGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 16,
  },

  dealCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
  },

  smallPill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: COLORS.green050,
    color: COLORS.green800,
    fontSize: 11,
    fontWeight: '800',
    overflow: 'hidden',
  },

  dealEmoji: {
    fontSize: 32,
    marginTop: 10,
  },

  dealTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
    marginTop: 10,
  },

  dealMeta: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },

  dealBottom: {
    marginTop: 12,
    gap: 10,
  },

  priceText: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },

  addTinyButton: {
    alignSelf: 'flex-start',
    backgroundColor: COLORS.green800,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },

  addTinyButtonText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '800',
  },

  chipRow: {
    gap: 10,
    paddingBottom: 14,
  },

  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#ffffff',
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },

  chipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },

  filterChip: {
    backgroundColor: '#ffffff',
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },

  filterChipActive: {
    backgroundColor: COLORS.green800,
    borderColor: COLORS.green800,
  },

  filterChipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },

  filterChipTextActive: {
    color: '#ffffff',
  },

  filterChipDark: {
    backgroundColor: COLORS.dark800,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },

  filterChipDarkActive: {
    backgroundColor: '#ffffff',
  },

  filterChipTextDark: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },

  filterChipTextDarkActive: {
    color: COLORS.dark900,
  },

  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 16,
  },

  categoryTile: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    alignItems: 'center',
  },

  categoryTileLarge: {
    width: '48%',
    minHeight: 110,
    borderRadius: 18,
    padding: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },

  categoryEmoji: {
    fontSize: 28,
    marginBottom: 10,
  },

  categoryTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
    textAlign: 'center',
  },

  horizontalRail: {
    gap: 12,
    paddingBottom: 12,
  },

  railCard: {
    width: 290,
  },

  promoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 16,
  },

  promoTile: {
    width: '48%',
    borderRadius: 18,
    padding: 16,
  },

  promoEmoji: {
    fontSize: 28,
  },

  promoTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
    marginTop: 10,
  },

  promoSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 6,
  },

  storeCard: {
    flexDirection: 'row',
    gap: 12,
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 12,
  },

  storeCardDark: {
    backgroundColor: COLORS.dark800,
    borderColor: 'rgba(255,255,255,0.08)',
  },

  storeAvatar: {
    width: 56,
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },

  storeAvatarText: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  storeContent: {
    flex: 1,
  },

  storeTitleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
  },

  storeName: {
    flex: 1,
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },

  storeNameDark: {
    color: '#ffffff',
  },

  storeDescription: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 6,
  },

  storeDescriptionDark: {
    color: 'rgba(255,255,255,0.72)',
  },

  badgeRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 10,
  },

  badge: {
    backgroundColor: COLORS.green050,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },

  badgeDark: {
    backgroundColor: 'rgba(255,255,255,0.12)',
  },

  badgeText: {
    color: COLORS.green800,
    fontSize: 12,
    fontWeight: '700',
  },

  badgeTextDark: {
    color: '#ffffff',
  },

  overlayArea: {
    position: 'absolute',
    left: 16,
    right: 16,
    alignItems: 'center',
  },

  floatingCartBar: {
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

  floatingCartCta: {
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

  floatingCartText: {
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
    textAlign: 'center',
  },

  panelCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },

  panelTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
  },

  panelText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    marginTop: 8,
  },

  panelSubText: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 6,
  },

  primaryButton: {
    backgroundColor: COLORS.green800,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
  },

  primaryButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '900',
  },

  secondaryButton: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
  },

  secondaryButtonText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },

  segmentWrap: {
    flexDirection: 'row',
    gap: 10,
  },

  segmentButton: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 12,
    alignItems: 'center',
  },

  segmentButtonActive: {
    backgroundColor: COLORS.green800,
    borderColor: COLORS.green800,
  },

  segmentButtonText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },

  segmentButtonTextActive: {
    color: '#ffffff',
  },

  simpleListRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingTop: 14,
  },

  simpleListIcon: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: COLORS.green100,
    alignItems: 'center',
    justifyContent: 'center',
  },

  simpleListIconText: {
    color: COLORS.green800,
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
    marginTop: 4,
  },

  accountHeaderCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },

  accountName: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },

  accountSubText: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },

  helpButton: {
    borderRadius: 12,
    backgroundColor: COLORS.green050,
    paddingHorizontal: 14,
    paddingVertical: 10,
    alignSelf: 'flex-start',
  },

  helpButtonText: {
    color: COLORS.green800,
    fontSize: 13,
    fontWeight: '800',
  },

  oneCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },

  quickGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 12,
  },

  quickCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 10,
  },

  quickCardText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },

  accountListCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },

  accountListRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingVertical: 16,
  },

  accountListRowBorder: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },

  accountListLabel: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },

  innerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 12,
    backgroundColor: COLORS.bg,
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

  innerHeaderTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },

  innerHeaderSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },

  vendorHeroCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },

  vendorHeroTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },

  vendorInitialBadge: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: COLORS.green100,
    alignItems: 'center',
    justifyContent: 'center',
  },

  vendorInitialBadgeText: {
    color: COLORS.green800,
    fontSize: 18,
    fontWeight: '900',
  },

  vendorHeroTitle: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
    marginTop: 14,
  },

  vendorHeroText: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },

  searchBoxPlain: {
    minHeight: 54,
    borderRadius: 16,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
  },

  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 16,
  },

  productCard: {
    flexDirection: 'row',
    gap: 12,
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
  },

  productLeft: {
    flex: 1,
  },

  productTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
    marginTop: 10,
  },

  productDesc: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 6,
  },

  productRight: {
    alignItems: 'center',
    gap: 10,
  },

  productEmojiWrap: {
    width: 82,
    height: 82,
    borderRadius: 18,
    backgroundColor: COLORS.green050,
    alignItems: 'center',
    justifyContent: 'center',
  },

  productEmoji: {
    fontSize: 36,
  },

  addButton: {
    backgroundColor: COLORS.green800,
    borderRadius: 14,
    paddingHorizontal: 18,
    paddingVertical: 10,
  },

  addButtonText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '900',
  },

  qtyStepper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 12,
    paddingHorizontal: 6,
    height: 38,
  },

  qtyButton: {
    width: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },

  qtyText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
    minWidth: 24,
    textAlign: 'center',
  },

  progressTrack: {
    height: 10,
    borderRadius: 999,
    backgroundColor: '#e5e7eb',
    overflow: 'hidden',
    marginTop: 12,
  },

  progressFill: {
    height: '100%',
    borderRadius: 999,
    backgroundColor: COLORS.green800,
  },

  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
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
    justifyContent: 'space-between',
    paddingVertical: 6,
  },

  summaryLabel: {
    color: COLORS.muted,
    fontSize: 14,
  },

  summaryValue: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },

  summaryDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: 8,
  },

  summaryLabelStrong: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },

  summaryValueStrong: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },

  orderCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    marginBottom: 12,
  },

  orderTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },

  orderThumb: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: COLORS.green100,
    alignItems: 'center',
    justifyContent: 'center',
  },

  orderThumbText: {
    color: COLORS.green800,
    fontSize: 14,
    fontWeight: '900',
  },

  orderTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },

  orderMeta: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 4,
  },

  orderStatus: {
    color: COLORS.green800,
    fontSize: 12,
    fontWeight: '900',
  },

  orderLine: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 12,
    marginBottom: 6,
  },

  sceneCard: {
    borderRadius: 20,
    padding: 16,
    marginBottom: 12,
  },

  sceneTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },

  sceneDate: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
    opacity: 0.92,
  },

  sceneEmoji: {
    fontSize: 26,
  },

  sceneTitle: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '900',
    marginTop: 16,
  },

  sceneVenue: {
    color: 'rgba(255,255,255,0.84)',
    fontSize: 14,
    marginTop: 8,
  },

  scenePrice: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
    marginTop: 12,
  },
});