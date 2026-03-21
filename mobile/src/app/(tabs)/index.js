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
  page: '#f6f7fb',
  white: '#ffffff',
  text: '#111827',
  muted: '#6b7280',
  subtle: '#9ca3af',
  border: '#ebeef3',
  success: '#16a34a',
  successSoft: '#ecfdf3',
  danger: '#ef4444',
  dark: '#050816',
  foodHero: '#6d28d9',
  foodHeroSoft: '#8b5cf6',
  foodCard: '#f4ebff',
  martHero: '#082b73',
  martHeroSoft: '#0b4fb3',
  martCard: '#eaf2ff',
  dineHero: '#551f9c',
  dineHeroSoft: '#8b5cf6',
  dineCard: '#fff3e9',
  dineAccent: '#f97316',
  scenesHero: '#030611',
  scenesCard: '#0f172a',
  scenesSoft: '#1f2937',
  scenesBorder: '#162033',
  gold: '#f59e0b',
  peach: '#fff1e8',
  peachText: '#f97316',
  blueSoft: '#eaf2ff',
  blueText: '#0b57d0',
  chip: '#f3f4f6',
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
    canvas: COLORS.page,
    hero: COLORS.foodHero,
    heroAccent: COLORS.foodHeroSoft,
    statusBar: 'light-content',
    locationTitle: 'Valliachans Place',
    locationSubtitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: "Search for 'EatRight'",
    heroLabel: 'CRAVE',
    heroTitle: 'Up to 60% off & more',
    heroSubtitle:
      'Restaurant-led discovery with stronger offer cards and better first-fold hierarchy.',
    actionText: 'VEG',
    actionIcon: 'leaf-outline',
  },
  warehouse: {
    canvas: '#f7fbff',
    hero: COLORS.martHero,
    heroAccent: COLORS.martHeroSoft,
    statusBar: 'light-content',
    locationTitle: '5 mins',
    locationSubtitle: 'To Valliachans Place · 12b, Great Orchard / Tower 1',
    searchPlaceholder: 'Search for Dryfruits',
    heroLabel: 'RAMZAN MUBARAK',
    heroTitle: 'Fast grocery, clearer merchandising',
    heroSubtitle:
      'Festival-led quick-commerce surfaces with faster add flows and cleaner shelf communication.',
    actionText: '',
    actionIcon: 'bookmark-outline',
  },
  eatout: {
    canvas: '#fbfbfd',
    hero: COLORS.dineHero,
    heroAccent: COLORS.dineHeroSoft,
    statusBar: 'light-content',
    locationTitle: 'Valliachans Place',
    locationSubtitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for cuisines',
    heroLabel: 'PARTY FULL',
    heroTitle: 'Flat 50% OFF',
    heroSubtitle:
      'Offer-first dining discovery with better campaigns, tables and bill-payment cues.',
    actionText: '',
    actionIcon: 'sparkles-outline',
    topStrip: 'Earn flat 10% Dinecash on every bill payment',
  },
  scenes: {
    canvas: COLORS.scenesHero,
    hero: COLORS.scenesHero,
    heroAccent: '#141b30',
    statusBar: 'light-content',
    locationTitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    locationSubtitle: 'Kochi experiences curated for tonight, this week and next weekend.',
    searchPlaceholder: 'Search for events or experiences',
    heroLabel: 'LIMITED TIME OFFER',
    heroTitle: 'Up to 20% off on select events',
    heroSubtitle:
      'Discovery-first events browsing with stronger moods, collections and premium dark surfaces.',
    actionText: '',
    actionIcon: 'sparkles-outline',
  },
};

const FOOD_HIGHLIGHTS = [
  { key: 'deals', title: 'Binge worthy deals', badge: 'Up to 60% OFF & more', icon: 'flame-outline' },
  { key: 'eatright', title: 'EatRight', badge: 'Win up to ₹300 free cash', icon: 'leaf-outline' },
  { key: 'awards', title: 'Restaurant awards', badge: 'Best rated around you', icon: 'trophy-outline' },
];

const WAREHOUSE_FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxxsaver', label: 'Maxxsaver', icon: 'pricetag-outline' },
  { key: 'festival', label: 'Ramzan', icon: 'moon-outline' },
  { key: 'ready', label: 'Exam ready', icon: 'flash-outline' },
];

const WAREHOUSE_BANNERS = [
  { key: 'snacks', title: 'Iftar snacks & drinks', icon: 'cafe-outline' },
  { key: 'biryani', title: 'Biryani & feasting corner', icon: 'restaurant-outline' },
  { key: 'dates', title: 'Dates, dry fruits & desserts', icon: 'gift-outline' },
  { key: 'gifting', title: 'Gift-ready picks', icon: 'sparkles-outline' },
];

const DINEOUT_SPOTLIGHT = [
  {
    key: 'awards',
    title: 'Restaurant Awards',
    subtitle: 'Vote, share & win up to ₹600!',
    action: 'Vote now',
    accent: '#6c1731',
  },
  {
    key: 'flavours',
    title: 'Flavours by the city',
    subtitle: 'Editor-picked tables and fresh menus.',
    action: 'Preview',
    accent: '#29421d',
  },
  {
    key: 'prebook',
    title: 'Pre-book offers',
    subtitle: 'Reserve early and unlock sharper dining deals.',
    action: 'Explore',
    accent: '#48218d',
  },
];

const SCENE_MOODS = [
  { key: 'today', label: "Today's\nVibe" },
  { key: 'weekend', label: 'Weekend\nMood' },
  { key: 'week', label: "This Week's\nDrops" },
  { key: 'next', label: 'Next Weekend\nTea' },
];

