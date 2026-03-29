import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Image } from 'expo-image';
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
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { BrandPalette, ConsumerServiceThemes, createShadow } from '@/constants/theme';

import { useCachedQuery } from '@/lib/query-cache';
import { useGrabBasket } from '../../../App';
const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');

const STALE_TIME_MS = 60 * 1000;
const CACHE_TIME_MS = 20 * 60 * 1000;
const DEBOUNCE_MS = 280;

const COLORS = {
  ...BrandPalette,
  page: BrandPalette.page,
  pageDark: BrandPalette.sceneBg,
  card: BrandPalette.surface,
  cardDark: BrandPalette.sceneSurface,
  border: BrandPalette.border,
  borderDark: BrandPalette.sceneBorder,
  text: BrandPalette.text,
  textMuted: BrandPalette.textMuted,
  textSubtle: BrandPalette.subtle,
  textDark: BrandPalette.sceneText,
  textMutedDark: BrandPalette.sceneMuted,
  orange: BrandPalette.primary,
  orangeSoft: BrandPalette.primarySoft,
  blue: BrandPalette.inkSoft,
  green: BrandPalette.success,
  danger: BrandPalette.danger,
  pill: '#FFF8F0',
  pillDark: BrandPalette.darkSurfaceAlt,
};

const THEMES = {
  food: {
    ...ConsumerServiceThemes.food,
    title: 'Explore food',
    subtitle: 'Search-first restaurant discovery with calmer spacing and clearer merchant signals.',
    placeholder: 'Search cuisines, dishes, restaurants',
    accent: BrandPalette.peach200,
  },
  warehouse: {
    ...ConsumerServiceThemes.warehouse,
    title: 'Explore instamart',
    subtitle: 'Faster grocery browsing with cleaner aisles, stronger deal visibility and softer surfaces.',
    placeholder: 'Search categories, brands, essentials',
    accent: BrandPalette.primary,
  },
  eatout: {
    ...ConsumerServiceThemes.eatout,
    title: 'Explore dineout',
    subtitle: 'Dining shortcuts, premium filters and better intent capture for faster table decisions.',
    placeholder: 'Search restaurants, vibe, area',
    accent: '#E8A46C',
  },
  scenes: {
    ...ConsumerServiceThemes.scenes,
    title: 'Explore scenes',
    subtitle: 'Event and experience discovery on a branded editorial dark canvas.',
    placeholder: 'Search events, creators, experiences',
    accent: '#5C3D31',
  },
};

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

const EMPTY_QUERY_DATA = Object.freeze({
  vendors: [],
  deals: [],
  events: [],
  suggestions: [],
  totalMatches: 0,
  empty: false,
});

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

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return Number((4.1 + (seed % 8) * 0.1).toFixed(1));
}

