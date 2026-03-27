import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
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
import { useGrabBasket } from '../../../App';

const PALETTE = {
  page: '#FFF9F3',
  surface: '#FFFFFF',
  surfaceAlt: '#FFF6EC',
  peach50: '#FFF7EE',
  peach100: '#FFF0DE',
  peach200: '#FFE5B4',
  peach300: '#FFD8AA',
  peach400: '#F4BC92',
  peach500: '#E8956E',
  peach600: '#D97651',
  text: '#2F241C',
  muted: '#756354',
  subtle: '#A18C7B',
  border: '#F2DDC7',
  line: '#F4E6D7',
  success: '#2E8B57',
  successSoft: '#EAF7EF',
  danger: '#D45454',
  dangerSoft: '#FCE9E9',
  brown: '#5A4333',
  brownDark: '#3A2A20',
  sceneBg: '#16110D',
  sceneSurface: '#231B14',
  sceneBorder: '#413226',
  sceneText: '#FFF7F0',
  sceneMuted: '#DCC5AF',
};

const SERVICE_TABS = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline', hint: 'Everyday meals' },
  { key: 'warehouse', label: 'Instamart', icon: 'basket-outline', hint: 'Quick grocery' },
  { key: 'eatout', label: 'Dineout', icon: 'restaurant-outline', hint: 'Tables & offers' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline', hint: 'Events & plans' },
];

const THEMES = {
  food: {
    page: PALETTE.page,
    hero: PALETTE.peach100,
    heroSoft: PALETTE.peach50,
    heroAccent: PALETTE.peach300,
    heroText: PALETTE.text,
    heroSub: PALETTE.muted,
    searchPlaceholder: 'Search biryani, cake, dosa...',
    bannerEyebrow: 'Freshly picked for today',
    bannerTitle: 'Made for hungry moments',
    bannerCopy: 'Better hierarchy, warmer visuals and stronger restaurant cards.',
    statusBar: 'dark-content',
  },
  warehouse: {
    page: '#FFFBF7',
    hero: '#FFF0E0',
    heroSoft: '#FFF8F1',
    heroAccent: '#F7D1B0',
    heroText: PALETTE.text,
    heroSub: PALETTE.muted,
    searchPlaceholder: 'Search fruits, dry fruits, dairy...',
    bannerEyebrow: 'Essentials in minutes',
    bannerTitle: 'Fast baskets, cleaner browsing',
    bannerCopy: 'Promos, categories and quick add cards feel more premium now.',
    statusBar: 'dark-content',
  },
  eatout: {
    page: '#FFF8F4',
    hero: '#FFEBDC',
    heroSoft: '#FFF7F1',
    heroAccent: '#F4C4A4',
    heroText: PALETTE.text,
    heroSub: PALETTE.muted,
    searchPlaceholder: 'Search restaurant, area or vibe...',
    bannerEyebrow: 'Plans made easier',
    bannerTitle: 'Book tables without the clutter',
    bannerCopy: 'Offer-led tiles and softer surfaces make dineout feel more polished.',
    statusBar: 'dark-content',
  },
  scenes: {
    page: PALETTE.sceneBg,
    hero: PALETTE.sceneSurface,
    heroSoft: '#2D2219',
    heroAccent: '#5B4030',
    heroText: PALETTE.sceneText,
    heroSub: PALETTE.sceneMuted,
    searchPlaceholder: 'Search events, creators, experiences...',
    bannerEyebrow: 'Weekend energy',
    bannerTitle: 'Plans worth stepping out for',
    bannerCopy: 'Peach accents keep the brand cohesive even on dark surfaces.',
    statusBar: 'light-content',
  },
};

const FOOD_COLLECTIONS = [
  { key: 'top-rated', label: 'Top rated', icon: 'star-outline' },
  { key: 'budget', label: 'Budget meals', icon: 'cash-outline' },
  { key: 'desserts', label: 'Desserts', icon: 'ice-cream-outline' },
  { key: 'healthy', label: 'Healthy', icon: 'leaf-outline' },
];

const MART_COLLECTIONS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'snacks', label: 'Snacks', icon: 'nutrition-outline' },
  { key: 'value', label: 'Value', icon: 'pricetag-outline' },
  { key: 'essentials', label: 'Essentials', icon: 'home-outline' },
];

const DINE_SHORTCUTS = [
  { key: 'offers', title: 'Flat 50% OFF', subtitle: 'Bill offers', icon: 'pricetag-outline', large: true },
  { key: 'family', title: 'Family-friendly', subtitle: 'Comfort tables', icon: 'people-outline' },
  { key: 'cafe', title: 'Cafe picks', subtitle: 'Desserts & coffee', icon: 'cafe-outline' },
  { key: 'prebook', title: 'Pre-book', subtitle: 'Better savings', icon: 'bookmark-outline' },
  { key: 'hot', title: 'New & Hot', subtitle: 'Popular this week', icon: 'flame-outline' },
];

