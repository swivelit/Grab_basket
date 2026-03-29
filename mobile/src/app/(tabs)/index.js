import React, { useMemo, useState } from 'react';
import { Image } from 'expo-image';
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
import { BrandPalette, ConsumerServiceThemes, createShadow } from '@/constants/theme';
import { buildApiUrl } from '../../config';
import { useGrabBasket } from '../../../App';
const PALETTE = BrandPalette;
const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');

const SERVICE_TABS = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline', hint: 'Everyday meals' },
  { key: 'warehouse', label: 'Instamart', icon: 'basket-outline', hint: 'Quick grocery' },
  { key: 'eatout', label: 'Dineout', icon: 'restaurant-outline', hint: 'Tables & offers' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline', hint: 'Events & plans' },
];

const THEMES = ConsumerServiceThemes;

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

function isAbsoluteUrl(value = '') {
  return /^https?:\/\//i.test(String(value || '').trim());
}

function resolveMediaUrl(value = '') {
  const raw = String(value || '').trim();
  if (!raw) return '';
  if (isAbsoluteUrl(raw)) return raw;

  try {
    return buildApiUrl(raw.startsWith('/') ? raw : `/${raw}`);
  } catch {
    // If API base URL isn't available for some reason, fall back to the raw value.
    return raw;
  }
}

function parseCuisineTags(rawValue = '') {
  const raw = String(rawValue || '').trim();
  if (!raw) return [];
  return raw
    .split(/[,|·]/)
    .map((item) => String(item || '').trim())
    .filter(Boolean);
}

function formatRatingLabel(vendor) {
  const ratingText = getVendorRating(vendor);
  const count = Number(vendor?.total_ratings || 0);

  if (ratingText === 'New' || !Number.isFinite(count) || count <= 0) {
    return ratingText;
  }

  if (count >= 1000) {
    return `${ratingText} (${Math.round(count / 100) / 10}k)`;
  }

  return `${ratingText} (${count})`;
}

function formatDistance(vendor) {
  const distance = Number(vendor?.distance_km);
  if (!Number.isFinite(distance) || distance <= 0) return '';
  if (distance < 1) return `${Math.round(distance * 10) / 10} km`;
  return `${Math.round(distance * 10) / 10} km`;
}

function getVendorTrustBadges(vendor) {
  const badges = [];
  const gstin = String(vendor?.gstin || '').trim();
  const supportPhone = String(vendor?.support_phone || '').trim();
  const supportEmail = String(vendor?.support_email || '').trim();

  if (gstin) badges.push('GST verified');
  if (supportPhone || supportEmail) badges.push('Support');

  return badges.slice(0, 2);
}

function buildVendorMetaLine(vendor, service = 'food') {
  const parts = [];

  const rating = formatRatingLabel(vendor);
  if (rating) parts.push(`★ ${rating}`);

  const eta = estimateEta(vendor, service);
  if (eta) parts.push(eta);

  const distance = formatDistance(vendor);
  if (distance) parts.push(distance);

  const priceBucket = String(vendor?.price_bucket || '').trim();
  if (priceBucket) parts.push(priceBucket);

  const cuisines = parseCuisineTags(vendor?.cuisine_tags).slice(0, 2);
  if (cuisines.length) parts.push(cuisines.join(' · '));

  return parts.join(' · ');
}