const SCENE_FILTERS = [
  { key: 'all', label: 'All scenes' },
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This Week' },
  { key: 'weekend', label: 'This Weekend' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room at Break N Chill',
    subtitle: 'Break n Chill · Rage Room · Chittethukara',
    date: '20\nMAR',
    price: 299,
    tag: 'Stress buster',
    bucket: 'today',
    accent: '#1b1020',
    icon: 'game-controller-outline',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Throwing Workshop',
    subtitle: 'Soil to Soul Ceramics · Kadavanthra',
    date: '20\nMAR',
    price: 1000,
    tag: 'Hands-on',
    bucket: 'week',
    accent: '#3b2d24',
    icon: 'color-palette-outline',
  },
  {
    id: 'scene-3',
    title: 'Kimchi Culture',
    subtitle: 'SKEI Presents · Korean food and culture',
    date: '22\nMAR',
    price: 699,
    tag: 'Culture',
    bucket: 'weekend',
    accent: '#6a1f24',
    icon: 'restaurant-outline',
  },
  {
    id: 'scene-4',
    title: 'Stand-up Comedy Night',
    subtitle: 'Top comics · Kakkanad',
    date: '23\nMAR',
    price: 499,
    tag: 'Top rated',
    bucket: 'weekend',
    accent: '#1c2f53',
    icon: 'mic-outline',
  },
  {
    id: 'scene-5',
    title: 'Kids Creative Lab',
    subtitle: 'Family plans · Panampilly Nagar',
    date: '29\nMAR',
    price: 399,
    tag: 'Family pick',
    bucket: 'next',
    accent: '#4a2c69',
    icon: 'happy-outline',
  },
];

const FALLBACK_DEALS = [
  {
    id: 'fallback-1',
    vendor_id: 'demo-mart',
    vendorName: 'Instamart Daily',
    name: 'Amul Curd',
    price: 35,
    brand: 'Daily essential',
  },
  {
    id: 'fallback-2',
    vendor_id: 'demo-mart',
    vendorName: 'Instamart Daily',
    name: 'Cadbury Dairy Milk',
    price: 20,
    brand: 'Quick sweet bite',
  },
  {
    id: 'fallback-3',
    vendor_id: 'demo-mart',
    vendorName: 'Instamart Daily',
    name: 'Kissan Jam',
    price: 49,
    brand: 'Breakfast saver',
  },
  {
    id: 'fallback-4',
    vendor_id: 'demo-mart',
    vendorName: 'Instamart Daily',
    name: 'Classic Chips',
    price: 20,
    brand: 'Impulse add-on',
  },
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

function chunkItems(items = [], size = 2) {
  const rows = [];
  for (let index = 0; index < items.length; index += size) {
    rows.push(items.slice(index, index + size));
  }
  return rows;
}

function SectionHeader({ title, subtitle, actionLabel, onPressAction, light = false }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>
            {subtitle}
          </Text>
        ) : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>
            {actionLabel}
          </Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function LoadingBlock({ label, light = false }) {
  return (
    <View style={[styles.feedbackCard, light && styles.feedbackCardDark]}>
      <ActivityIndicator size="large" color={light ? '#ffffff' : COLORS.success} />
      <Text style={[styles.feedbackTitle, light && styles.feedbackTitleDark]}>{label}</Text>
    </View>
  );
}

function EmptyBlock({ title, subtitle, light = false }) {
  return (
    <View style={[styles.feedbackCard, light && styles.feedbackCardDark]}>
      <Text style={[styles.feedbackTitle, light && styles.feedbackTitleDark]}>{title}</Text>
      <Text style={[styles.feedbackSubtitle, light && styles.feedbackSubtitleDark]}>
        {subtitle}
      </Text>
    </View>
  );
}

function ServiceChip({ item, active, dark = false, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[
        styles.serviceChip,
        dark && styles.serviceChipDark,
        active && styles.serviceChipActive,
        dark && active && styles.serviceChipDarkActive,
      ]}
      onPress={onPress}>
      <View style={styles.serviceChipIconWrap}>
        <Ionicons
          name={item.icon}
          size={18}
          color={active ? '#ffffff' : dark ? '#ffffff' : COLORS.text}
        />
      </View>
      <View style={{ flex: 1 }}>
        {item.badge && active ? <Text style={styles.serviceChipBadge}>{item.badge}</Text> : null}
        <Text
          style={[
            styles.serviceChipText,
            active && styles.serviceChipTextActive,
            dark && !active && styles.serviceChipTextDark,
          ]}>
          {item.label}
        </Text>
      </View>
    </TouchableOpacity>
  );
}

function HeroSearchBar({
  placeholder,
  value,
  onChangeText,
  onSubmit,
  actionText,
  actionIcon,
  dark = false,
}) {
  return (
    <View style={styles.searchBarWrap}>
      <View style={[styles.searchBar, dark && styles.searchBarDark]}>
        <Ionicons
          name="search-outline"
          size={20}
          color={dark ? '#c7cedd' : COLORS.muted}
        />
        <TextInput
          style={[styles.searchInput, dark && styles.searchInputDark]}
          placeholder={placeholder}
          placeholderTextColor={dark ? '#aab4c8' : COLORS.subtle}
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          returnKeyType="search"
        />
        <Ionicons
          name={actionText ? 'mic-outline' : actionIcon || 'bookmark-outline'}
          size={20}
          color={dark ? '#c7cedd' : COLORS.muted}
        />
      </View>

      <TouchableOpacity
        activeOpacity={0.92}
        style={[styles.searchAction, dark && styles.searchActionDark]}>
        {actionText ? (
          <>
            <Text style={styles.searchActionText}>{actionText}</Text>
            <Ionicons name={actionIcon || 'leaf-outline'} size={18} color="#ffffff" />
          </>
        ) : (
          <Ionicons name={actionIcon || 'bookmark-outline'} size={20} color="#ffffff" />
        )}
      </TouchableOpacity>
    </View>
  );
}

function BasketBanner({ cartCount, cartTotal, onPress, dark = false }) {
  if (cartCount <= 0) return null;

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.basketBanner, dark && styles.basketBannerDark]}
      onPress={onPress}>
      <View style={[styles.basketIconWrap, dark && styles.basketIconWrapDark]}>
        <Ionicons
          name="bag-handle-outline"
          size={20}
          color={dark ? '#ffffff' : COLORS.success}
        />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.basketBannerTitle, dark && styles.basketBannerTitleDark]}>
          Active basket
        </Text>
        <Text
          style={[styles.basketBannerSubtitle, dark && styles.basketBannerSubtitleDark]}>
          {cartCount} items · {money(cartTotal)}
        </Text>
      </View>
      <Ionicons
        name="chevron-forward"
        size={18}
        color={dark ? '#ffffff' : COLORS.success}
      />
    </TouchableOpacity>
  );
}

function FoodHighlightCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.foodHighlightCard}>
      <View style={styles.foodHighlightIconWrap}>
        <Ionicons name={item.icon} size={20} color={COLORS.foodHero} />
      </View>
      <Text style={styles.foodHighlightTitle}>{item.title}</Text>
      <Text style={styles.foodHighlightBadge}>{item.badge}</Text>
    </TouchableOpacity>
  );
}

