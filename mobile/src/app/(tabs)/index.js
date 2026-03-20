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

const COLORS = {
  page: '#f7f7fb',
  pageAlt: '#ffffff',
  text: '#101828',
  textSoft: '#667085',
  textMuted: '#8f96a3',
  border: '#eaecf0',
  white: '#ffffff',
  black: '#0f172a',
  foodPrimary: '#7c3aed',
  foodPrimaryDark: '#5b21b6',
  foodAccent: '#f59e0b',
  foodSoft: '#f3e8ff',
  martPrimary: '#0d2d75',
  martAccent: '#2f6fed',
  martSoft: '#dbeafe',
  dinePrimary: '#4c1d95',
  dineAccent: '#f97316',
  dineSoft: '#ffedd5',
  scenesPrimary: '#060816',
  scenesCard: '#111827',
  scenesSoft: '#1f2937',
  success: '#12b76a',
  danger: '#ef4444',
  warning: '#f59e0b',
  shadow: 'rgba(15, 23, 42, 0.08)',
};

const TOP_SERVICES = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline', badge: null, emoji: '🍔' },
  { key: 'warehouse', label: 'Instamart', icon: 'basket-outline', badge: '5 mins', emoji: '🧺' },
  { key: 'eatout', label: 'Dineout', icon: 'restaurant-outline', badge: null, emoji: '🍽️' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline', badge: null, emoji: '🪩' },
];

const SERVICE_THEMES = {
  food: {
    hero: COLORS.foodPrimary,
    heroAccent: '#a78bfa',
    canvas: COLORS.page,
    title: 'Valliachans Place',
    subtitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: "Search for 'EatRight'",
    actionLabel: 'VEG',
    actionIcon: 'leaf-outline',
    statusBar: 'light-content',
  },
  warehouse: {
    hero: COLORS.martPrimary,
    heroAccent: '#3b82f6',
    canvas: '#f8fbff',
    title: '5 mins',
    subtitle: 'To Valliachans Place: 12b, Great Orchard / Tower 1',
    searchPlaceholder: 'Search for Dryfruits',
    actionLabel: '',
    actionIcon: 'bookmark-outline',
    statusBar: 'light-content',
  },
  eatout: {
    hero: COLORS.dinePrimary,
    heroAccent: '#8b5cf6',
    canvas: COLORS.pageAlt,
    title: 'Valliachans Place',
    subtitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for cuisines',
    actionLabel: '',
    actionIcon: 'sparkles-outline',
    statusBar: 'light-content',
    topStrip: 'Earn flat 10% Dinecash on every bill payment',
  },
  scenes: {
    hero: '#040713',
    heroAccent: '#7c3aed',
    canvas: '#040713',
    title: '12b, Great Orchard / Tower 1, Vidya Nagar',
    subtitle: 'Kochi experiences curated for tonight and the weekend',
    searchPlaceholder: 'Search for events or experiences',
    actionLabel: '',
    actionIcon: 'sparkles-outline',
    statusBar: 'light-content',
  },
};

const FOOD_HIGHLIGHTS = [
  { key: 'deal', title: 'Binge worthy deals', badge: 'Up to 60% OFF & more', emoji: '🔥' },
  { key: 'eatright', title: 'EatRight', badge: 'Win up to ₹300 free cash', emoji: '🥗' },
  { key: 'awards', title: 'Restaurant awards', badge: 'Best rated around you', emoji: '🏅' },
];

const WAREHOUSE_FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxxsaver', label: 'Maxxsaver', icon: 'pricetags-outline' },
  { key: 'festival', label: 'Ramzan', icon: 'moon-outline' },
  { key: 'ready', label: 'Exam ready', icon: 'flash-outline' },
];

const WAREHOUSE_BANNERS = [
  { key: 'snacks', title: 'Iftar snacks & drinks', emoji: '🥤' },
  { key: 'biryani', title: 'Biryani & feasting corner', emoji: '🍛' },
  { key: 'dates', title: 'Dates, dry fruits & desserts', emoji: '🌰' },
  { key: 'gifting', title: 'Gift-ready picks', emoji: '🎁' },
];

const DINEOUT_SPOTLIGHT = [
  { key: 'awards', title: 'Restaurant Awards', subtitle: 'Vote, share & win up to ₹600!', accent: '#5b1021', action: 'Vote now' },
  { key: 'flavours', title: 'Flavours by the city', subtitle: 'Editor-picked places and fresh menus.', accent: '#29421d', action: 'Preview' },
  { key: 'gourmet', title: 'Pre-book offers', subtitle: 'Book early and unlock extra dining deals.', accent: '#4b1d95', action: 'Explore' },
];

const SCENE_MOMENTS = [
  { key: 'today', label: 'Today' },
  { key: 'weekend', label: 'This Weekend' },
  { key: 'week', label: 'This Week' },
  { key: 'next', label: 'Next Weekend' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room at Break N Chill',
    subtitle: 'Break n Chill · Rage Room · Chittethukara',
    date: '20 MAR',
    price: 299,
    emoji: '💥',
    tag: 'Stress buster',
    accent: '#1a0c18',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Throwing Workshop',
    subtitle: 'Soil to Soul Ceramics · Kadavanthra',
    date: '20 MAR',
    price: 1000,
    emoji: '🏺',
    tag: 'Hands-on',
    accent: '#3f2a21',
  },
  {
    id: 'scene-3',
    title: 'Kimchi Culture',
    subtitle: 'SKEI Presents · Korean food and culture',
    date: '22 MAR',
    price: 699,
    emoji: '🥢',
    tag: 'Culture',
    accent: '#61161a',
  },
  {
    id: 'scene-4',
    title: 'Stand-up Comedy Night',
    subtitle: 'Top comics · Kakkanad',
    date: '23 MAR',
    price: 499,
    emoji: '🎤',
    tag: 'Top rated',
    accent: '#13233f',
  },
  {
    id: 'scene-5',
    title: 'Kids Creative Lab',
    subtitle: 'Family plans · Panampilly Nagar',
    date: '29 MAR',
    price: 399,
    emoji: '🎨',
    tag: 'Family pick',
    accent: '#402061',
  },
];