function buildVendorDetailLine(vendor, service = 'food') {
  const parts = [];

  const cuisines = parseCuisineTags(vendor?.cuisine_tags).slice(0, 3);
  if (cuisines.length) parts.push(cuisines.join(' · '));

  const priceBucket = String(vendor?.price_bucket || '').trim();
  if (priceBucket) parts.push(priceBucket);

  const minOrder = Number(vendor?.min_order_amount || 0);
  if (service !== 'eatout' && service !== 'scenes' && Number.isFinite(minOrder) && minOrder > 0) {
    parts.push(`Min ${money(minOrder)}`);
  }

  const prep = Number(vendor?.avg_prep_time_min || 0);
  if (service === 'eatout' && Number.isFinite(prep) && prep > 0) {
    parts.push(`${Math.max(5, Math.round(prep))} min prep`);
  }

  return parts.join(' · ');
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
      <View style={styles.heroBadgeRow}>
        <View style={[styles.heroBrandPill, activeService === 'scenes' && styles.heroBrandPillDark]}>
          <Image source={BRAND_LOGO} style={styles.heroBrandLogo} contentFit="contain" />
          <Text style={[styles.heroBrandLabel, activeService === 'scenes' && styles.heroBrandLabelDark]}>
            Grab Basket Select
          </Text>
        </View>
        <View style={[styles.heroSignalPill, activeService === 'scenes' && styles.heroSignalPillDark]}>
          <Ionicons
            name="sparkles-outline"
            size={13}
            color={activeService === 'scenes' ? PALETTE.peach200 : PALETTE.peach600}
          />
          <Text
            style={[
              styles.heroSignalText,
              activeService === 'scenes' && styles.heroSignalTextDark,
            ]}>
            Refreshed today
          </Text>
        </View>
      </View>
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
  const coverUri = resolveMediaUrl(vendor?.cover_image_url || vendor?.banner_image_url || vendor?.logo_image_url);
  const logoUri = resolveMediaUrl(vendor?.logo_image_url);
  const trustBadges = getVendorTrustBadges(vendor);

  return (
    <TouchableOpacity
      activeOpacity={0.94}
      onPress={onPress}
      style={[styles.vendorRailCard, dark && styles.vendorRailCardDark]}>
      <View style={[styles.vendorRailVisual, { backgroundColor: tone.bg }]}>
        {coverUri ? (
          <Image
            source={{ uri: coverUri }}
            style={styles.vendorRailImage}
            contentFit="cover"
            transition={180}
          />
        ) : null}

        <View style={[styles.vendorRailOverlay, dark && styles.vendorRailOverlayDark]} />

        <View style={[styles.vendorOfferBadge, { backgroundColor: tone.accent }]}>
          <Text style={styles.vendorOfferBadgeText}>{getOfferLabel(vendor, service)}</Text>
        </View>

        <TouchableOpacity
          activeOpacity={0.9}
          onPress={onToggleFavorite}
          style={[styles.favoriteButton, dark && styles.favoriteButtonDark]}>
          <Ionicons
            name={favorite ? 'heart' : 'heart-outline'}
            size={16}
            color={favorite ? PALETTE.danger : dark ? '#ffffff' : PALETTE.brown}
          />
        </TouchableOpacity>

        <View style={styles.vendorRailBottomRow}>
          <View style={[styles.vendorLogoWrap, { borderColor: tone.accent }]}>
            {logoUri ? (
              <Image
                source={{ uri: logoUri }}
                style={styles.vendorLogoImage}
                contentFit="cover"
                transition={180}
              />
            ) : (
              <Text style={[styles.vendorLogoText, dark && styles.vendorLogoTextDark]}>
                {initials(vendor?.name)}
              </Text>
            )}
          </View>

          <View style={[styles.vendorBadgeRow, styles.vendorBadgeRowEnd]}>
            {trustBadges.map((badge) => (
              <View key={badge} style={[styles.vendorMiniBadge, dark && styles.vendorMiniBadgeDark]}>
                <Text style={[styles.vendorMiniBadgeText, dark && styles.vendorMiniBadgeTextDark]}>
                  {badge}
                </Text>
              </View>
            ))}
          </View>
        </View>
      </View>

      <Text style={[styles.vendorName, dark && styles.vendorNameDark]} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={[styles.vendorMeta, dark && styles.vendorMetaDark]} numberOfLines={1}>
        {buildVendorMetaLine(vendor, service)}
      </Text>

      {service !== 'scenes' ? (
        <Text style={[styles.vendorSubline, dark && styles.vendorSublineDark]} numberOfLines={2}>
          {buildVendorDetailLine(vendor, service) || getVendorNote(vendor, service)}
        </Text>
      ) : (
        <Text style={[styles.vendorSubline, dark && styles.vendorSublineDark]} numberOfLines={2}>
          {getVendorNote(vendor, service)}
        </Text>
      )}

      <Text style={[styles.vendorDeliveryLine, dark && styles.vendorDeliveryLineDark]}>
        {getDeliveryLine(vendor, service)}
      </Text>
    </TouchableOpacity>
  );
}

