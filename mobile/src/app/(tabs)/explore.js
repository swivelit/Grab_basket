import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
import { useRouter } from 'expo-router';
import { useGrabBasket } from '../../../App';

const CACHE_KEY = '@grab_basket/explore_query_cache_v2';
const STALE_TIME_MS = 60 * 1000;
const CACHE_TIME_MS = 20 * 60 * 1000;
const DEBOUNCE_MS = 280;

const COLORS = {
  bg: '#f6f7fb',
  card: '#ffffff',
  text: '#101828',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e8ecf3',
  orange: '#ff6d00',
  orangeSoft: '#fff1e7',
  blue: '#0b57d0',
  green: '#119b56',
  black: '#050816',
  darkSurface: '#0c1324',
  darkSurfaceAlt: '#131d35',
  darkBorder: '#202c48',
  darkMuted: '#b8c4dc',
};

const SCREEN_THEME = {
  food: {
    page: COLORS.bg,
    hero: '#fff4d7',
    accent: '#ffd95e',
    title: 'Gourmet',
    subtitle: 'Search-first restaurant discovery for the consumer app.',
    placeholder: 'Search cuisines, dishes, restaurants',
    heroTitle: 'Search, filter and rediscover faster',
    heroSubtitle: 'Swiggy-style discovery with cached results and quick refinement.',
  },
  warehouse: {
    page: '#f7fbff',
    hero: '#eaf2ff',
    accent: '#b7d2ff',
    title: 'Categories',
    subtitle: 'Faster grocery navigation with smart aisle shortcuts.',
    placeholder: 'Search categories, brands, essentials',
    heroTitle: 'Aisle search that feels instant',
    heroSubtitle: 'Cached grocery results, quick filters and tighter browse flows.',
  },
  eatout: {
    page: '#fbfbfd',
    hero: '#fff2e4',
    accent: '#ffd1a8',
    title: 'My corner',
    subtitle: 'Dining shortcuts, premium filters and better intent capture.',
    placeholder: 'Search restaurants, vibe, area',
    heroTitle: 'Find the right table faster',
    heroSubtitle: 'Offers, premium picks and vibe filters without losing context.',
  },
  scenes: {
    page: COLORS.black,
    hero: '#121a2e',
    accent: '#223150',
    title: 'Explore',
    subtitle: 'Event and experience discovery with a premium dark surface.',
    placeholder: 'Search events, creators, experiences',
    heroTitle: 'Experiences worth stepping out for',
    heroSubtitle: 'Searchable and cache-friendly discovery for workshops, comedy and live music.',
  },
};

const FOOD_COLLECTIONS = [
  { key: 'chef', title: 'Chef-curated picks', subtitle: 'Premium and high-rated', icon: 'sparkles-outline' },
  { key: 'late', title: 'Late-night cravings', subtitle: 'Comfort food after hours', icon: 'moon-outline' },
  { key: 'healthy', title: 'Healthy choices', subtitle: 'Bowls, salads, lighter meals', icon: 'leaf-outline' },
  { key: 'dessert', title: 'Dessert mission', subtitle: 'Cakes, jars and sweet fixes', icon: 'ice-cream-outline' },
];

const WAREHOUSE_COLLECTIONS = [
  { key: 'daily', title: 'Daily essentials', subtitle: 'Milk, bread, eggs and breakfast basics', icon: 'basket-outline' },
  { key: 'fresh', title: 'Fresh & greens', subtitle: 'Fruits, vegetables and dairy', icon: 'leaf-outline' },
  { key: 'value', title: 'Value picks', subtitle: 'Budget-friendly quick additions', icon: 'cash-outline' },
  { key: 'snacks', title: 'Snack shelf', subtitle: 'Crunchies, chocolates and treats', icon: 'pricetag-outline' },
];

const EATOUT_COLLECTIONS = [
  { key: 'family', title: 'Family-friendly spots', subtitle: 'Comfortable dine-in choices', icon: 'people-outline' },
  { key: 'cafes', title: 'Cafe dates', subtitle: 'Coffee, desserts and chill plans', icon: 'cafe-outline' },
  { key: 'premium', title: 'Premium dining', subtitle: 'Better ambience and offers', icon: 'wine-outline' },
  { key: 'rooftop', title: 'Rooftop evenings', subtitle: 'Open-air tables and views', icon: 'moon-outline' },
];

const SCENE_COLLECTIONS = [
  { key: 'music', title: 'Live music', subtitle: 'Bands, acoustic sets and gigs', icon: 'musical-notes-outline' },
  { key: 'comedy', title: 'Comedy nights', subtitle: 'Stand-up and crowd work', icon: 'mic-outline' },
  { key: 'workshops', title: 'Workshops', subtitle: 'Pottery, art and maker sessions', icon: 'color-palette-outline' },
  { key: 'premium', title: 'Premium drops', subtitle: 'High-demand and curated', icon: 'diamond-outline' },
];

const QUICK_GRID = {
  food: [
    { key: 'biryani', icon: 'flame-outline', label: 'Biryani' },
    { key: 'burgers', icon: 'fast-food-outline', label: 'Burgers' },
    { key: 'breakfast', icon: 'sunny-outline', label: 'Breakfast' },
    { key: 'healthy', icon: 'leaf-outline', label: 'Healthy' },
    { key: 'cakes', icon: 'gift-outline', label: 'Cakes' },
    { key: 'juice', icon: 'cafe-outline', label: 'Juices' },
    { key: 'south', icon: 'restaurant-outline', label: 'South Indian' },
    { key: 'late', icon: 'moon-outline', label: 'Late night' },
  ],
  warehouse: [
    { key: 'fresh', icon: 'leaf-outline', label: 'Fresh' },
    { key: 'fruits', icon: 'nutrition-outline', label: 'Fruits' },
    { key: 'dairy', icon: 'water-outline', label: 'Dairy' },
    { key: 'bakery', icon: 'pizza-outline', label: 'Bakery' },
    { key: 'snacks', icon: 'pricetag-outline', label: 'Snacks' },
    { key: 'drinks', icon: 'beer-outline', label: 'Drinks' },
    { key: 'beauty', icon: 'flower-outline', label: 'Beauty' },
    { key: 'home', icon: 'home-outline', label: 'Home care' },
  ],
};

const EATOUT_SHORTCUTS = [
  { key: 'near', label: 'Restaurants near me', icon: 'navigate-outline' },
  { key: 'offers', label: 'Pre-book offers', icon: 'bookmark-outline' },
  { key: 'cashback', label: 'Cashback picks', icon: 'cash-outline' },
  { key: 'new', label: 'New & Hot', icon: 'flame-outline' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room Experience',
    subtitle: 'Break n Chill · Chittethukara',
    price: 299,
    icon: 'hammer-outline',
    accent: '#311015',
    bucket: 'premium',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Workshop',
    subtitle: 'Soil to Soul Ceramics · Kadavanthra',
    price: 1000,
    icon: 'color-palette-outline',
    accent: '#6b4a35',
    bucket: 'workshops',
  },
  {
    id: 'scene-3',
    title: 'Stand-up Comedy Night',
    subtitle: 'Kakkanad · Weekend special',
    price: 499,
    icon: 'mic-outline',
    accent: '#243963',
    bucket: 'comedy',
  },
  {
    id: 'scene-4',
    title: 'Indie Music Sundowner',
    subtitle: 'Panampilly · Acoustic rooftop set',
    price: 799,
    icon: 'musical-notes-outline',
    accent: '#1f3156',
    bucket: 'music',
  },
];