const FALLBACK_DEALS = [
  { id: 'fallback-1', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Amul Curd', price: 35, brand: 'Daily essential', emoji: '🥛' },
  { id: 'fallback-2', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Cadbury Dairy Milk', price: 20, brand: 'Quick sweet bite', emoji: '🍫' },
  { id: 'fallback-3', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Kissan Jam', price: 49, brand: 'Breakfast saver', emoji: '🍓' },
  { id: 'fallback-4', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Classic Chips', price: 20, brand: 'Impulse add-on', emoji: '🥔' },
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
  if (service === 'eatout') return 'Table in 10-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
    return '30-45 mins';
  }
  return service === 'warehouse' ? '5-15 mins' : '23 mins';
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getDeliveryFeeLabel(vendor, service = 'food') {
  if (service === 'eatout') return 'Extra bank offers';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return '₹19 delivery';
  return service === 'warehouse' ? '₹9 delivery' : '₹29 delivery';
}

function getOfferLabel(vendor, service = 'food') {
  const foodOffers = ['40% OFF', 'Up to ₹80 OFF', 'Items at ₹79', 'Flat 25% OFF'];
  const martOffers = ['₹9 everyday', 'Best brands', 'Flat 20% OFF', 'Daily saver'];
  const dineOffers = ['Flat 50% OFF', 'Extra 10% cashback', 'Pre-book perks', 'Bank offer'];
  const source = service === 'warehouse' ? martOffers : service === 'eatout' ? dineOffers : foodOffers;
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return source[seed % source.length];
}

function getVendorDescription(vendor, service = 'food') {
  if (service === 'eatout') {
    return vendor?.description || 'Bill offers, table booking and sharper dine-in discovery.';
  }
  if (service === 'warehouse') {
    return vendor?.description || 'Everyday essentials, fruits, snacks and home needs.';
  }
  return vendor?.description || vendor?.address || 'Curated local restaurant with fast delivery.';
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
  return '🛍️';
}

function SectionHeader({ title, subtitle, actionLabel, onPressAction, light = false }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text> : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.9} onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function ServiceChip({ item, active, onPress, dark = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[
        styles.serviceChip,
        active && styles.serviceChipActive,
        dark && styles.serviceChipDark,
        dark && active && styles.serviceChipDarkActive,
      ]}
      onPress={onPress}>
      <Text style={styles.serviceChipEmoji}>{item.emoji}</Text>
      <View>
        {item.badge ? <Text style={[styles.serviceChipBadge, dark && styles.serviceChipBadgeDark]}>{item.badge}</Text> : null}
        <Text style={[styles.serviceChipLabel, dark && styles.serviceChipLabelDark, active && styles.serviceChipLabelActive]}>{item.label}</Text>
      </View>
    </TouchableOpacity>
  );
}

function HeroSearch({ placeholder, value, onChangeText, onSubmit, actionLabel, actionIcon, dark = false }) {
  return (
    <View style={styles.heroSearchRow}>
      <View style={[styles.heroSearch, dark && styles.heroSearchDark]}>
        <Ionicons name="search-outline" size={22} color={dark ? '#d0d5dd' : COLORS.textSoft} />
        <TextInput
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          placeholder={placeholder}
          placeholderTextColor={dark ? '#98a2b3' : '#98a2b3'}
          style={[styles.heroSearchInput, dark && styles.heroSearchInputDark]}
          returnKeyType="search"
        />
        <Ionicons name={actionLabel ? 'mic-outline' : actionIcon || 'search-outline'} size={20} color={dark ? '#d0d5dd' : COLORS.textSoft} />
      </View>
      <TouchableOpacity activeOpacity={0.92} style={[styles.heroSearchAction, dark && styles.heroSearchActionDark]}>
        {actionLabel ? (
          <>
            <Text style={styles.heroSearchActionText}>{actionLabel}</Text>
            <Ionicons name={actionIcon || 'leaf-outline'} size={16} color={COLORS.white} />
          </>
        ) : (
          <Ionicons name={actionIcon || 'bookmark-outline'} size={20} color={COLORS.white} />
        )}
      </TouchableOpacity>
    </View>
  );
}

function SearchShortcuts({ items, dark = false }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.shortcutRow}>
      {items.map((item) => (
        <View key={item} style={[styles.shortcutChip, dark && styles.shortcutChipDark]}>
          <Text style={[styles.shortcutText, dark && styles.shortcutTextDark]}>{item}</Text>
        </View>
      ))}
    </ScrollView>
  );
}

function FoodHighlightCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.foodHighlightCard}>
      <Text style={styles.foodHighlightEmoji}>{item.emoji}</Text>
      <Text style={styles.foodHighlightTitle}>{item.title}</Text>
      <Text style={styles.foodHighlightBadge}>{item.badge}</Text>
    </TouchableOpacity>
  );
}

function FoodRestaurantCard({ vendor, favorite, onToggleFavorite, onOpen, service = 'food' }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.restaurantCard} onPress={onOpen}>
      <View style={[styles.restaurantPoster, service === 'eatout' ? styles.restaurantPosterDineout : styles.restaurantPosterFood]}>
        <View style={styles.offerPillBlack}>
          <Text style={styles.offerPillBlackText}>{getOfferLabel(vendor, service)}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.heartFab} onPress={onToggleFavorite}>
          <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={favorite ? COLORS.danger : COLORS.white} />
        </TouchableOpacity>
        <Text style={styles.restaurantPosterInitials}>{initials(vendor.name)}</Text>
      </View>
      <Text style={styles.restaurantCardTitle} numberOfLines={1}>{vendor.name}</Text>
      <Text style={styles.restaurantCardMeta} numberOfLines={1}>{estimateEta(vendor, service)} • {getVendorRating(vendor)} ★</Text>
    </TouchableOpacity>
  );
}