function getEtaLabel(vendor, service = 'food') {
  if (service === 'warehouse') return '5-15 mins';
  if (service === 'eatout') return 'Table in 10-15 mins';
  if (service === 'scenes') return 'Instant confirmation';
  if (vendor?.distance_km != null) {
    if (Number(vendor.distance_km) <= 2) return '15-20 mins';
    if (Number(vendor.distance_km) <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorMeta(vendor, service = 'food') {
  if (service === 'warehouse') {
    return vendor?.description || 'Essentials, snacks and quick home needs';
  }
  if (service === 'eatout') {
    return vendor?.description || 'Reserve tables, unlock bill offers and skip decision fatigue';
  }
  return vendor?.description || vendor?.address || 'Comfort food, premium presentation and reliable delivery';
}

function getVendorBadge(vendor, service = 'food') {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  const sets = {
    food: ['40% OFF', 'FREE DELIVERY', 'TOP PICK', 'BESTSELLER'],
    warehouse: ['₹9 DEAL', 'VALUE PICK', 'FAST ADD', 'DAILY SAVER'],
    eatout: ['FLAT 50% OFF', 'BANK OFFER', 'PRE-BOOK', 'EXTRA CASHBACK'],
    scenes: ['TRENDING', 'POPULAR', 'LIMITED', 'WEEKEND'],
  };

  const options = sets[service] || sets.food;
  return options[seed % options.length];
}

function getVendorSearchText(vendor, service = 'food') {
  return normalizeText(
    [
      vendor?.name,
      vendor?.description,
      vendor?.address,
      service,
      getVendorMeta(vendor, service),
      getVendorBadge(vendor, service),
    ].join(' ')
  );
}

function getDealSearchText(item) {
  return normalizeText([item?.name, item?.vendorName, item?.brand, item?.description].join(' '));
}

function getEventSearchText(item) {
  return normalizeText([item?.title, item?.subtitle, item?.bucket].join(' '));
}

function getVendorScore(vendor, service, query, favorites) {
  let score = 0;
  const haystack = getVendorSearchText(vendor, service);

  if (!query) {
    score += 10;
  } else if (haystack.includes(query)) {
    score += 30;
    if (normalizeText(vendor?.name).startsWith(query)) score += 10;
  }

  const rating = getVendorRating(vendor);
  score += Math.round(rating * 4);

  const distance = Number(vendor?.distance_km ?? 99);
  if (distance <= 2) score += 12;
  else if (distance <= 5) score += 7;

  if (favorites?.[vendor?.id]) score += 8;
  if (/offer|free|discount|deal|save|cashback/i.test(haystack)) score += 4;

  return score;
}

function getDealScore(item, query) {
  let score = 0;
  const haystack = getDealSearchText(item);

  if (!query) score += 8;
  else if (haystack.includes(query)) score += 26;

  if (Number(item?.price || 0) <= 99) score += 8;
  if (Number(item?.price || 0) <= 49) score += 4;

  return score;
}

function getEventScore(item, query) {
  let score = 0;
  const haystack = getEventSearchText(item);

  if (!query) score += 8;
  else if (haystack.includes(query)) score += 24;

  if (item?.bucket === 'premium') score += 4;
  return score;
}

function matchesVendorFilter(vendor, service, filterKey, favorites) {
  if (filterKey === 'all') return true;
  if (filterKey === 'favorites') return Boolean(favorites?.[vendor?.id]);

  const haystack = getVendorSearchText(vendor, service);
  const rating = getVendorRating(vendor);
  const distance = Number(vendor?.distance_km ?? 99);

  if (filterKey === 'fast') return service === 'warehouse' ? true : distance <= 5;
  if (filterKey === 'topRated') return rating >= 4.5;
  if (filterKey === 'offers') return /offer|discount|deal|save|cashback|free/i.test(haystack);
  if (filterKey === 'biryani') return /biryani/.test(haystack);
  if (filterKey === 'dessert') return /dessert|cake|ice cream|sweet|brownie|jar/.test(haystack);
  if (filterKey === 'healthy') return /healthy|salad|bowl|protein|juice/.test(haystack);
  if (filterKey === 'daily') return /milk|bread|egg|breakfast|daily|essentials/.test(haystack);
  if (filterKey === 'fresh') return /fruit|vegetable|fresh|greens|dairy/.test(haystack);
  if (filterKey === 'snacks') return /snack|chips|cracker|chocolate|treat/.test(haystack);
  if (filterKey === 'beverages') return /drink|juice|coffee|tea|water|cola/.test(haystack);
  if (filterKey === 'beauty') return /beauty|cream|soap|shampoo|skin|hair/.test(haystack);
  if (filterKey === 'cafes') return /cafe|coffee|dessert|bakery/.test(haystack);
  if (filterKey === 'group') return /family|group|large|table|friends/.test(haystack);
  if (filterKey === 'premium') return /premium|fine|chef|rooftop|curated/.test(haystack) || rating >= 4.7;
  if (filterKey === 'rooftop') return /rooftop|terrace|view/.test(haystack);

  return true;
}

function matchesDealFilter(item, filterKey) {
  if (filterKey === 'all' || filterKey === 'favorites' || filterKey === 'topRated') return true;

  const haystack = getDealSearchText(item);

  if (filterKey === 'offers') return true;
  if (filterKey === 'biryani') return /biryani/.test(haystack);
  if (filterKey === 'dessert') return /dessert|cake|ice cream|sweet|brownie|jar/.test(haystack);
  if (filterKey === 'healthy') return /healthy|salad|juice|protein/.test(haystack);
  if (filterKey === 'daily') return /milk|bread|egg|daily|essentials/.test(haystack);
  if (filterKey === 'fresh') return /fruit|vegetable|fresh|greens|dairy/.test(haystack);
  if (filterKey === 'snacks') return /snack|chips|cracker|chocolate|treat/.test(haystack);
  if (filterKey === 'beverages') return /drink|juice|coffee|tea|water|cola/.test(haystack);
  if (filterKey === 'beauty') return /beauty|cream|soap|shampoo|skin|hair/.test(haystack);

  return true;
}

function matchesEventFilter(item, filterKey) {
  if (filterKey === 'all') return true;
  if (filterKey === 'weekend') return true;
  return item?.bucket === filterKey;
}

function sortByOption(list = [], sortBy = 'relevance', type = 'vendor') {
  return [...list].sort((a, b) => {
    if (sortBy === 'name') {
      const aLabel = type === 'event' ? String(a?.title || '') : String(a?.name || '');
      const bLabel = type === 'event' ? String(b?.title || '') : String(b?.name || '');
      return aLabel.localeCompare(bLabel);
    }

    if (sortBy === 'rating') {
      const aRating = type === 'vendor' ? Number(a?.__rating || 0) : 0;
      const bRating = type === 'vendor' ? Number(b?.__rating || 0) : 0;
      return bRating - aRating || (b.__score || 0) - (a.__score || 0);
    }

    if (sortBy === 'delivery') {
      if (type === 'vendor') {
        return Number(a?.distance_km ?? 999) - Number(b?.distance_km ?? 999);
      }
      return Number(a?.price || 0) - Number(b?.price || 0);
    }

    return (b.__score || 0) - (a.__score || 0);
  });
}

function buildSourceSignature(vendors = [], deals = [], recentSearches = [], favorites = {}) {
  const vendorPart = (vendors || [])
    .slice(0, 20)
    .map((item) => `${item?.id}-${item?.name}-${item?.distance_km}`)
    .join('|');

  const dealPart = (deals || [])
    .slice(0, 20)
    .map((item) => `${item?.id || item?.name}-${item?.name}-${item?.price}`)
    .join('|');

  const favoritePart = Object.keys(favorites || {})
    .filter((key) => favorites[key])
    .sort()
    .join('|');

  return `${vendors.length}:${deals.length}:${recentSearches.length}:${vendorPart}:${dealPart}:${favoritePart}`;
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

  const vendorResults = sortByOption(
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
          __eta: getEtaLabel(vendor, service),
        };
      })
      .filter(Boolean),
    sortBy,
    'vendor'
  );

  const dealResults = sortByOption(
    (deals || [])
      .map((item) => {
        const score = getDealScore(item, query);
        const passesSearch = !query || score > 0 || getDealSearchText(item).includes(query);
        const passesFilter = matchesDealFilter(item, filterKey);
        if (!passesSearch || !passesFilter) return null;

        return {
          ...item,
          __score: score,
        };
      })
      .filter(Boolean),
    sortBy,
    'deal'
  );

  const eventResults =
    service === 'scenes'
      ? sortByOption(
          SCENE_EVENTS.map((item) => {
            const score = getEventScore(item, query);
            const passesSearch = !query || score > 0 || getEventSearchText(item).includes(query);
            const passesFilter = matchesEventFilter(item, filterKey);
            if (!passesSearch || !passesFilter) return null;

            return {
              ...item,
              __score: score,
            };
          }).filter(Boolean),
          sortBy,
          'event'
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
    () => buildSourceSignature(vendors, deals, recentSearches, favorites),
    [vendors, deals, recentSearches, favorites]
  );

  const queryKey = useMemo(
    () => ['consumer', 'explore', service, normalizeText(search), filterKey, sortBy, sourceSignature],
    [service, search, filterKey, sortBy, sourceSignature]
  );

  const fetcher = useCallback(
    async () =>
      computeQueryData({
        service,
        search,
        filterKey,
        sortBy,
        vendors,
        deals,
        recentSearches,
        favorites,
      }),
    [service, search, filterKey, sortBy, vendors, deals, recentSearches, favorites]
  );

  const query = useCachedQuery({
    queryKey,
    staleTime: STALE_TIME_MS,
    cacheTime: CACHE_TIME_MS,
    keepPreviousData: true,
    refetchOnMount: 'stale',
    refetchOnAppFocus: true,
    retry: 0,
    initialData: EMPTY_QUERY_DATA,
    fetcher,
  });

  return {
    data: query.data || EMPTY_QUERY_DATA,
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    isFromCache: query.isFromCache,
    updatedAt: query.updatedAt || 0,
    refresh: query.refresh,
  };
}