function RailVendorCard({
  vendor,
  service = 'food',
  favorite,
  onToggleFavorite,
  onOpen,
}) {
  return (
    <TouchableOpacity
      activeOpacity={0.94}
      style={[styles.railVendorCard, service === 'eatout' && styles.railVendorCardDine]}
      onPress={onOpen}>
      <View
        style={[
          styles.railVendorVisual,
          service === 'eatout' && styles.railVendorVisualDine,
        ]}>
        <View style={styles.offerBadge}>
          <Text style={styles.offerBadgeText}>{getOfferLabel(vendor, service)}</Text>
        </View>
        <TouchableOpacity
          activeOpacity={0.9}
          style={styles.favoriteButton}
          onPress={onToggleFavorite}>
          <Ionicons
            name={favorite ? 'heart' : 'heart-outline'}
            size={16}
            color={favorite ? COLORS.danger : COLORS.white}
          />
        </TouchableOpacity>
        <View style={styles.visualMonogramWrap}>
          <Text style={styles.visualMonogram}>{initials(vendor?.name)}</Text>
        </View>
      </View>

      <Text style={styles.railVendorName} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={styles.railVendorMeta} numberOfLines={1}>
        {estimateEta(vendor, service)} · {getDeliveryFeeLabel(vendor, service)}
      </Text>
      <View style={styles.railVendorRatingRow}>
        <Ionicons name="star" size={12} color={COLORS.success} />
        <Text style={styles.railVendorRating}>{getVendorRating(vendor)}</Text>
      </View>
    </TouchableOpacity>
  );
}

function VendorFeedCard({
  vendor,
  service = 'food',
  favorite,
  onToggleFavorite,
  onOpen,
}) {
  const ctaLabel =
    service === 'eatout'
      ? 'BOOK TABLE'
      : service === 'warehouse'
        ? 'START SHOPPING'
        : 'OPEN STORE';

  return (
    <TouchableOpacity
      activeOpacity={0.94}
      style={[styles.feedCard, service === 'eatout' && styles.feedCardDine]}
      onPress={onOpen}>
      <View style={styles.feedVisualCol}>
        <View
          style={[
            styles.feedVisual,
            service === 'warehouse' && styles.feedVisualMart,
            service === 'eatout' && styles.feedVisualDine,
          ]}>
          <Text style={styles.feedVisualText}>{initials(vendor?.name)}</Text>
          <View style={styles.feedOfferInline}>
            <Text style={styles.feedOfferInlineText}>
              {getOfferLabel(vendor, service)}
            </Text>
          </View>
        </View>
      </View>

      <View style={styles.feedContent}>
        <View style={styles.feedTitleRow}>
          <Text style={styles.feedTitle} numberOfLines={1}>
            {vendor?.name}
          </Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite}>
            <Ionicons
              name={favorite ? 'heart' : 'heart-outline'}
              size={20}
              color={favorite ? COLORS.danger : COLORS.subtle}
            />
          </TouchableOpacity>
        </View>

        <View style={styles.feedMetaRow}>
          <View style={styles.ratingPill}>
            <Ionicons name="star" size={11} color="#ffffff" />
            <Text style={styles.ratingPillText}>{getVendorRating(vendor)}</Text>
          </View>
          <Text style={styles.feedMetaText}>{estimateEta(vendor, service)}</Text>
          <Text style={styles.feedMetaDot}>•</Text>
          <Text style={styles.feedMetaText}>
            {getDeliveryFeeLabel(vendor, service)}
          </Text>
        </View>

        <Text style={styles.feedDescription} numberOfLines={2}>
          {getVendorDescription(vendor, service)}
        </Text>

        <View style={styles.feedFooterRow}>
          <Text style={styles.feedAddress} numberOfLines={1}>
            {vendor?.address || 'Near your saved location'}
          </Text>
          <Text style={styles.feedCta}>{ctaLabel}</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function WarehouseFilterChip({ item, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.warehouseFilterChip, active && styles.warehouseFilterChipActive]}
      onPress={onPress}>
      <Ionicons name={item.icon} size={16} color={COLORS.white} />
      <Text style={styles.warehouseFilterText}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function QtyStepper({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyStepper}>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyButton} onPress={onRemove}>
        <Ionicons name="remove" size={14} color={COLORS.text} />
      </TouchableOpacity>
      <Text style={styles.qtyValue}>{qty}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyButton} onPress={onAdd}>
        <Ionicons name="add" size={14} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

function WarehouseDealCard({ item, qty, onAdd, onRemove }) {
  return (
    <View style={styles.dealCard}>
      <View style={styles.dealVisual}>
        <Text style={styles.dealVisualEmoji}>{pickEmoji(item?.name)}</Text>
      </View>
      <Text style={styles.dealBrand} numberOfLines={1}>
        {item?.brand || item?.vendorName || 'Daily essential'}
      </Text>
      <Text style={styles.dealName} numberOfLines={2}>
        {item?.name}
      </Text>
      <Text style={styles.dealPrice}>{money(item?.price)}</Text>
      {qty > 0 ? (
        <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} />
      ) : (
        <TouchableOpacity activeOpacity={0.92} style={styles.dealAddButton} onPress={onAdd}>
          <Text style={styles.dealAddButtonText}>ADD</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

function DineoutSpotlightCard({ item }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.spotlightCard, { backgroundColor: item.accent }]}>
      <Text style={styles.spotlightTitle}>{item.title}</Text>
      <Text style={styles.spotlightSubtitle}>{item.subtitle}</Text>
      <View style={styles.spotlightActionPill}>
        <Text style={styles.spotlightActionText}>{item.action}</Text>
      </View>
    </TouchableOpacity>
  );
}

function SceneMoodChip({ item, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.sceneMoodChip, active && styles.sceneMoodChipActive]}
      onPress={onPress}>
      <Text style={[styles.sceneMoodText, active && styles.sceneMoodTextActive]}>
        {item.label}
      </Text>
    </TouchableOpacity>
  );
}

function SceneFilterChip({ item, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.sceneFilterChip, active && styles.sceneFilterChipActive]}
      onPress={onPress}>
      <Text style={[styles.sceneFilterText, active && styles.sceneFilterTextActive]}>
        {item.label}
      </Text>
    </TouchableOpacity>
  );
}