const FILTERS_BY_SERVICE = {
  food: [
    { key: 'all', label: 'All', icon: 'apps-outline' },
    { key: 'favorites', label: 'Favorites', icon: 'heart-outline' },
    { key: 'fast', label: 'Fast delivery', icon: 'flash-outline' },
    { key: 'topRated', label: 'Top rated', icon: 'star-outline' },
    { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
    { key: 'biryani', label: 'Biryani', icon: 'flame-outline' },
    { key: 'dessert', label: 'Desserts', icon: 'ice-cream-outline' },
    { key: 'healthy', label: 'Healthy', icon: 'leaf-outline' },
  ],
  warehouse: [
    { key: 'all', label: 'All', icon: 'apps-outline' },
    { key: 'favorites', label: 'Favorites', icon: 'heart-outline' },
    { key: 'fast', label: 'Quick delivery', icon: 'flash-outline' },
    { key: 'daily', label: 'Daily essentials', icon: 'basket-outline' },
    { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
    { key: 'snacks', label: 'Snacks', icon: 'pricetag-outline' },
    { key: 'beverages', label: 'Drinks', icon: 'beer-outline' },
    { key: 'beauty', label: 'Beauty', icon: 'flower-outline' },
  ],
  eatout: [
    { key: 'all', label: 'All', icon: 'apps-outline' },
    { key: 'favorites', label: 'Favorites', icon: 'heart-outline' },
    { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
    { key: 'topRated', label: 'Top rated', icon: 'star-outline' },
    { key: 'cafes', label: 'Cafes', icon: 'cafe-outline' },
    { key: 'group', label: 'Groups', icon: 'people-outline' },
    { key: 'premium', label: 'Premium', icon: 'wine-outline' },
    { key: 'rooftop', label: 'Rooftop', icon: 'moon-outline' },
  ],
  scenes: [
    { key: 'all', label: 'All', icon: 'apps-outline' },
    { key: 'music', label: 'Music', icon: 'musical-notes-outline' },
    { key: 'comedy', label: 'Comedy', icon: 'mic-outline' },
    { key: 'workshops', label: 'Workshops', icon: 'color-palette-outline' },
    { key: 'premium', label: 'Premium', icon: 'diamond-outline' },
    { key: 'weekend', label: 'Weekend', icon: 'calendar-outline' },
  ],
};

const SORT_OPTIONS = [
  { key: 'relevance', label: 'Relevance' },
  { key: 'rating', label: 'Rating' },
  { key: 'delivery', label: 'Delivery' },
  { key: 'name', label: 'Name' },
];

let memoryCache = {};
let cacheHydrated = false;
let cacheHydrationPromise = null;

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

function dedupeStrings(values = []) {
  const seen = new Set();
  return values.filter((value) => {
    const normalized = normalizeText(value);
    if (!normalized || seen.has(normalized)) return false;
    seen.add(normalized);
    return true;
  });
}

function safeJsonParse(raw, fallback) {
  try {
    return JSON.parse(raw);
  } catch {
    return fallback;
  }
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return Number((4.1 + (seed % 8) * 0.1).toFixed(1));
}

function getVendorEta(vendor, service) {
  if (service === 'warehouse') return 12;
  if (service === 'eatout') return 15;
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return 18;
    if (vendor.distance_km <= 5) return 26;
  }
  return 23;
}

function getEtaLabel(vendor, service) {
  if (service === 'warehouse') return '5-15 mins';
  if (service === 'eatout') return 'Table in 10-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorMeta(vendor, service) {
  if (service === 'warehouse') return vendor?.description || 'Essentials · Snacks · Daily needs';
  if (service === 'eatout') return vendor?.description || 'Table offers · Bill savings · Dining';
  if (service === 'scenes') return vendor?.description || 'Tickets · Experiences · Book now';
  return vendor?.description || vendor?.address || 'Popular near you';
}

function getVendorBadge(vendor, service) {
  const food = ['40% OFF', 'UPTO ₹80', 'FREE DELIVERY', 'BESTSELLER'];
  const warehouse = ['₹9 DEAL', 'VALUE PICK', 'TOP BRANDS', 'DAILY SAVER'];
  const eatout = ['Flat 50% OFF', '10% Cashback', 'Pre-book', 'Bill offer'];
  const scenes = ['Weekend drop', 'Trending', 'Premium pass', 'Quick plan'];
  const source =
    service === 'warehouse'
      ? warehouse
      : service === 'eatout'
        ? eatout
        : service === 'scenes'
          ? scenes
          : food;
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return source[seed % source.length];
}

function getVendorTint(name = '', service = 'food') {
  const seed = String(name || '').length % 5;
  if (service === 'warehouse') return ['#1d4c9a', '#2357ae', '#103c81', '#2d67bb', '#204f9a'][seed];
  if (service === 'eatout') return ['#7a3813', '#5f2151', '#8f5a11', '#80411e', '#743217'][seed];
  if (service === 'scenes') return ['#27385d', '#54296f', '#265744', '#6c4530', '#283451'][seed];
  return ['#5c229f', '#0f5e49', '#7a1e29', '#174285', '#70471d'][seed];
}

function getProductEmoji(name = '') {
  const text = normalizeText(name);
  if (/(milk|curd|paneer|dairy)/.test(text)) return '🥛';
  if (/(jam|berry|fruit)/.test(text)) return '🍓';
  if (/(chip|snack|cracker)/.test(text)) return '🥔';
  if (/(drink|juice|cola|water|tea|coffee)/.test(text)) return '🥤';
  if (/(dessert|cake|sweet|brownie)/.test(text)) return '🍰';
  return '🛍️';
}

function getVendorSearchText(vendor, service) {
  return [vendor?.name, vendor?.description, vendor?.address, getVendorMeta(vendor, service)]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function getDealSearchText(item) {
  return [item?.name, item?.vendorName, item?.brand, item?.description].filter(Boolean).join(' ').toLowerCase();
}

function getEventSearchText(item) {
  return [item?.title, item?.subtitle, item?.bucket].filter(Boolean).join(' ').toLowerCase();
}

function getVendorScore(vendor, service, search, favorites = {}) {
  const query = normalizeText(search);
  if (!query) return 1;

  const name = normalizeText(vendor?.name);
  const description = normalizeText(vendor?.description);
  const address = normalizeText(vendor?.address);
  const meta = normalizeText(getVendorMeta(vendor, service));

  let score = 0;
  if (name.includes(query)) score += 90;
  if (description.includes(query)) score += 32;
  if (address.includes(query)) score += 16;
  if (meta.includes(query)) score += 12;
  if (favorites?.[vendor?.id]) score += 4;
  return score;
}

function getDealScore(item, search) {
  const query = normalizeText(search);
  if (!query) return 1;
  let score = 0;
  if (normalizeText(item?.name).includes(query)) score += 70;
  if (normalizeText(item?.vendorName).includes(query)) score += 24;
  if (normalizeText(item?.brand).includes(query)) score += 12;
  return score;
}

function getEventScore(item, search) {
  const query = normalizeText(search);
  if (!query) return 1;
  let score = 0;
  if (normalizeText(item?.title).includes(query)) score += 70;
  if (normalizeText(item?.subtitle).includes(query)) score += 24;
  if (normalizeText(item?.bucket).includes(query)) score += 12;
  return score;
}

function matchesVendorFilter(vendor, service, filterKey, favorites = {}) {
  if (!vendor) return false;
  if (!filterKey || filterKey === 'all') return true;

  const text = getVendorSearchText(vendor, service);
  const rating = getVendorRating(vendor);
  const eta = getVendorEta(vendor, service);

  switch (filterKey) {
    case 'favorites':
      return Boolean(favorites?.[vendor.id]);
    case 'fast':
      return eta <= (service === 'warehouse' ? 15 : 22);
    case 'topRated':
      return rating >= 4.5;
    case 'offers':
      return /(off|deal|cashback|free delivery|offer|bill)/.test(text);
    case 'biryani':
      return /(biryani|kebab|shawarma|grill)/.test(text);
    case 'dessert':
      return /(dessert|cake|sweet|brownie|ice cream)/.test(text);
    case 'healthy':
      return /(healthy|salad|bowl|protein|fresh)/.test(text);
    case 'daily':
      return /(milk|bread|egg|curd|breakfast|daily)/.test(text);
    case 'fresh':
      return /(fresh|fruit|vegetable|greens|dairy)/.test(text);
    case 'snacks':
      return /(snack|chips|biscuit|cracker|chocolate)/.test(text);
    case 'beverages':
      return /(drink|juice|water|tea|coffee|cola)/.test(text);
    case 'beauty':
      return /(beauty|soap|shampoo|cream|care)/.test(text);
    case 'cafes':
      return /(cafe|coffee|dessert|brunch)/.test(text);
    case 'group':
      return /(family|group|sharing|buffet|table)/.test(text);
    case 'premium':
      return rating >= 4.6 || /(premium|exclusive|chef|reserve|vip|rooftop)/.test(text);
    case 'rooftop':
      return /(rooftop|terrace|sky|view)/.test(text);
    default:
      return true;
  }
}

function matchesDealFilter(item, filterKey) {
  if (!item) return false;
  if (!filterKey || filterKey === 'all') return true;

  const text = getDealSearchText(item);
  const price = Number(item?.price || 0);

  switch (filterKey) {
    case 'offers':
      return true;
    case 'dessert':
      return /(dessert|cake|sweet|brownie|ice cream)/.test(text);
    case 'healthy':
      return /(healthy|salad|juice|bowl)/.test(text);
    case 'biryani':
      return /(biryani|kebab|shawarma)/.test(text);
    case 'daily':
      return /(milk|bread|egg|curd|rice|atta)/.test(text);
    case 'fresh':
      return /(fresh|fruit|vegetable|greens)/.test(text);
    case 'snacks':
      return /(snack|chips|chocolate|biscuit)/.test(text);
    case 'beverages':
      return /(drink|juice|water|tea|coffee)/.test(text);
    case 'beauty':
      return /(beauty|soap|shampoo|cream)/.test(text);
    case 'premium':
      return price >= 300;
    default:
      return true;
  }
}

function matchesEventFilter(item, filterKey) {
  if (!item) return false;
  if (!filterKey || filterKey === 'all') return true;
  const text = getEventSearchText(item);

  switch (filterKey) {
    case 'music':
      return /(music|gig|acoustic|band)/.test(text);
    case 'comedy':
      return /(comedy|stand-up|comic)/.test(text);
    case 'workshops':
      return /(workshop|pottery|maker|class)/.test(text);
    case 'premium':
      return /(premium|vip|exclusive)/.test(text) || Number(item?.price || 0) >= 700;
    case 'weekend':
      return true;
    default:
      return true;
  }
}

function sortVendors(list = [], sortBy = 'relevance') {
  return [...list].sort((a, b) => {
    if (sortBy === 'rating') return (b.__rating || 0) - (a.__rating || 0);
    if (sortBy === 'delivery') return (a.__eta || 0) - (b.__eta || 0);
    if (sortBy === 'name') return String(a?.name || '').localeCompare(String(b?.name || ''));
    return (b.__score || 0) - (a.__score || 0) || (b.__rating || 0) - (a.__rating || 0);
  });
}

function sortDeals(list = [], sortBy = 'relevance') {
  return [...list].sort((a, b) => {
    if (sortBy === 'name') return String(a?.name || '').localeCompare(String(b?.name || ''));
    if (sortBy === 'delivery') return Number(a?.price || 0) - Number(b?.price || 0);
    return (b.__score || 0) - (a.__score || 0) || Number(a?.price || 0) - Number(b?.price || 0);
  });
}

function sortEvents(list = [], sortBy = 'relevance') {
  return [...list].sort((a, b) => {
    if (sortBy === 'name') return String(a?.title || '').localeCompare(String(b?.title || ''));
    if (sortBy === 'delivery') return Number(a?.price || 0) - Number(b?.price || 0);
    return (b.__score || 0) - (a.__score || 0) || Number(a?.price || 0) - Number(b?.price || 0);
  });
}

function getSourceSignature(vendors = [], deals = [], recentSearches = [], favorites = {}) {
  const vendorPart = (vendors || []).slice(0, 20).map((item) => `${item?.id}-${item?.name}`).join('|');
  const dealPart = (deals || []).slice(0, 20).map((item) => `${item?.id || item?.name}-${item?.name}`).join('|');
  const favoritePart = Object.keys(favorites || {}).filter((key) => favorites[key]).sort().join('|');
  return `${vendors.length}:${deals.length}:${recentSearches.length}:${vendorPart}:${dealPart}:${favoritePart}`;
}

function buildQueryKey({ service, search, filterKey, sortBy, sourceSignature }) {
  return ['explore', service, normalizeText(search), filterKey, sortBy, sourceSignature].join('::');
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

  Object.keys(next).forEach((itemKey) => {
    if (now - Number(next[itemKey]?.updatedAt || 0) > CACHE_TIME_MS) {
      delete next[itemKey];
    }
  });

  memoryCache = next;
  AsyncStorage.setItem(CACHE_KEY, JSON.stringify(next)).catch(() => {});
}

function formatAge(timestamp) {
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

function computeQueryData({ service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites }) {
  const query = normalizeText(search);

  const vendorResults = sortVendors(
    (vendors || [])
      .map((vendor) => {
        const score = getVendorScore(vendor, service, query, favorites);
        const passesSearch = !query || score > 0 || getVendorSearchText(vendor, service).includes(query);
        const passesFilter = matchesVendorFilter(vendor, service, filterKey, favorites);
        if (!passesSearch || !passesFilter) return null;

        return {
          ...vendor,
          __score: score,
          __rating: getVendorRating(vendor),
          __eta: getVendorEta(vendor, service),
        };
      })
      .filter(Boolean),
    sortBy
  );

  const dealResults = sortDeals(
    (deals || [])
      .map((item) => {
        const score = getDealScore(item, query);
        const passesSearch = !query || score > 0 || getDealSearchText(item).includes(query);
        const passesFilter = matchesDealFilter(item, filterKey);
        if (!passesSearch || !passesFilter) return null;
        return { ...item, __score: score };
      })
      .filter(Boolean),
    sortBy
  );

  const eventResults =
    service === 'scenes'
      ? sortEvents(
          SCENE_EVENTS.map((item) => {
            const score = getEventScore(item, query);
            const passesSearch = !query || score > 0 || getEventSearchText(item).includes(query);
            const passesFilter = matchesEventFilter(item, filterKey);
            if (!passesSearch || !passesFilter) return null;
            return { ...item, __score: score };
          }).filter(Boolean),
          sortBy
        )
      : [];

  const suggestions = dedupeStrings([
    ...(recentSearches || []),
    ...(vendors || []).map((item) => item?.name),
    ...(deals || []).map((item) => item?.name),
    ...(service === 'scenes' ? SCENE_EVENTS.map((item) => item.title) : []),
  ])
    .filter((item) => (!query ? true : normalizeText(item).includes(query)))
    .slice(0, 8);

  return {
    vendors: vendorResults,
    deals: dealResults,
    events: eventResults,
    suggestions,
    totalMatches: vendorResults.length + dealResults.length + eventResults.length,
    empty: vendorResults.length === 0 && dealResults.length === 0 && eventResults.length === 0,
  };
}

function useExploreQuery({ service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites }) {
  const sourceSignature = useMemo(
    () => getSourceSignature(vendors, deals, recentSearches, favorites),
    [vendors, deals, recentSearches, favorites]
  );

  const queryKey = useMemo(
    () => buildQueryKey({ service, search, filterKey, sortBy, sourceSignature }),
    [service, search, filterKey, sortBy, sourceSignature]
  );

  const [state, setState] = useState({
    data: { vendors: [], deals: [], events: [], suggestions: [], totalMatches: 0, empty: false },
    isLoading: true,
    isFetching: false,
    isFromCache: false,
    updatedAt: 0,
  });

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      const entry = getCacheEntry(queryKey);
      const entryIsFresh = entry && Date.now() - Number(entry.updatedAt || 0) <= STALE_TIME_MS;

      if (entry?.data) {
        setState((current) => ({
          ...current,
          data: entry.data,
          isLoading: false,
          isFetching: !entryIsFresh,
          isFromCache: true,
          updatedAt: Number(entry.updatedAt || 0),
        }));
      } else {
        setState((current) => ({
          ...current,
          isLoading: current.data.totalMatches ? false : true,
          isFetching: true,
        }));
      }

      await hydrateCache();
      if (cancelled) return;

      const hydratedEntry = getCacheEntry(queryKey);
      const hydratedIsFresh = hydratedEntry && Date.now() - Number(hydratedEntry.updatedAt || 0) <= STALE_TIME_MS;
      if (hydratedEntry?.data) {
        setState((current) => ({
          ...current,
          data: hydratedEntry.data,
          isLoading: false,
          isFetching: !hydratedIsFresh,
          isFromCache: true,
          updatedAt: Number(hydratedEntry.updatedAt || 0),
        }));
      }

      const fresh = computeQueryData({ service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites });
      await writeCacheEntry(queryKey, fresh);
      if (cancelled) return;

      setState({
        data: fresh,
        isLoading: false,
        isFetching: false,
        isFromCache: false,
        updatedAt: Date.now(),
      });
    };

    run().catch(() => {
      if (cancelled) return;
      const fallback = computeQueryData({ service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites });
      setState({ data: fallback, isLoading: false, isFetching: false, isFromCache: false, updatedAt: Date.now() });
    });

    return () => {
      cancelled = true;
    };
  }, [queryKey, service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites]);

  return state;
}

function SectionHeader({ title, subtitle, light = false, actionLabel, onActionPress }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text> : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onActionPress}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function SearchBar({
  value,
  onChangeText,
  onSubmitEditing,
  onClear,
  onToggleFilters,
  filterCount = 0,
  filtersOpen = false,
  placeholder,
  dark = false,
}) {
  return (
    <View style={[styles.searchBar, dark && styles.searchBarDark]}>
      <Ionicons name="search-outline" size={20} color={dark ? '#b8c4dc' : COLORS.muted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        onSubmitEditing={onSubmitEditing}
        placeholder={placeholder}
        placeholderTextColor={dark ? '#8fa2c4' : COLORS.subtle}
        style={[styles.searchInput, dark && styles.searchInputDark]}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
      />
      {value ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onClear} style={styles.iconButton}>
          <Ionicons name="close-outline" size={18} color={dark ? '#ffffff' : COLORS.muted} />
        </TouchableOpacity>
      ) : null}
      <TouchableOpacity
        activeOpacity={0.92}
        onPress={onToggleFilters}
        style={[styles.filterButton, filtersOpen && styles.filterButtonActive]}>
        <Ionicons name="options-outline" size={18} color={filtersOpen ? '#ffffff' : dark ? '#ffffff' : COLORS.orange} />
        {filterCount > 0 ? (
          <View style={styles.filterBadge}>
            <Text style={styles.filterBadgeText}>{filterCount}</Text>
          </View>
        ) : null}
      </TouchableOpacity>
    </View>
  );
}