function SectionHeader({ title, subtitle, light = false, actionLabel, onActionPress }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleDark]}>{title}</Text>
        {subtitle ? <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleDark]}>{subtitle}</Text> : null}
      </View>

      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onActionPress}>
          <Text style={[styles.sectionAction, light && styles.sectionActionDark]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function SearchBar({ value, onChangeText, onSubmitEditing, onClear, placeholder, dark = false }) {
  return (
    <View style={[styles.searchBar, dark && styles.searchBarDark]}>
      <Ionicons name="search-outline" size={18} color={dark ? COLORS.textMutedDark : COLORS.textMuted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        onSubmitEditing={onSubmitEditing}
        placeholder={placeholder}
        placeholderTextColor={dark ? '#8fa2c4' : COLORS.textSubtle}
        style={[styles.searchInput, dark && styles.searchInputDark]}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
      />
      {value ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onClear}>
          <Ionicons name="close-outline" size={18} color={dark ? COLORS.textDark : COLORS.textMuted} />
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function MetaBanner({ isFetching, isFromCache, updatedAt, onRefresh, dark = false }) {
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
          <ActivityIndicator size="small" color={dark ? COLORS.textDark : COLORS.orange} />
        ) : (
          <Ionicons
            name={isFromCache ? 'cloud-outline' : 'sparkles-outline'}
            size={16}
            color={dark ? COLORS.textDark : COLORS.orange}
          />
        )}
        <Text style={[styles.metaText, dark && styles.metaTextDark]}>{title}</Text>
      </View>

      <TouchableOpacity activeOpacity={0.92} onPress={() => onRefresh?.({ force: true, reason: 'manual', throwOnError: false })}>
        <Text style={[styles.metaTime, dark && styles.metaTimeDark]}>{formatAge(updatedAt)}</Text>
      </TouchableOpacity>
    </View>
  );
}

function Chip({ label, icon, active, onPress, dark = false, compact = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[
        compact ? styles.sortChip : styles.filterChip,
        dark && (compact ? styles.sortChipDark : styles.filterChipDark),
        active && (compact ? styles.sortChipActive : styles.filterChipActive),
      ]}>
      {!compact ? (
        <Ionicons
          name={icon}
          size={14}
          color={active ? '#ffffff' : dark ? COLORS.textMutedDark : COLORS.textMuted}
        />
      ) : null}
      <Text
        style={[
          compact ? styles.sortChipText : styles.filterChipText,
          dark && (compact ? styles.sortChipTextDark : styles.filterChipTextDark),
          active && (compact ? styles.sortChipTextActive : styles.filterChipTextActive),
        ]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function SuggestionChip({ label, onPress, dark = false }) {
  return (
    <TouchableOpacity activeOpacity={0.92} onPress={onPress} style={[styles.suggestionChip, dark && styles.suggestionChipDark]}>
      <Ionicons name="time-outline" size={14} color={dark ? COLORS.textMutedDark : COLORS.textMuted} />
      <Text style={[styles.suggestionText, dark && styles.suggestionTextDark]} numberOfLines={1}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function VendorCard({ vendor, service, favorite, onToggleFavorite, onPress, dark = false }) {
  const visualColor =
    service === 'scenes'
      ? '#2C221D'
      : service === 'warehouse'
        ? '#F6E6CB'
        : service === 'eatout'
          ? '#EEDBCC'
          : COLORS.primary;

  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={[styles.vendorCard, dark && styles.vendorCardDark]}>
      <View style={[styles.vendorVisual, { backgroundColor: visualColor }]}>
        <View style={styles.vendorTopRow}>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{getVendorBadge(vendor, service)}</Text>
          </View>
          <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite} style={styles.favoriteButton}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color="#ffffff" />
          </TouchableOpacity>
        </View>

        <View style={styles.vendorMonogram}>
          <Text style={styles.vendorMonogramText}>{initials(vendor?.name)}</Text>
        </View>
      </View>

      <Text style={[styles.cardTitle, dark && styles.cardTitleDark]} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={[styles.cardMeta, dark && styles.cardMetaDark]} numberOfLines={1}>
        ★ {getVendorRating(vendor).toFixed(1)} · {getEtaLabel(vendor, service)}
      </Text>
      <Text style={[styles.cardSub, dark && styles.cardSubDark]} numberOfLines={2}>
        {getVendorMeta(vendor, service)}
      </Text>
    </TouchableOpacity>
  );
}

function DealCard({ item, dark = false }) {
  return (
    <View style={[styles.dealCard, dark && styles.dealCardDark]}>
      <View style={styles.dealVisual}>
        <Text style={styles.dealEmoji}>🧺</Text>
      </View>
      <Text style={[styles.cardTitle, dark && styles.cardTitleDark]} numberOfLines={2}>
        {item?.name}
      </Text>
      <Text style={styles.dealPrice}>{money(item?.price)}</Text>
      <Text style={[styles.cardSub, dark && styles.cardSubDark]} numberOfLines={1}>
        {item?.vendorName || item?.brand || 'Featured deal'}
      </Text>
    </View>
  );
}

function EventCard({ item }) {
  return (
    <View style={styles.eventCard}>
      <View style={[styles.eventVisual, { backgroundColor: item?.accent || '#223150' }]}>
        <Ionicons name={item?.icon || 'sparkles-outline'} size={24} color="#ffffff" />
        <Text style={styles.eventPrice}>Starts at {money(item?.price)}</Text>
      </View>
      <Text style={[styles.cardTitle, styles.cardTitleDark]} numberOfLines={2}>
        {item?.title}
      </Text>
      <Text style={[styles.cardSub, styles.cardSubDark]} numberOfLines={2}>
        {item?.subtitle}
      </Text>
    </View>
  );
}

function EmptyState({ title, subtitle, dark = false }) {
  return (
    <View style={[styles.emptyState, dark && styles.emptyStateDark]}>
      <Text style={[styles.emptyTitle, dark && styles.emptyTitleDark]}>{title}</Text>
      <Text style={[styles.emptySubtitle, dark && styles.emptySubtitleDark]}>{subtitle}</Text>
    </View>
  );
}

export default function ExploreScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const [search, setSearch] = useState('');
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

  const theme = THEMES[activeService] || THEMES.food;
  const isDark = activeService === 'scenes';
  const debouncedSearch = useDebouncedValue(search);
  const filterOptions = FILTERS_BY_SERVICE[activeService] || FILTERS_BY_SERVICE.food;

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

  const isQueryActive = Boolean(normalizeText(debouncedSearch)) || activeFilter !== 'all' || sortBy !== 'relevance';

  const fallbackVendors = useMemo(() => {
    const source =
      featuredVendors?.length > 0
        ? featuredVendors
        : recentVendors?.length > 0
          ? recentVendors
          : vendors || [];
    return source.slice(0, 8);
  }, [featuredVendors, recentVendors, vendors]);

  const fallbackDeals = useMemo(() => (homeDeals?.length ? homeDeals.slice(0, 6) : []), [homeDeals]);

  const displayVendors = isQueryActive ? queryState.data.vendors.slice(0, 8) : fallbackVendors;
  const displayDeals = isQueryActive ? queryState.data.deals.slice(0, 6) : fallbackDeals;
  const displayEvents = isQueryActive ? queryState.data.events : SCENE_EVENTS;

  const suggestionItems = useMemo(() => {
    if (queryState.data.suggestions?.length) return queryState.data.suggestions;
    return dedupeStrings([...(recentSearches || []), ...(vendors || []).map((item) => item?.name)]).slice(0, 8);
  }, [queryState.data.suggestions, recentSearches, vendors]);

  const openVendor = useCallback(
    (vendor) => {
      if (!vendor?.id) return;
      rememberStore(vendor.id);
      if (search.trim()) rememberSearch(search.trim());
      router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
    },
    [rememberSearch, rememberStore, router, search]
  );

  const openDealVendor = useCallback(
    (item) => {
      const vendorId = item?.vendor_id || item?.vendorId || item?.vendor_id_snapshot;
      if (!vendorId) return;
      const vendor = (vendors || []).find((row) => String(row?.id) === String(vendorId));
      if (vendor) openVendor(vendor);
    },
    [openVendor, vendors]
  );

  const applySuggestion = useCallback(
    (value) => {
      const next = String(value || '').trim();
      if (!next) return;
      setSearch(next);
      rememberSearch(next);
    },
    [rememberSearch]
  );

  const resetControls = useCallback(() => {
    setSearch('');
    setActiveFilter('all');
    setSortBy('relevance');
  }, []);

  const activeControlCount = Number(activeFilter !== 'all') + Number(sortBy !== 'relevance');
  const emptyQuery = isQueryActive && !queryState.isLoading && queryState.data.empty;

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.page} />

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: tabBarHeight + 24 }}>
        <View style={[styles.hero, { backgroundColor: theme.hero }]}>
          <View style={[styles.heroOrbLarge, { backgroundColor: theme.accent }]} />
          <View style={[styles.heroOrbSmall, { backgroundColor: theme.accent }]} />

          <View style={styles.heroBrandRow}>
            <View style={styles.heroBrandPill}>
              <Image source={BRAND_LOGO} style={styles.heroBrandLogo} contentFit="contain" />
              <View>
                <Text style={[styles.heroBrandTitle, isDark && styles.heroBrandTitleDark]}>
                  Grab Basket
                </Text>
                <Text style={[styles.heroBrandCopy, isDark && styles.heroBrandCopyDark]}>
                  Search-led discovery across the Grab Basket network
                </Text>
              </View>
            </View>

            <View style={[styles.heroStatePill, isDark && styles.heroStatePillDark]}>
              <Ionicons
                name="flash-outline"
                size={14}
                color={isDark ? COLORS.textDark : COLORS.orange}
              />
              <Text style={[styles.heroStateText, isDark && styles.heroStateTextDark]}>
                {queryState.data.totalMatches || fallbackVendors.length} picks
              </Text>
            </View>
          </View>

          <SectionHeader title={theme.title} subtitle={theme.subtitle} light={isDark} />

          <SearchBar
            value={search}
            onChangeText={setSearch}
            onSubmitEditing={() => {
              if (search.trim()) rememberSearch(search.trim());
            }}
            onClear={() => setSearch('')}
            placeholder={theme.placeholder}
            dark={isDark}
          />

          <MetaBanner
            isFetching={queryState.isFetching}
            isFromCache={queryState.isFromCache}
            updatedAt={queryState.updatedAt}
            onRefresh={queryState.refresh}
            dark={isDark}
          />
        </View>

        <View style={[styles.body, isDark && styles.bodyDark]}>
          <SectionHeader
            title="Filters"
            subtitle={`Active controls: ${activeControlCount}`}
            actionLabel={activeControlCount ? 'Reset' : ''}
            onActionPress={resetControls}
            light={isDark}
          />

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
            {filterOptions.map((item) => (
              <Chip
                key={item.key}
                label={item.label}
                icon={item.icon}
                active={activeFilter === item.key}
                onPress={() => setActiveFilter(item.key)}
                dark={isDark}
              />
            ))}
          </ScrollView>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
            {SORT_OPTIONS.map((item) => (
              <Chip
                key={item.key}
                label={item.label}
                active={sortBy === item.key}
                onPress={() => setSortBy(item.key)}
                dark={isDark}
                compact
              />
            ))}
          </ScrollView>

          <SectionHeader title="Suggestions" subtitle="Powered by recent intent + cached consumer data." light={isDark} />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
            {suggestionItems.length ? (
              suggestionItems.map((item) => (
                <SuggestionChip key={item} label={item} onPress={() => applySuggestion(item)} dark={isDark} />
              ))
            ) : (
              <Text style={[styles.helperText, isDark && styles.helperTextDark]}>No suggestions yet.</Text>
            )}
          </ScrollView>

          {queryState.isLoading && !isQueryActive ? (
            <View style={[styles.loadingCard, isDark && styles.loadingCardDark]}>
              <ActivityIndicator color={isDark ? '#ffffff' : COLORS.orange} />
              <Text style={[styles.helperText, isDark && styles.helperTextDark]}>Preparing consumer discovery…</Text>
            </View>
          ) : null}

          {emptyQuery ? (
            <EmptyState
              title="No matches found"
              subtitle="Try a broader search, reset filters, or pick a suggestion."
              dark={isDark}
            />
          ) : null}

          <SectionHeader
            title={isQueryActive ? `Stores (${queryState.data.vendors.length})` : 'Featured stores'}
            subtitle={
              isQueryActive
                ? `${queryState.data.totalMatches} total matches across stores, deals and events.`
                : 'Curated from your consumer feed and recent behavior.'
            }
            light={isDark}
          />

          {displayVendors.length ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
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
          ) : (
            <EmptyState
              title="No stores available"
              subtitle="Your vendor feed is empty for this service right now."
              dark={isDark}
            />
          )}

          {activeService !== 'scenes' ? (
            <>
              <SectionHeader
                title={isQueryActive ? `Deals (${queryState.data.deals.length})` : 'Trending deals'}
                subtitle="Quick product discovery now shares the same cache lifecycle as the other apps."
                light={isDark}
              />

              {displayDeals.length ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
                  {displayDeals.map((item) => (
                    <TouchableOpacity
                      key={String(item.id || item.name)}
                      activeOpacity={0.92}
                      onPress={() => openDealVendor(item)}>
                      <DealCard item={item} dark={isDark} />
                    </TouchableOpacity>
                  ))}
                </ScrollView>
              ) : (
                <EmptyState
                  title="No quick deals yet"
                  subtitle="Once product feeds are richer, this section will feel much stronger."
                  dark={isDark}
                />
              )}
            </>
          ) : null}

          {activeService === 'scenes' ? (
            <>
              <SectionHeader
                title={isQueryActive ? `Experiences (${queryState.data.events.length})` : 'Trending experiences'}
                subtitle="Scene discovery is now included in the same shared cache model."
                light={isDark}
              />

              {displayEvents.length ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
                  {displayEvents.map((item) => (
                    <EventCard key={item.id} item={item} />
                  ))}
                </ScrollView>
              ) : (
                <EmptyState
                  title="No experiences found"
                  subtitle="Try another keyword or reset your active filters."
                  dark={isDark}
                />
              )}
            </>
          ) : null}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },

  hero: {
    paddingHorizontal: 18,
    paddingTop: 12,
    paddingBottom: 24,
    overflow: 'hidden',
  },
  heroOrbLarge: {
    position: 'absolute',
    right: -28,
    top: -24,
    width: 140,
    height: 140,
    borderRadius: 70,
    opacity: 0.45,
  },
  heroOrbSmall: {
    position: 'absolute',
    left: -22,
    bottom: -28,
    width: 120,
    height: 120,
    borderRadius: 60,
    opacity: 0.22,
  },

  body: {
    paddingHorizontal: 18,
    paddingTop: 18,
    backgroundColor: COLORS.page,
  },
  bodyDark: {
    backgroundColor: COLORS.pageDark,
  },

  heroBrandRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 14,
  },
  heroBrandPill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 26,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: 'rgba(255,251,246,0.94)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
  },
  heroBrandLogo: {
    width: 34,
    height: 34,
  },
  heroBrandTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },
  heroBrandTitleDark: {
    color: COLORS.textDark,
  },
  heroBrandCopy: {
    marginTop: 2,
    color: COLORS.textMuted,
    fontSize: 11,
    lineHeight: 16,
    fontWeight: '700',
  },
  heroBrandCopyDark: {
    color: COLORS.textMutedDark,
  },
  heroStatePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 13,
    paddingVertical: 10,
    backgroundColor: 'rgba(255,251,246,0.94)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
  },
  heroStatePillDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderColor: 'rgba(255,255,255,0.10)',
  },
  heroStateText: {
    color: COLORS.text,
    fontSize: 11,
    fontWeight: '900',
  },
  heroStateTextDark: {
    color: COLORS.textDark,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    marginBottom: 12,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 19,
    fontWeight: '900',
  },
  sectionTitleDark: {
    color: COLORS.textDark,
  },
  sectionSubtitle: {
    color: COLORS.textMuted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
    marginTop: 4,
  },
  sectionSubtitleDark: {
    color: COLORS.textMutedDark,
  },
  sectionAction: {
    color: COLORS.orange,
    fontSize: 13,
    fontWeight: '900',
    marginTop: 2,
  },
  sectionActionDark: {
    color: COLORS.textDark,
  },

  searchBar: {
    minHeight: 58,
    borderRadius: 26,
    backgroundColor: 'rgba(255,251,246,0.98)',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 17,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 12,
    ...createShadow(0.1, 18, 8),
  },
  searchBarDark: {
    backgroundColor: COLORS.cardDark,
    borderColor: COLORS.borderDark,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  searchInputDark: {
    color: COLORS.textDark,
  },

  metaBanner: {
    minHeight: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,251,246,0.96)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  metaBannerDark: {
    backgroundColor: 'rgba(16,26,45,0.78)',
    borderColor: 'rgba(255,255,255,0.08)',
  },
  metaLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flex: 1,
  },
  metaText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '800',
    flexShrink: 1,
  },
  metaTextDark: {
    color: COLORS.textDark,
  },
  metaTime: {
    color: COLORS.textMuted,
    fontSize: 11,
    fontWeight: '800',
  },
  metaTimeDark: {
    color: COLORS.textMutedDark,
  },

  row: {
    gap: 10,
    paddingBottom: 12,
    marginBottom: 6,
  },

  filterChip: {
    minHeight: 38,
    paddingHorizontal: 15,
    borderRadius: 22,
    backgroundColor: COLORS.chip,
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  filterChipDark: {
    backgroundColor: COLORS.pillDark,
    borderColor: COLORS.borderDark,
  },
  filterChipActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  filterChipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  filterChipTextDark: {
    color: COLORS.textDark,
  },
  filterChipTextActive: {
    color: '#ffffff',
  },

  sortChip: {
    minHeight: 34,
    paddingHorizontal: 14,
    borderRadius: 18,
    backgroundColor: COLORS.chip,
    borderWidth: 1,
    borderColor: COLORS.border,
    justifyContent: 'center',
  },
  sortChipDark: {
    backgroundColor: COLORS.pillDark,
    borderColor: COLORS.borderDark,
  },
  sortChipActive: {
    backgroundColor: COLORS.inkSoft,
    borderColor: COLORS.inkSoft,
  },
  sortChipText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '800',
  },
  sortChipTextDark: {
    color: COLORS.textDark,
  },
  sortChipTextActive: {
    color: '#ffffff',
  },

  suggestionChip: {
    minHeight: 34,
    maxWidth: 220,
    paddingHorizontal: 12,
    borderRadius: 18,
    backgroundColor: COLORS.chip,
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  suggestionChipDark: {
    backgroundColor: COLORS.pillDark,
    borderColor: COLORS.borderDark,
  },
  suggestionText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
    maxWidth: 180,
  },
  suggestionTextDark: {
    color: COLORS.textDark,
  },

  loadingCard: {
    minHeight: 88,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    marginBottom: 18,
    ...createShadow(0.05, 12, 6),
  },
  loadingCardDark: {
    backgroundColor: COLORS.cardDark,
    borderColor: COLORS.borderDark,
  },

  helperText: {
    color: COLORS.textMuted,
    fontSize: 13,
    fontWeight: '700',
  },
  helperTextDark: {
    color: COLORS.textMutedDark,
  },

  emptyState: {
    minHeight: 112,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    gap: 8,
    marginBottom: 18,
    ...createShadow(0.05, 12, 6),
  },
  emptyStateDark: {
    backgroundColor: COLORS.cardDark,
    borderColor: COLORS.borderDark,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
    textAlign: 'center',
  },
  emptyTitleDark: {
    color: COLORS.textDark,
  },
  emptySubtitle: {
    color: COLORS.textMuted,
    fontSize: 13,
    fontWeight: '700',
    lineHeight: 18,
    textAlign: 'center',
  },
  emptySubtitleDark: {
    color: COLORS.textMutedDark,
  },

  vendorCard: {
    width: 220,
    borderRadius: 28,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    ...createShadow(0.08, 16, 8),
  },
  vendorCardDark: {
    backgroundColor: COLORS.cardDark,
    borderColor: COLORS.borderDark,
  },
  vendorVisual: {
    height: 124,
    borderRadius: 18,
    padding: 12,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  vendorTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.18)',
  },
  badgeText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  favoriteButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(0,0,0,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogram: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogramText: {
    color: '#ffffff',
    fontSize: 20,
    fontWeight: '900',
  },

  dealCard: {
    width: 138,
    borderRadius: 26,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 13,
    ...createShadow(0.08, 16, 8),
  },
  dealCardDark: {
    backgroundColor: COLORS.cardDark,
    borderColor: COLORS.borderDark,
  },
  dealVisual: {
    height: 78,
    borderRadius: 16,
    backgroundColor: COLORS.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  dealEmoji: {
    fontSize: 30,
  },
  dealPrice: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: '900',
    marginTop: 8,
  },

  eventCard: {
    width: 240,
    borderRadius: 26,
    backgroundColor: COLORS.cardDark,
    borderWidth: 1,
    borderColor: COLORS.borderDark,
    padding: 12,
    ...createShadow(0.14, 16, 8),
  },
  eventVisual: {
    height: 132,
    borderRadius: 18,
    padding: 14,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
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

  cardTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  cardTitleDark: {
    color: COLORS.textDark,
  },
  cardMeta: {
    color: COLORS.textMuted,
    fontSize: 12,
    fontWeight: '800',
    marginTop: 4,
  },
  cardMetaDark: {
    color: COLORS.textMutedDark,
  },
  cardSub: {
    color: COLORS.textSubtle,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
    lineHeight: 17,
  },
  cardSubDark: {
    color: COLORS.textMutedDark,
  },
});