function SceneEventCard({ item, compact = false }) {
  if (compact) {
    return (
      <TouchableOpacity activeOpacity={0.92} style={styles.sceneGridCard}>
        <View style={[styles.sceneGridVisual, { backgroundColor: item.accent }]}>
          <Ionicons name={item.icon} size={30} color="#ffffff" />
          <Text style={styles.scenePriceTag}>Starts at {money(item.price)}</Text>
        </View>
        <View style={styles.sceneGridBody}>
          <View style={styles.sceneDateBlock}>
            <Text style={styles.sceneDateText}>{item.date}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.sceneCardTitle} numberOfLines={2}>
              {item.title}
            </Text>
            <Text style={styles.sceneCardSubtitle} numberOfLines={2}>
              {item.subtitle}
            </Text>
          </View>
        </View>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.sceneRailCard}>
      <View style={[styles.sceneRailVisual, { backgroundColor: item.accent }]}>
        <Ionicons name={item.icon} size={34} color="#ffffff" />
        <Text style={styles.scenePriceTag}>Starts at {money(item.price)}</Text>
      </View>
      <View style={styles.sceneRailInfo}>
        <View style={styles.sceneDateBlock}>
          <Text style={styles.sceneDateText}>{item.date}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.sceneCardTitle} numberOfLines={2}>
            {item.title}
          </Text>
          <Text style={styles.sceneCardSubtitle} numberOfLines={2}>
            {item.subtitle}
          </Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