function MetaBanner({ isFetching, isFromCache, updatedAt, dark = false }) {
  const title = isFetching
    ? isFromCache
      ? 'Showing cached results while refreshing'
      : 'Refreshing results'
    : isFromCache
      ? 'Showing cached results'
      : 'Showing fresh results';

  return (
    <View style={[styles.metaBanner, dark && styles.metaBannerDark]}>
      <View style={styles.metaLeft}>
        {isFetching ? (
          <ActivityIndicator size="small" color={dark ? '#ffffff' : COLORS.orange} />
        ) : (
          <Ionicons name={isFromCache ? 'cloud-outline' : 'sparkles-outline'} size={16} color={dark ? '#ffffff' : COLORS.orange} />
        )}
        <Text style={[styles.metaTitle, dark && styles.metaTitleDark]}>{title}</Text>
      </View>
      <Text style={[styles.metaTime, dark && styles.metaTimeDark]}>{formatAge(updatedAt)}</Text>
    </View>
  );
}

function Pill({ label, icon, active, onPress, dark = false, compact = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[
        compact ? styles.sortPill : styles.filterPill,
        dark && (compact ? styles.sortPillDark : styles.filterPillDark),
        active && (compact ? styles.sortPillActive : styles.filterPillActive),
      ]}>
      {icon ? (
        <Ionicons
          name={icon}
          size={compact ? 0 : 15}
          color={compact ? 'transparent' : active ? '#ffffff' : dark ? '#d5def3' : COLORS.muted}
          style={compact ? styles.hiddenIcon : null}
        />
      ) : null}
      <Text
        style={[
          compact ? styles.sortPillText : styles.filterPillText,
          dark && (compact ? styles.sortPillTextDark : styles.filterPillTextDark),
          active && (compact ? styles.sortPillTextActive : styles.filterPillTextActive),
        ]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function SuggestionChip({ label, dark = false, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} onPress={onPress} style={[styles.suggestionChip, dark && styles.suggestionChipDark]}>
      <Ionicons name="search-outline" size={14} color={dark ? '#d5def3' : COLORS.muted} />
      <Text style={[styles.suggestionChipText, dark && styles.suggestionChipTextDark]} numberOfLines={1}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function SummaryCard({ totalMatches, search, filterLabel, sortLabel, dark = false, onReset }) {
  return (
    <View style={[styles.summaryCard, dark && styles.summaryCardDark]}>
      <View style={styles.summaryRow}>
        <Text style={[styles.summaryTitle, dark && styles.summaryTitleDark]}>
          {totalMatches} result{totalMatches === 1 ? '' : 's'} found
        </Text>
        <TouchableOpacity activeOpacity={0.92} onPress={onReset}>
          <Text style={[styles.summaryAction, dark && styles.summaryActionDark]}>Reset</Text>
        </TouchableOpacity>
      </View>
      <View style={styles.summaryChipRow}>
        {search ? (
          <View style={[styles.summaryChip, dark && styles.summaryChipDark]}>
            <Text style={[styles.summaryChipText, dark && styles.summaryChipTextDark]}>Search: “{search}”</Text>
          </View>
        ) : null}
        {filterLabel && filterLabel !== 'All' ? (
          <View style={[styles.summaryChip, dark && styles.summaryChipDark]}>
            <Text style={[styles.summaryChipText, dark && styles.summaryChipTextDark]}>Filter: {filterLabel}</Text>
          </View>
        ) : null}
        {sortLabel && sortLabel !== 'Relevance' ? (
          <View style={[styles.summaryChip, dark && styles.summaryChipDark]}>
            <Text style={[styles.summaryChipText, dark && styles.summaryChipTextDark]}>Sort: {sortLabel}</Text>
          </View>
        ) : null}
      </View>
    </View>
  );
}

function HeroCard({ theme, dark = false }) {
  return (
    <View style={[styles.heroCard, { backgroundColor: theme.hero }]}>
      <View style={[styles.heroOrbLarge, { backgroundColor: theme.accent }]} />
      <View style={[styles.heroOrbSmall, { backgroundColor: theme.accent }]} />
      <Text style={[styles.heroTitle, dark && styles.heroTitleDark]}>{theme.heroTitle}</Text>
      <Text style={[styles.heroSubtitle, dark && styles.heroSubtitleDark]}>{theme.heroSubtitle}</Text>
      <View style={[styles.heroButton, dark && styles.heroButtonDark]}>
        <Text style={[styles.heroButtonText, dark && styles.heroButtonTextDark]}>Explore faster</Text>
      </View>
    </View>
  );
}

function CollectionCard({ item, service, onPress }) {
  const dark = service === 'scenes';
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[styles.collectionCard, dark && styles.collectionCardDark]}>
      <View style={[styles.collectionIconWrap, dark && styles.collectionIconWrapDark]}>
        <Ionicons name={item.icon} size={20} color={dark ? '#ffffff' : COLORS.text} />
      </View>
      <Text style={[styles.collectionTitle, dark && styles.collectionTitleDark]}>{item.title}</Text>
      <Text style={[styles.collectionSubtitle, dark && styles.collectionSubtitleDark]}>{item.subtitle}</Text>
    </TouchableOpacity>
  );
}

function GridCard({ item, active = false, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} onPress={onPress} style={[styles.gridCard, active && styles.gridCardActive]}>
      <View style={[styles.gridIconWrap, active && styles.gridIconWrapActive]}>
        <Ionicons name={item.icon} size={20} color={active ? '#ffffff' : COLORS.text} />
      </View>
      <Text style={[styles.gridLabel, active && styles.gridLabelActive]}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function VendorCard({ vendor, service, favorite, onToggleFavorite, onPress, dark = false }) {
  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={[styles.vendorCard, dark && styles.vendorCardDark]}>
      <View style={[styles.vendorVisual, { backgroundColor: getVendorTint(vendor?.name, service) }]}>
        <View style={styles.vendorTopRow}>
          <View style={styles.vendorBadge}>
            <Text style={styles.vendorBadgeText}>{getVendorBadge(vendor, service)}</Text>
          </View>
          <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite} style={styles.favoriteButton}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={favorite ? '#ff5b6e' : '#ffffff'} />
          </TouchableOpacity>
        </View>
        <View style={styles.vendorMonogram}>
          <Text style={styles.vendorMonogramText}>{initials(vendor?.name)}</Text>
        </View>
      </View>
      <Text style={[styles.vendorName, dark && styles.vendorNameDark]} numberOfLines={1}>{vendor?.name}</Text>
      <Text style={[styles.vendorMeta, dark && styles.vendorMetaDark]} numberOfLines={1}>
        ⭐ {getVendorRating(vendor).toFixed(1)} · {getEtaLabel(vendor, service)}
      </Text>
      <Text style={[styles.vendorSub, dark && styles.vendorSubDark]} numberOfLines={1}>{getVendorMeta(vendor, service)}</Text>
    </TouchableOpacity>
  );
}