function VendorListCard({ vendor, service = 'food', favorite, onOpen, onToggleFavorite }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.vendorListCard} onPress={onOpen}>
      <View style={[styles.vendorThumb, service === 'warehouse' ? styles.vendorThumbMart : service === 'eatout' ? styles.vendorThumbDine : styles.vendorThumbFood]}>
        <Text style={styles.vendorThumbText}>{initials(vendor.name)}</Text>
      </View>
      <View style={{ flex: 1 }}>
        <View style={styles.vendorTopRow}>
          <Text style={styles.vendorName} numberOfLines={1}>{vendor.name}</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={favorite ? COLORS.danger : COLORS.text} />
          </TouchableOpacity>
        </View>
        <Text style={styles.vendorDescription} numberOfLines={2}>{getVendorDescription(vendor, service)}</Text>
        <View style={styles.vendorMetaWrap}>
          <View style={styles.metaPillAccent}><Text style={styles.metaPillAccentText}>{getOfferLabel(vendor, service)}</Text></View>
          <View style={styles.metaPill}><Text style={styles.metaPillText}>{estimateEta(vendor, service)}</Text></View>
          <View style={styles.metaPill}><Text style={styles.metaPillText}>{getDeliveryFeeLabel(vendor, service)}</Text></View>
          <View style={styles.metaPill}><Text style={styles.metaPillText}>{getVendorRating(vendor)} ★</Text></View>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function WarehouseFilterChip({ item, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.martFilterChip, active && styles.martFilterChipActive]} onPress={onPress}>
      <Ionicons name={item.icon} size={16} color={active ? COLORS.martPrimary : COLORS.white} />
      <Text style={[styles.martFilterChipText, active && styles.martFilterChipTextActive]}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function WarehouseDealCard({ item, qty, onAdd, onRemove }) {
  return (
    <View style={styles.martDealCard}>
      <Text style={styles.martDealBadge}>₹9 everyday</Text>
      <View style={styles.martDealImage}>
        <Text style={styles.martDealEmoji}>{item.emoji || pickEmoji(item.name)}</Text>
      </View>
      <Text style={styles.martDealTitle} numberOfLines={1}>{item.name}</Text>
      <Text style={styles.martDealMeta} numberOfLines={1}>{item.vendorName || item.brand || 'GrabBasket Mart'}</Text>
      <View style={styles.martDealFooter}>
        <Text style={styles.martDealPrice}>{money(item.price)}</Text>
        {qty > 0 ? (
          <View style={styles.qtyStepper}>
            <TouchableOpacity activeOpacity={0.92} style={styles.qtyStepperButton} onPress={onRemove}>
              <Ionicons name="remove" size={15} color={COLORS.text} />
            </TouchableOpacity>
            <Text style={styles.qtyStepperText}>{qty}</Text>
            <TouchableOpacity activeOpacity={0.92} style={styles.qtyStepperButton} onPress={onAdd}>
              <Ionicons name="add" size={15} color={COLORS.text} />
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity activeOpacity={0.92} style={styles.selectButton} onPress={onAdd}>
            <Text style={styles.selectButtonText}>Select</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function DineoutSpotlightCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.spotlightCard, { backgroundColor: item.accent }]}>
      <Text style={styles.spotlightTitle}>{item.title}</Text>
      <Text style={styles.spotlightSub}>{item.subtitle}</Text>
      <View style={styles.spotlightButton}>
        <Text style={styles.spotlightButtonText}>{item.action}</Text>
      </View>
    </TouchableOpacity>
  );
}

function SceneEventCard({ item, compact = false }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.sceneCard, compact ? styles.sceneCardCompact : styles.sceneCardGrid, { backgroundColor: item.accent }]}>
      <View style={styles.sceneDateBlock}>
        <Text style={styles.sceneDateText}>{item.date}</Text>
      </View>
      <View style={styles.sceneContent}>
        <View style={styles.sceneTagRow}>
          <View style={styles.sceneTag}><Text style={styles.sceneTagText}>{item.tag}</Text></View>
          <Text style={styles.sceneEmoji}>{item.emoji}</Text>
        </View>
        <Text style={styles.scenePrice}>Starts at {money(item.price)}</Text>
        <Text style={styles.sceneTitle} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.sceneSubtitle} numberOfLines={2}>{item.subtitle}</Text>
      </View>
    </TouchableOpacity>
  );
}

function EmptyBlock({ light = false, title, subtitle }) {
  return (
    <View style={[styles.emptyBlock, light && styles.emptyBlockDark]}>
      <Text style={[styles.emptyBlockTitle, light && styles.emptyBlockTitleDark]}>{title}</Text>
      <Text style={[styles.emptyBlockSubtitle, light && styles.emptyBlockSubtitleDark]}>{subtitle}</Text>
    </View>
  );
}

function LoadingBlock({ light = false, label }) {
  return (
    <View style={styles.loadingBlock}>
      <ActivityIndicator size="large" color={light ? COLORS.white : COLORS.success} />
      <Text style={[styles.loadingBlockText, light && styles.loadingBlockTextDark]}>{label}</Text>
    </View>
  );
}