function VendorListCard({ vendor, service, favorite, onToggleFavorite, onPress }) {
  const tone = getCardTone(vendor?.name);
  const thumbUri = resolveMediaUrl(vendor?.logo_image_url || vendor?.cover_image_url || vendor?.banner_image_url);
  const trustBadges = getVendorTrustBadges(vendor);
  const closed = vendor?.open_now === false || vendor?.is_open === false;

  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.vendorListCard}>
      <View style={[styles.vendorListThumb, { backgroundColor: tone.bg }]}>
        {thumbUri ? (
          <Image
            source={{ uri: thumbUri }}
            style={styles.vendorListThumbImage}
            contentFit="cover"
            transition={180}
          />
        ) : (
          <Text style={styles.vendorListThumbText}>{initials(vendor?.name)}</Text>
        )}

        {closed ? (
          <View style={styles.vendorListThumbOverlay}>
            <Text style={styles.vendorListThumbOverlayText}>Closed</Text>
          </View>
        ) : null}
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
          {buildVendorMetaLine(vendor, service)} · {getDeliveryLine(vendor, service)}
        </Text>

        <Text style={styles.vendorListCopy} numberOfLines={2}>
          {buildVendorDetailLine(vendor, service) || getVendorNote(vendor, service)}
        </Text>

        <View style={styles.vendorListBottomRow}>
          <View style={styles.vendorBadgeRow}>
            <View style={styles.vendorTagPill}>
              <Text style={styles.vendorTagPillText}>{getOfferLabel(vendor, service)}</Text>
            </View>

            {trustBadges.map((badge) => (
              <View key={badge} style={styles.vendorTagPillSoft}>
                <Text style={styles.vendorTagPillSoftText}>{badge}</Text>
              </View>
            ))}
          </View>

          <Text style={[styles.vendorListAction, closed && styles.vendorListActionClosed]}>
            {closed ? 'Closed' : 'Open'}
          </Text>
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
              <View style={styles.brandRow}>
                <View style={styles.brandBadge}>
                  <Image source={BRAND_LOGO} style={styles.brandBadgeLogo} contentFit="contain" />
                </View>
                <View>
                  <Text style={[styles.brandTitle, { color: theme.heroText }]}>Grab Basket</Text>
                  <Text style={[styles.brandCaption, { color: theme.heroSub }]}>
                    Local commerce, refined for faster browsing
                  </Text>
                </View>
              </View>
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
    paddingBottom: 26,
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
    alignItems: 'flex-start',
    marginBottom: 20,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 14,
  },
  brandBadge: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandBadgeLogo: {
    width: 32,
    height: 32,
  },
  brandTitle: {
    fontSize: 19,
    fontWeight: '900',
    marginBottom: 2,
  },
  brandCaption: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
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
    width: 46,
    height: 46,
    borderRadius: 23,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceSwitcherRow: {
    paddingBottom: 8,
    gap: 10,
  },
  serviceChip: {
    width: 164,
    borderRadius: 26,
    paddingHorizontal: 15,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.14)',
    backgroundColor: 'rgba(255,255,255,0.10)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  serviceChipDark: {
    borderColor: 'rgba(255,255,255,0.08)',
    backgroundColor: 'rgba(255,255,255,0.04)',
  },
  serviceChipActive: {
    backgroundColor: PALETTE.primary,
    borderColor: PALETTE.primary,
  },
  serviceChipActiveDark: {
    backgroundColor: PALETTE.peach200,
    borderColor: PALETTE.peach200,
  },
  serviceChipIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.14)',
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
    borderRadius: 24,
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: 'rgba(255,249,241,0.98)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
    ...createShadow(0.1, 18, 8),
  },
  searchBarDark: {
    backgroundColor: '#231A16',
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
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
    backgroundColor: 'rgba(255,246,235,0.98)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchSideActionDark: {
    backgroundColor: '#231A16',
    borderColor: PALETTE.sceneBorder,
  },
  heroBanner: {
    marginTop: 16,
    borderRadius: 30,
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 20,
    overflow: 'hidden',
    ...createShadow(0.12, 24, 12),
  },
  heroBannerLight: {
    backgroundColor: 'rgba(255,250,244,0.96)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
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
  heroBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 12,
  },
  heroBrandPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.88)',
    borderWidth: 1,
    borderColor: 'rgba(20,18,16,0.06)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  heroBrandPillDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderColor: 'rgba(255,255,255,0.12)',
  },
  heroBrandLogo: {
    width: 20,
    height: 20,
  },
  heroBrandLabel: {
    color: PALETTE.text,
    fontSize: 12,
    fontWeight: '900',
  },
  heroBrandLabelDark: {
    color: PALETTE.sceneText,
  },
  heroSignalPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    backgroundColor: PALETTE.primarySoft,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  heroSignalPillDark: {
    backgroundColor: 'rgba(255,255,255,0.10)',
  },
  heroSignalText: {
    color: PALETTE.peach600,
    fontSize: 11,
    fontWeight: '900',
  },
  heroSignalTextDark: {
    color: PALETTE.peach200,
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
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.92)',
    paddingHorizontal: 12,
    paddingVertical: 12,
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
    paddingTop: 18,
    gap: 20,
  },
  bodyDark: {
    backgroundColor: PALETTE.sceneBg,
    paddingBottom: 10,
  },
  basketBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 15,
    borderRadius: 22,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    ...createShadow(0.08, 16, 8),
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
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 11,
    backgroundColor: PALETTE.chip,
    borderWidth: 1,
    borderColor: PALETTE.border,
  },
  filterChipDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  filterChipActive: {
    backgroundColor: PALETTE.primary,
    borderColor: PALETTE.primary,
  },
  filterChipActiveDark: {
    backgroundColor: PALETTE.peach200,
    borderColor: PALETTE.peach200,
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
    height: 152,
    borderRadius: 26,
    padding: 15,
    justifyContent: 'space-between',
    marginBottom: 12,
    overflow: 'hidden',
  },
  vendorRailImage: {
    ...StyleSheet.absoluteFillObject,
    borderRadius: 24,
  },
  vendorRailOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255,255,255,0.10)',
  },
  vendorRailOverlayDark: {
    backgroundColor: 'rgba(0,0,0,0.25)',
  },
  vendorRailBottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  vendorLogoWrap: {
    width: 52,
    height: 52,
    borderRadius: 26,
    borderWidth: 2,
    backgroundColor: 'rgba(255,255,255,0.78)',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  vendorLogoImage: {
    width: '100%',
    height: '100%',
  },
  vendorLogoText: {
    color: PALETTE.brownDark,
    fontSize: 18,
    fontWeight: '900',
  },
  vendorLogoTextDark: {
    color: PALETTE.sceneText,
  },
  vendorBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flexWrap: 'wrap',
  },
  vendorBadgeRowEnd: {
    justifyContent: 'flex-end',
  },
  vendorMiniBadge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.88)',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.05)',
  },
  vendorMiniBadgeDark: {
    backgroundColor: 'rgba(0,0,0,0.35)',
    borderColor: 'rgba(255,255,255,0.16)',
  },
  vendorMiniBadgeText: {
    color: PALETTE.brownDark,
    fontSize: 11,
    fontWeight: '900',
  },
  vendorMiniBadgeTextDark: {
    color: '#ffffff',
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
  favoriteButtonDark: {
    backgroundColor: 'rgba(0,0,0,0.35)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.20)',
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
    padding: 15,
    borderRadius: 26,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    ...createShadow(0.08, 16, 8),
  },
  vendorListThumb: {
    width: 72,
    height: 72,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  vendorListThumbImage: {
    width: '100%',
    height: '100%',
  },
  vendorListThumbOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.42)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorListThumbOverlayText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.4,
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
  vendorTagPillSoft: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: PALETTE.peach100,
  },
  vendorTagPillSoftText: {
    color: PALETTE.brownDark,
    fontSize: 11,
    fontWeight: '900',
  },
  vendorListAction: {
    color: PALETTE.peach600,
    fontSize: 13,
    fontWeight: '900',
  },
  vendorListActionClosed: {
    color: PALETTE.subtle,
  },

  quickTile: {
    borderRadius: 24,
    padding: 14,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    marginBottom: 10,
    ...createShadow(0.05, 12, 6),
  },
  quickTileLarge: {
    paddingVertical: 18,
  },
  quickTileIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: PALETTE.peach50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  quickTileTitle: {
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '900',
    marginBottom: 4,
  },
  quickTileTitleLarge: {
    fontSize: 18,
  },
  quickTileSubtitle: {
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '600',
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
  },
  dealCard: {
    width: 170,
    padding: 15,
    borderRadius: 26,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    ...createShadow(0.08, 16, 8),
  },
  dealVisual: {
    height: 96,
    borderRadius: 20,
    padding: 14,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  dealVisualEmoji: {
    fontSize: 26,
  },
  dealPricePill: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  dealPricePillText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  dealBrand: {
    color: PALETTE.muted,
    fontSize: 11,
    fontWeight: '800',
  },
  dealName: {
    marginTop: 4,
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '900',
    marginBottom: 10,
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    justifyContent: 'center',
  },
  qtyButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    borderWidth: 1,
    borderColor: PALETTE.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: PALETTE.surface,
  },
  qtyText: {
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '900',
    minWidth: 22,
    textAlign: 'center',
  },
  addButton: {
    borderRadius: 18,
    paddingVertical: 11,
    backgroundColor: PALETTE.primarySoft,
    borderWidth: 1,
    borderColor: '#F7C8CF',
    alignItems: 'center',
  },
  addButtonText: {
    color: PALETTE.primary,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.6,
  },
  feedbackCard: {
    borderRadius: 22,
    padding: 16,
    backgroundColor: PALETTE.surface,
    borderWidth: 1,
    borderColor: PALETTE.border,
    alignItems: 'center',
    gap: 10,
    ...createShadow(0.05, 12, 6),
  },
  feedbackCardDark: {
    backgroundColor: '#1D1712',
    borderColor: PALETTE.sceneBorder,
  },
  feedbackTitle: {
    color: PALETTE.text,
    fontSize: 14,
    fontWeight: '900',
    textAlign: 'center',
  },
  feedbackTitleDark: {
    color: PALETTE.sceneText,
  },
  feedbackSubtitle: {
    color: PALETTE.muted,
    fontSize: 12,
    fontWeight: '600',
    textAlign: 'center',
    lineHeight: 17,
  },
  feedbackSubtitleDark: {
    color: PALETTE.sceneMuted,
  },
  sceneGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    paddingBottom: 4,
  },
  sceneCard: {
    width: '47.5%',
    backgroundColor: '#1D1712',
    borderWidth: 1,
    borderColor: PALETTE.sceneBorder,
    borderRadius: 22,
    padding: 12,
  },
  scenePoster: {
    height: 110,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
    overflow: 'hidden',
  },
  sceneDatePill: {
    position: 'absolute',
    top: 10,
    left: 10,
    borderRadius: 14,
    paddingHorizontal: 8,
    paddingVertical: 6,
    backgroundColor: 'rgba(0,0,0,0.45)',
  },
  sceneDateText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
    textAlign: 'center',
    lineHeight: 12,
  },
  sceneTitle: {
    color: PALETTE.sceneText,
    fontSize: 14,
    fontWeight: '900',
    marginBottom: 4,
  },
  sceneSub: {
    color: PALETTE.sceneMuted,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 16,
    marginBottom: 10,
  },
  scenePrice: {
    color: PALETTE.peach300,
    fontSize: 12,
    fontWeight: '900',
  },
});