function DealCard({ item }) {
  return (
    <View style={styles.dealCard}>
      <View style={styles.dealVisual}>
        <Text style={styles.dealEmoji}>{getProductEmoji(item?.name)}</Text>
      </View>
      <Text style={styles.dealName} numberOfLines={2}>{item?.name}</Text>
      <Text style={styles.dealPrice}>{money(item?.price)}</Text>
      {item?.vendorName ? <Text style={styles.dealVendor}>{item.vendorName}</Text> : null}
    </View>
  );
}

function ShortcutCard({ item, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} onPress={onPress} style={styles.shortcutCard}>
      <Ionicons name={item.icon} size={16} color={COLORS.text} />
      <Text style={styles.shortcutText}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function EventCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.eventCard}>
      <View style={[styles.eventVisual, { backgroundColor: item.accent }]}>
        <Ionicons name={item.icon} size={30} color="#ffffff" />
        <Text style={styles.eventPrice}>Starts at {money(item.price)}</Text>
      </View>
      <Text style={styles.eventTitle} numberOfLines={2}>{item.title}</Text>
      <Text style={styles.eventSubtitle} numberOfLines={2}>{item.subtitle}</Text>
    </TouchableOpacity>
  );
}

function LoadingState({ dark = false, label = 'Loading...' }) {
  return (
    <View style={[styles.feedbackCard, dark && styles.feedbackCardDark]}>
      <ActivityIndicator color={dark ? '#ffffff' : COLORS.orange} />
      <Text style={[styles.feedbackTitle, dark && styles.feedbackTitleDark]}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, subtitle, dark = false }) {
  return (
    <View style={[styles.feedbackCard, dark && styles.feedbackCardDark]}>
      <Text style={[styles.feedbackTitle, dark && styles.feedbackTitleDark]}>{title}</Text>
      <Text style={[styles.feedbackSubtitle, dark && styles.feedbackSubtitleDark]}>{subtitle}</Text>
    </View>
  );
}

