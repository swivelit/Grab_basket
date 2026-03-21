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
  page: '#f5f6fa',
  white: '#ffffff',
  text: '#0f172a',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e8ebf1',
  success: '#119b56',
  successSoft: '#e8f8ee',
  warning: '#ff9f0a',
  orange: '#ff6d00',
  danger: '#ef4444',
  black: '#020617',
  shadow: 'rgba(2, 6, 23, 0.08)',
  foodHero: '#5b18cf',
  foodHeroAlt: '#7b36f1',
  martHero: '#0a2f75',
  martHeroAlt: '#1548a0',
  dineHero: '#5d21b5',
  dineHeroAlt: '#7f46e7',
  scenesHero: '#050816',
  scenesHeroAlt: '#182038',
  scenesSurface: '#0d1426',
  scenesBorder: '#1c2740',
  scenesMuted: '#b7c3db',
  yellow: '#ffd639',
  yellowDeep: '#ffcf1b',
  pink: '#ff4ca2',
  green: '#0f6b44',
  gold: '#f3c14a',
  chip: '#f2f4f7',
  chipDark: 'rgba(255,255,255,0.08)',
};

const TOP_SERVICES = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline' },
  { key: 'warehouse', label: 'Instamart', icon: 'basket-outline', badge: '5 mins' },
  { key: 'eatout', label: 'Dineout', icon: 'restaurant-outline' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline' },
];

const SERVICE_THEME = {
  food: {
    bg: COLORS.page,
    hero: COLORS.foodHero,
    heroAlt: COLORS.foodHeroAlt,
    headerTitle: 'Valliachans Place',
    headerSub: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: "Search for 'EatRight'",
    bannerTitle: 'CRAVE',
    bannerSub: 'UP TO 60% OFF & MORE',
    bannerCaption: 'Fresh offers from restaurants near you',
    showVegButton: true,
    statusBar: 'light-content',
  },
  warehouse: {
    bg: '#f7fbff',
    hero: COLORS.martHero,
    heroAlt: COLORS.martHeroAlt,
    headerTitle: '5 mins',
    headerSub: 'To Valliachans Place · 12b, Great Orchard / Tower 1',
    searchPlaceholder: 'Search for Dryfruits',
    bannerTitle: 'RAMZAN MUBARAK',
    bannerSub: 'Groceries in minutes',
    bannerCaption: 'Festival specials, essentials and quick adds',
    showVegButton: false,
    statusBar: 'light-content',
  },
  eatout: {
    bg: '#fbfbfd',
    hero: COLORS.dineHero,
    heroAlt: COLORS.dineHeroAlt,
    headerTitle: 'Valliachans Place',
    headerSub: '12b, Great Orchard / Tower 1, Vidya Nagar',
    searchPlaceholder: 'Search for restaurant, area, vibe...',
    bannerTitle: 'PARTY FULL',
    bannerSub: 'Flat 50% OFF',
    bannerCaption: 'Book tables, unlock bill offers and cashback',
    showVegButton: false,
    topStrip: 'Earn flat 10% Dinecash on every bill payment',
    statusBar: 'light-content',
  },
  scenes: {
    bg: COLORS.scenesHero,
    hero: COLORS.scenesHero,
    heroAlt: COLORS.scenesHeroAlt,
    headerTitle: '12b, Great Orchard / Tower 1, Vidya Nagar',
    headerSub: 'Experiences around you',
    searchPlaceholder: 'Search',
    bannerTitle: 'CRAZZY DEAL',
    bannerSub: 'UP TO 20% OFF',
    bannerCaption: 'on select events',
    showVegButton: false,
    statusBar: 'light-content',
  },
};

const FOOD_TILES = [
  { key: 'deals', title: 'Binge worthy\ndeals', tag: 'UP TO 60% OFF & MORE' },
  { key: 'eatright', title: 'EatRight', tag: 'WIN UP TO ₹300 FREE CASH' },
  { key: 'awards', title: 'Restaurant\nAwards', tag: 'Best rated around you' },
];

const MART_FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxxsaver', label: 'Maxxsaver', icon: 'pricetag-outline' },
  { key: 'festival', label: 'Ramzan', icon: 'moon-outline' },
  { key: 'ready', label: 'Exam Ready', icon: 'flash-outline' },
];

const MART_COLLECTIONS = [
  { key: 'iftar', title: 'Iftar snacks\n& drinks', emoji: '🥤' },
  { key: 'biryani', title: 'Biryani &\nfeasting corner', emoji: '🍛' },
  { key: 'dates', title: 'Dates, dry\nfruits & desserts', emoji: '🌰' },
  { key: 'gifts', title: 'Gift-ready\npicks', emoji: '🎁' },
];

const DINE_TILES = [
  { key: 'big', title: 'FLAT\n50% OFF', large: true },
  { key: 'hall', title: 'GIRF Hall\nOf Fame' },
  { key: 'family', title: 'Family-Friendly\nSpots' },
  { key: 'cafe', title: 'Cafes &\nQuick Bites' },
  { key: 'freebies', title: 'Exciting\nFreebies' },
];

const DINE_SPOTLIGHT = [
  { key: 'awards', title: 'Restaurant Awards', subtitle: 'Vote, share & win up to ₹600!', action: 'Vote Now', color: '#611018' },
  { key: 'flavours', title: 'Flavours by Kochi', subtitle: 'Fresh tables and chef specials', action: 'Preview', color: '#24431c' },
  { key: 'prebook', title: 'Pre-Book Offers', subtitle: 'Reserve early and save more', action: 'Explore', color: '#51257d' },
];

const DINE_PROMPTS = ['Restaurants near me', 'Pre-Book Offers'];

const SCENE_MOODS = [
  { key: 'today', label: "TODAY'S\nVIBE" },
  { key: 'weekend', label: 'WEEKEND\nMOOD' },
  { key: 'week', label: "THIS WEEK'S\nDROPS" },
  { key: 'next', label: 'NEXT WEEKEND\nTEA' },
];