export default function HomeTabScreen() {
  const {
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    rememberSearch,
    refreshing,
    loadVendors,
    vendorsLoading,
    featuredVendors,
    favorites,
    toggleFavorite,
    rememberStore,
    cart,
    cartCount,
    cartSubtotal,
    cartTotal,
    freeDeliveryRemaining,
    homeDeals,
    homeDealsLoading,
    addToCart,
    updateQty,
    recentSearches,
    suggestionPool,
  } = useGrabBasket();

  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const [sceneMoment, setSceneMoment] = useState('today');

  const theme = SERVICE_THEMES[activeService] || SERVICE_THEMES.food;
  const isScenes = activeService === 'scenes';
  const isWarehouse = activeService === 'warehouse';
  const isEatout = activeService === 'eatout';

  const deals = homeDeals.length > 0 ? homeDeals : FALLBACK_DEALS;

  const quickSearches = useMemo(() => {
    if (activeService === 'warehouse') {
      return ['Dryfruits', 'Dates', 'Snacks', 'Desserts', 'Best brands'];
    }
    if (activeService === 'eatout') {
      return ['Restaurants near me', 'Pre-Book Offers', 'Family dining', 'Cafe dates'];
    }
    if (activeService === 'scenes') {
      return ["Today's vibe", 'Weekend mood', "This week's drops", 'Next weekend'];
    }

    const seeds = ['Binge worthy deals', 'EatRight', 'Restaurant awards', 'Top rated near you'];
    const dynamic = [...recentSearches, ...suggestionPool].filter(Boolean);
    return [...new Set([...seeds, ...dynamic])].slice(0, 6);
  }, [activeService, recentSearches, suggestionPool]);

  const openVendor = (vendor) => {
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const deliveryStripText =
    cartSubtotal <= 0
      ? 'FREE DELIVERY on orders above ₹199'
      : freeDeliveryRemaining > 0
        ? `Add ${money(freeDeliveryRemaining)} more for FREE DELIVERY`
        : 'FREE DELIVERY unlocked';

  const onRefresh = () => loadVendors({ pullToRefresh: true });

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.hero }]}>
      <StatusBar barStyle={theme.statusBar} backgroundColor={theme.hero} />
      <View style={[styles.root, { backgroundColor: theme.canvas }]}>
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={[
            styles.scrollContent,
            { paddingBottom: tabBarHeight + 130 },
            isScenes && styles.scrollContentScenes,
          ]}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={isScenes ? COLORS.white : COLORS.success} />}>
          <View style={[styles.hero, { backgroundColor: theme.hero }]}>
            {theme.topStrip ? (
              <View style={styles.topStrip}>
                <Ionicons name="cash-outline" size={16} color="#dcfce7" />
                <Text style={styles.topStripText}>{theme.topStrip}</Text>
              </View>
            ) : null}

            <View style={styles.heroHeader}>
              <View style={{ flex: 1 }}>
                <Text style={[styles.heroTitle, isScenes && styles.heroTitleScenes]} numberOfLines={1}>{theme.title}</Text>
                <TouchableOpacity activeOpacity={0.9} style={styles.heroAddressRow}>
                  <Text style={styles.heroAddress} numberOfLines={1}>{theme.subtitle}</Text>
                  <Ionicons name="chevron-down" size={16} color={COLORS.white} />
                </TouchableOpacity>
              </View>
              <TouchableOpacity activeOpacity={0.92} style={styles.profileButton}>
                <Ionicons name="person-outline" size={22} color={COLORS.white} />
              </TouchableOpacity>
            </View>

            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceRow}>
              {TOP_SERVICES.map((item) => (
                <ServiceChip
                  key={item.key}
                  item={item}
                  active={activeService === item.key}
                  dark={isScenes}
                  onPress={() => setActiveService(item.key)}
                />
              ))}
            </ScrollView>

            <HeroSearch
              placeholder={theme.searchPlaceholder}
              value={homeSearch}
              onChangeText={setHomeSearch}
              onSubmit={() => rememberSearch(homeSearch)}
              actionLabel={theme.actionLabel}
              actionIcon={theme.actionIcon}
              dark={isScenes}
            />

            {isWarehouse ? (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.martFilterRow}>
                {WAREHOUSE_FILTERS.map((item) => (
                  <WarehouseFilterChip
                    key={item.key}
                    item={item}
                    active={activeShortcut === item.key}
                    onPress={() => setActiveShortcut(item.key)}
                  />
                ))}
              </ScrollView>
            ) : (
              <SearchShortcuts items={quickSearches.slice(0, 4)} dark={isScenes} />
            )}
          </View>

          {activeService === 'food' ? (
            <View style={styles.sectionWrap}>
              <View style={styles.foodCraveBanner}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.foodCraveTitle}>CRAVE</Text>
                  <Text style={styles.foodCraveSub}>UP TO 60% OFF & MORE</Text>
                </View>
                <TouchableOpacity activeOpacity={0.92} style={styles.foodCraveButton}>
                  <Text style={styles.foodCraveButtonText}>ORDER NOW</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.foodHighlightRow}>
                {FOOD_HIGHLIGHTS.map((item) => (
                  <FoodHighlightCard key={item.key} item={item} />
                ))}
              </View>

              <SectionHeader title="Top rated near you" subtitle="Make the first fold feel visual, branded and browseable." />

              {vendorsLoading ? (
                <LoadingBlock label="Loading restaurants..." />
              ) : featuredVendors.length === 0 ? (
                <EmptyBlock title="No restaurants available" subtitle="Connect your vendors feed or seed more demo stores to fill this rail." />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {featuredVendors.slice(0, 8).map((vendor) => (
                    <FoodRestaurantCard
                      key={vendor.id}
                      vendor={vendor}
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onOpen={() => openVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Restaurants to order from" subtitle="A cleaner visual list with denser metadata and stronger offer hierarchy." />

              {vendorsLoading ? (
                <LoadingBlock label="Refreshing restaurant feed..." />
              ) : featuredVendors.length === 0 ? (
                <EmptyBlock title="Your feed is empty" subtitle="Once your backend vendors load, this list becomes the main commerce discovery stack." />
              ) : (
                featuredVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="food"
                    favorite={Boolean(favorites[vendor.id])}
                    onOpen={() => openVendor(vendor)}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                  />
                ))
              )}
            </View>
          ) : null}

          {activeService === 'warehouse' ? (
            <View style={styles.sectionWrap}>
              <View style={styles.martHeroBanner}>
                <Text style={styles.martHeroBannerEyebrow}>BEST BRANDS</Text>
                <Text style={styles.martHeroBannerTitle}>Ramzan Mubarak</Text>
                <Text style={styles.martHeroBannerSub}>Festival merchandising, fast grocery intent and stronger quick-commerce density.</Text>
              </View>

              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {WAREHOUSE_BANNERS.map((item) => (
                  <View key={item.key} style={styles.martMiniBanner}>
                    <Text style={styles.martMiniBannerEmoji}>{item.emoji}</Text>
                    <Text style={styles.martMiniBannerTitle}>{item.title}</Text>
                  </View>
                ))}
              </ScrollView>

              <TouchableOpacity activeOpacity={0.92} style={styles.martInfoStrip}>
                <Text style={styles.martInfoStripText}>Explore 28 varieties of dates sourced from 12 countries.</Text>
                <Ionicons name="chevron-forward" size={18} color={COLORS.white} />
              </TouchableOpacity>

              <View style={styles.martDealsPanel}>
                <View style={styles.martDealsHeader}>
                  <Text style={styles.martDealsTitle}>₹9 everyday</Text>
                  <Text style={styles.martDealsSub}>Impulse-friendly add-ons, better pricing visibility and a faster add-to-cart loop.</Text>
                </View>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {(homeDealsLoading ? FALLBACK_DEALS : deals).map((item) => (
                    <WarehouseDealCard
                      key={item.id}
                      item={item}
                      qty={cart.items[item.id]?.qty || 0}
                      onAdd={() => addToCart(item)}
                      onRemove={() => updateQty(item, -1)}
                    />
                  ))}
                </ScrollView>
              </View>

              <SectionHeader title="Quick grocery stores" subtitle="This list should feel like instant commerce, not a generic marketplace feed." />

              {vendorsLoading ? (
                <LoadingBlock label="Loading nearby grocery stores..." />
              ) : featuredVendors.length === 0 ? (
                <EmptyBlock title="No nearby stores yet" subtitle="Seed warehouse-ready vendors and category imagery to make this tab feel complete." />
              ) : (
                featuredVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="warehouse"
                    favorite={Boolean(favorites[vendor.id])}
                    onOpen={() => openVendor(vendor)}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                  />
                ))
              )}
            </View>
          ) : null}

          {activeService === 'eatout' ? (
            <View style={styles.sectionWrapDineout}>
              <View style={styles.dineoutHeroCard}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.dineoutHeroTitle}>PARTY{`\n`}FULL</Text>
                  <Text style={styles.dineoutHeroSub}>Offer-led dining discovery with better hierarchy for plans, bookings and bill payment benefits.</Text>
                </View>
                <View style={styles.dineoutHeroFace}><Text style={styles.dineoutHeroFaceText}>😎</Text></View>
              </View>

              <View style={styles.dineoutFeatureGrid}>
                <TouchableOpacity activeOpacity={0.92} style={styles.dineoutBigFeature}>
                  <Text style={styles.dineoutBigFeatureValue}>FLAT{`\n`}50% OFF</Text>
                </TouchableOpacity>
                <View style={styles.dineoutMiniFeatureColumn}>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineoutMiniFeature}><Text style={styles.dineoutMiniFeatureText}>GIRF Hall of Fame</Text></TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineoutMiniFeature}><Text style={styles.dineoutMiniFeatureText}>Family-Friendly Spots</Text></TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineoutMiniFeature}><Text style={styles.dineoutMiniFeatureText}>Cafes & Quick Bites</Text></TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineoutMiniFeature}><Text style={styles.dineoutMiniFeatureText}>Exciting Freebies</Text></TouchableOpacity>
                </View>
              </View>

              <SectionHeader title="In the spotlight" subtitle="Campaign surfaces, event cards and offer-led discovery should feel instantly premium." actionLabel="View all" />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {DINEOUT_SPOTLIGHT.map((item) => (
                  <DineoutSpotlightCard key={item.key} item={item} />
                ))}
              </ScrollView>

              <Text style={styles.personalPrompt}>Hari, what's on your mind?</Text>
              <View style={styles.quickPromptRow}>
                <TouchableOpacity activeOpacity={0.92} style={styles.quickPromptCard}><Text style={styles.quickPromptText}>Restaurants near me</Text></TouchableOpacity>
                <TouchableOpacity activeOpacity={0.92} style={styles.quickPromptCard}><Text style={styles.quickPromptText}>Pre-Book Offers</Text></TouchableOpacity>
              </View>

              <SectionHeader title="Popular picks" subtitle="Horizontal venue storytelling adds warmth before the denser list below." actionLabel="View all" />

              {vendorsLoading ? (
                <LoadingBlock label="Loading dineout venues..." />
              ) : featuredVendors.length === 0 ? (
                <EmptyBlock title="No venues available" subtitle="Once you connect dining-specific vendors and artwork, this section becomes much stronger." />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {featuredVendors.slice(0, 8).map((vendor) => (
                    <FoodRestaurantCard
                      key={vendor.id}
                      vendor={vendor}
                      service="eatout"
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onOpen={() => openVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Places with bill offers" subtitle="List view should support conversion once discovery has created intent." />

              {vendorsLoading ? (
                <LoadingBlock label="Refreshing dineout feed..." />
              ) : featuredVendors.length === 0 ? (
                <EmptyBlock title="No dineout list yet" subtitle="Your design shell is ready; now the experience needs venue imagery, offers and bookings data." />
              ) : (
                featuredVendors.slice(0, 6).map((vendor) => (
                  <VendorListCard
                    key={vendor.id}
                    vendor={vendor}
                    service="eatout"
                    favorite={Boolean(favorites[vendor.id])}
                    onOpen={() => openVendor(vendor)}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                  />
                ))
              )}
            </View>
          ) : null}

          {activeService === 'scenes' ? (
            <View style={styles.sectionWrapScenes}>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRailScenes}>
                {SCENE_EVENTS.slice(0, 2).map((item) => (
                  <SceneEventCard key={item.id} item={item} compact />
                ))}
              </ScrollView>

              <TouchableOpacity activeOpacity={0.92} style={styles.scenesViewAllButton}>
                <Text style={styles.scenesViewAllText}>View all</Text>
              </TouchableOpacity>

              <SectionHeader title="WHEN IS THE PLAN?" subtitle="Use fast filters and bold typography to make the event feed feel editorial." light />
              <View style={styles.sceneMomentRow}>
                {SCENE_MOMENTS.map((item) => {
                  const active = sceneMoment === item.key;
                  return (
                    <TouchableOpacity
                      key={item.key}
                      activeOpacity={0.92}
                      style={[styles.sceneMomentChip, active && styles.sceneMomentChipActive]}
                      onPress={() => setSceneMoment(item.key)}>
                      <Text style={[styles.sceneMomentChipText, active && styles.sceneMomentChipTextActive]}>{item.label}</Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              <SectionHeader title="ALL SCENES" subtitle="Grid cards, clear dates and bold price markers push this closer to a production events marketplace." light />
              <View style={styles.sceneGrid}>
                {SCENE_EVENTS.map((item) => (
                  <SceneEventCard key={item.id} item={item} />
                ))}
              </View>
            </View>
          ) : null}
        </ScrollView>

        <View style={[styles.overlayWrap, { bottom: tabBarHeight + 12 }]}>
          {cartCount > 0 ? (
            <TouchableOpacity activeOpacity={0.94} style={styles.cartBar} onPress={() => router.push('/cart')}>
              <View>
                <Text style={styles.cartBarTitle}>{isEatout ? 'Continue booking' : 'View cart'}</Text>
                <Text style={styles.cartBarText}>{cartCount} items · {money(cartTotal)}</Text>
              </View>
              <Ionicons name="arrow-forward" size={20} color={COLORS.white} />
            </TouchableOpacity>
          ) : null}

          {isWarehouse ? (
            <View style={styles.deliveryStrip}>
              <Text style={styles.deliveryStripText}>{deliveryStripText}</Text>
            </View>
          ) : null}
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  root: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 24,
  },
  scrollContentScenes: {
    backgroundColor: '#040713',
  },
  hero: {
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 18,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    overflow: 'hidden',
  },
  topStrip: {
    minHeight: 34,
    borderRadius: 18,
    paddingHorizontal: 12,
    marginBottom: 14,
    backgroundColor: 'rgba(255,255,255,0.12)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    alignSelf: 'stretch',
  },
  topStripText: {
    flex: 1,
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '700',
  },
  heroHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  heroTitle: {
    color: COLORS.white,
    fontSize: 31,
    fontWeight: '900',
    letterSpacing: -0.8,
  },
  heroTitleScenes: {
    fontSize: 21,
    fontWeight: '800',
  },
  heroAddressRow: {
    marginTop: 4,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    maxWidth: '95%',
  },
  heroAddress: {
    flex: 1,
    color: 'rgba(255,255,255,0.92)',
    fontSize: 15,
    fontWeight: '600',
  },
  profileButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.16)',
  },
  serviceRow: {
    paddingTop: 4,
    paddingBottom: 14,
    gap: 10,
  },
  serviceChip: {
    minWidth: 92,
    borderRadius: 24,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  serviceChipActive: {
    backgroundColor: COLORS.white,
    borderColor: COLORS.white,
  },
  serviceChipDark: {
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderColor: 'rgba(255,255,255,0.08)',
  },
  serviceChipDarkActive: {
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderColor: 'rgba(255,255,255,0.18)',
  },
  serviceChipEmoji: {
    fontSize: 20,
  },
  serviceChipBadge: {
    color: '#dbeafe',
    fontSize: 10,
    fontWeight: '800',
  },
  serviceChipBadgeDark: {
    color: '#c7d2fe',
  },
  serviceChipLabel: {
    color: COLORS.white,
    fontSize: 13,
    fontWeight: '800',
  },
  serviceChipLabelDark: {
    color: COLORS.white,
  },
  serviceChipLabelActive: {
    color: COLORS.black,
  },
  heroSearchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  heroSearch: {
    flex: 1,
    minHeight: 58,
    borderRadius: 18,
    paddingHorizontal: 16,
    backgroundColor: COLORS.white,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  heroSearchDark: {
    backgroundColor: 'rgba(255,255,255,0.10)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.10)',
  },
  heroSearchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '600',
  },
  heroSearchInputDark: {
    color: COLORS.white,
  },
  heroSearchAction: {
    width: 62,
    minHeight: 58,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.20)',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
  },
  heroSearchActionDark: {
    backgroundColor: 'rgba(255,255,255,0.14)',
  },
  heroSearchActionText: {
    color: COLORS.white,
    fontSize: 13,
    fontWeight: '900',
  },
  shortcutRow: {
    gap: 10,
    paddingTop: 14,
  },
  shortcutChip: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  shortcutChipDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  shortcutText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '800',
  },
  shortcutTextDark: {
    color: '#e5e7eb',
  },
  martFilterRow: {
    gap: 10,
    paddingTop: 14,
  },
  martFilterChip: {
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: 'rgba(255,255,255,0.12)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  martFilterChipActive: {
    backgroundColor: COLORS.white,
  },
  martFilterChipText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '800',
  },
  martFilterChipTextActive: {
    color: COLORS.martPrimary,
  },
  sectionWrap: {
    paddingTop: 16,
    paddingHorizontal: 16,
  },
  sectionWrapDineout: {
    paddingTop: 16,
    paddingHorizontal: 16,
    backgroundColor: COLORS.white,
  },
  sectionWrapScenes: {
    paddingTop: 16,
    paddingHorizontal: 16,
    backgroundColor: '#040713',
  },
  foodCraveBanner: {
    borderRadius: 28,
    padding: 20,
    backgroundColor: COLORS.foodPrimary,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    shadowColor: COLORS.shadow,
    shadowOpacity: 1,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 8,
  },
  foodCraveTitle: {
    color: '#ffcf24',
    fontSize: 44,
    fontWeight: '900',
    letterSpacing: -1,
  },
  foodCraveSub: {
    color: COLORS.white,
    fontSize: 15,
    fontWeight: '800',
  },
  foodCraveButton: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 16,
    backgroundColor: '#ffd400',
  },
  foodCraveButtonText: {
    color: COLORS.foodPrimaryDark,
    fontSize: 12,
    fontWeight: '900',
  },
  foodHighlightRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 14,
  },
  foodHighlightCard: {
    flex: 1,
    minHeight: 126,
    borderRadius: 22,
    padding: 16,
    backgroundColor: '#ffd644',
  },
  foodHighlightEmoji: {
    fontSize: 28,
    marginBottom: 10,
  },
  foodHighlightTitle: {
    color: COLORS.foodPrimaryDark,
    fontSize: 15,
    fontWeight: '900',
  },
  foodHighlightBadge: {
    color: '#5b2d00',
    fontSize: 12,
    fontWeight: '700',
    marginTop: 6,
  },
  sectionHeader: {
    marginTop: 22,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
    letterSpacing: -0.8,
  },
  sectionTitleLight: {
    color: COLORS.white,
  },
  sectionSubtitle: {
    color: COLORS.textSoft,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 4,
    fontWeight: '600',
  },
  sectionSubtitleLight: {
    color: '#98a2b3',
  },
  sectionAction: {
    color: COLORS.dineAccent,
    fontSize: 15,
    fontWeight: '800',
  },
  sectionActionLight: {
    color: COLORS.white,
  },
  horizontalRail: {
    gap: 14,
    paddingBottom: 4,
  },
  horizontalRailScenes: {
    gap: 14,
    paddingBottom: 4,
    paddingRight: 12,
  },
  restaurantCard: {
    width: 156,
  },
  restaurantPoster: {
    height: 136,
    borderRadius: 24,
    padding: 12,
    justifyContent: 'space-between',
    overflow: 'hidden',
  },
  restaurantPosterFood: {
    backgroundColor: '#3a0f57',
  },
  restaurantPosterDineout: {
    backgroundColor: '#522012',
  },
  offerPillBlack: {
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  offerPillBlackText: {
    color: COLORS.white,
    fontSize: 11,
    fontWeight: '900',
  },
  heartFab: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  restaurantPosterInitials: {
    position: 'absolute',
    left: 14,
    bottom: 14,
    color: 'rgba(255,255,255,0.92)',
    fontSize: 34,
    fontWeight: '900',
    letterSpacing: -1,
  },
  restaurantCardTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    marginTop: 10,
  },
  restaurantCardMeta: {
    color: COLORS.textSoft,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 4,
  },
  vendorListCard: {
    backgroundColor: COLORS.white,
    borderRadius: 24,
    padding: 14,
    marginBottom: 12,
    flexDirection: 'row',
    gap: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    shadowColor: COLORS.shadow,
    shadowOpacity: 1,
    shadowRadius: 14,
    shadowOffset: { width: 0, height: 6 },
    elevation: 5,
  },
  vendorThumb: {
    width: 84,
    height: 84,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorThumbFood: {
    backgroundColor: COLORS.foodSoft,
  },
  vendorThumbMart: {
    backgroundColor: COLORS.martSoft,
  },
  vendorThumbDine: {
    backgroundColor: COLORS.dineSoft,
  },
  vendorThumbText: {
    color: COLORS.black,
    fontSize: 24,
    fontWeight: '900',
  },
  vendorTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  vendorName: {
    flex: 1,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  vendorDescription: {
    color: COLORS.textSoft,
    fontSize: 13,
    lineHeight: 18,
    marginTop: 6,
    fontWeight: '600',
  },
  vendorMetaWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 10,
  },
  metaPill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: '#f2f4f7',
  },
  metaPillText: {
    color: COLORS.textSoft,
    fontSize: 11,
    fontWeight: '800',
  },
  metaPillAccent: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: '#fff1e7',
  },
  metaPillAccentText: {
    color: '#c2410c',
    fontSize: 11,
    fontWeight: '900',
  },
  martHeroBanner: {
    borderRadius: 28,
    padding: 20,
    backgroundColor: COLORS.martPrimary,
  },
  martHeroBannerEyebrow: {
    color: '#dbeafe',
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.8,
  },
  martHeroBannerTitle: {
    color: COLORS.white,
    fontSize: 30,
    fontWeight: '900',
    marginTop: 8,
  },
  martHeroBannerSub: {
    color: '#dbeafe',
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
    marginTop: 8,
  },
  martMiniBanner: {
    width: 146,
    minHeight: 116,
    borderRadius: 22,
    padding: 14,
    backgroundColor: COLORS.martPrimary,
    justifyContent: 'space-between',
  },
  martMiniBannerEmoji: {
    fontSize: 28,
  },
  martMiniBannerTitle: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '800',
    lineHeight: 20,
  },
  martInfoStrip: {
    marginTop: 14,
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: COLORS.martPrimary,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  martInfoStripText: {
    flex: 1,
    color: COLORS.white,
    fontSize: 15,
    fontWeight: '800',
  },
  martDealsPanel: {
    marginTop: 16,
    borderRadius: 28,
    padding: 16,
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  martDealsHeader: {
    marginBottom: 12,
  },
  martDealsTitle: {
    color: COLORS.martPrimary,
    fontSize: 28,
    fontWeight: '900',
  },
  martDealsSub: {
    color: COLORS.textSoft,
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
    marginTop: 4,
  },
  martDealCard: {
    width: 172,
    borderRadius: 24,
    padding: 14,
    backgroundColor: '#f8fbff',
    borderWidth: 1,
    borderColor: '#dce8ff',
  },
  martDealBadge: {
    alignSelf: 'flex-start',
    color: COLORS.martPrimary,
    fontSize: 11,
    fontWeight: '900',
    backgroundColor: '#dbeafe',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  martDealImage: {
    marginTop: 12,
    width: 68,
    height: 68,
    borderRadius: 18,
    backgroundColor: COLORS.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  martDealEmoji: {
    fontSize: 34,
  },
  martDealTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
    marginTop: 12,
  },
  martDealMeta: {
    color: COLORS.textSoft,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  martDealFooter: {
    marginTop: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  martDealPrice: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  selectButton: {
    minWidth: 76,
    height: 38,
    borderRadius: 14,
    backgroundColor: COLORS.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#b9ccff',
  },
  selectButtonText: {
    color: COLORS.martAccent,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyStepper: {
    minWidth: 92,
    height: 38,
    borderRadius: 14,
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: '#d0d5dd',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 6,
  },
  qtyStepperButton: {
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: '#f2f4f7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyStepperText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },
  dineoutHeroCard: {
    borderRadius: 28,
    padding: 20,
    backgroundColor: COLORS.dinePrimary,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  dineoutHeroTitle: {
    color: '#fde047',
    fontSize: 40,
    fontWeight: '900',
    letterSpacing: -1,
    lineHeight: 38,
  },
  dineoutHeroSub: {
    color: '#ede9fe',
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
    marginTop: 10,
  },
  dineoutHeroFace: {
    width: 76,
    height: 76,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  dineoutHeroFaceText: {
    fontSize: 34,
  },
  dineoutFeatureGrid: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 14,
  },
  dineoutBigFeature: {
    flex: 0.9,
    minHeight: 182,
    borderRadius: 24,
    backgroundColor: '#fde047',
    padding: 18,
    justifyContent: 'flex-end',
  },
  dineoutBigFeatureValue: {
    color: '#4a1d96',
    fontSize: 34,
    fontWeight: '900',
    lineHeight: 32,
    letterSpacing: -1,
  },
  dineoutMiniFeatureColumn: {
    flex: 1,
    gap: 10,
  },
  dineoutMiniFeature: {
    flex: 1,
    minHeight: 40,
    borderRadius: 18,
    backgroundColor: '#ffef98',
    paddingHorizontal: 14,
    paddingVertical: 12,
    justifyContent: 'center',
  },
  dineoutMiniFeatureText: {
    color: '#5b2d00',
    fontSize: 13,
    fontWeight: '900',
  },
  spotlightCard: {
    width: 252,
    minHeight: 170,
    borderRadius: 24,
    padding: 18,
    justifyContent: 'space-between',
  },
  spotlightTitle: {
    color: COLORS.white,
    fontSize: 24,
    fontWeight: '900',
    letterSpacing: -0.6,
  },
  spotlightSub: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
    marginTop: 8,
  },
  spotlightButton: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  spotlightButtonText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '900',
  },
  personalPrompt: {
    color: COLORS.text,
    fontSize: 26,
    fontWeight: '900',
    letterSpacing: -0.8,
    marginTop: 16,
  },
  quickPromptRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 12,
  },
  quickPromptCard: {
    flex: 1,
    minHeight: 72,
    borderRadius: 22,
    padding: 16,
    backgroundColor: '#fff1e7',
    justifyContent: 'center',
  },
  quickPromptText: {
    color: '#8a3500',
    fontSize: 16,
    fontWeight: '900',
  },
  sceneMomentRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginTop: 6,
    marginBottom: 6,
  },
  sceneMomentChip: {
    borderRadius: 999,
    paddingHorizontal: 16,
    paddingVertical: 11,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.10)',
  },
  sceneMomentChipActive: {
    backgroundColor: COLORS.white,
  },
  sceneMomentChipText: {
    color: COLORS.white,
    fontSize: 13,
    fontWeight: '900',
  },
  sceneMomentChipTextActive: {
    color: COLORS.black,
  },
  scenesViewAllButton: {
    alignSelf: 'center',
    marginTop: 10,
    marginBottom: 12,
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  scenesViewAllText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '800',
  },
  sceneGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 12,
    marginTop: 4,
  },
  sceneCard: {
    borderRadius: 24,
    overflow: 'hidden',
  },
  sceneCardCompact: {
    width: 240,
  },
  sceneCardGrid: {
    width: '48%',
  },
  sceneDateBlock: {
    width: 70,
    minHeight: 54,
    borderBottomRightRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 8,
    paddingVertical: 10,
  },
  sceneDateText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '900',
    textAlign: 'center',
  },
  sceneContent: {
    padding: 16,
    minHeight: 182,
    justifyContent: 'space-between',
  },
  sceneTagRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  sceneTag: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: 'rgba(255,255,255,0.12)',
  },
  sceneTagText: {
    color: COLORS.white,
    fontSize: 11,
    fontWeight: '900',
  },
  sceneEmoji: {
    fontSize: 24,
  },
  scenePrice: {
    color: '#f8fafc',
    fontSize: 16,
    fontWeight: '900',
    marginTop: 14,
  },
  sceneTitle: {
    color: COLORS.white,
    fontSize: 19,
    fontWeight: '900',
    lineHeight: 22,
    marginTop: 8,
  },
  sceneSubtitle: {
    color: '#cbd5e1',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
    marginTop: 6,
  },
  loadingBlock: {
    borderRadius: 24,
    paddingVertical: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingBlockText: {
    color: COLORS.textSoft,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 12,
  },
  loadingBlockTextDark: {
    color: '#cbd5e1',
  },
  emptyBlock: {
    borderRadius: 24,
    padding: 18,
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  emptyBlockDark: {
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderColor: 'rgba(255,255,255,0.08)',
  },
  emptyBlockTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  emptyBlockTitleDark: {
    color: COLORS.white,
  },
  emptyBlockSubtitle: {
    color: COLORS.textSoft,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '600',
    marginTop: 8,
  },
  emptyBlockSubtitleDark: {
    color: '#cbd5e1',
  },
  overlayWrap: {
    position: 'absolute',
    left: 16,
    right: 16,
    gap: 10,
  },
  cartBar: {
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    backgroundColor: COLORS.black,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: COLORS.shadow,
    shadowOpacity: 1,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 8,
  },
  cartBarTitle: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '900',
  },
  cartBarText: {
    color: '#d0d5dd',
    fontSize: 13,
    fontWeight: '700',
    marginTop: 4,
  },
  deliveryStrip: {
    alignSelf: 'center',
    borderRadius: 16,
    paddingHorizontal: 18,
    paddingVertical: 12,
    backgroundColor: '#e6f7f3',
  },
  deliveryStripText: {
    color: '#0f5132',
    fontSize: 14,
    fontWeight: '900',
  },
});