export default function ExploreScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const [search, setSearch] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [activeFilter, setActiveFilter] = useState('all');
  const [sortBy, setSortBy] = useState('relevance');

  const {
    activeService,
    vendors,
    featuredVendors,
    recentVendors,
    favorites,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    recentSearches,
    homeDeals,
  } = useGrabBasket();

  const debouncedSearch = useDebouncedValue(search);
  const theme = SCREEN_THEME[activeService] || SCREEN_THEME.food;
  const isDark = activeService === 'scenes';
  const filterOptions = FILTERS_BY_SERVICE[activeService] || FILTERS_BY_SERVICE.food;
  const activeFilterDef = filterOptions.find((item) => item.key === activeFilter) || filterOptions[0];
  const activeSortDef = SORT_OPTIONS.find((item) => item.key === sortBy) || SORT_OPTIONS[0];

  useEffect(() => {
    if (!filterOptions.some((item) => item.key === activeFilter)) {
      setActiveFilter('all');
    }
  }, [activeFilter, filterOptions]);

  const queryState = useExploreQuery({
    service: activeService,
    search: debouncedSearch,
    filterKey: activeFilter,
    sortBy,
    vendors,
    deals: homeDeals,
    recentSearches,
    favorites,
  });

  const fallbackVendors = useMemo(() => {
    const source = featuredVendors?.length ? featuredVendors : recentVendors?.length ? recentVendors : vendors || [];
    return source.slice(0, 8);
  }, [featuredVendors, recentVendors, vendors]);

  const fallbackDeals = useMemo(() => (homeDeals?.length ? homeDeals.slice(0, 6) : []), [homeDeals]);

  const isQueryActive = Boolean(normalizeText(debouncedSearch)) || activeFilter !== 'all' || sortBy !== 'relevance';
  const activeControlCount = Number(activeFilter !== 'all') + Number(sortBy !== 'relevance');

  const displayVendors = isQueryActive ? queryState.data.vendors.slice(0, 8) : fallbackVendors;
  const displayDeals = isQueryActive ? queryState.data.deals.slice(0, 6) : fallbackDeals;
  const displayEvents = isQueryActive ? queryState.data.events : SCENE_EVENTS;

  const suggestionItems = useMemo(() => {
    if (queryState.data.suggestions?.length) return queryState.data.suggestions;
    return dedupeStrings([...(recentSearches || []), ...(vendors || []).map((item) => item?.name)]).slice(0, 8);
  }, [queryState.data.suggestions, recentSearches, vendors]);

  const gridItems = QUICK_GRID[activeService] || [];
  const collectionItems =
    activeService === 'warehouse'
      ? WAREHOUSE_COLLECTIONS
      : activeService === 'eatout'
        ? EATOUT_COLLECTIONS
        : activeService === 'scenes'
          ? SCENE_COLLECTIONS
          : FOOD_COLLECTIONS;

  const openVendor = (vendor) => {
    if (!vendor?.id) return;
    rememberStore(vendor.id);
    if (search.trim()) rememberSearch(search.trim());
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const applySuggestion = (value) => {
    const next = String(value || '').trim();
    if (!next) return;
    setSearch(next);
    rememberSearch(next);
  };

  const resetControls = () => {
    setSearch('');
    setActiveFilter('all');
    setSortBy('relevance');
  };

  const emptyQuery = isQueryActive && !queryState.isLoading && queryState.data.empty;

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.page} />

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: tabBarHeight + 24 }}>
        <View style={[styles.headerWrap, isDark && styles.headerWrapDark]}>
          <SectionHeader title={theme.title} subtitle={theme.subtitle} light={isDark} />

          <SearchBar
            value={search}
            onChangeText={setSearch}
            onSubmitEditing={() => rememberSearch(search)}
            onClear={() => setSearch('')}
            onToggleFilters={() => setFiltersOpen((current) => !current)}
            filterCount={activeControlCount}
            filtersOpen={filtersOpen}
            placeholder={theme.placeholder}
            dark={isDark}
          />

          <MetaBanner
            isFetching={queryState.isFetching}
            isFromCache={queryState.isFromCache}
            updatedAt={queryState.updatedAt}
            dark={isDark}
          />

          {filtersOpen ? (
            <View style={[styles.filterPanel, isDark && styles.filterPanelDark]}>
              <Text style={[styles.filterPanelTitle, isDark && styles.filterPanelTitleDark]}>Quick filters</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
                {filterOptions.map((item) => (
                  <Pill
                    key={item.key}
                    label={item.label}
                    icon={item.icon}
                    active={activeFilter === item.key}
                    onPress={() => setActiveFilter(item.key)}
                    dark={isDark}
                  />
                ))}
              </ScrollView>

              <Text style={[styles.filterPanelTitle, isDark && styles.filterPanelTitleDark]}>Sort by</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
                {SORT_OPTIONS.map((item) => (
                  <Pill
                    key={item.key}
                    label={item.label}
                    active={sortBy === item.key}
                    onPress={() => setSortBy(item.key)}
                    dark={isDark}
                    compact
                  />
                ))}
              </ScrollView>
            </View>
          ) : null}

          {suggestionItems.length > 0 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.suggestionRow}>
              {suggestionItems.map((item) => (
                <SuggestionChip key={item} label={item} dark={isDark} onPress={() => applySuggestion(item)} />
              ))}
            </ScrollView>
          ) : null}

          <HeroCard theme={theme} dark={isDark} />
        </View>

        <View style={[styles.body, isDark && styles.bodyDark]}>
          {isQueryActive ? (
            <SummaryCard
              totalMatches={queryState.data.totalMatches}
              search={debouncedSearch}
              filterLabel={activeFilterDef?.label}
              sortLabel={activeSortDef?.label}
              dark={isDark}
              onReset={resetControls}
            />
          ) : null}

          {queryState.isLoading && isQueryActive ? <LoadingState dark={isDark} label="Preparing cached results..." /> : null}
          {emptyQuery ? (
            <EmptyState
              dark={isDark}
              title="No matches found"
              subtitle="Try a broader keyword, switch the filter, or reset the current sort."
            />
          ) : null}

          <SectionHeader
            title={isQueryActive ? 'Matching collections' : activeService === 'scenes' ? 'Featured collections' : 'Curated collections'}
            actionLabel="View all"
            light={isDark}
          />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
            {collectionItems.map((item) => (
              <CollectionCard
                key={item.key}
                item={item}
                service={activeService}
                onPress={() => {
                  if (filterOptions.some((option) => option.key === item.key)) {
                    setActiveFilter(item.key);
                  } else {
                    applySuggestion(item.title);
                  }
                }}
              />
            ))}
          </ScrollView>

          {gridItems.length > 0 ? (
            <>
              <SectionHeader title={activeService === 'warehouse' ? 'Shop by aisle' : 'Browse faster'} />
              <View style={styles.gridWrap}>
                {gridItems.map((item) => {
                  const active = normalizeText(search) === normalizeText(item.label);
                  return (
                    <View key={item.key} style={styles.gridCell}>
                      <GridCard item={item} active={active} onPress={() => applySuggestion(item.label)} />
                    </View>
                  );
                })}
              </View>
            </>
          ) : null}

          {activeService === 'eatout' ? (
            <>
              <SectionHeader title="Shortcuts for tonight" />
              <View style={styles.shortcutWrap}>
                {EATOUT_SHORTCUTS.map((item) => (
                  <View key={item.key} style={styles.shortcutCell}>
                    <ShortcutCard item={item} onPress={() => applySuggestion(item.label)} />
                  </View>
                ))}
              </View>
            </>
          ) : null}

          {activeService === 'scenes' ? (
            <>
              <SectionHeader title={isQueryActive ? 'Matching experiences' : 'Trending experiences'} light subtitle="Cached results stay on screen while the query refreshes in the background." />
              {displayEvents.length === 0 && !queryState.isLoading ? (
                <EmptyState
                  dark
                  title="No experiences found"
                  subtitle="Try comedy, music, workshops or premium to widen the result set."
                />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
                  {displayEvents.map((item) => (
                    <EventCard key={item.id} item={item} />
                  ))}
                </ScrollView>
              )}
            </>
          ) : null}

          {displayDeals.length > 0 ? (
            <>
              <SectionHeader
                title={
                  isQueryActive
                    ? activeService === 'warehouse'
                      ? 'Matching add-ons'
                      : 'Matching quick picks'
                    : activeService === 'warehouse'
                      ? 'Fast add-ons'
                      : 'Trending quick picks'
                }
                actionLabel="View all"
                light={isDark}
              />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
                {displayDeals.map((item) => (
                  <DealCard key={item.id || item.name} item={item} />
                ))}
              </ScrollView>
            </>
          ) : null}

          <SectionHeader
            title={
              isQueryActive
                ? activeService === 'warehouse'
                  ? 'Matching stores'
                  : activeService === 'eatout'
                    ? 'Matching venues'
                    : activeService === 'scenes'
                      ? 'Matching venues'
                      : 'Matching restaurants'
                : activeService === 'warehouse'
                  ? 'Top grocery stores'
                  : activeService === 'eatout'
                    ? 'Places worth booking'
                    : activeService === 'scenes'
                      ? 'Popular venues'
                      : 'Popular picks near you'
            }
            actionLabel="View all"
            light={isDark}
          />
          {displayVendors.length === 0 && !queryState.isLoading ? (
            <EmptyState
              dark={isDark}
              title={activeService === 'warehouse' ? 'No stores found' : 'No venues found'}
              subtitle={
                activeService === 'warehouse'
                  ? 'Try essentials, snacks, drinks or a known brand keyword.'
                  : 'Remove the current filter or try a broader search term.'
              }
            />
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRow}>
              {displayVendors.map((vendor) => (
                <VendorCard
                  key={vendor.id}
                  vendor={vendor}
                  service={activeService}
                  favorite={Boolean(favorites[vendor.id])}
                  onToggleFavorite={() => toggleFavorite(vendor.id)}
                  onPress={() => openVendor(vendor)}
                  dark={isDark}
                />
              ))}
            </ScrollView>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  hiddenIcon: { width: 0, marginRight: 0 },
  safeArea: { flex: 1 },
  headerWrap: { paddingHorizontal: 16, paddingTop: 8 },
  headerWrapDark: { backgroundColor: COLORS.black },
  body: { paddingHorizontal: 16, paddingTop: 14 },
  bodyDark: { backgroundColor: COLORS.black },

  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
    marginBottom: 12,
  },
  sectionTitle: { color: COLORS.text, fontSize: 26, fontWeight: '900' },
  sectionTitleLight: { color: '#ffffff' },
  sectionSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  sectionSubtitleLight: { color: COLORS.darkMuted },
  sectionAction: { color: COLORS.orange, fontSize: 14, fontWeight: '900' },
  sectionActionLight: { color: '#ffffff' },

  searchBar: {
    minHeight: 56,
    borderRadius: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 10,
  },
  searchBarDark: {
    backgroundColor: COLORS.darkSurface,
    borderColor: COLORS.darkBorder,
  },
  searchInput: { flex: 1, color: COLORS.text, fontSize: 15, fontWeight: '600' },
  searchInputDark: { color: '#ffffff' },
  iconButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterButton: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.orangeSoft,
    position: 'relative',
  },
  filterButtonActive: { backgroundColor: COLORS.orange },
  filterBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: COLORS.text,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  filterBadgeText: { color: '#ffffff', fontSize: 10, fontWeight: '900' },

  metaBanner: {
    minHeight: 44,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 10,
  },
  metaBannerDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  metaLeft: { flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 },
  metaTitle: { color: COLORS.text, fontSize: 13, fontWeight: '800', flexShrink: 1 },
  metaTitleDark: { color: '#ffffff' },
  metaTime: { color: COLORS.muted, fontSize: 12, fontWeight: '700' },
  metaTimeDark: { color: COLORS.darkMuted },

  filterPanel: {
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 10,
  },
  filterPanelDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  filterPanelTitle: { color: COLORS.text, fontSize: 13, fontWeight: '900', marginBottom: 10 },
  filterPanelTitleDark: { color: '#ffffff' },

  horizontalRow: { gap: 12, paddingBottom: 8 },
  suggestionRow: { gap: 8, paddingBottom: 10 },
  suggestionChip: {
    maxWidth: 220,
    minHeight: 34,
    paddingHorizontal: 12,
    borderRadius: 17,
    backgroundColor: '#f7f8fb',
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  suggestionChipDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  suggestionChipText: { color: COLORS.text, fontSize: 12, fontWeight: '700', maxWidth: 180 },
  suggestionChipTextDark: { color: '#ffffff' },

  filterPill: {
    minHeight: 38,
    paddingHorizontal: 14,
    borderRadius: 19,
    backgroundColor: '#f7f8fb',
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  filterPillDark: { backgroundColor: COLORS.darkSurfaceAlt, borderColor: COLORS.darkBorder },
  filterPillActive: { backgroundColor: COLORS.orange, borderColor: COLORS.orange },
  filterPillText: { color: COLORS.text, fontSize: 13, fontWeight: '800' },
  filterPillTextDark: { color: '#ffffff' },
  filterPillTextActive: { color: '#ffffff' },

  sortPill: {
    minHeight: 34,
    paddingHorizontal: 14,
    borderRadius: 17,
    backgroundColor: '#f7f8fb',
    borderWidth: 1,
    borderColor: COLORS.border,
    justifyContent: 'center',
  },
  sortPillDark: { backgroundColor: COLORS.darkSurfaceAlt, borderColor: COLORS.darkBorder },
  sortPillActive: { backgroundColor: COLORS.blue, borderColor: COLORS.blue },
  sortPillText: { color: COLORS.text, fontSize: 12, fontWeight: '800' },
  sortPillTextDark: { color: '#ffffff' },
  sortPillTextActive: { color: '#ffffff' },

  heroCard: {
    minHeight: 156,
    borderRadius: 24,
    overflow: 'hidden',
    padding: 20,
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  heroOrbLarge: {
    position: 'absolute',
    right: -26,
    top: -16,
    width: 150,
    height: 150,
    borderRadius: 75,
    opacity: 0.55,
  },
  heroOrbSmall: {
    position: 'absolute',
    left: -20,
    bottom: -40,
    width: 130,
    height: 130,
    borderRadius: 65,
    opacity: 0.35,
  },
  heroTitle: { color: COLORS.text, fontSize: 27, fontWeight: '900', lineHeight: 32, maxWidth: '92%' },
  heroTitleDark: { color: '#ffffff' },
  heroSubtitle: { color: '#6b5c2a', fontSize: 14, fontWeight: '700', lineHeight: 20, marginTop: 8, maxWidth: '92%' },
  heroSubtitleDark: { color: COLORS.darkMuted },
  heroButton: {
    alignSelf: 'flex-start',
    minHeight: 42,
    borderRadius: 14,
    backgroundColor: COLORS.text,
    paddingHorizontal: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroButtonDark: { backgroundColor: '#ffffff' },
  heroButtonText: { color: '#ffffff', fontSize: 14, fontWeight: '900' },
  heroButtonTextDark: { color: COLORS.text },

  summaryCard: {
    borderRadius: 20,
    padding: 16,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 16,
  },
  summaryCardDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  summaryRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  summaryTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  summaryTitleDark: { color: '#ffffff' },
  summaryAction: { color: COLORS.orange, fontSize: 13, fontWeight: '900' },
  summaryActionDark: { color: '#ffffff' },
  summaryChipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 12 },
  summaryChip: {
    minHeight: 32,
    paddingHorizontal: 12,
    borderRadius: 16,
    backgroundColor: '#f7f8fb',
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryChipDark: { backgroundColor: COLORS.darkSurfaceAlt },
  summaryChipText: { color: COLORS.text, fontSize: 12, fontWeight: '800' },
  summaryChipTextDark: { color: '#ffffff' },

  collectionCard: {
    width: 220,
    minHeight: 148,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  collectionCardDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  collectionIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#f4f5f7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  collectionIconWrapDark: { backgroundColor: COLORS.darkSurfaceAlt },
  collectionTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  collectionTitleDark: { color: '#ffffff' },
  collectionSubtitle: { color: COLORS.muted, fontSize: 13, lineHeight: 18, fontWeight: '700' },
  collectionSubtitleDark: { color: COLORS.darkMuted },

  gridWrap: { flexDirection: 'row', flexWrap: 'wrap', marginHorizontal: -6, marginBottom: 12 },
  gridCell: { width: '25%', paddingHorizontal: 6, marginBottom: 12 },
  gridCard: {
    minHeight: 96,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
    paddingVertical: 12,
    gap: 8,
  },
  gridCardActive: { backgroundColor: COLORS.orange, borderColor: COLORS.orange },
  gridIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#f4f5f7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  gridIconWrapActive: { backgroundColor: 'rgba(255,255,255,0.16)' },
  gridLabel: { color: COLORS.text, fontSize: 12, fontWeight: '800', textAlign: 'center' },
  gridLabelActive: { color: '#ffffff' },

  shortcutWrap: { flexDirection: 'row', flexWrap: 'wrap', marginHorizontal: -6, marginBottom: 12 },
  shortcutCell: { width: '50%', paddingHorizontal: 6, marginBottom: 12 },
  shortcutCard: {
    minHeight: 52,
    borderRadius: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  shortcutText: { flex: 1, color: COLORS.text, fontSize: 13, fontWeight: '800' },

  vendorCard: {
    width: 220,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
    marginBottom: 12,
  },
  vendorCardDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  vendorVisual: {
    height: 126,
    borderRadius: 18,
    padding: 12,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  vendorTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  vendorBadge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  vendorBadgeText: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  favoriteButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(0,0,0,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogram: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogramText: { color: '#ffffff', fontSize: 20, fontWeight: '900' },
  vendorName: { color: COLORS.text, fontSize: 16, fontWeight: '900' },
  vendorNameDark: { color: '#ffffff' },
  vendorMeta: { color: COLORS.muted, fontSize: 12, fontWeight: '800', marginTop: 4 },
  vendorMetaDark: { color: COLORS.darkMuted },
  vendorSub: { color: COLORS.subtle, fontSize: 12, fontWeight: '700', marginTop: 4 },
  vendorSubDark: { color: '#aebbd8' },

  dealCard: {
    width: 132,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
    marginBottom: 12,
  },
  dealVisual: {
    height: 76,
    borderRadius: 16,
    backgroundColor: COLORS.orangeSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  dealEmoji: { fontSize: 30 },
  dealName: { color: COLORS.text, fontSize: 13, fontWeight: '800', lineHeight: 18, minHeight: 36 },
  dealPrice: { color: COLORS.orange, fontSize: 14, fontWeight: '900', marginTop: 8 },
  dealVendor: { color: COLORS.muted, fontSize: 11, fontWeight: '700', marginTop: 4 },

  eventCard: {
    width: 240,
    borderRadius: 22,
    backgroundColor: COLORS.darkSurface,
    borderWidth: 1,
    borderColor: COLORS.darkBorder,
    padding: 12,
    marginBottom: 12,
  },
  eventVisual: { height: 140, borderRadius: 18, padding: 14, justifyContent: 'space-between', marginBottom: 12 },
  eventPrice: {
    alignSelf: 'flex-start',
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
    backgroundColor: 'rgba(255,255,255,0.14)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 14,
  },
  eventTitle: { color: '#ffffff', fontSize: 16, fontWeight: '900' },
  eventSubtitle: { color: COLORS.darkMuted, fontSize: 12, fontWeight: '700', lineHeight: 18, marginTop: 4 },

  feedbackCard: {
    minHeight: 110,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    paddingHorizontal: 18,
    paddingVertical: 20,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    marginBottom: 16,
  },
  feedbackCardDark: { backgroundColor: COLORS.darkSurface, borderColor: COLORS.darkBorder },
  feedbackTitle: { color: COLORS.text, fontSize: 17, fontWeight: '900', textAlign: 'center' },
  feedbackTitleDark: { color: '#ffffff' },
  feedbackSubtitle: { color: COLORS.muted, fontSize: 13, fontWeight: '700', lineHeight: 18, textAlign: 'center' },
  feedbackSubtitleDark: { color: COLORS.darkMuted },
});