const SCENE_FILTERS = [
  { key: 'all', label: 'All scenes' },
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This Week' },
  { key: 'weekend', label: 'This Weekend' },
  { key: 'next', label: 'Next Weekend' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Rage Room At Break N Chill',
    subtitle: 'Break n Chill | Rage Room | Chittethukara',
    date: '20\nMAR',
    price: 299,
    accent: '#2a0c10',
    bucket: 'today',
    icon: 'hammer-outline',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Throwing Workshop',
    subtitle: 'Soil to Soul Ceramics\nKadavanthra',
    date: '20\nMAR',
    price: 1000,
    accent: '#6c4a34',
    bucket: 'week',
    icon: 'color-palette-outline',
  },
  {
    id: 'scene-3',
    title: 'Kimchi Culture',
    subtitle: 'SKEI Presents\nThe first Korean culture pop-up',
    date: '22\nMAR',
    price: 699,
    accent: '#7b1f28',
    bucket: 'weekend',
    icon: 'restaurant-outline',
  },
  {
    id: 'scene-4',
    title: 'Stand-up Comedy Night',
    subtitle: 'Kakkanad\nTop comics this weekend',
    date: '23\nMAR',
    price: 499,
    accent: '#213a66',
    bucket: 'weekend',
    icon: 'mic-outline',
  },
  {
    id: 'scene-5',
    title: 'Kids Creative Lab',
    subtitle: 'Panampilly Nagar\nFamily-friendly maker session',
    date: '29\nMAR',
    price: 399,
    accent: '#5a2f80',
    bucket: 'next',
    icon: 'happy-outline',
  },
];

const FALLBACK_DEALS = [
  { id: 'deal-1', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Amul Curd', price: 35, brand: 'Daily essential' },
  { id: 'deal-2', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Cadbury Dairy Milk', price: 20, brand: 'Quick sweet bite' },
  { id: 'deal-3', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Kissan Jam', price: 49, brand: 'Breakfast saver' },
  { id: 'deal-4', vendor_id: 'demo-mart', vendorName: 'Instamart Daily', name: 'Classic Chips', price: 20, brand: 'Impulse add-on' },
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
  if (service === 'warehouse') return '5-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 7) * 0.1).toFixed(1);
}

function getOfferLabel(vendor, service = 'food') {
  const foodOffers = ['40% OFF', 'UPTO ₹80', 'ITEMS AT ₹79', 'FREE DELIVERY'];
  const martOffers = ['₹9 DEAL', 'BEST BRANDS', 'DAILY SAVER', 'VALUE PICK'];
  const dineOffers = ['Flat 50% OFF', '10% Cashback', 'Pre-book', 'Bank Offer'];
  const source = service === 'warehouse' ? martOffers : service === 'eatout' ? dineOffers : foodOffers;
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return source[seed % source.length];
}

function getVendorSubtitle(vendor, service = 'food') {
  if (service === 'warehouse') {
    return vendor?.description || 'Fruits, daily essentials, snacks and home needs';
  }
  if (service === 'eatout') {
    return vendor?.description || 'Bill offers, table booking and dine-in deals';
  }
  return vendor?.description || vendor?.address || 'Fast delivery • Great value • Popular near you';
}

function getDeliveryMeta(vendor, service = 'food') {
  if (service === 'eatout') return 'Extra bank offers';
  if (service === 'warehouse') return 'Free delivery above ₹199';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  return '₹29 delivery';
}

function getCardTint(name = '') {
  const seed = String(name || '').length % 5;
  const palette = ['#22113f', '#163b73', '#0f5b44', '#7a1f24', '#69471f'];
  return palette[seed];
}

function pickDealEmoji(name = '') {
  const value = String(name || '').toLowerCase();
  if (/(milk|curd|dairy|paneer)/.test(value)) return '🥛';
  if (/(jam|fruit|berry)/.test(value)) return '🍓';
  if (/(chip|snack)/.test(value)) return '🥔';
  if (/(chocolate|candy)/.test(value)) return '🍫';
  return '🛍️';
}

function chunk(items = [], size = 2) {
  const rows = [];
  for (let i = 0; i < items.length; i += size) rows.push(items.slice(i, i + size));
  return rows;
}

function SectionHeader({ title, actionLabel, onPressAction, light = false, subtitle }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>{subtitle}</Text>
        ) : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.9} onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function LoadingState({ light = false, label = 'Loading...' }) {
  return (
    <View style={[styles.feedbackCard, light && styles.feedbackCardDark]}>
      <ActivityIndicator color={light ? '#ffffff' : COLORS.success} />
      <Text style={[styles.feedbackTitle, light && styles.feedbackTitleDark]}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, subtitle, light = false }) {
  return (
    <View style={[styles.feedbackCard, light && styles.feedbackCardDark]}>
      <Text style={[styles.feedbackTitle, light && styles.feedbackTitleDark]}>{title}</Text>
      <Text style={[styles.feedbackSubtitle, light && styles.feedbackSubtitleDark]}>{subtitle}</Text>
    </View>
  );
}

function ServiceTabs({ activeService, onChange, dark = false }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.serviceTabsRow}>
      {TOP_SERVICES.map((item) => {
        const active = item.key === activeService;
        return (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.92}
            onPress={() => onChange(item.key)}
            style={[
              styles.serviceTab,
              dark && styles.serviceTabDark,
              active && styles.serviceTabActive,
              dark && active && styles.serviceTabDarkActive,
            ]}>
            <View style={[styles.serviceTabIconWrap, active && styles.serviceTabIconWrapActive]}>
              <Ionicons name={item.icon} size={20} color={active ? '#ffffff' : dark ? '#dbe4ff' : COLORS.text} />
            </View>
            <View style={{ flex: 1 }}>
              {item.badge && active ? <Text style={styles.serviceTabBadge}>{item.badge}</Text> : null}
              <Text style={[styles.serviceTabLabel, active && styles.serviceTabLabelActive, dark && !active && styles.serviceTabLabelDark]}>{item.label}</Text>
            </View>
          </TouchableOpacity>
        );
      })}
    </ScrollView>
  );
}