const SCENE_FILTERS = [
  { key: 'all', label: 'All scenes' },
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This week' },
  { key: 'weekend', label: 'Weekend' },
  { key: 'next', label: 'Next weekend' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Break Room Experience',
    subtitle: 'Stress-busting activity · Chittethukara',
    bucket: 'today',
    date: '20\nMAR',
    price: 299,
    icon: 'hammer-outline',
  },
  {
    id: 'scene-2',
    title: 'Pottery Workshop',
    subtitle: 'Maker session · Kadavanthra',
    bucket: 'week',
    date: '20\nMAR',
    price: 1000,
    icon: 'color-palette-outline',
  },
  {
    id: 'scene-3',
    title: 'Comedy Night',
    subtitle: 'Weekend laughs · Kakkanad',
    bucket: 'weekend',
    date: '22\nMAR',
    price: 499,
    icon: 'mic-outline',
  },
  {
    id: 'scene-4',
    title: 'K-Culture Pop-up',
    subtitle: 'Food + music + community',
    bucket: 'weekend',
    date: '23\nMAR',
    price: 699,
    icon: 'restaurant-outline',
  },
  {
    id: 'scene-5',
    title: 'Creative Lab',
    subtitle: 'Family activity · Panampilly Nagar',
    bucket: 'next',
    date: '29\nMAR',
    price: 399,
    icon: 'happy-outline',
  },
];

const FALLBACK_DEALS = [
  { id: 'deal-1', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Fresh Curd', price: 35, brand: 'Everyday essential' },
  { id: 'deal-2', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Milk Chocolate', price: 20, brand: 'Quick sweet bite' },
  { id: 'deal-3', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Fruit Jam', price: 49, brand: 'Breakfast saver' },
  { id: 'deal-4', vendor_id: 'demo-mart', vendorName: 'Daily Basket', name: 'Classic Chips', price: 20, brand: 'Impulse add-on' },
];

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

function estimateEta(vendor, service = 'food') {
  const eta = Number(vendor?.estimated_delivery_time_min);

  if (service === 'eatout') {
    if (Number.isFinite(eta) && eta > 0) return `Table in ${Math.max(10, eta)} mins`;
    return 'Reserve now';
  }

  if (service === 'scenes') {
    return 'Instant confirmation';
  }

  if (Number.isFinite(eta) && eta > 0) {
    if (eta <= 15) return `${Math.round(eta)} mins`;
    return `${Math.max(10, Math.round(eta - 5))}-${Math.round(eta)} mins`;
  }

  if (service === 'warehouse') return '10-20 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '25-35 mins';
}

function getVendorRating(vendor) {
  const rating = Number(vendor?.avg_rating);
  if (Number.isFinite(rating) && rating > 0) {
    return rating.toFixed(1);
  }
  return 'New';
}

function getOfferLabel(vendor, service = 'food') {
  if (vendor?.open_now === false) return 'Closed now';
  if ((service === 'food' || service === 'warehouse') && vendor?.can_deliver === false) {
    return 'Out of range';
  }
  if (vendor?.is_busy) return 'High demand';
  if (vendor?.accepts_cod === false) return 'Online only';
  if (Number(vendor?.total_ratings || 0) >= 100) return 'Top rated';
  if (service === 'eatout') return 'Reserve now';
  if (service === 'scenes') return 'Limited slots';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  return 'New on GrabBasket';
}

function getVendorNote(vendor, service = 'food') {
  if (service === 'warehouse') {
    return vendor?.description || 'Essentials, snacks and quick home needs';
  }
  if (service === 'eatout') {
    return vendor?.description || 'Reserve tables, unlock bill offers and skip decision fatigue';
  }
  return vendor?.description || vendor?.address || 'Comfort food, premium presentation and reliable delivery';
}

function getDeliveryLine(vendor, service = 'food') {
  if (service === 'eatout') return vendor?.open_now === false ? 'Closed for reservations' : 'Table booking available';
  if (service === 'scenes') return 'Instant confirmation';
  if (vendor?.can_deliver === false) return 'Outside delivery radius';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return '₹19 delivery';
  if (service === 'warehouse') return 'Fast basket delivery';
  return '₹29 delivery';
}

function getCardTone(seedInput = '', dark = false) {
  const lightTones = [
    { bg: '#FFF1E1', accent: '#E48D67' },
    { bg: '#FBE7D8', accent: '#C77752' },
    { bg: '#FFF5E8', accent: '#C79667' },
    { bg: '#FEEADB', accent: '#D97C54' },
  ];

  const darkTones = [
    { bg: '#2B2119', accent: '#F3B58B' },
    { bg: '#31261D', accent: '#F6C09B' },
    { bg: '#35281D', accent: '#EFA577' },
    { bg: '#292018', accent: '#FFD0AE' },
  ];

  const source = dark ? darkTones : lightTones;
  const seed = String(seedInput || '').length % source.length;
  return source[seed];
}

function SectionHeader({ title, subtitle, actionLabel, onPressAction, light = false }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text>
        ) : null}
      </View>

      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function ServiceSwitcher({ activeService, onChange, dark = false }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceSwitcherRow}>
      {SERVICE_TABS.map((item) => {
        const active = item.key === activeService;
        return (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.92}
            onPress={() => onChange(item.key)}
            style={[
              styles.serviceChip,
              dark && styles.serviceChipDark,
              active && styles.serviceChipActive,
              dark && active && styles.serviceChipActiveDark,
            ]}>
            <View style={[styles.serviceChipIcon, active && styles.serviceChipIconActive]}>
              <Ionicons
                name={item.icon}
                size={18}
                color={active ? '#ffffff' : dark ? PALETTE.sceneText : PALETTE.brown}
              />
            </View>
            <View style={{ flex: 1 }}>
              <Text
                style={[
                  styles.serviceChipLabel,
                  dark && styles.serviceChipLabelDark,
                  active && styles.serviceChipLabelActive,
                ]}>
                {item.label}
              </Text>
              <Text
                style={[
                  styles.serviceChipHint,
                  dark && styles.serviceChipHintDark,
                  active && styles.serviceChipHintActive,
                ]}>
                {item.hint}
              </Text>
            </View>
          </TouchableOpacity>
        );
      })}
    </ScrollView>
  );
}