export default function HomeScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const [sceneMood, setSceneMood] = useState('week');
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
    favorites,
    toggleFavorite,
    featuredVendors,
    cart,
    cartCount,
    cartTotal,
    addToCart,
    updateQty,
    rememberSearch,
    rememberStore,
  } = useGrabBasket();

  const theme = SERVICE_THEMES[activeService] || SERVICE_THEMES.food;

  const displayVendors = useMemo(() => {
    const source = featuredVendors?.length ? featuredVendors : vendors;
    return source.slice(0, 8);
  }, [featuredVendors, vendors]);

  const displayDeals = useMemo(() => {
    if (homeDeals?.length) return homeDeals;
    return FALLBACK_DEALS;
  }, [homeDeals]);

  const sceneRailItems = useMemo(() => {
    if (sceneMood === 'today') return SCENE_EVENTS.filter((item) => item.bucket === 'today');
    if (sceneMood === 'weekend') return SCENE_EVENTS.filter((item) => item.bucket === 'weekend');
    if (sceneMood === 'next') return SCENE_EVENTS.filter((item) => item.bucket === 'next');
    return SCENE_EVENTS.filter(
      (item) => item.bucket === 'week' || item.bucket === 'weekend'
    );
  }, [sceneMood]);

  const sceneGridItems = useMemo(() => {
    if (sceneFilter === 'all') return SCENE_EVENTS;
    return SCENE_EVENTS.filter((item) => item.bucket === sceneFilter);
  }, [sceneFilter]);

  const sceneGridRows = useMemo(() => chunkItems(sceneGridItems, 2), [sceneGridItems]);

  const handleSubmitSearch = () => {
    rememberSearch(homeSearch);
    loadVendors();
  };

  const handleRefresh = () => {
    loadVendors({ pullToRefresh: true });
  };

  const handleOpenVendor = (vendor) => {
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const handleChangeService = (serviceKey) => {
    setActiveService(serviceKey);
    if (serviceKey !== 'warehouse' && activeShortcut !== 'all') {
      setActiveShortcut('all');
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.canvas }]} edges={['top']}>
      <StatusBar barStyle={theme.statusBar} backgroundColor={theme.hero} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={activeService === 'scenes' ? '#ffffff' : COLORS.success}
          />
        }
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        {activeService === 'eatout' && theme.topStrip ? (
          <View style={styles.topStrip}>
            <Ionicons name="cash-outline" size={18} color="#ffffff" />
            <Text style={styles.topStripText}>{theme.topStrip}</Text>
          </View>
        ) : null}

        <View style={[styles.heroWrap, { backgroundColor: theme.hero }]}>
          <View style={[styles.heroBlobOne, { backgroundColor: theme.heroAccent }]} />
          <View style={[styles.heroBlobTwo, { backgroundColor: theme.heroAccent }]} />

          <View style={styles.heroTopRow}>
            <View style={{ flex: 1, paddingRight: 10 }}>
              <View style={styles.locationRow}>
                <Ionicons name="navigate" size={18} color="#ffffff" />
                <Text style={styles.locationTitle} numberOfLines={1}>
                  {theme.locationTitle}
                </Text>
                <Ionicons name="chevron-down" size={18} color="#ffffff" />
              </View>
              <Text style={styles.locationSubtitle} numberOfLines={1}>
                {theme.locationSubtitle}
              </Text>
            </View>

            <View style={styles.heroRightActions}>
              {activeService === 'food' ? (
                <TouchableOpacity activeOpacity={0.92} style={styles.upgradePill}>
                  <Text style={styles.upgradePillText}>UPGRADE ONE</Text>
                </TouchableOpacity>
              ) : null}
              <TouchableOpacity
                activeOpacity={0.92}
                style={styles.profileButton}
                onPress={() => router.push('/account')}>
                <Ionicons name="person" size={20} color="#111827" />
              </TouchableOpacity>
            </View>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.serviceChipsRow}>
            {TOP_SERVICES.map((item) => (
              <ServiceChip
                key={item.key}
                item={item}
                active={activeService === item.key}
                dark
                onPress={() => handleChangeService(item.key)}
              />
            ))}
          </ScrollView>

          <HeroSearchBar
            placeholder={theme.searchPlaceholder}
            value={homeSearch}
            onChangeText={setHomeSearch}
            onSubmit={handleSubmitSearch}
            actionText={theme.actionText}
            actionIcon={theme.actionIcon}
            dark={activeService === 'scenes'}
          />

          <View
            style={[
              styles.heroCampaignCard,
              activeService === 'warehouse' && styles.heroCampaignCardMart,
              activeService === 'eatout' && styles.heroCampaignCardDine,
              activeService === 'scenes' && styles.heroCampaignCardScenes,
            ]}>
            <Text
              style={[
                styles.heroCampaignLabel,
                activeService === 'warehouse' && styles.heroCampaignLabelMart,
                activeService === 'scenes' && styles.heroCampaignLabelLight,
              ]}>
              {theme.heroLabel}
            </Text>
            <Text
              style={[
                styles.heroCampaignTitle,
                activeService === 'scenes' && styles.heroCampaignTitleLight,
              ]}>
              {theme.heroTitle}
            </Text>
            <Text
              style={[
                styles.heroCampaignSubtitle,
                activeService === 'scenes' && styles.heroCampaignSubtitleLight,
              ]}>
              {theme.heroSubtitle}
            </Text>
          </View>
        </View>

        <View style={[styles.contentWrap, activeService === 'scenes' && styles.contentWrapScenes]}>
          <BasketBanner
            cartCount={cartCount}
            cartTotal={cartTotal}
            onPress={() => router.push('/cart')}
            dark={activeService === 'scenes'}
          />

          {activeService === 'food' ? (
            <>
              <View style={styles.foodHighlightRow}>
                {FOOD_HIGHLIGHTS.map((item) => (
                  <FoodHighlightCard key={item.key} item={item} />
                ))}
              </View>

              <SectionHeader
                title="Top rated near you"
                subtitle="The first rail now feels more visual, branded and easier to browse."
              />

              {vendorsLoading ? (
                <LoadingBlock label="Loading restaurants..." />
              ) : displayVendors.length === 0 ? (
                <EmptyBlock
                  title="No restaurants available"
                  subtitle="Connect your vendors feed or seed more demo stores to fill this rail."
                />
              ) : (
                <ScrollView
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={styles.horizontalRail}>
                  {displayVendors.map((vendor) => (
                    <RailVendorCard
                      key={vendor.id}
                      vendor={vendor}
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onOpen={() => handleOpenVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader
                title="Restaurants to order from"
                subtitle="Cleaner metadata, stronger offer hierarchy and better list density."
              />

              {vendorsLoading ? (
                <LoadingBlock label="Refreshing restaurant feed..." />
              ) : displayVendors.length === 0 ? (
                <EmptyBlock
                  title="Your feed is empty"
                  subtitle="Once your backend vendors load, this becomes the main commerce discovery stack."
                />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorFeedCard
                    key={vendor.id}
                    vendor={vendor}
                    service="food"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onOpen={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'warehouse' ? (
            <>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {WAREHOUSE_FILTERS.map((item) => (
                  <WarehouseFilterChip
                    key={item.key}
                    item={item}
                    active={activeShortcut === item.key}
                    onPress={() => setActiveShortcut(item.key)}
                  />
                ))}
              </ScrollView>

              <View style={styles.martHeroPanel}>
                <View style={styles.bestBrandBadge}>
                  <Text style={styles.bestBrandBadgeText}>BEST BRANDS</Text>
                </View>
                <Text style={styles.martHeroTitle}>Ramzan Mubarak</Text>
                <Text style={styles.martHeroSub}>
                  Sharper festival merchandising, clearer collections and a more Swiggy-like
                  quick-commerce feel.
                </Text>
              </View>

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {WAREHOUSE_BANNERS.map((item) => (
                  <View key={item.key} style={styles.martMiniBanner}>
                    <View style={styles.martMiniBannerIcon}>
                      <Ionicons name={item.icon} size={22} color={COLORS.martHero} />
                    </View>
                    <Text style={styles.martMiniBannerTitle}>{item.title}</Text>
                  </View>
                ))}
              </ScrollView>

              <TouchableOpacity activeOpacity={0.92} style={styles.martInfoStrip}>
                <Text style={styles.martInfoStripText}>
                  Explore 28 varieties of dates sourced from 12 countries.
                </Text>
                <Ionicons name="chevron-forward" size={18} color="#ffffff" />
              </TouchableOpacity>

              <View style={styles.dealsPanel}>
                <View style={styles.dealsPanelHeader}>
                  <Text style={styles.dealsPanelTitle}>₹9 everyday</Text>
                  <Text style={styles.dealsPanelSubtitle}>
                    Impulse-friendly pricing visibility and a quicker add-to-cart loop.
                  </Text>
                </View>

                {homeDealsLoading && homeDeals.length === 0 ? (
                  <LoadingBlock label="Loading quick deals..." />
                ) : (
                  <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.horizontalRail}>
                    {displayDeals.map((item) => (
                      <WarehouseDealCard
                        key={item.id}
                        item={item}
                        qty={cart.items[item.id]?.qty || 0}
                        onAdd={() => addToCart(item)}
                        onRemove={() => updateQty(item, -1)}
                      />
                    ))}
                  </ScrollView>
                )}
              </View>

              <SectionHeader
                title="Quick grocery stores"
                subtitle="This tab should feel like instant commerce, not a generic marketplace feed."
              />

              {vendorsLoading ? (
                <LoadingBlock label="Loading nearby grocery stores..." />
              ) : displayVendors.length === 0 ? (
                <EmptyBlock
                  title="No nearby stores yet"
                  subtitle="Seed warehouse-ready vendors and category imagery to make this tab feel complete."
                />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorFeedCard
                    key={vendor.id}
                    vendor={vendor}
                    service="warehouse"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onOpen={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'eatout' ? (
            <>
              <View style={styles.dineFeatureGrid}>
                <TouchableOpacity activeOpacity={0.92} style={styles.dineBigFeature}>
                  <Text style={styles.dineBigFeatureText}>FLAT{`\n`}50% OFF</Text>
                </TouchableOpacity>
                <View style={styles.dineMiniGrid}>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineMiniFeature}>
                    <Text style={styles.dineMiniFeatureText}>GIRF Hall{`\n`}Of Fame</Text>
                  </TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineMiniFeature}>
                    <Text style={styles.dineMiniFeatureText}>
                      Family-Friendly{`\n`}Spots
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineMiniFeature}>
                    <Text style={styles.dineMiniFeatureText}>Cafes &{`\n`}Quick Bites</Text>
                  </TouchableOpacity>
                  <TouchableOpacity activeOpacity={0.92} style={styles.dineMiniFeature}>
                    <Text style={styles.dineMiniFeatureText}>Exciting{`\n`}Freebies</Text>
                  </TouchableOpacity>
                </View>
              </View>

              <SectionHeader
                title="In the spotlight"
                subtitle="Campaign surfaces and offer-led discovery should feel instantly premium."
                actionLabel="View all"
              />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {DINEOUT_SPOTLIGHT.map((item) => (
                  <DineoutSpotlightCard key={item.key} item={item} />
                ))}
              </ScrollView>

              <Text style={styles.personalPrompt}>Hari, what's on your mind?</Text>
              <View style={styles.quickPromptRow}>
                <TouchableOpacity activeOpacity={0.92} style={styles.quickPromptCard}>
                  <Text style={styles.quickPromptText}>Restaurants near me</Text>
                </TouchableOpacity>
                <TouchableOpacity activeOpacity={0.92} style={styles.quickPromptCard}>
                  <Text style={styles.quickPromptText}>Pre-Book Offers</Text>
                </TouchableOpacity>
              </View>

              <SectionHeader
                title="Popular picks"
                subtitle="Horizontal venue storytelling adds warmth before the denser list below."
                actionLabel="View all"
              />

              {vendorsLoading ? (
                <LoadingBlock label="Loading dineout venues..." />
              ) : displayVendors.length === 0 ? (
                <EmptyBlock
                  title="No venues available"
                  subtitle="Connect dining-specific vendors and artwork to make this section feel complete."
                />
              ) : (
                <ScrollView
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={styles.horizontalRail}>
                  {displayVendors.map((vendor) => (
                    <RailVendorCard
                      key={vendor.id}
                      vendor={vendor}
                      service="eatout"
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onOpen={() => handleOpenVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader
                title="Places with bill offers"
                subtitle="List view should support conversion once discovery has created intent."
              />

              {vendorsLoading ? (
                <LoadingBlock label="Refreshing dineout feed..." />
              ) : displayVendors.length === 0 ? (
                <EmptyBlock
                  title="No dineout list yet"
                  subtitle="The shell is ready; now the experience needs venue imagery, offers and bookings data."
                />
              ) : (
                displayVendors.slice(0, 6).map((vendor) => (
                  <VendorFeedCard
                    key={vendor.id}
                    vendor={vendor}
                    service="eatout"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onOpen={() => handleOpenVendor(vendor)}
                  />
                ))
              )}
            </>
          ) : null}

          {activeService === 'scenes' ? (
            <>
              <SectionHeader
                title="Featured tonight"
                subtitle="Premium event cards should anchor the first fold."
                light
              />

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {sceneRailItems.length > 0 ? (
                  sceneRailItems.map((item) => <SceneEventCard key={item.id} item={item} />)
                ) : (
                  <EmptyBlock
                    title="No events in this mood"
                    subtitle="Add more scenes content to widen the browsing surface."
                    light
                  />
                )}
              </ScrollView>

              <SectionHeader
                title="When is the plan?"
                subtitle="Mood-led discovery brings the Swiggy Scenes feeling much closer."
                light
              />

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {SCENE_MOODS.map((item) => (
                  <SceneMoodChip
                    key={item.key}
                    item={item}
                    active={sceneMood === item.key}
                    onPress={() => setSceneMood(item.key)}
                  />
                ))}
              </ScrollView>

              <SectionHeader
                title="All scenes"
                subtitle={`${sceneGridItems.length} events`}
                light
              />

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {SCENE_FILTERS.map((item) => (
                  <SceneFilterChip
                    key={item.key}
                    item={item}
                    active={sceneFilter === item.key}
                    onPress={() => setSceneFilter(item.key)}
                  />
                ))}
              </ScrollView>

              {sceneGridItems.length === 0 ? (
                <EmptyBlock
                  title="No events found"
                  subtitle="Try another moment or seed more events into this section."
                  light
                />
              ) : (
                sceneGridRows.map((row, rowIndex) => (
                  <View key={`row-${rowIndex}`} style={styles.sceneGridRow}>
                    {row.map((item) => (
                      <View key={item.id} style={styles.sceneGridCell}>
                        <SceneEventCard item={item} compact />
                      </View>
                    ))}
                    {row.length === 1 ? <View style={styles.sceneGridCell} /> : null}
                  </View>
                ))
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

  topStrip: {
    minHeight: 42,
    backgroundColor: '#0f5e35',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  topStripText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
  },

  heroWrap: {
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 24,
    overflow: 'hidden',
  },
  heroBlobOne: {
    position: 'absolute',
    right: -40,
    top: -20,
    width: 220,
    height: 220,
    borderRadius: 110,
    opacity: 0.22,
  },
  heroBlobTwo: {
    position: 'absolute',
    left: -35,
    top: 90,
    width: 160,
    height: 160,
    borderRadius: 80,
    opacity: 0.14,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  locationTitle: {
    flex: 1,
    color: '#ffffff',
    fontSize: 17,
    fontWeight: '900',
  },
  locationSubtitle: {
    marginTop: 4,
    color: 'rgba(255,255,255,0.84)',
    fontSize: 13,
    fontWeight: '500',
  },
  heroRightActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  upgradePill: {
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.16)',
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  upgradePillText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  profileButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },

  serviceChipsRow: {
    paddingTop: 18,
    paddingBottom: 16,
    gap: 10,
  },
  serviceChip: {
    minWidth: 118,
    borderRadius: 24,
    backgroundColor: 'rgba(255,255,255,0.10)',
    paddingHorizontal: 14,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.10)',
  },
  serviceChipDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderColor: 'rgba(255,255,255,0.09)',
  },
  serviceChipActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderColor: 'rgba(255,255,255,0.45)',
  },
  serviceChipDarkActive: {
    backgroundColor: 'rgba(255,255,255,0.14)',
  },
  serviceChipIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceChipBadge: {
    color: '#8ad4ff',
    fontSize: 10,
    fontWeight: '900',
    marginBottom: 2,
  },
  serviceChipText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  serviceChipTextActive: {
    color: '#ffffff',
  },
  serviceChipTextDark: {
    color: '#ffffff',
  },

  searchBarWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  searchBar: {
    flex: 1,
    minHeight: 58,
    borderRadius: 20,
    backgroundColor: '#ffffff',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  searchBarDark: {
    backgroundColor: '#10172a',
    borderWidth: 1,
    borderColor: '#1c2740',
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '600',
    paddingVertical: 0,
  },
  searchInputDark: {
    color: '#ffffff',
  },
  searchAction: {
    minWidth: 58,
    height: 58,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 12,
    flexDirection: 'row',
    gap: 6,
  },
  searchActionDark: {
    backgroundColor: '#162033',
    borderWidth: 1,
    borderColor: '#273452',
  },
  searchActionText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '900',
  },

  heroCampaignCard: {
    marginTop: 18,
    borderRadius: 30,
    backgroundColor: '#ffffff',
    paddingHorizontal: 20,
    paddingVertical: 20,
  },
  heroCampaignCardMart: {
    backgroundColor: '#0b4fb3',
  },
  heroCampaignCardDine: {
    backgroundColor: '#2d0b52',
  },
  heroCampaignCardScenes: {
    backgroundColor: '#0d1426',
    borderWidth: 1,
    borderColor: '#1c2740',
  },
  heroCampaignLabel: {
    color: COLORS.foodHero,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  heroCampaignLabelMart: {
    color: '#dbeafe',
  },
  heroCampaignLabelLight: {
    color: '#9fb0d4',
  },
  heroCampaignTitle: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 30,
    lineHeight: 34,
    fontWeight: '900',
  },
  heroCampaignTitleLight: {
    color: '#ffffff',
  },
  heroCampaignSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '500',
  },
  heroCampaignSubtitleLight: {
    color: '#c7d0df',
  },

  contentWrap: {
    paddingHorizontal: 16,
    paddingTop: 16,
    backgroundColor: COLORS.page,
  },
  contentWrapScenes: {
    backgroundColor: COLORS.scenesHero,
  },

  basketBanner: {
    marginBottom: 16,
    borderRadius: 20,
    backgroundColor: COLORS.successSoft,
    borderWidth: 1,
    borderColor: '#bbf7d0',
    paddingHorizontal: 16,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  basketBannerDark: {
    backgroundColor: '#0d1426',
    borderColor: '#1b2843',
  },
  basketIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#dcfce7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  basketIconWrapDark: {
    backgroundColor: '#162033',
  },
  basketBannerTitle: {
    color: COLORS.success,
    fontSize: 15,
    fontWeight: '900',
  },
  basketBannerTitleDark: {
    color: '#ffffff',
  },
  basketBannerSubtitle: {
    color: COLORS.success,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  basketBannerSubtitleDark: {
    color: '#c7d0df',
  },

  foodHighlightRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 18,
  },
  foodHighlightCard: {
    flex: 1,
    borderRadius: 22,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 16,
    minHeight: 120,
  },
  foodHighlightIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: COLORS.foodCard,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  foodHighlightTitle: {
    color: COLORS.text,
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '900',
  },
  foodHighlightBadge: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
  },

  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
    marginBottom: 14,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
  },
  sectionTitleLight: {
    color: '#ffffff',
  },
  sectionSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '500',
  },
  sectionSubtitleLight: {
    color: '#aab4c8',
  },
  sectionAction: {
    color: COLORS.dineAccent,
    fontSize: 14,
    fontWeight: '900',
  },
  sectionActionLight: {
    color: '#ffffff',
  },

  horizontalRail: {
    gap: 12,
    paddingBottom: 18,
  },

  railVendorCard: {
    width: 168,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
  },
  railVendorCardDine: {
    width: 184,
  },
  railVendorVisual: {
    height: 128,
    borderRadius: 20,
    backgroundColor: COLORS.foodCard,
    marginBottom: 12,
    padding: 12,
    justifyContent: 'space-between',
  },
  railVendorVisualDine: {
    backgroundColor: COLORS.dineCard,
  },
  offerBadge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: '#111827',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  offerBadgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '900',
  },
  favoriteButton: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: 'rgba(0,0,0,0.24)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  visualMonogramWrap: {
    width: 54,
    height: 54,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.74)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  visualMonogram: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  railVendorName: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  railVendorMeta: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  railVendorRatingRow: {
    marginTop: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  railVendorRating: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '900',
  },

  feedCard: {
    marginBottom: 14,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
    flexDirection: 'row',
    gap: 12,
  },
  feedCardDine: {
    borderColor: '#f2e8ff',
  },
  feedVisualCol: {
    width: 112,
  },
  feedVisual: {
    height: 112,
    borderRadius: 20,
    backgroundColor: COLORS.foodCard,
    padding: 10,
    justifyContent: 'space-between',
  },
  feedVisualMart: {
    backgroundColor: COLORS.martCard,
  },
  feedVisualDine: {
    backgroundColor: COLORS.dineCard,
  },
  feedVisualText: {
    alignSelf: 'center',
    marginTop: 18,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
  },
  feedOfferInline: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: '#111827',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  feedOfferInlineText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '900',
  },
  feedContent: {
    flex: 1,
    justifyContent: 'space-between',
  },
  feedTitleRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  feedTitle: {
    flex: 1,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  feedMetaRow: {
    marginTop: 8,
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 6,
  },
  ratingPill: {
    borderRadius: 999,
    backgroundColor: COLORS.success,
    paddingHorizontal: 8,
    paddingVertical: 4,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ratingPillText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  feedMetaText: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  feedMetaDot: {
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '900',
  },
  feedDescription: {
    marginTop: 10,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '500',
  },
  feedFooterRow: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  feedAddress: {
    flex: 1,
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '600',
  },
  feedCta: {
    color: COLORS.dineAccent,
    fontSize: 12,
    fontWeight: '900',
  },

  warehouseFilterChip: {
    borderRadius: 999,
    backgroundColor: '#0c2d72',
    paddingHorizontal: 14,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.14)',
  },
  warehouseFilterChipActive: {
    backgroundColor: '#1d4ed8',
  },
  warehouseFilterText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
  },

  martHeroPanel: {
    borderRadius: 28,
    backgroundColor: '#0b3f98',
    paddingHorizontal: 18,
    paddingVertical: 18,
    marginBottom: 16,
  },
  bestBrandBadge: {
    alignSelf: 'flex-end',
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.18)',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  bestBrandBadgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '900',
  },
  martHeroTitle: {
    marginTop: 6,
    color: '#ffffff',
    fontSize: 30,
    fontWeight: '900',
  },
  martHeroSub: {
    marginTop: 8,
    color: '#dbeafe',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '500',
  },

  martMiniBanner: {
    width: 146,
    borderRadius: 20,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#dbeafe',
    padding: 14,
    minHeight: 112,
  },
  martMiniBannerIcon: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  martMiniBannerTitle: {
    color: COLORS.text,
    fontSize: 14,
    lineHeight: 19,
    fontWeight: '800',
  },

  martInfoStrip: {
    marginBottom: 16,
    borderRadius: 18,
    backgroundColor: '#113b89',
    paddingHorizontal: 16,
    paddingVertical: 15,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  martInfoStripText: {
    flex: 1,
    color: '#ffffff',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '800',
  },

  dealsPanel: {
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#dce8ff',
    paddingVertical: 16,
    marginBottom: 18,
  },
  dealsPanelHeader: {
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  dealsPanelTitle: {
    color: COLORS.martHero,
    fontSize: 24,
    fontWeight: '900',
  },
  dealsPanelSubtitle: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '500',
  },

  dealCard: {
    width: 154,
    borderRadius: 22,
    backgroundColor: '#f8fbff',
    borderWidth: 1,
    borderColor: '#dbeafe',
    padding: 12,
  },
  dealVisual: {
    height: 92,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  dealVisualEmoji: {
    fontSize: 34,
  },
  dealBrand: {
    color: COLORS.subtle,
    fontSize: 11,
    fontWeight: '700',
  },
  dealName: {
    marginTop: 4,
    color: COLORS.text,
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '800',
    minHeight: 38,
  },
  dealPrice: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  dealAddButton: {
    marginTop: 12,
    height: 40,
    borderRadius: 14,
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.martHeroSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dealAddButtonText: {
    color: COLORS.martHeroSoft,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyStepper: {
    marginTop: 12,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
  },
  qtyButton: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyValue: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },

  dineFeatureGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 18,
  },
  dineBigFeature: {
    flex: 1,
    minHeight: 184,
    borderRadius: 28,
    backgroundColor: '#ffe600',
    padding: 18,
    justifyContent: 'flex-end',
  },
  dineBigFeatureText: {
    color: COLORS.text,
    fontSize: 30,
    lineHeight: 32,
    fontWeight: '900',
  },
  dineMiniGrid: {
    flex: 1,
    gap: 10,
  },
  dineMiniFeature: {
    flex: 1,
    borderRadius: 20,
    backgroundColor: '#ffe600',
    paddingHorizontal: 14,
    paddingVertical: 12,
    justifyContent: 'center',
  },
  dineMiniFeatureText: {
    color: COLORS.text,
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '900',
  },

  spotlightCard: {
    width: 248,
    borderRadius: 26,
    padding: 18,
    minHeight: 152,
    justifyContent: 'space-between',
  },
  spotlightTitle: {
    color: '#ffffff',
    fontSize: 24,
    lineHeight: 28,
    fontWeight: '900',
  },
  spotlightSubtitle: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '500',
    marginTop: 8,
  },
  spotlightActionPill: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.18)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  spotlightActionText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
  },

  personalPrompt: {
    marginBottom: 12,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
  },
  quickPromptRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 18,
  },
  quickPromptCard: {
    flex: 1,
    borderRadius: 20,
    backgroundColor: '#fff3ec',
    borderWidth: 1,
    borderColor: '#ffe4d5',
    paddingHorizontal: 14,
    paddingVertical: 16,
  },
  quickPromptText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },

  sceneRailCard: {
    width: 258,
    borderRadius: 24,
    backgroundColor: COLORS.scenesCard,
    borderWidth: 1,
    borderColor: COLORS.scenesBorder,
    overflow: 'hidden',
  },
  sceneRailVisual: {
    height: 172,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
  },
  scenePriceTag: {
    position: 'absolute',
    left: 12,
    bottom: 12,
    borderRadius: 999,
    backgroundColor: '#ff4da0',
    color: '#ffffff',
    paddingHorizontal: 12,
    paddingVertical: 7,
    fontSize: 12,
    fontWeight: '900',
    overflow: 'hidden',
  },
  sceneRailInfo: {
    padding: 14,
    flexDirection: 'row',
    gap: 12,
  },
  sceneDateBlock: {
    width: 54,
    minHeight: 62,
    borderRadius: 16,
    backgroundColor: '#202a3f',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
  },
  sceneDateText: {
    color: '#ffffff',
    fontSize: 12,
    lineHeight: 15,
    textAlign: 'center',
    fontWeight: '900',
  },
  sceneCardTitle: {
    color: '#ffffff',
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
  },
  sceneCardSubtitle: {
    marginTop: 6,
    color: '#aab4c8',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '500',
  },

  sceneMoodChip: {
    width: 104,
    height: 104,
    borderRadius: 30,
    backgroundColor: '#172033',
    borderWidth: 1,
    borderColor: '#25314c',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  sceneMoodChipActive: {
    backgroundColor: '#8b5cf6',
    borderColor: '#c4b5fd',
  },
  sceneMoodText: {
    color: '#ffffff',
    fontSize: 18,
    lineHeight: 20,
    textAlign: 'center',
    fontWeight: '900',
    textTransform: 'uppercase',
  },
  sceneMoodTextActive: {
    color: '#ffffff',
  },

  sceneFilterChip: {
    borderRadius: 999,
    backgroundColor: '#172033',
    borderWidth: 1,
    borderColor: '#25314c',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  sceneFilterChipActive: {
    backgroundColor: '#ffffff',
    borderColor: '#ffffff',
  },
  sceneFilterText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
  },
  sceneFilterTextActive: {
    color: COLORS.text,
  },

  sceneGridRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 12,
  },
  sceneGridCell: {
    flex: 1,
  },
  sceneGridCard: {
    borderRadius: 24,
    backgroundColor: COLORS.scenesCard,
    borderWidth: 1,
    borderColor: COLORS.scenesBorder,
    overflow: 'hidden',
  },
  sceneGridVisual: {
    height: 156,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
  },
  sceneGridBody: {
    padding: 14,
    flexDirection: 'row',
    gap: 12,
  },

  feedbackCard: {
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 20,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 132,
    marginBottom: 16,
  },
  feedbackCardDark: {
    backgroundColor: COLORS.scenesCard,
    borderColor: COLORS.scenesBorder,
  },
  feedbackTitle: {
    marginTop: 12,
    color: COLORS.text,
    fontSize: 16,
    textAlign: 'center',
    fontWeight: '900',
  },
  feedbackTitleDark: {
    color: '#ffffff',
  },
  feedbackSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
  feedbackSubtitleDark: {
    color: '#aab4c8',
  },
});