function SearchBar({ placeholder, value, onChangeText, onSubmit, showVegButton = false, dark = false }) {
  return (
    <View style={styles.searchWrap}>
      <View style={[styles.searchBar, dark && styles.searchBarDark]}>
        <Ionicons name="search-outline" size={22} color={dark ? '#b8c4dc' : COLORS.muted} />
        <TextInput
          style={[styles.searchInput, dark && styles.searchInputDark]}
          placeholder={placeholder}
          placeholderTextColor={dark ? '#8ea0bf' : COLORS.subtle}
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          returnKeyType="search"
        />
        <Ionicons name="mic-outline" size={20} color={dark ? '#d6def1' : COLORS.orange} />
      </View>

      <TouchableOpacity activeOpacity={0.92} style={[styles.searchAction, dark && styles.searchActionDark]}>
        {showVegButton ? (
          <>
            <Text style={styles.searchActionText}>VEG</Text>
            <View style={styles.vegDot} />
          </>
        ) : (
          <Ionicons name="bookmark-outline" size={20} color={dark ? '#ffffff' : COLORS.muted} />
        )}
      </TouchableOpacity>
    </View>
  );
}

function BasketBanner({ cartCount, cartTotal, onPress, dark = false }) {
  if (!cartCount) return null;
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.basketBanner, dark && styles.basketBannerDark]}
      onPress={onPress}>
      <View style={[styles.basketIcon, dark && styles.basketIconDark]}>
        <Ionicons name="bag-handle-outline" size={18} color={dark ? '#ffffff' : COLORS.success} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.basketTitle, dark && styles.basketTitleDark]}>Active basket</Text>
        <Text style={[styles.basketSubtitle, dark && styles.basketSubtitleDark]}>
          {cartCount} items · {money(cartTotal)}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={dark ? '#ffffff' : COLORS.success} />
    </TouchableOpacity>
  );
}

function PromoBanner({ activeService, theme }) {
  if (activeService === 'warehouse') {
    return (
      <View style={[styles.heroBanner, styles.heroBannerMart]}>
        <View style={styles.bestBrandPill}>
          <Text style={styles.bestBrandText}>BEST BRANDS</Text>
        </View>
        <Text style={styles.heroBannerTitleMart}>{theme.bannerTitle}</Text>
        <Text style={styles.heroBannerSubMart}>{theme.bannerSub}</Text>
        <Text style={styles.heroBannerCaptionMart}>{theme.bannerCaption}</Text>
      </View>
    );
  }

  if (activeService === 'eatout') {
    return (
      <View style={[styles.heroBanner, styles.heroBannerDine]}>
        <Text style={styles.heroBannerTitleLight}>{theme.bannerTitle}</Text>
        <Text style={styles.heroBannerSubLight}>{theme.bannerSub}</Text>
        <Text style={styles.heroBannerCaptionLight}>{theme.bannerCaption}</Text>
      </View>
    );
  }

  if (activeService === 'scenes') {
    return (
      <View style={[styles.heroBanner, styles.heroBannerScenes]}>
        <Text style={styles.heroBannerTagScenes}>{theme.bannerTitle}</Text>
        <Text style={styles.heroBannerSubScenes}>{theme.bannerSub}</Text>
        <Text style={styles.heroBannerCaptionScenes}>{theme.bannerCaption}</Text>
      </View>
    );
  }

  return (
    <View style={styles.heroBanner}>
      <Text style={styles.heroBannerTitle}>{theme.bannerTitle}</Text>
      <Text style={styles.heroBannerSub}>{theme.bannerSub}</Text>
      <Text style={styles.heroBannerCaption}>{theme.bannerCaption}</Text>
    </View>
  );
}

function FoodTile({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.foodTile}>
      <Text style={styles.foodTileTitle}>{item.title}</Text>
      <Text style={styles.foodTileTag}>{item.tag}</Text>
    </TouchableOpacity>
  );
}

function VendorRailCard({ vendor, service = 'food', favorite, onToggleFavorite, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.vendorRailCard} onPress={onPress}>
      <View style={[styles.vendorRailVisual, { backgroundColor: getCardTint(vendor?.name) }]}>
        <View style={styles.offerBadge}>
          <Text style={styles.offerBadgeText}>{getOfferLabel(vendor, service)}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.9} style={styles.favoriteButton} onPress={onToggleFavorite}>
          <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={favorite ? COLORS.danger : '#ffffff'} />
        </TouchableOpacity>
        <View style={styles.monogramBubble}>
          <Text style={styles.monogramText}>{initials(vendor?.name)}</Text>
        </View>
      </View>
      <Text style={styles.vendorRailName} numberOfLines={1}>{vendor?.name}</Text>
      <Text style={styles.vendorRailMeta} numberOfLines={1}>
        ⭐ {getVendorRating(vendor)} · {estimateEta(vendor, service)}
      </Text>
      <Text style={styles.vendorRailFee} numberOfLines={1}>{getDeliveryMeta(vendor, service)}</Text>
    </TouchableOpacity>
  );
}

function VendorListCard({ vendor, service = 'food', favorite, onToggleFavorite, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.vendorListCard} onPress={onPress}>
      <View style={[styles.vendorListVisual, { backgroundColor: getCardTint(vendor?.name) }]}>
        <View style={styles.offerBadgeLarge}>
          <Text style={styles.offerBadgeLargeText}>{getOfferLabel(vendor, service)}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.9} style={styles.favoriteButton} onPress={onToggleFavorite}>
          <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={favorite ? COLORS.danger : '#ffffff'} />
        </TouchableOpacity>
        <Text style={styles.visualStoreMark}>{initials(vendor?.name)}</Text>
      </View>

      <View style={styles.vendorListBody}>
        <Text style={styles.vendorListTitle} numberOfLines={1}>{vendor?.name}</Text>
        <Text style={styles.vendorListMeta} numberOfLines={1}>
          ⭐ {getVendorRating(vendor)} · {estimateEta(vendor, service)} · {getDeliveryMeta(vendor, service)}
        </Text>
        <Text style={styles.vendorListSubtitle} numberOfLines={2}>{getVendorSubtitle(vendor, service)}</Text>
      </View>
    </TouchableOpacity>
  );
}