function SearchBar({ value, onChangeText, onSubmit, placeholder, dark = false }) {
  return (
    <View style={styles.searchWrap}>
      <View style={[styles.searchBar, dark && styles.searchBarDark]}>
        <Ionicons name="search-outline" size={20} color={dark ? PALETTE.sceneMuted : PALETTE.subtle} />
        <TextInput
          style={[styles.searchInput, dark && styles.searchInputDark]}
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          placeholder={placeholder}
          placeholderTextColor={dark ? '#B89E87' : PALETTE.subtle}
          returnKeyType="search"
        />
        <Ionicons name="mic-outline" size={18} color={dark ? PALETTE.peach300 : PALETTE.peach600} />
      </View>
      <TouchableOpacity activeOpacity={0.92} style={[styles.searchSideAction, dark && styles.searchSideActionDark]}>
        <Ionicons name="options-outline" size={18} color={dark ? PALETTE.sceneText : PALETTE.brown} />
      </TouchableOpacity>
    </View>
  );
}

function HeroBanner({ theme, activeService, vendors = [] }) {
  const etaValues = (vendors || [])
    .map((vendor) => Number(vendor?.estimated_delivery_time_min))
    .filter((value) => Number.isFinite(value) && value > 0);

  const avgEta = etaValues.length
    ? `${Math.round(etaValues.reduce((sum, value) => sum + value, 0) / etaValues.length)} mins`
    : activeService === 'eatout'
      ? 'Reserve now'
      : activeService === 'scenes'
        ? `${(vendors || []).length} picks`
        : 'Live catalog';

  const openCount = (vendors || []).filter((vendor) => vendor?.open_now !== false).length;

  const experienceLabel =
    activeService === 'warehouse'
      ? `${openCount} stores live`
      : activeService === 'eatout'
        ? `${openCount} tables available`
        : activeService === 'scenes'
          ? `${(vendors || []).length} experiences`
          : `${openCount} kitchens live`;

  return (
    <View
      style={[
        styles.heroBanner,
        activeService === 'scenes' ? styles.heroBannerDark : styles.heroBannerLight,
      ]}>
      <View style={[styles.heroBannerGlow, { backgroundColor: theme.heroAccent }]} />
      <Text style={[styles.heroEyebrow, activeService === 'scenes' && styles.heroEyebrowDark]}>
        {theme.bannerEyebrow}
      </Text>
      <Text style={[styles.heroTitle, activeService === 'scenes' && styles.heroTitleDark]}>
        {theme.bannerTitle}
      </Text>
      <Text style={[styles.heroCopy, activeService === 'scenes' && styles.heroCopyDark]}>
        {theme.bannerCopy}
      </Text>

      <View style={styles.heroStatRow}>
        <View style={[styles.heroStat, activeService === 'scenes' && styles.heroStatDark]}>
          <Text style={[styles.heroStatLabel, activeService === 'scenes' && styles.heroStatLabelDark]}>
            Avg ETA
          </Text>
          <Text style={[styles.heroStatValue, activeService === 'scenes' && styles.heroStatValueDark]}>
            {avgEta}
          </Text>
        </View>

        <View style={[styles.heroStat, activeService === 'scenes' && styles.heroStatDark]}>
          <Text style={[styles.heroStatLabel, activeService === 'scenes' && styles.heroStatLabelDark]}>
            Live now
          </Text>
          <Text style={[styles.heroStatValue, activeService === 'scenes' && styles.heroStatValueDark]}>
            {experienceLabel}
          </Text>
        </View>
      </View>
    </View>
  );
}
function BasketBanner({ cartCount, cartTotal, onPress, dark = false }) {
  if (!cartCount) return null;

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[styles.basketBanner, dark && styles.basketBannerDark]}>
      <View style={[styles.basketIconWrap, dark && styles.basketIconWrapDark]}>
        <Ionicons name="bag-handle-outline" size={18} color={dark ? '#ffffff' : PALETTE.peach600} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.basketTitle, dark && styles.basketTitleDark]}>Active basket</Text>
        <Text style={[styles.basketCopy, dark && styles.basketCopyDark]}>
          {cartCount} items · {money(cartTotal)}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={dark ? '#ffffff' : PALETTE.peach600} />
    </TouchableOpacity>
  );
}