function MartFilter({ item, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.martFilter, active && styles.martFilterActive]} onPress={onPress}>
      <Ionicons name={item.icon} size={18} color={active ? '#ffffff' : COLORS.martHero} />
      <Text style={[styles.martFilterText, active && styles.martFilterTextActive]}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function MartCollection({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.martCollection}>
      <Text style={styles.martCollectionEmoji}>{item.emoji}</Text>
      <Text style={styles.martCollectionTitle}>{item.title}</Text>
    </TouchableOpacity>
  );
}

function QtyButton({ qty, onAdd, onRemove }) {
  if (qty > 0) {
    return (
      <View style={styles.qtyWrap}>
        <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onRemove}>
          <Ionicons name="remove" size={16} color={COLORS.white} />
        </TouchableOpacity>
        <Text style={styles.qtyText}>{qty}</Text>
        <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onAdd}>
          <Ionicons name="add" size={16} color={COLORS.white} />
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.addButton} onPress={onAdd}>
      <Text style={styles.addButtonText}>ADD</Text>
    </TouchableOpacity>
  );
}

function DealCard({ item, qty, onAdd, onRemove }) {
  return (
    <View style={styles.dealCard}>
      <View style={styles.dealVisual}>
        <Text style={styles.dealEmoji}>{pickDealEmoji(item?.name)}</Text>
      </View>
      <Text style={styles.dealName} numberOfLines={2}>{item?.name}</Text>
      <Text style={styles.dealBrand} numberOfLines={1}>{item?.brand || item?.vendorName}</Text>
      <Text style={styles.dealPrice}>{money(item?.price)}</Text>
      <QtyButton qty={qty} onAdd={onAdd} onRemove={onRemove} />
    </View>
  );
}

function DineTile({ item }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.dineTile, item.large && styles.dineTileLarge]}>
      <Text style={[styles.dineTileText, item.large && styles.dineTileTextLarge]}>{item.title}</Text>
    </TouchableOpacity>
  );
}

function SpotlightCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.spotlightCard, { backgroundColor: item.color }]}>
      <Text style={styles.spotlightTitle}>{item.title}</Text>
      <Text style={styles.spotlightSubtitle}>{item.subtitle}</Text>
      <View style={styles.spotlightPill}>
        <Text style={styles.spotlightPillText}>{item.action}</Text>
      </View>
    </TouchableOpacity>
  );
}

function PromptChip({ label }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.promptChip}>
      <Text style={styles.promptChipText}>{label}</Text>
    </TouchableOpacity>
  );
}