function FilterChip({ label, icon, active, onPress, dark = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[
        styles.filterChip,
        dark && styles.filterChipDark,
        active && styles.filterChipActive,
        dark && active && styles.filterChipActiveDark,
      ]}>
      {icon ? (
        <Ionicons
          name={icon}
          size={14}
          color={active ? '#ffffff' : dark ? PALETTE.sceneMuted : PALETTE.muted}
        />
      ) : null}
      <Text
        style={[
          styles.filterChipLabel,
          dark && styles.filterChipLabelDark,
          active && styles.filterChipLabelActive,
        ]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function QuickTile({ title, subtitle, icon, large = false }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={[styles.quickTile, large && styles.quickTileLarge]}>
      <View style={styles.quickTileIconWrap}>
        <Ionicons name={icon} size={18} color={PALETTE.peach600} />
      </View>
      <Text style={[styles.quickTileTitle, large && styles.quickTileTitleLarge]}>{title}</Text>
      <Text style={styles.quickTileSubtitle}>{subtitle}</Text>
    </TouchableOpacity>
  );
}

function VendorRailCard({ vendor, service, favorite, onToggleFavorite, onPress, dark = false }) {
  const tone = getCardTone(vendor?.name, dark);

  return (
    <TouchableOpacity
      activeOpacity={0.94}
      onPress={onPress}
      style={[styles.vendorRailCard, dark && styles.vendorRailCardDark]}>
      <View style={[styles.vendorRailVisual, { backgroundColor: tone.bg }]}>
        <View style={[styles.vendorOfferBadge, { backgroundColor: tone.accent }]}>
          <Text style={styles.vendorOfferBadgeText}>{getOfferLabel(vendor, service)}</Text>
        </View>

        <TouchableOpacity activeOpacity={0.9} onPress={onToggleFavorite} style={styles.favoriteButton}>
          <Ionicons
            name={favorite ? 'heart' : 'heart-outline'}
            size={16}
            color={favorite ? PALETTE.danger : dark ? '#ffffff' : PALETTE.brown}
          />
        </TouchableOpacity>

        <View style={[styles.vendorMonogram, { borderColor: tone.accent }]}>
          <Text style={[styles.vendorMonogramText, dark && styles.vendorMonogramTextDark]}>{initials(vendor?.name)}</Text>
        </View>
      </View>

      <Text style={[styles.vendorName, dark && styles.vendorNameDark]} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={[styles.vendorMeta, dark && styles.vendorMetaDark]} numberOfLines={1}>
        ★ {getVendorRating(vendor)} · {estimateEta(vendor, service)}
      </Text>
      <Text style={[styles.vendorSubline, dark && styles.vendorSublineDark]} numberOfLines={2}>
        {getVendorNote(vendor, service)}
      </Text>
      <Text style={[styles.vendorDeliveryLine, dark && styles.vendorDeliveryLineDark]}>
        {getDeliveryLine(vendor, service)}
      </Text>
    </TouchableOpacity>
  );
}

function VendorListCard({ vendor, service, favorite, onToggleFavorite, onPress }) {
  const tone = getCardTone(vendor?.name);

  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.vendorListCard}>
      <View style={[styles.vendorListThumb, { backgroundColor: tone.bg }]}>
        <Text style={styles.vendorListThumbText}>{initials(vendor?.name)}</Text>
      </View>

      <View style={{ flex: 1 }}>
        <View style={styles.vendorListTopRow}>
          <Text style={styles.vendorListName} numberOfLines={1}>{vendor?.name}</Text>
          <TouchableOpacity activeOpacity={0.9} onPress={onToggleFavorite} style={styles.vendorListFavorite}>
            <Ionicons
              name={favorite ? 'heart' : 'heart-outline'}
              size={16}
              color={favorite ? PALETTE.danger : PALETTE.subtle}
            />
          </TouchableOpacity>
        </View>

        <Text style={styles.vendorListMeta} numberOfLines={1}>
          ★ {getVendorRating(vendor)} · {estimateEta(vendor, service)} · {getDeliveryLine(vendor, service)}
        </Text>
        <Text style={styles.vendorListCopy} numberOfLines={2}>{getVendorNote(vendor, service)}</Text>

        <View style={styles.vendorListBottomRow}>
          <View style={styles.vendorTagPill}>
            <Text style={styles.vendorTagPillText}>{getOfferLabel(vendor, service)}</Text>
          </View>
          <Text style={styles.vendorListAction}>Open</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function DealCard({ item, qty, onAdd, onRemove }) {
  const tone = getCardTone(item?.name);

  return (
    <View style={styles.dealCard}>
      <View style={[styles.dealVisual, { backgroundColor: tone.bg }]}>
        <Text style={styles.dealVisualEmoji}>🧺</Text>
        <View style={[styles.dealPricePill, { backgroundColor: tone.accent }]}>
          <Text style={styles.dealPricePillText}>{money(item?.price)}</Text>
        </View>
      </View>

      <Text style={styles.dealBrand} numberOfLines={1}>{item?.brand || item?.vendorName}</Text>
      <Text style={styles.dealName} numberOfLines={1}>{item?.name}</Text>

      {qty > 0 ? (
        <View style={styles.qtyRow}>
          <TouchableOpacity activeOpacity={0.92} onPress={onRemove} style={styles.qtyButton}>
            <Ionicons name="remove" size={16} color={PALETTE.peach600} />
          </TouchableOpacity>
          <Text style={styles.qtyText}>{qty}</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.qtyButton}>
            <Ionicons name="add" size={16} color={PALETTE.peach600} />
          </TouchableOpacity>
        </View>
      ) : (
        <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.addButton}>
          <Text style={styles.addButtonText}>ADD</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

function SceneEventCard({ item }) {
  const tone = getCardTone(item?.title, true);

  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.sceneCard}>
      <View style={[styles.scenePoster, { backgroundColor: tone.bg }]}>
        <View style={styles.sceneDatePill}>
          <Text style={styles.sceneDateText}>{item.date}</Text>
        </View>
        <Ionicons name={item.icon} size={24} color={tone.accent} />
      </View>
      <Text style={styles.sceneTitle} numberOfLines={2}>{item.title}</Text>
      <Text style={styles.sceneSub} numberOfLines={2}>{item.subtitle}</Text>
      <Text style={styles.scenePrice}>Starts at {money(item.price)}</Text>
    </TouchableOpacity>
  );
}

function LoadingState({ label, dark = false }) {
  return (
    <View style={[styles.feedbackCard, dark && styles.feedbackCardDark]}>
      <ActivityIndicator color={dark ? PALETTE.peach300 : PALETTE.peach600} />
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

export default function HomeScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const [sceneFilter, setSceneFilter] = useState('all');

  const {
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    vendors,
    vendorsLoading,
    refreshing,
    loadVendors,
    homeDeals,
    homeDealsLoading,
    featuredVendors,
    favorites,
    toggleFavorite,
    rememberSearch,
    rememberStore,
    addToCart,
    updateQty,
    cart,
    cartCount,
    cartTotal,
  } = useGrabBasket();

  const theme = THEMES[activeService] || THEMES.food;
  const isDark = activeService === 'scenes';

  const displayVendors = useMemo(() => {
    const source = featuredVendors?.length ? featuredVendors : vendors;
    return source.slice(0, 8);
  }, [featuredVendors, vendors]);

  const displayDeals = useMemo(() => {
    return homeDeals?.length ? homeDeals : FALLBACK_DEALS;
  }, [homeDeals]);

  const sceneItems = useMemo(() => {
    if (sceneFilter === 'all') return SCENE_EVENTS;
    return SCENE_EVENTS.filter((item) => item.bucket === sceneFilter);
  }, [sceneFilter]);

  const handleSearch = () => {
    rememberSearch(homeSearch);
    loadVendors();
  };

  const handleRefresh = () => {
    loadVendors({ pullToRefresh: true });
  };

  const handleOpenVendor = (vendor) => {
    if (!vendor?.id) return;
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const handleServiceChange = (serviceKey) => {
    setActiveService(serviceKey);
    if (serviceKey !== 'warehouse') {
      setActiveShortcut('all');
    }
    if (serviceKey !== 'scenes') {
      setSceneFilter('all');
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle={theme.statusBar} backgroundColor={theme.hero} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={isDark ? PALETTE.peach300 : PALETTE.peach600}
          />
        }
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        <View style={[styles.heroWrap, { backgroundColor: theme.hero }]}>
          <View style={[styles.heroOrbLarge, { backgroundColor: theme.heroAccent }]} />
          <View style={[styles.heroOrbSmall, { backgroundColor: theme.heroSoft }]} />

          <View style={styles.topRow}>
            <View style={{ flex: 1, paddingRight: 12 }}>
              <View style={styles.locationRow}>
                <Ionicons
                  name={activeService === 'warehouse' ? 'time-outline' : 'location-outline'}
                  size={17}
                  color={theme.heroText}
                />
                <Text style={[styles.locationTitle, { color: theme.heroText }]} numberOfLines={1}>
                  Delivering to your area
                </Text>
                <Ionicons name="chevron-down" size={16} color={theme.heroText} />
              </View>
              <Text style={[styles.locationSub, { color: theme.heroSub }]} numberOfLines={1}>
                GrabBasket demo flow · privacy-safe preview mode
              </Text>
            </View>

            <TouchableOpacity activeOpacity={0.92} onPress={() => router.push('/account')} style={styles.profileGhostBtn}>
              <Ionicons name="person-outline" size={20} color={theme.heroText} />
            </TouchableOpacity>
          </View>

          <ServiceSwitcher activeService={activeService} onChange={handleServiceChange} dark={isDark} />

          <SearchBar
            value={homeSearch}
            onChangeText={setHomeSearch}
            onSubmit={handleSearch}
            placeholder={theme.searchPlaceholder}
            dark={isDark}
          />

          <HeroBanner
            theme={theme}
            activeService={activeService}
            vendors={featuredVendors?.length ? featuredVendors : vendors}
          />
        </View>

        <View style={[styles.body, isDark && styles.bodyDark]}>
          <BasketBanner
            cartCount={cartCount}
            cartTotal={cartTotal}
            onPress={() => router.push('/cart')}
            dark={isDark}
          />

          {activeService === 'food' ? (
            <>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                {FOOD_COLLECTIONS.map((item) => (
                  <FilterChip
                    key={item.key}
                    label={item.label}
                    icon={item.icon}
                    active={homeSearch.toLowerCase() === item.label.toLowerCase()}
                    onPress={() => {
                      setHomeSearch(item.label);
                      rememberSearch(item.label);
                      loadVendors();
                    }}
                  />
                ))}
              </ScrollView>

              <SectionHeader
                title="Popular around you"
                subtitle="A warmer, less cluttered first fold with better restaurant emphasis."
              />

              {vendorsLoading ? (
                <LoadingState label="Loading restaurants..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState
                  title="No restaurants available"
                  subtitle="Once your vendor feed is connected, this section will feel much more alive."
                />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.railRow}>
                  {displayVendors.map((vendor) => (
                    <VendorRailCard
                      key={vendor.id}
                      vendor={vendor}
                      service="food"
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onPress={() => handleOpenVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Restaurants to order from" actionLabel="View all" />

              {vendorsLoading ? (
                <LoadingState label="Refreshing list..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="Your restaurant list is empty" subtitle="Seed more vendors to make discovery feel premium." />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="food"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'warehouse' ? (
            <>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                {MART_COLLECTIONS.map((item) => (
                  <FilterChip
                    key={item.key}
                    label={item.label}
                    icon={item.icon}
                    active={activeShortcut === item.key}
                    onPress={() => setActiveShortcut(item.key)}
                  />
                ))}
              </ScrollView>

              <SectionHeader
                title="Quick add deals"
                subtitle="Swiggy-like speed comes from making decision-making effortless."
              />

              {homeDealsLoading && homeDeals.length === 0 ? (
                <LoadingState label="Loading quick deals..." />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.railRow}>
                  {displayDeals.map((item) => (
                    <DealCard
                      key={item.id}
                      item={item}
                      qty={cart.items[item.id]?.qty || 0}
                      onAdd={() => addToCart(item)}
                      onRemove={() => updateQty(item, -1)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Quick grocery stores" />

              {vendorsLoading ? (
                <LoadingState label="Loading nearby stores..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No stores available" subtitle="Add grocery-ready vendors to complete this flow." />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="warehouse"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'eatout' ? (
            <>
              <SectionHeader
                title="Tonight's shortcuts"
                subtitle="Offer-first storytelling makes dineout feel closer to a real consumer app."
              />

              <View style={styles.dineGrid}>
                <View style={styles.dineColumnLarge}>
                  <QuickTile {...DINE_SHORTCUTS[0]} />
                </View>
                <View style={styles.dineColumnRight}>
                  {DINE_SHORTCUTS.slice(1).map((item) => (
                    <QuickTile key={item.key} {...item} />
                  ))}
                </View>
              </View>

              <SectionHeader title="Places worth booking" actionLabel="See all" />

              {vendorsLoading ? (
                <LoadingState label="Loading restaurants..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No dineout partners yet" subtitle="Add vendors with dine-in value props to complete this surface." />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="eatout"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'scenes' ? (
            <>
              <SectionHeader
                title="When is the plan?"
                subtitle="Keep the layout editorial, not marketplace-heavy."
                light
              />

              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                {SCENE_FILTERS.map((item) => (
                  <FilterChip
                    key={item.key}
                    label={item.label}
                    active={sceneFilter === item.key}
                    onPress={() => setSceneFilter(item.key)}
                    dark
                  />
                ))}
              </ScrollView>

              <SectionHeader title="All scenes" subtitle="Curated drops around you" light />

              {sceneItems.length === 0 ? (
                <EmptyState dark title="No events in this bucket" subtitle="Adjust the time filter or seed more experience cards." />
              ) : (
                <View style={styles.sceneGrid}>
                  {sceneItems.map((item) => (
                    <SceneEventCard key={item.id} item={item} />
                  ))}
                </View>
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
  heroWrap: {
    paddingHorizontal: 18,
    paddingTop: 10,
    paddingBottom: 22,
    overflow: 'hidden',
  },
  heroOrbLarge: {
    position: 'absolute',
    width: 180,
    height: 180,
    borderRadius: 90,
    top: -40,
    right: -50,
    opacity: 0.7,
  },
  heroOrbSmall: {
    position: 'absolute',
    width: 120,
    height: 120,
    borderRadius: 60,
    left: -35,
    bottom: 12,
    opacity: 0.55,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 18,
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  locationTitle: {
    flex: 1,
    fontSize: 18,
    fontWeight: '800',
  },
  locationSub: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  profileGhostBtn: {
    width: 42,
    height: 42,
    borderRadius: 21,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.35)',
    backgroundColor: 'rgba(255,255,255,0.42)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceSwitcherRow: {
    paddingBottom: 8,
    gap: 10,
  },
  serviceChip: {
    width: 152,
    borderRadius: 22,
    paddingHorizontal: 12,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.05)',
    backgroundColor: 'rgba(255,255,255,0.65)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  serviceChipDark: {
    borderColor: PALETTE.sceneBorder,
    backgroundColor: 'rgba(255,255,255,0.04)',
  },
  serviceChipActive: {
    backgroundColor: PALETTE.peach600,
    borderColor: PALETTE.peach600,
  },
  serviceChipActiveDark: {
    backgroundColor: '#F0AA81',
    borderColor: '#F0AA81',
  },
  serviceChipIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.5)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceChipIconActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
  },
  serviceChipLabel: {
    color: PALETTE.text,
    fontSize: 15,
    fontWeight: '800',
  },
  serviceChipLabelDark: {
    color: PALETTE.sceneText,
  },
  serviceChipLabelActive: {
    color: '#ffffff',
  },
  serviceChipHint: {
    marginTop: 2,
    color: PALETTE.muted,
    fontSize: 11,
    fontWeight: '600',
  },
  serviceChipHintDark: {
    color: PALETTE.sceneMuted,
  },
  serviceChipHintActive: {
    color: 'rgba(255,255,255,0.75)',
  },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 10,
  },
  searchBar: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 14,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
  },
  searchBarDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    fontWeight: '600',
    color: PALETTE.text,
  },
  searchInputDark: {
    color: PALETTE.sceneText,
  },
  searchSideAction: {
    width: 52,
    height: 52,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchSideActionDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  heroBanner: {
    marginTop: 16,
    borderRadius: 24,
    paddingHorizontal: 18,
    paddingTop: 18,
    paddingBottom: 16,
    overflow: 'hidden',
  },
  heroBannerLight: {
    backgroundColor: 'rgba(255,255,255,0.72)',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
  },
  heroBannerDark: {
    backgroundColor: '#1D1712',
    borderWidth: 1,
    borderColor: PALETTE.sceneBorder,
  },
  heroBannerGlow: {
    position: 'absolute',
    width: 140,
    height: 140,
    borderRadius: 70,
    right: -30,
    top: -20,
    opacity: 0.28,
  },
  heroEyebrow: {
    color: PALETTE.peach600,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.4,
    marginBottom: 8,
  },
  heroEyebrowDark: {
    color: PALETTE.peach300,
  },
  heroTitle: {
    color: PALETTE.brownDark,
    fontSize: 24,
    lineHeight: 30,
    fontWeight: '900',
    marginBottom: 8,
  },
  heroTitleDark: {
    color: PALETTE.sceneText,
  },
  heroCopy: {
    color: PALETTE.muted,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '600',
  },
  heroCopyDark: {
    color: PALETTE.sceneMuted,
  },
  heroStatRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 16,
  },
  heroStat: {
    flex: 1,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.85)',
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  heroStatDark: {
    backgroundColor: '#2A2018',
  },
  heroStatLabel: {
    color: PALETTE.subtle,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 4,
  },
  heroStatLabelDark: {
    color: '#C9AC92',
  },
  heroStatValue: {
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '800',
  },
  heroStatValueDark: {
    color: PALETTE.sceneText,
  },
  body: {
    paddingHorizontal: 18,
    paddingTop: 16,
    gap: 18,
  },
  bodyDark: {
    backgroundColor: PALETTE.sceneBg,
    paddingBottom: 10,
  },
  basketBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 14,
    borderRadius: 20,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
  },
  basketBannerDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  basketIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: PALETTE.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  basketIconWrapDark: {
    backgroundColor: '#2D2219',
  },
  basketTitle: {
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '800',
  },
  basketTitleDark: {
    color: PALETTE.sceneText,
  },
  basketCopy: {
    marginTop: 2,
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  basketCopyDark: {
    color: PALETTE.sceneMuted,
  },
  chipRow: {
    gap: 10,
  },
  filterChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
  },
  filterChipDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  filterChipActive: {
    backgroundColor: PALETTE.peach600,
    borderColor: PALETTE.peach600,
  },
  filterChipActiveDark: {
    backgroundColor: '#F0AA81',
    borderColor: '#F0AA81',
  },
  filterChipLabel: {
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  filterChipLabelDark: {
    color: PALETTE.sceneMuted,
  },
  filterChipLabelActive: {
    color: '#ffffff',
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 12,
  },
  sectionTitle: {
    color: PALETTE.text,
    fontSize: 20,
    fontWeight: '900',
  },
  sectionTitleLight: {
    color: PALETTE.sceneText,
  },
  sectionSubtitle: {
    marginTop: 4,
    color: PALETTE.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  sectionSubtitleLight: {
    color: PALETTE.sceneMuted,
  },
  sectionAction: {
    color: PALETTE.peach600,
    fontSize: 13,
    fontWeight: '800',
  },
  sectionActionLight: {
    color: PALETTE.peach300,
  },
  railRow: {
    gap: 14,
    paddingRight: 8,
  },
  vendorRailCard: {
    width: 220,
  },
  vendorRailCardDark: {
    width: 220,
  },
  vendorRailVisual: {
    height: 140,
    borderRadius: 24,
    padding: 14,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  vendorOfferBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  vendorOfferBadgeText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  favoriteButton: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.9)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogram: {
    width: 52,
    height: 52,
    borderRadius: 26,
    borderWidth: 2,
    backgroundColor: 'rgba(255,255,255,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogramText: {
    color: PALETTE.brownDark,
    fontSize: 18,
    fontWeight: '900',
  },
  vendorMonogramTextDark: {
    color: PALETTE.sceneText,
  },
  vendorName: {
    color: PALETTE.text,
    fontSize: 16,
    fontWeight: '900',
    marginBottom: 4,
  },
  vendorNameDark: {
    color: PALETTE.sceneText,
  },
  vendorMeta: {
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
  },
  vendorMetaDark: {
    color: PALETTE.sceneMuted,
  },
  vendorSubline: {
    color: PALETTE.subtle,
    fontSize: 12,
    lineHeight: 18,
    fontWeight: '600',
    marginBottom: 6,
  },
  vendorSublineDark: {
    color: '#C9AC92',
  },
  vendorDeliveryLine: {
    color: PALETTE.peach600,
    fontSize: 12,
    fontWeight: '800',
  },
  vendorDeliveryLineDark: {
    color: PALETTE.peach300,
  },
  vendorListCard: {
    flexDirection: 'row',
    gap: 14,
    padding: 14,
    borderRadius: 22,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
  },
  vendorListThumb: {
    width: 72,
    height: 72,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorListThumbText: {
    color: PALETTE.brownDark,
    fontSize: 22,
    fontWeight: '900',
  },
  vendorListTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  vendorListName: {
    flex: 1,
    color: PALETTE.text,
    fontSize: 16,
    fontWeight: '900',
  },
  vendorListFavorite: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorListMeta: {
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
  },
  vendorListCopy: {
    color: PALETTE.subtle,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
    marginBottom: 10,
  },
  vendorListBottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  vendorTagPill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: PALETTE.peach50,
  },
  vendorTagPillText: {
    color: PALETTE.peach600,
    fontSize: 11,
    fontWeight: '900',
  },
  vendorListAction: {
    color: PALETTE.peach600,
    fontSize: 13,
    fontWeight: '900',
  },
  quickTile: {
    borderRadius: 22,
    padding: 14,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    minHeight: 108,
    justifyContent: 'space-between',
  },
  quickTileLarge: {
    minHeight: 228,
  },
  quickTileIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: PALETTE.peach50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  quickTileTitle: {
    color: PALETTE.text,
    fontSize: 15,
    lineHeight: 20,
    fontWeight: '900',
  },
  quickTileTitleLarge: {
    fontSize: 24,
    lineHeight: 28,
  },
  quickTileSubtitle: {
    color: PALETTE.muted,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
    marginTop: 6,
  },
  dineGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  dineColumnLarge: {
    flex: 1,
  },
  dineColumnRight: {
    flex: 1,
    gap: 12,
  },
  dealCard: {
    width: 160,
    borderRadius: 22,
    padding: 12,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
  },
  dealVisual: {
    height: 110,
    borderRadius: 20,
    marginBottom: 10,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  dealVisualEmoji: {
    fontSize: 34,
  },
  dealPricePill: {
    position: 'absolute',
    bottom: 10,
    left: 10,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  dealPricePillText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
  },
  dealBrand: {
    color: PALETTE.subtle,
    fontSize: 11,
    fontWeight: '700',
  },
  dealName: {
    color: PALETTE.text,
    fontSize: 15,
    fontWeight: '900',
    marginTop: 4,
    marginBottom: 10,
  },
  addButton: {
    height: 36,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: PALETTE.peach50,
  },
  addButtonText: {
    color: PALETTE.peach600,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 14,
    paddingHorizontal: 8,
    paddingVertical: 6,
    backgroundColor: PALETTE.peach50,
  },
  qtyButton: {
    width: 28,
    height: 28,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
  },
  qtyText: {
    color: PALETTE.text,
    fontSize: 13,
    fontWeight: '900',
  },
  sceneGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: 14,
  },
  sceneCard: {
    width: '48.2%',
    borderRadius: 22,
    padding: 12,
    backgroundColor: '#1D1712',
    borderWidth: 1,
    borderColor: PALETTE.sceneBorder,
  },
  scenePoster: {
    height: 118,
    borderRadius: 18,
    marginBottom: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sceneDatePill: {
    position: 'absolute',
    left: 10,
    top: 10,
    borderRadius: 14,
    backgroundColor: 'rgba(0,0,0,0.35)',
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  sceneDateText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
    textAlign: 'center',
  },
  sceneTitle: {
    color: PALETTE.sceneText,
    fontSize: 15,
    lineHeight: 20,
    fontWeight: '900',
    marginBottom: 5,
  },
  sceneSub: {
    color: PALETTE.sceneMuted,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
    marginBottom: 8,
  },
  scenePrice: {
    color: PALETTE.peach300,
    fontSize: 12,
    fontWeight: '900',
  },
  feedbackCard: {
    borderRadius: 22,
    padding: 16,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  feedbackCardDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  feedbackTitle: {
    color: PALETTE.text,
    fontSize: 15,
    fontWeight: '800',
    textAlign: 'center',
  },
  feedbackTitleDark: {
    color: PALETTE.sceneText,
  },
  feedbackSubtitle: {
    color: PALETTE.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
    textAlign: 'center',
  },
  feedbackSubtitleDark: {
    color: PALETTE.sceneMuted,
  },
});