function SceneMood({ item, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.sceneMood, active && styles.sceneMoodActive]} onPress={onPress}>
      <Text style={[styles.sceneMoodText, active && styles.sceneMoodTextActive]}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function SceneFilter({ item, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={[styles.sceneFilter, active && styles.sceneFilterActive]} onPress={onPress}>
      <Text style={[styles.sceneFilterText, active && styles.sceneFilterTextActive]}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function SceneCard({ item, compact = false }) {
  if (compact) {
    return (
      <TouchableOpacity activeOpacity={0.92} style={styles.sceneCardCompact}>
        <View style={[styles.sceneVisualCompact, { backgroundColor: item.accent }]}>
          <Ionicons name={item.icon} size={28} color="#ffffff" />
          <Text style={styles.scenePricePill}>Starts at {money(item.price)}</Text>
        </View>
        <View style={styles.sceneCardInfoCompact}>
          <View style={styles.sceneDateBox}>
            <Text style={styles.sceneDateText}>{item.date}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.sceneTitle} numberOfLines={2}>{item.title}</Text>
            <Text style={styles.sceneSubtitle} numberOfLines={2}>{item.subtitle}</Text>
          </View>
        </View>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.sceneCardRail}>
      <View style={[styles.sceneVisualRail, { backgroundColor: item.accent }]}>
        <Ionicons name={item.icon} size={34} color="#ffffff" />
        <Text style={styles.scenePricePill}>Starts at {money(item.price)}</Text>
      </View>
      <View style={styles.sceneCardInfoRail}>
        <View style={styles.sceneDateBox}>
          <Text style={styles.sceneDateText}>{item.date}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.sceneTitle} numberOfLines={2}>{item.title}</Text>
          <Text style={styles.sceneSubtitle} numberOfLines={2}>{item.subtitle}</Text>
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

  const theme = SERVICE_THEME[activeService] || SERVICE_THEME.food;

  const displayVendors = useMemo(() => {
    const source = featuredVendors?.length ? featuredVendors : vendors;
    return source.slice(0, 8);
  }, [featuredVendors, vendors]);

  const displayDeals = useMemo(() => (homeDeals?.length ? homeDeals : FALLBACK_DEALS), [homeDeals]);

  const featuredScenes = useMemo(() => {
    if (sceneMood === 'today') return SCENE_EVENTS.filter((item) => item.bucket === 'today');
    if (sceneMood === 'weekend') return SCENE_EVENTS.filter((item) => item.bucket === 'weekend');
    if (sceneMood === 'next') return SCENE_EVENTS.filter((item) => item.bucket === 'next');
    return SCENE_EVENTS.filter((item) => item.bucket === 'week' || item.bucket === 'weekend');
  }, [sceneMood]);

  const sceneGridItems = useMemo(() => {
    if (sceneFilter === 'all') return SCENE_EVENTS;
    return SCENE_EVENTS.filter((item) => item.bucket === sceneFilter);
  }, [sceneFilter]);

  const sceneRows = useMemo(() => chunk(sceneGridItems, 2), [sceneGridItems]);

  const handleOpenVendor = (vendor) => {
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const handleRefresh = () => loadVendors({ pullToRefresh: true });
  const handleSearch = () => {
    rememberSearch(homeSearch);
    loadVendors();
  };

  const handleChangeService = (serviceKey) => {
    setActiveService(serviceKey);
    if (serviceKey !== 'warehouse' && activeShortcut !== 'all') {
      setActiveShortcut('all');
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.bg }]} edges={['top']}>
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
        contentContainerStyle={{ paddingBottom: tabBarHeight + 26 }}>
        {activeService === 'eatout' && theme.topStrip ? (
          <View style={styles.topStrip}>
            <Ionicons name="cash-outline" size={18} color="#ffffff" />
            <Text style={styles.topStripText}>{theme.topStrip}</Text>
          </View>
        ) : null}

        <View style={[styles.hero, { backgroundColor: theme.hero }]}>
          <View style={[styles.heroOrbOne, { backgroundColor: theme.heroAlt }]} />
          <View style={[styles.heroOrbTwo, { backgroundColor: theme.heroAlt }]} />

          <View style={styles.heroHeader}>
            <View style={{ flex: 1, paddingRight: 10 }}>
              <View style={styles.locationRow}>
                <Ionicons name={activeService === 'warehouse' ? 'time-outline' : 'navigate'} size={18} color="#ffffff" />
                <Text style={styles.locationTitle} numberOfLines={1}>{theme.headerTitle}</Text>
                <Ionicons name="chevron-down" size={16} color="#ffffff" />
              </View>
              <Text style={styles.locationSub} numberOfLines={1}>{theme.headerSub}</Text>
            </View>

            <View style={styles.heroActions}>
              {activeService === 'food' ? (
                <TouchableOpacity activeOpacity={0.92} style={styles.onePill}>
                  <Text style={styles.onePillText}>UPGRADE one</Text>
                </TouchableOpacity>
              ) : null}
              <TouchableOpacity activeOpacity={0.92} style={styles.profileBtn} onPress={() => router.push('/account')}>
                <Ionicons name="person" size={20} color={COLORS.text} />
              </TouchableOpacity>
            </View>
          </View>

          <ServiceTabs activeService={activeService} onChange={handleChangeService} dark />

          <SearchBar
            placeholder={theme.searchPlaceholder}
            value={homeSearch}
            onChangeText={setHomeSearch}
            onSubmit={handleSearch}
            showVegButton={theme.showVegButton}
            dark={activeService === 'scenes'}
          />

          <PromoBanner activeService={activeService} theme={theme} />
        </View>

        <View style={[styles.body, activeService === 'scenes' && styles.bodyScenes]}>
          <BasketBanner cartCount={cartCount} cartTotal={cartTotal} onPress={() => router.push('/cart')} dark={activeService === 'scenes'} />

          {activeService === 'food' ? (
            <>
              <View style={styles.foodTilesRow}>
                {FOOD_TILES.map((item) => <FoodTile key={item.key} item={item} />)}
              </View>

              <SectionHeader title="Top rated near you" />

              {vendorsLoading ? (
                <LoadingState label="Loading restaurants..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No restaurants yet" subtitle="Connect your vendors feed to populate this rail." />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {displayVendors.map((vendor) => (
                    <VendorRailCard
                      key={vendor.id}
                      vendor={vendor}
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onPress={() => handleOpenVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Restaurants to order from" actionLabel="View all" />

              {vendorsLoading ? (
                <LoadingState label="Refreshing restaurants..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No restaurants available" subtitle="Seed more stores to make this feed feel alive." />
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
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {MART_FILTERS.map((item) => (
                  <MartFilter
                    key={item.key}
                    item={item}
                    active={activeShortcut === item.key}
                    onPress={() => setActiveShortcut(item.key)}
                  />
                ))}
              </ScrollView>

              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {MART_COLLECTIONS.map((item) => <MartCollection key={item.key} item={item} />)}
              </ScrollView>

              <TouchableOpacity activeOpacity={0.92} style={styles.martInfoStrip}>
                <Text style={styles.martInfoText}>Explore 28 varieties of dates sourced from 12 countries!</Text>
                <Ionicons name="chevron-forward" size={18} color="#ffffff" />
              </TouchableOpacity>

              <View style={styles.dealsPanel}>
                <Text style={styles.dealsPanelTitle}>₹9 everyday</Text>
                <Text style={styles.dealsPanelSubtitle}>Shop for ₹199 to get one item at ₹9</Text>

                {homeDealsLoading && homeDeals.length === 0 ? (
                  <LoadingState label="Loading quick deals..." />
                ) : (
                  <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
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
              </View>

              <SectionHeader title="Quick grocery stores" />

              {vendorsLoading ? (
                <LoadingState label="Loading nearby stores..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No stores available" subtitle="Add grocery-ready vendors to complete this tab." />
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
              <View style={styles.dineGrid}>
                <View style={styles.dineGridLeft}>
                  <DineTile item={DINE_TILES[0]} />
                </View>
                <View style={styles.dineGridRight}>
                  {DINE_TILES.slice(1).map((item) => <DineTile key={item.key} item={item} />)}
                </View>
              </View>

              <SectionHeader title="In the spotlight" />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {DINE_SPOTLIGHT.map((item) => <SpotlightCard key={item.key} item={item} />)}
              </ScrollView>

              <Text style={styles.personalText}>Hari, what's on your mind?</Text>
              <View style={styles.promptRow}>
                {DINE_PROMPTS.map((label) => <PromptChip key={label} label={label} />)}
              </View>

              <SectionHeader title="Popular picks" actionLabel="View all" />

              {vendorsLoading ? (
                <LoadingState label="Loading dineout venues..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No venues available" subtitle="Add more dineout venues and bill-offer data." />
              ) : (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {displayVendors.map((vendor) => (
                    <VendorRailCard
                      key={vendor.id}
                      vendor={vendor}
                      service="eatout"
                      favorite={Boolean(favorites[vendor.id])}
                      onToggleFavorite={() => toggleFavorite(vendor.id)}
                      onPress={() => handleOpenVendor(vendor)}
                    />
                  ))}
                </ScrollView>
              )}

              <SectionHeader title="Places with bill offers" />

              {vendorsLoading ? (
                <LoadingState label="Refreshing dineout feed..." />
              ) : displayVendors.length === 0 ? (
                <EmptyState title="No venues yet" subtitle="Connect dining partners to build this list." />
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
              <SectionHeader title="Featured tonight" light />
              {featuredScenes.length > 0 ? (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                  {featuredScenes.map((item) => <SceneCard key={item.id} item={item} />)}
                </ScrollView>
              ) : (
                <EmptyState light title="No events yet" subtitle="Seed more experiences for this mood." />
              )}

              <SectionHeader title="When is the plan?" light />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {SCENE_MOODS.map((item) => (
                  <SceneMood key={item.key} item={item} active={sceneMood === item.key} onPress={() => setSceneMood(item.key)} />
                ))}
              </ScrollView>

              <SectionHeader title="All scenes" subtitle={`${sceneGridItems.length} events`} light />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
                {SCENE_FILTERS.map((item) => (
                  <SceneFilter key={item.key} item={item} active={sceneFilter === item.key} onPress={() => setSceneFilter(item.key)} />
                ))}
              </ScrollView>

              <View style={styles.sceneFilterFab}>
                <Ionicons name="options-outline" size={22} color={COLORS.scenesHero} />
              </View>

              {sceneGridItems.length === 0 ? (
                <EmptyState light title="No events found" subtitle="Try another time bucket." />
              ) : (
                sceneRows.map((row, index) => (
                  <View key={`scene-row-${index}`} style={styles.sceneGridRow}>
                    {row.map((item) => (
                      <View key={item.id} style={styles.sceneGridCell}>
                        <SceneCard item={item} compact />
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
  safeArea: { flex: 1 },
  topStrip: {
    minHeight: 42,
    backgroundColor: '#0e6b40',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  topStripText: { color: '#ffffff', fontSize: 13, fontWeight: '800' },

  hero: {
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 22,
    overflow: 'hidden',
  },
  heroOrbOne: {
    position: 'absolute',
    right: -46,
    top: -18,
    width: 220,
    height: 220,
    borderRadius: 110,
    opacity: 0.22,
  },
  heroOrbTwo: {
    position: 'absolute',
    left: -36,
    top: 108,
    width: 170,
    height: 170,
    borderRadius: 85,
    opacity: 0.16,
  },
  heroHeader: { flexDirection: 'row', alignItems: 'center' },
  locationRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  locationTitle: { flex: 1, color: '#ffffff', fontSize: 17, fontWeight: '900' },
  locationSub: { marginTop: 4, color: 'rgba(255,255,255,0.84)', fontSize: 13, fontWeight: '500' },
  heroActions: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  onePill: {
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  onePillText: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  profileBtn: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },

  serviceTabsRow: { paddingTop: 18, paddingBottom: 16, gap: 10 },
  serviceTab: {
    minWidth: 118,
    borderRadius: 24,
    paddingHorizontal: 14,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  serviceTabDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderColor: 'rgba(255,255,255,0.08)',
  },
  serviceTabActive: {
    backgroundColor: 'rgba(255,255,255,0.16)',
    borderColor: 'rgba(255,255,255,0.4)',
  },
  serviceTabDarkActive: { backgroundColor: 'rgba(255,255,255,0.14)' },
  serviceTabIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  serviceTabIconWrapActive: { backgroundColor: 'rgba(255,255,255,0.18)' },
  serviceTabBadge: { color: '#9ad8ff', fontSize: 10, fontWeight: '900', marginBottom: 2 },
  serviceTabLabel: { color: COLORS.text, fontSize: 14, fontWeight: '900' },
  serviceTabLabelDark: { color: '#ffffff' },
  serviceTabLabelActive: { color: '#ffffff' },

  searchWrap: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  searchBar: {
    flex: 1,
    minHeight: 58,
    borderRadius: 20,
    backgroundColor: '#ffffff',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  searchBarDark: { backgroundColor: 'rgba(255,255,255,0.1)' },
  searchInput: { flex: 1, color: COLORS.text, fontSize: 16, fontWeight: '600' },
  searchInputDark: { color: '#ffffff' },
  searchAction: {
    minWidth: 58,
    minHeight: 58,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: 10,
  },
  searchActionDark: { backgroundColor: 'rgba(255,255,255,0.12)' },
  searchActionText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },
  vegDot: { width: 10, height: 10, borderRadius: 3, backgroundColor: '#22c55e' },

  heroBanner: {
    marginTop: 16,
    minHeight: 126,
    borderRadius: 24,
    backgroundColor: COLORS.yellow,
    padding: 18,
    justifyContent: 'flex-end',
  },
  heroBannerMart: { backgroundColor: '#123d90' },
  heroBannerDine: { backgroundColor: COLORS.yellowDeep },
  heroBannerScenes: {
    backgroundColor: '#0d0f18',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.06)',
  },
  heroBannerTitle: { color: '#6d1a7a', fontSize: 34, fontWeight: '900', lineHeight: 34 },
  heroBannerSub: { marginTop: 4, color: COLORS.text, fontSize: 24, fontWeight: '900', lineHeight: 26 },
  heroBannerCaption: { marginTop: 6, color: '#4b5565', fontSize: 14, fontWeight: '700' },
  heroBannerTitleLight: { color: COLORS.text, fontSize: 36, fontWeight: '900', lineHeight: 36 },
  heroBannerSubLight: { marginTop: 4, color: COLORS.text, fontSize: 24, fontWeight: '900' },
  heroBannerCaptionLight: { marginTop: 6, color: '#4b5565', fontSize: 14, fontWeight: '700' },
  bestBrandPill: {
    position: 'absolute',
    right: 14,
    top: 14,
    borderRadius: 999,
    backgroundColor: '#ffffff',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  bestBrandText: { color: '#10377d', fontSize: 10, fontWeight: '900' },
  heroBannerTitleMart: { color: '#ffffff', fontSize: 30, fontWeight: '900' },
  heroBannerSubMart: { marginTop: 4, color: '#ffffff', fontSize: 22, fontWeight: '900' },
  heroBannerCaptionMart: { marginTop: 6, color: '#d4e2ff', fontSize: 14, fontWeight: '700' },
  heroBannerTagScenes: { color: '#ff7cab', fontSize: 34, fontWeight: '900', lineHeight: 34 },
  heroBannerSubScenes: { marginTop: 4, color: '#ffffff', fontSize: 26, fontWeight: '900' },
  heroBannerCaptionScenes: { marginTop: 6, color: '#d1d8e8', fontSize: 14, fontWeight: '700' },

  body: { paddingHorizontal: 16, paddingTop: 14 },
  bodyScenes: { backgroundColor: COLORS.scenesHero },
  horizontalRail: { gap: 12, paddingBottom: 4 },

  basketBanner: {
    marginBottom: 16,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 13,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  basketBannerDark: { backgroundColor: COLORS.scenesSurface, borderColor: COLORS.scenesBorder },
  basketIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.successSoft,
  },
  basketIconDark: { backgroundColor: 'rgba(255,255,255,0.08)' },
  basketTitle: { color: COLORS.text, fontSize: 15, fontWeight: '900' },
  basketTitleDark: { color: '#ffffff' },
  basketSubtitle: { marginTop: 2, color: COLORS.muted, fontSize: 13, fontWeight: '600' },
  basketSubtitleDark: { color: '#cad3e6' },

  sectionHeader: { marginBottom: 12, flexDirection: 'row', alignItems: 'flex-end', gap: 10 },
  sectionTitle: { color: COLORS.text, fontSize: 26, fontWeight: '900' },
  sectionTitleLight: { color: '#ffffff' },
  sectionSubtitle: { marginTop: 4, color: COLORS.muted, fontSize: 13, fontWeight: '600' },
  sectionSubtitleLight: { color: COLORS.scenesMuted },
  sectionAction: { color: COLORS.orange, fontSize: 16, fontWeight: '900' },
  sectionActionLight: { color: '#ffffff' },

  feedbackCard: {
    borderRadius: 20,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 26,
    paddingHorizontal: 18,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginBottom: 16,
  },
  feedbackCardDark: { backgroundColor: COLORS.scenesSurface, borderColor: COLORS.scenesBorder },
  feedbackTitle: { color: COLORS.text, fontSize: 16, fontWeight: '800' },
  feedbackTitleDark: { color: '#ffffff' },
  feedbackSubtitle: { color: COLORS.muted, fontSize: 14, fontWeight: '500', textAlign: 'center' },
  feedbackSubtitleDark: { color: COLORS.scenesMuted },

  foodTilesRow: { flexDirection: 'row', gap: 10, marginBottom: 22 },
  foodTile: {
    flex: 1,
    borderRadius: 22,
    backgroundColor: COLORS.yellow,
    paddingHorizontal: 14,
    paddingVertical: 18,
    minHeight: 136,
    justifyContent: 'space-between',
  },
  foodTileTitle: { color: '#6d1f7f', fontSize: 20, fontWeight: '900', lineHeight: 24 },
  foodTileTag: { color: COLORS.text, fontSize: 12, fontWeight: '800' },

  vendorRailCard: { width: 176, marginBottom: 18 },
  vendorRailVisual: {
    height: 154,
    borderRadius: 22,
    overflow: 'hidden',
    padding: 12,
    justifyContent: 'space-between',
  },
  offerBadge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.18)',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  offerBadgeText: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  favoriteButton: {
    position: 'absolute',
    right: 12,
    top: 12,
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(0,0,0,0.22)',
  },
  monogramBubble: {
    width: 58,
    height: 58,
    borderRadius: 29,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    backgroundColor: 'rgba(255,255,255,0.18)',
  },
  monogramText: { color: '#ffffff', fontSize: 22, fontWeight: '900' },
  vendorRailName: { marginTop: 10, color: COLORS.text, fontSize: 16, fontWeight: '900' },
  vendorRailMeta: { marginTop: 4, color: COLORS.text, fontSize: 13, fontWeight: '700' },
  vendorRailFee: { marginTop: 3, color: COLORS.muted, fontSize: 12, fontWeight: '700' },

  vendorListCard: {
    marginBottom: 14,
    borderRadius: 22,
    overflow: 'hidden',
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  vendorListVisual: { height: 186, padding: 14, justifyContent: 'space-between' },
  offerBadgeLarge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.14)',
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  offerBadgeLargeText: { color: '#ffffff', fontSize: 12, fontWeight: '900' },
  visualStoreMark: { color: '#ffffff', fontSize: 42, fontWeight: '900', opacity: 0.9 },
  vendorListBody: { paddingHorizontal: 14, paddingVertical: 14 },
  vendorListTitle: { color: COLORS.text, fontSize: 18, fontWeight: '900' },
  vendorListMeta: { marginTop: 5, color: COLORS.text, fontSize: 13, fontWeight: '700' },
  vendorListSubtitle: { marginTop: 6, color: COLORS.muted, fontSize: 13, fontWeight: '600', lineHeight: 18 },

  martFilter: {
    minWidth: 94,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#d8e4f7',
    paddingHorizontal: 14,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  martFilterActive: { backgroundColor: COLORS.martHero, borderColor: COLORS.martHero },
  martFilterText: { color: COLORS.martHero, fontSize: 12, fontWeight: '800' },
  martFilterTextActive: { color: '#ffffff' },
  martCollection: {
    width: 136,
    borderRadius: 20,
    backgroundColor: '#1a4ba7',
    paddingHorizontal: 14,
    paddingVertical: 16,
    minHeight: 118,
    justifyContent: 'space-between',
  },
  martCollectionEmoji: { fontSize: 30 },
  martCollectionTitle: { color: '#ffffff', fontSize: 16, fontWeight: '800', lineHeight: 20 },
  martInfoStrip: {
    marginTop: 10,
    marginBottom: 18,
    borderRadius: 16,
    backgroundColor: '#0f3b8d',
    paddingHorizontal: 16,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  martInfoText: { flex: 1, color: '#ffffff', fontSize: 14, fontWeight: '800' },
  dealsPanel: {
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#dde7f7',
    paddingTop: 18,
    paddingBottom: 14,
    marginBottom: 22,
  },
  dealsPanelTitle: { paddingHorizontal: 16, color: COLORS.martHero, fontSize: 26, fontWeight: '900' },
  dealsPanelSubtitle: { paddingHorizontal: 16, marginTop: 4, color: COLORS.muted, fontSize: 13, fontWeight: '600', marginBottom: 14 },
  dealCard: {
    width: 160,
    borderRadius: 20,
    backgroundColor: '#f7fbff',
    borderWidth: 1,
    borderColor: '#e3edf9',
    padding: 12,
  },
  dealVisual: {
    height: 100,
    borderRadius: 16,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  dealEmoji: { fontSize: 42 },
  dealName: { color: COLORS.text, fontSize: 14, fontWeight: '800', lineHeight: 18, minHeight: 36 },
  dealBrand: { marginTop: 4, color: COLORS.muted, fontSize: 12, fontWeight: '600' },
  dealPrice: { marginTop: 8, color: COLORS.text, fontSize: 16, fontWeight: '900' },
  addButton: {
    marginTop: 10,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.martHero,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButtonText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },
  qtyWrap: {
    marginTop: 10,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.martHero,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 6,
  },
  qtyAction: { width: 24, height: 24, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  qtyText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },

  dineGrid: { flexDirection: 'row', gap: 10, marginBottom: 22 },
  dineGridLeft: { width: '38%' },
  dineGridRight: { flex: 1, flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  dineTile: {
    width: '47%',
    minHeight: 90,
    borderRadius: 20,
    backgroundColor: COLORS.yellow,
    padding: 14,
    justifyContent: 'flex-end',
  },
  dineTileLarge: { width: '100%', minHeight: 190 },
  dineTileText: { color: COLORS.text, fontSize: 17, fontWeight: '900', lineHeight: 20 },
  dineTileTextLarge: { fontSize: 34, lineHeight: 36 },
  spotlightCard: { width: 260, borderRadius: 24, padding: 18, minHeight: 160, justifyContent: 'space-between', marginBottom: 16 },
  spotlightTitle: { color: '#ffffff', fontSize: 28, fontWeight: '900', lineHeight: 30 },
  spotlightSubtitle: { marginTop: 8, color: 'rgba(255,255,255,0.92)', fontSize: 15, fontWeight: '700', lineHeight: 20 },
  spotlightPill: {
    alignSelf: 'flex-start',
    borderRadius: 14,
    backgroundColor: '#fff5e9',
    paddingHorizontal: 14,
    paddingVertical: 9,
  },
  spotlightPillText: { color: COLORS.text, fontSize: 13, fontWeight: '900' },
  personalText: { marginBottom: 12, color: COLORS.text, fontSize: 20, fontWeight: '900' },
  promptRow: { flexDirection: 'row', gap: 10, marginBottom: 22 },
  promptChip: {
    flex: 1,
    borderRadius: 18,
    backgroundColor: '#f8ece4',
    paddingHorizontal: 14,
    paddingVertical: 15,
  },
  promptChipText: { color: COLORS.text, fontSize: 15, fontWeight: '800' },

  sceneMood: {
    width: 118,
    minHeight: 112,
    borderRadius: 30,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    paddingHorizontal: 16,
    paddingVertical: 18,
    justifyContent: 'center',
  },
  sceneMoodActive: { backgroundColor: 'rgba(255,255,255,0.14)', borderColor: 'rgba(255,255,255,0.18)' },
  sceneMoodText: { color: '#ffffff', fontSize: 22, fontWeight: '900', lineHeight: 24 },
  sceneMoodTextActive: { color: '#ffffff' },
  sceneFilter: {
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  sceneFilterActive: { backgroundColor: '#ffffff' },
  sceneFilterText: { color: '#ffffff', fontSize: 13, fontWeight: '800' },
  sceneFilterTextActive: { color: COLORS.scenesHero },
  sceneFilterFab: {
    alignSelf: 'center',
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 8,
  },

  sceneCardRail: { width: 276, borderRadius: 24, overflow: 'hidden', backgroundColor: COLORS.scenesSurface, borderWidth: 1, borderColor: COLORS.scenesBorder, marginBottom: 12 },
  sceneVisualRail: { height: 186, padding: 16, justifyContent: 'space-between' },
  sceneCardInfoRail: { flexDirection: 'row', gap: 12, padding: 14 },
  sceneCardCompact: { borderRadius: 24, overflow: 'hidden', backgroundColor: COLORS.scenesSurface, borderWidth: 1, borderColor: COLORS.scenesBorder },
  sceneVisualCompact: { height: 176, padding: 14, justifyContent: 'space-between' },
  sceneCardInfoCompact: { flexDirection: 'row', gap: 10, padding: 12 },
  sceneDateBox: {
    width: 52,
    borderRadius: 14,
    backgroundColor: '#121c31',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    alignSelf: 'flex-start',
  },
  sceneDateText: { color: '#ffffff', fontSize: 15, fontWeight: '900', textAlign: 'center' },
  sceneTitle: { color: '#ffffff', fontSize: 18, fontWeight: '900', lineHeight: 22 },
  sceneSubtitle: { marginTop: 4, color: COLORS.scenesMuted, fontSize: 13, fontWeight: '600', lineHeight: 18 },
  scenePricePill: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: COLORS.pink,
    color: '#ffffff',
    paddingHorizontal: 12,
    paddingVertical: 7,
    overflow: 'hidden',
    fontSize: 12,
    fontWeight: '900',
  },
  sceneGridRow: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  sceneGridCell: { flex: 1 },
});