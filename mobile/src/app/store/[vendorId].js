import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Image } from 'expo-image';
import {
  ActivityIndicator,
  ScrollView,
  Share,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { BrandPalette, StoreServiceThemes, createShadow } from '@/constants/theme';
import { useGrabBasket } from '../../../App';
import InlineNoticeCard from '../../components/inline-notice-card';
import { APP_ENV } from '../../config';
import { captureEvent } from '../../lib/telemetry';
import { FEATURE_FLAGS } from '../../constants/feature-flags';
import { ANALYTICS_EVENTS, ANALYTICS_TAXONOMY_VERSION } from '../../constants/analytics-taxonomy';
const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');

const COLORS = {
  ...BrandPalette,
  page: BrandPalette.page,
  card: BrandPalette.surface,
  text: BrandPalette.text,
  muted: BrandPalette.textMuted,
  subtle: BrandPalette.subtle,
  border: BrandPalette.border,
  success: BrandPalette.success,
  successSoft: BrandPalette.successSoft,
  orange: BrandPalette.primary,
  orangeSoft: BrandPalette.primarySoft,
  yellow: BrandPalette.peach200,
  blue: BrandPalette.inkSoft,
  blueSoft: '#F7EDE5',
  purple: BrandPalette.primary,
  dark: BrandPalette.sceneBg,
  darkCard: BrandPalette.sceneSurface,
  darkBorder: BrandPalette.sceneBorder,
  darkMuted: BrandPalette.sceneMuted,
};
const SERVICE_THEME = StoreServiceThemes;

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

function findVendorById(list = [], id) {
  return list.find((item) => String(item.id) === String(id)) || null;
}

function estimateEta(vendor, service = 'food') {
  const eta = Number(vendor?.estimated_delivery_time_min);

  if (service === 'eatout') {
    if (Number.isFinite(eta) && eta > 0) return `Table in ${Math.max(10, eta)} mins`;
    return 'Reserve now';
  }

  if (service === 'scenes') return 'Instant confirmation';

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

function getDeliveryLabel(vendor, service = 'food') {
  if (service === 'eatout') return vendor?.open_now === false ? 'Closed for reservations' : 'Table booking available';
  if (service === 'scenes') return 'Instant confirmation';
  if (vendor?.can_deliver === false) return 'Outside delivery radius';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return '₹19 delivery';
  return service === 'warehouse' ? 'Fast basket delivery' : '₹29 delivery';
}

function getHeroSubtitle(vendor, service = 'food') {
  if (service === 'warehouse') {
    return vendor?.description || 'Quick grocery delivery, daily essentials and faster reorders.';
  }
  if (service === 'eatout') {
    return vendor?.description || 'Bill offers, table booking and dine-in discovery.';
  }
  if (service === 'scenes') {
    return vendor?.description || 'Events, experiences and bookable plans around you.';
  }
  return vendor?.description || 'Popular local store with fast delivery and strong value.';
}

function getVendorImage(vendor) {
  const candidate =
    vendor?.cover_image_url || vendor?.banner_image_url || vendor?.logo_image_url || '';
  return String(candidate || '').trim();
}

function getTrustSignals(vendor) {
  const signals = [];
  const ratingCount = Number(vendor?.total_ratings || 0);

  if (ratingCount >= 100) signals.push(`${ratingCount}+ verified ratings`);
  if (String(vendor?.gstin || '').trim()) signals.push('GST verified merchant');
  if (String(vendor?.support_phone || '').trim() || String(vendor?.support_email || '').trim()) {
    signals.push('Help & refund support available');
  }
  if (vendor?.can_deliver !== false) signals.push('Reliable fulfillment zone');

  return signals.slice(0, 3);
}

function getReviewHighlights(vendor) {
  const base = [];
  const rating = Number(vendor?.avg_rating || 0);
  const ratingCount = Number(vendor?.total_ratings || 0);

  if (rating >= 4.5) base.push('Customers praise consistent quality and packing.');
  if (ratingCount >= 200) base.push('Large repeat-customer base with strong trust signals.');
  if (vendor?.is_busy) base.push('High demand right now — popular pick this hour.');
  if (vendor?.open_now === false) base.push('Currently closed, but reviews are visible for confidence.');
  if (base.length === 0) base.push('Be first to review after your order to help future customers.');

  return base.slice(0, 2);
}

function getCouponMessage(vendor, service = 'food') {
  if (service === 'eatout') return 'Use code TABLE25 for up to 25% off on bill payments.';
  if (service === 'warehouse') return 'Use code MARTSAVE for free delivery on eligible baskets.';
  if (service === 'scenes') return 'Use code SCENE20 to unlock early-bird passes.';
  if (Number(vendor?.total_ratings || 0) >= 150) return 'Use code TRUST30 for up to ₹120 off.';
  return 'Use code FIRSTTRUST for a welcome discount on this store.';
}

function getProductBadge(product, service = 'food') {
  const explicitBadge = String(product?.badge_text || '').trim();
  if (explicitBadge) return explicitBadge;

  const name = String(product?.name || '').toLowerCase();
  const price = Number(product?.price || 0);
  const originalPrice = Number(product?.original_price || 0);
  const rating = Number(product?.avg_rating || 0);

  if (originalPrice > price && price > 0) return `Save ₹${Math.round(originalPrice - price)}`;
  if (product?.is_featured) return 'Featured';
  if (rating >= 4.5) return 'Top rated';

  if (service === 'eatout') {
    if (/(combo|platter|feast)/.test(name)) return 'Best for sharing';
    if (price >= 300) return 'Premium pick';
    if (price <= 150) return 'Great value';
    return 'Popular choice';
  }

  if (service === 'warehouse') {
    if (price <= 30) return 'Everyday value';
    if (/(milk|bread|egg|curd)/.test(name)) return 'Daily essential';
    if (/(snack|chips|chocolate)/.test(name)) return 'Quick add-on';
    return 'Smart pick';
  }

  if (service === 'scenes') {
    if (/(vip|premium)/.test(name)) return 'Premium access';
    if (price <= 500) return 'Quick plan';
    return 'Trending pass';
  }

  if (price <= 60) return 'Value buy';
  if (/(combo|meal|biryani|burger)/.test(name)) return 'Bestseller';
  return 'Popular';
}

function getVendorPreferenceBoost(product, profile = {}) {
  if (!profile || !product) return 0;

  const name = String(product?.name || '').toLowerCase();
  const category = String(product?.category_name || product?.category || '').toLowerCase();
  const preferredTerms = Array.isArray(profile?.topTerms) ? profile.topTerms : [];
  const preferredCategories = Array.isArray(profile?.topCategories) ? profile.topCategories : [];

  let boost = 0;

  if (preferredCategories.some((term) => category.includes(term))) boost += 30;
  if (preferredTerms.some((term) => name.includes(term))) boost += 20;
  if (profile?.vendorRepeatScore > 0) boost += Math.min(40, profile.vendorRepeatScore * 6);

  return boost;
}

function sortRecommended(products = [], profile = null) {
  return [...products].sort((a, b) => {
    const score = (item) => {
      const discount = Math.max(0, Number(item?.original_price || 0) - Number(item?.price || 0));
      return (
        (item?.is_available ? 1000 : 0) +
        (item?.is_featured ? 300 : 0) +
        Math.round(Number(item?.avg_rating || 0) * 40) +
        Math.min(discount, 200) +
        Math.max(0, 100 - Number(item?.price || 0)) +
        (FEATURE_FLAGS.personalizationRanking ? getVendorPreferenceBoost(item, profile) : 0)
      );
    };

    return score(b) - score(a) || String(a?.name || '').localeCompare(String(b?.name || ''));
  });
}

function deriveCategory(product, service = 'food') {
  const explicit = String(product?.category_name || product?.category || '').trim();
  if (explicit) return explicit;

  const name = String(product?.name || '').toLowerCase();

  if (service === 'warehouse') {
    if (/(fruit|vegetable|greens|banana|apple|tomato|onion|potato)/.test(name)) return 'Fresh';
    if (/(milk|bread|egg|curd|paneer|rice|atta|oil)/.test(name)) return 'Essentials';
    if (/(chips|snack|chocolate|cookie|cola|juice)/.test(name)) return 'Snacks';
    return 'Store picks';
  }

  if (service === 'eatout') {
    if (/(starter|soup|appetizer)/.test(name)) return 'Starters';
    if (/(dessert|cake|ice cream|sweet)/.test(name)) return 'Desserts';
    if (/(drink|juice|shake|coffee|tea)/.test(name)) return 'Drinks';
    return 'Menu';
  }

  if (service === 'scenes') {
    if (/(vip|premium)/.test(name)) return 'Premium';
    if (/(family|group|couple)/.test(name)) return 'Group plans';
    return 'Experiences';
  }

  if (/(biryani|rice|noodle|pasta)/.test(name)) return 'Mains';
  if (/(burger|sandwich|wrap|pizza)/.test(name)) return 'Fast food';
  if (/(drink|juice|shake|coffee|tea)/.test(name)) return 'Beverages';
  if (/(dessert|cake|ice cream|sweet)/.test(name)) return 'Desserts';
  return 'Recommended';
}

function pickEmoji(name = '', service = 'food') {
  const value = String(name || '').toLowerCase();

  if (service === 'warehouse') {
    if (/(milk|curd|paneer)/.test(value)) return '🥛';
    if (/(bread|toast|bun)/.test(value)) return '🍞';
    if (/(fruit|apple|banana|orange)/.test(value)) return '🍎';
    if (/(vegetable|tomato|potato|onion)/.test(value)) return '🥬';
    if (/(chips|snack|cookie|chocolate)/.test(value)) return '🍪';
    return '🛒';
  }

  if (service === 'eatout') {
    if (/(dessert|cake|ice cream|sweet)/.test(value)) return '🍰';
    if (/(drink|juice|shake|coffee|tea)/.test(value)) return '🥤';
    if (/(pizza)/.test(value)) return '🍕';
    if (/(burger|sandwich|wrap)/.test(value)) return '🍔';
    return '🍽️';
  }

  if (service === 'scenes') {
    if (/(vip|premium)/.test(value)) return '🎟️';
    if (/(music|concert|dj)/.test(value)) return '🎵';
    if (/(movie|cinema)/.test(value)) return '🎬';
    return '✨';
  }

  if (/(biryani|rice)/.test(value)) return '🍛';
  if (/(pizza)/.test(value)) return '🍕';
  if (/(burger|sandwich|wrap)/.test(value)) return '🍔';
  if (/(dessert|cake|ice cream|sweet)/.test(value)) return '🍰';
  if (/(drink|juice|shake|coffee|tea)/.test(value)) return '🥤';
  return '🍽️';
}

function buildPersonalizationProfile(orderHistory = [], vendor) {
  const orders = Array.isArray(orderHistory) ? orderHistory : [];
  const vendorId = String(vendor?.id || '');
  const vendorOrders = orders.filter(
    (order) => String(order?.vendor_id || order?.vendorId || '') === vendorId
  );

  const topTerms = [];
  const topCategories = [];

  vendorOrders.forEach((order) => {
    const items = Array.isArray(order?.items) ? order.items : [];
    items.forEach((item) => {
      const name = String(item?.name || item?.name_snapshot || '').toLowerCase();
      const category = String(item?.category || item?.category_name || '').toLowerCase();
      if (name) topTerms.push(...name.split(/\s+/).slice(0, 2));
      if (category) topCategories.push(category);
    });
  });

  return {
    vendorRepeatScore: vendorOrders.length,
    topTerms: Array.from(new Set(topTerms)).slice(0, 6),
    topCategories: Array.from(new Set(topCategories)).slice(0, 4),
  };
}

function getLoyaltyCopy(profile) {
  if (!FEATURE_FLAGS.loyaltyMembership) return '';
  if ((profile?.vendorRepeatScore || 0) >= 5) return 'GB Plus Gold: extra perks unlocked for this merchant.';
  if ((profile?.vendorRepeatScore || 0) >= 2) return 'GB Plus Silver: add one more order to unlock larger rewards.';
  return 'Join GB Plus for member-only pricing, support priority, and reorder cashback.';
}

function LoadingState({ dark = false, label = 'Loading...' }) {
  return (
    <View style={[styles.feedbackCard, dark && styles.feedbackCardDark]}>
      <ActivityIndicator color={dark ? '#ffffff' : COLORS.success} />
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

function InfoPill({ text, theme }) {
  return (
    <View style={[styles.infoPill, { backgroundColor: theme.pillBg }]}>
      <Text style={[styles.infoPillText, { color: theme.pillText }]}>{text}</Text>
    </View>
  );
}

function QtyControl({ qty, onAdd, onRemove, theme, dark = false, bookMode = false }) {
  if (qty > 0) {
    return (
      <View style={[styles.qtyWrap, { backgroundColor: dark ? '#ffffff' : theme.primary }]}>
        <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onRemove}>
          <Ionicons name="remove" size={16} color={dark ? COLORS.text : '#ffffff'} />
        </TouchableOpacity>
        <Text style={[styles.qtyText, { color: dark ? COLORS.text : '#ffffff' }]}>{qty}</Text>
        <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onAdd}>
          <Ionicons name="add" size={16} color={dark ? COLORS.text : '#ffffff'} />
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[
        styles.addButton,
        {
          backgroundColor: dark ? '#ffffff' : theme.primarySoft,
          borderColor: dark ? '#ffffff' : theme.primary,
        },
      ]}
      onPress={onAdd}>
      <Text style={[styles.addButtonText, { color: dark ? COLORS.text : theme.primary }]}>
        {bookMode ? 'BOOK' : 'ADD'}
      </Text>
    </TouchableOpacity>
  );
}

function RecommendedCard({ product, qty, onAdd, onRemove, theme, service }) {
  const dark = service === 'scenes';

  return (
    <View style={[styles.recommendedCard, dark && styles.recommendedCardDark]}>
      <View style={[styles.recommendedVisual, { backgroundColor: dark ? '#1a2440' : '#f7f8fb' }]}>
        <Text style={styles.recommendedEmoji}>{pickEmoji(product?.name, service)}</Text>
      </View>
      <Text style={[styles.productBadge, dark && styles.productBadgeDark]}>
        {getProductBadge(product, service)}
      </Text>
      <Text style={[styles.recommendedName, dark && styles.recommendedNameDark]} numberOfLines={2}>
        {product?.name}
      </Text>
      <Text style={[styles.recommendedDesc, dark && styles.recommendedDescDark]} numberOfLines={2}>
        {product?.description || 'Curated pick from this store'}
      </Text>
      <View style={styles.recommendedFooter}>
        <Text style={[styles.recommendedPrice, dark && styles.recommendedPriceDark]}>
          {money(product?.price)}
        </Text>
        <QtyControl
          qty={qty}
          onAdd={onAdd}
          onRemove={onRemove}
          theme={theme}
          dark={dark}
          bookMode={service === 'eatout' || service === 'scenes'}
        />
      </View>
    </View>
  );
}

function MenuItemCard({ product, qty, onAdd, onRemove, theme, service }) {
  const dark = service === 'scenes';
  const bookMode = service === 'eatout' || service === 'scenes';

  return (
    <View style={[styles.menuCard, dark && styles.menuCardDark]}>
      <View style={styles.menuContent}>
        <Text style={[styles.productBadge, dark && styles.productBadgeDark]}>
          {getProductBadge(product, service)}
        </Text>
        <Text style={[styles.menuName, dark && styles.menuNameDark]} numberOfLines={2}>
          {product?.name}
        </Text>
        <Text style={[styles.menuDesc, dark && styles.menuDescDark]} numberOfLines={2}>
          {product?.description || 'Popular choice from this store'}
        </Text>
        <Text style={[styles.menuPrice, dark && styles.menuPriceDark]}>
          {money(product?.price)}
        </Text>
      </View>

      <View style={styles.menuActionCol}>
        <View style={[styles.menuVisual, { backgroundColor: dark ? '#1a2440' : '#f7f8fb' }]}>
          <Text style={styles.menuEmoji}>{pickEmoji(product?.name, service)}</Text>
        </View>
        <QtyControl
          qty={qty}
          onAdd={onAdd}
          onRemove={onRemove}
          theme={theme}
          dark={dark}
          bookMode={bookMode}
        />
      </View>
    </View>
  );
}

export default function VendorDetailsScreen() {
  const { vendorId } = useLocalSearchParams();
  const router = useRouter();
  const {
    vendors,
    vendorsLoading,
    activeService,
    favorites,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    orderHistory,
    cart,
    cartCount,
    cartTotal,
    freeDeliveryRemaining,
    loadProducts,
    addToCart,
    updateQty,
  } = useGrabBasket();

  const theme = SERVICE_THEME[activeService] || SERVICE_THEME.food;
  const isDark = activeService === 'scenes';
  const vendor = useMemo(() => findVendorById(vendors, vendorId), [vendors, vendorId]);

  const [productSearch, setProductSearch] = useState('');
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [shareNotice, setShareNotice] = useState(null);

  useEffect(() => {
    if (vendor?.id) rememberStore(vendor.id);
  }, [vendor, rememberStore]);

  useEffect(() => {
    setShareNotice(null);
  }, [vendor?.id]);

  useEffect(() => {
    if (!vendor) return undefined;

    const timer = setTimeout(async () => {
      setProductsLoading(true);
      const list = await loadProducts(vendor, productSearch);
      setProducts(list.filter((item) => item?.is_available !== false));
      setProductsLoading(false);
    }, 220);

    return () => clearTimeout(timer);
  }, [vendor, productSearch, loadProducts]);

  const categories = useMemo(() => {
    const unique = Array.from(new Set(products.map((item) => deriveCategory(item, activeService))));
    return ['All', ...unique];
  }, [products, activeService]);

  useEffect(() => {
    if (!categories.includes(selectedCategory)) {
      setSelectedCategory('All');
    }
  }, [categories, selectedCategory]);

  const visibleProducts = useMemo(() => {
    if (selectedCategory === 'All') return products;
    return products.filter((item) => deriveCategory(item, activeService) === selectedCategory);
  }, [products, activeService, selectedCategory]);

  const personalizationProfile = useMemo(
    () => buildPersonalizationProfile(orderHistory, vendor),
    [orderHistory, vendor]
  );

  const recommendedProducts = useMemo(
    () => sortRecommended(products, personalizationProfile).slice(0, 4),
    [products, personalizationProfile]
  );

  const sameVendorCart = cartCount > 0 && String(cart.vendorId) === String(vendor?.id);
  const otherVendorCart = cartCount > 0 && cart.vendorId && String(cart.vendorId) !== String(vendor?.id);

  const handleShareStore = useCallback(async () => {
    if (!vendor) return;

    try {
      const shareLines = [vendor.name, getHeroSubtitle(vendor, activeService), vendor?.address]
        .filter(Boolean)
        .join('\n');
      const shareUrl = vendor?.id ? `grab-basket://store/${vendor.id}` : '';
      const message = shareUrl ? `${shareLines}\n${shareUrl}` : shareLines;

      const result = await Share.share({
        title: vendor.name || 'Store',
        message,
      });

      if (result?.action === Share.dismissedAction) {
        return;
      }

      setShareNotice({
        tone: 'success',
        title: 'Store ready to share',
        message: 'The native share sheet opened for this store.',
      });
      captureEvent(ANALYTICS_EVENTS.consumerStoreShareOpened, {
        taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
        service: activeService,
        vendor_id: String(vendor?.id || ''),
        app_env: APP_ENV,
      });
    } catch (error) {
      if (/cancel/i.test(String(error?.message || ''))) {
        return;
      }

      setShareNotice({
        tone: 'warning',
        title: 'Share unavailable',
        message: 'Could not open the native share sheet on this device.',
      });
    }
  }, [activeService, vendor]);

  useEffect(() => {
    if (!vendor?.id) return;

    captureEvent(ANALYTICS_EVENTS.consumerStoreViewed, {
      taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
      vendor_id: String(vendor.id),
      service: activeService,
      personalization_enabled: FEATURE_FLAGS.personalizationRanking,
      premium_trust_cards_enabled: FEATURE_FLAGS.premiumTrustCards,
      app_env: APP_ENV,
    });
  }, [activeService, vendor?.id]);

  if (vendorsLoading && !vendor) {
    return (
      <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]}>
        <LoadingState dark={isDark} label="Loading store..." />
      </SafeAreaView>
    );
  }

  if (!vendor) {
    return (
      <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]}>
        <View style={styles.centerWrap}>
          <EmptyState
            dark={isDark}
            title="Store not found"
            subtitle="This vendor is missing or not loaded yet."
          />
          <TouchableOpacity
            activeOpacity={0.92}
            style={[styles.primaryCta, { backgroundColor: isDark ? '#ffffff' : COLORS.text }]}
            onPress={() => router.replace('/')}>
            <Text style={[styles.primaryCtaText, { color: isDark ? COLORS.text : '#ffffff' }]}>
              Back to home
            </Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={theme.hero} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: sameVendorCart ? 118 : 28 }}>
        <View style={[styles.hero, { backgroundColor: theme.hero }]}>
          <View style={[styles.heroOrbOne, { backgroundColor: theme.heroAlt }]} />
          <View style={[styles.heroOrbTwo, { backgroundColor: theme.heroAlt }]} />

          <View style={styles.topBar}>
            <TouchableOpacity
              activeOpacity={0.92}
              style={styles.heroIconButton}
              onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
              <Ionicons name="arrow-back" size={20} color="#ffffff" />
            </TouchableOpacity>

            <View style={styles.topBarActions}>
              <TouchableOpacity
                activeOpacity={0.92}
                style={styles.heroIconButton}
                onPress={handleShareStore}>
                <Ionicons name="share-social-outline" size={18} color="#ffffff" />
              </TouchableOpacity>

              <TouchableOpacity
                activeOpacity={0.92}
                style={styles.heroIconButton}
                onPress={() => toggleFavorite(vendor.id)}>
                <Ionicons
                  name={favorites[vendor.id] ? 'heart' : 'heart-outline'}
                  size={18}
                  color={favorites[vendor.id] ? theme.heroAccent : '#ffffff'}
                />
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.heroContent}>
            <View style={styles.heroBrandRow}>
              <View style={[styles.heroBrandPill, isDark && styles.heroBrandPillDark]}>
                <Image source={BRAND_LOGO} style={styles.heroBrandLogo} contentFit="contain" />
                <Text style={[styles.heroBrandLabel, isDark && styles.heroBrandLabelDark]}>
                  Grab Basket merchant
                </Text>
              </View>
              <View style={[styles.heroMetaBadge, isDark && styles.heroMetaBadgeDark]}>
                <Ionicons
                  name="shield-checkmark-outline"
                  size={14}
                  color={isDark ? COLORS.text : theme.heroAccent}
                />
                <Text style={[styles.heroMetaBadgeText, isDark && styles.heroMetaBadgeTextDark]}>
                  Curated details
                </Text>
              </View>
            </View>
            <View style={styles.heroTitleRow}>
              <View style={[styles.heroMonogram, { backgroundColor: theme.pillBg }]}>
                {getVendorImage(vendor) ? (
                  <Image
                    source={{ uri: getVendorImage(vendor) }}
                    style={styles.heroImage}
                    contentFit="cover"
                  />
                ) : (
                  <Text style={styles.heroMonogramText}>{initials(vendor.name)}</Text>
                )}
              </View>

              <View style={{ flex: 1 }}>
                <Text style={styles.heroTitle}>{vendor.name}</Text>
                <Text style={styles.heroSubtitle}>{getHeroSubtitle(vendor, activeService)}</Text>
                {vendor?.address ? <Text style={styles.heroAddress}>{vendor.address}</Text> : null}
              </View>
            </View>

            <View style={styles.heroPillRow}>
              <InfoPill text={`${getVendorRating(vendor)} ★ rated`} theme={theme} />
              <InfoPill text={estimateEta(vendor, activeService)} theme={theme} />
              <InfoPill text={getDeliveryLabel(vendor, activeService)} theme={theme} />
              <InfoPill text={vendor?.open_now ? 'Open now' : 'Store info'} theme={theme} />
              {vendor?.distance_km != null ? (
                <InfoPill text={`${Number(vendor.distance_km).toFixed(1)} km`} theme={theme} />
              ) : null}
            </View>

            <View style={styles.heroMetaStrip}>
              <Text style={styles.heroMetaText}>
                {activeService === 'eatout'
                  ? 'Reserve-ready · Bill offers available'
                  : activeService === 'warehouse'
                    ? 'Delivery in minutes · Fresh essentials'
                    : activeService === 'scenes'
                      ? 'Live experiences · Instant confirmation'
                      : 'Fast delivery · Popular near you'}
              </Text>
            </View>
          </View>
        </View>

        <View style={[styles.content, isDark && styles.contentDark]}>
          <View style={[styles.searchBar, { backgroundColor: theme.searchBg }, isDark && styles.searchBarDark]}>
            <Ionicons
              name="search-outline"
              size={20}
              color={isDark ? COLORS.darkMuted : COLORS.muted}
            />
            <TextInput
              style={[styles.searchInput, isDark && styles.searchInputDark]}
              placeholder={
                activeService === 'eatout' || activeService === 'scenes'
                  ? 'Search offers, passes or experiences'
                  : 'Search inside store'
              }
              placeholderTextColor={isDark ? '#8fa2c4' : COLORS.subtle}
              value={productSearch}
              onChangeText={setProductSearch}
              onSubmitEditing={() => rememberSearch(productSearch)}
              returnKeyType="search"
            />
            <Ionicons name="options-outline" size={20} color={isDark ? '#ffffff' : theme.primary} />
          </View>

          {shareNotice ? (
            <View style={styles.noticeWrap}>
              <InlineNoticeCard
                tone={shareNotice.tone}
                title={shareNotice.title}
                message={shareNotice.message}
                onDismiss={() => setShareNotice(null)}
              />
            </View>
          ) : null}

          {otherVendorCart ? (
            <View style={[styles.warningBanner, isDark && styles.warningBannerDark]}>
              <Ionicons
                name="information-circle-outline"
                size={18}
                color={isDark ? '#ffffff' : theme.primary}
              />
              <Text style={[styles.warningText, isDark && styles.warningTextDark]}>
                Adding items here will replace the basket from your other store.
              </Text>
            </View>
          ) : null}

          {FEATURE_FLAGS.premiumTrustCards ? (
            <View style={[styles.trustCard, isDark && styles.trustCardDark]}>
            <View style={styles.trustHeaderRow}>
              <Ionicons
                name="shield-checkmark-outline"
                size={17}
                color={isDark ? '#ffffff' : theme.primary}
              />
              <Text style={[styles.trustTitle, isDark && styles.trustTitleDark]}>
                Trust & transparency
              </Text>
            </View>

            {getTrustSignals(vendor).map((signal) => (
              <Text key={signal} style={[styles.trustPoint, isDark && styles.trustPointDark]}>
                • {signal}
              </Text>
            ))}
            </View>
          ) : null}

          <View style={[styles.couponCard, isDark && styles.couponCardDark]}>
            <View style={styles.couponHeader}>
              <Text style={[styles.couponTitle, isDark && styles.couponTitleDark]}>Offers & coupons</Text>
              <Ionicons name="pricetags-outline" size={16} color={isDark ? '#ffffff' : theme.primary} />
            </View>
            <Text style={[styles.couponText, isDark && styles.couponTextDark]}>
              {getCouponMessage(vendor, activeService)}
            </Text>
          </View>

          {FEATURE_FLAGS.loyaltyMembership ? (
            <View style={[styles.membershipCard, isDark && styles.membershipCardDark]}>
              <View style={styles.membershipHeader}>
                <Ionicons name="diamond-outline" size={16} color={isDark ? '#ffffff' : theme.primary} />
                <Text style={[styles.membershipTitle, isDark && styles.membershipTitleDark]}>GB Plus membership</Text>
              </View>
              <Text style={[styles.membershipText, isDark && styles.membershipTextDark]}>
                {getLoyaltyCopy(personalizationProfile)}
              </Text>
            </View>
          ) : null}

          <View style={[styles.reviewCard, isDark && styles.reviewCardDark]}>
            <View style={styles.reviewHeader}>
              <Text style={[styles.reviewTitle, isDark && styles.reviewTitleDark]}>Ratings & reviews</Text>
              <Text style={[styles.reviewScore, isDark && styles.reviewScoreDark]}>
                {getVendorRating(vendor)} ★
              </Text>
            </View>
            {getReviewHighlights(vendor).map((line) => (
              <Text key={line} style={[styles.reviewLine, isDark && styles.reviewLineDark]}>
                {line}
              </Text>
            ))}
          </View>

          {activeService !== 'eatout' && activeService !== 'scenes' ? (
            <View style={[styles.savingsCard, isDark && styles.savingsCardDark]}>
              <View style={styles.savingsRow}>
                <View
                  style={[
                    styles.savingsIcon,
                    { backgroundColor: isDark ? 'rgba(255,255,255,0.10)' : theme.primarySoft },
                  ]}>
                  <Ionicons
                    name="pricetag-outline"
                    size={18}
                    color={isDark ? '#ffffff' : theme.primary}
                  />
                </View>

                <View style={{ flex: 1 }}>
                  <Text style={[styles.savingsTitle, isDark && styles.savingsTitleDark]}>
                    {freeDeliveryRemaining > 0
                      ? `${money(freeDeliveryRemaining)} away from free delivery`
                      : 'Free delivery unlocked'}
                  </Text>
                  <Text style={[styles.savingsSubtitle, isDark && styles.savingsSubtitleDark]}>
                    Keep this strip dynamic and backend-driven, not hardcoded.
                  </Text>
                </View>
              </View>
            </View>
          ) : null}

          {categories.length > 1 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.categoryRail}>
              {categories.map((item) => {
                const active = item === selectedCategory;

                return (
                  <TouchableOpacity
                    key={item}
                    activeOpacity={0.92}
                    style={[
                      styles.categoryChip,
                      isDark && styles.categoryChipDark,
                      active && {
                        backgroundColor: isDark ? '#ffffff' : theme.primary,
                        borderColor: isDark ? '#ffffff' : theme.primary,
                      },
                    ]}
                    onPress={() => setSelectedCategory(item)}>
                    <Text
                      style={[
                        styles.categoryChipText,
                        isDark && styles.categoryChipTextDark,
                        active && { color: isDark ? COLORS.text : '#ffffff' },
                      ]}>
                      {item}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          ) : null}

          {productsLoading ? <LoadingState dark={isDark} label="Loading products..." /> : null}

          {!productsLoading && products.length === 0 ? (
            <EmptyState
              dark={isDark}
              title="No products yet"
              subtitle="Seed more products from the seller side and this page will start feeling complete."
            />
          ) : (
            <>
              {recommendedProducts.length > 0 ? (
                <>
                  <View style={styles.sectionHeader}>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.sectionTitle, isDark && styles.sectionTitleDark]}>
                        Recommended for you
                      </Text>
                      <Text style={[styles.sectionSubtitle, isDark && styles.sectionSubtitleDark]}>
                        Stronger merchandising is one of the biggest gaps vs Swiggy.
                      </Text>
                    </View>
                  </View>

                  <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.recommendedRail}>
                    {recommendedProducts.map((product) => (
                      <RecommendedCard
                        key={product.id}
                        product={product}
                        qty={cart.items[product.id]?.qty || 0}
                        onAdd={() => addToCart(product)}
                        onRemove={() => updateQty(product, -1)}
                        theme={theme}
                        service={activeService}
                      />
                    ))}
                  </ScrollView>
                </>
              ) : null}

              <View style={styles.sectionHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.sectionTitle, isDark && styles.sectionTitleDark]}>
                    {selectedCategory === 'All' ? 'Full menu' : selectedCategory}
                  </Text>
                  <Text style={[styles.sectionSubtitle, isDark && styles.sectionSubtitleDark]}>
                    {visibleProducts.length} items available
                  </Text>
                </View>
              </View>

              {visibleProducts.length === 0 ? (
                <EmptyState
                  dark={isDark}
                  title="No matching items"
                  subtitle="Try a broader search or another category."
                />
              ) : (
                visibleProducts.map((product) => (
                  <MenuItemCard
                    key={product.id}
                    product={product}
                    qty={cart.items[product.id]?.qty || 0}
                    onAdd={() => addToCart(product)}
                    onRemove={() => updateQty(product, -1)}
                    theme={theme}
                    service={activeService}
                  />
                ))
              )}
            </>
          )}
        </View>
      </ScrollView>

      {sameVendorCart ? (
        <TouchableOpacity
          activeOpacity={0.94}
          style={[
            styles.floatingCart,
            { backgroundColor: theme.cartBg },
            isDark && styles.floatingCartDark,
          ]}
          onPress={() => router.push('/cart')}>
          <View>
            <Text style={[styles.floatingCartTitle, { color: isDark ? COLORS.text : '#ffffff' }]}>
              {activeService === 'eatout' || activeService === 'scenes' ? 'Continue booking' : 'View cart'}
            </Text>
            <Text style={[styles.floatingCartText, { color: isDark ? COLORS.text : '#ffffff' }]}>
              {cartCount} items · {money(cartTotal)}
            </Text>
          </View>
          <Ionicons name="arrow-forward" size={20} color={isDark ? COLORS.text : '#ffffff'} />
        </TouchableOpacity>
      ) : null}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },

  centerWrap: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 16,
  },

  hero: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 24,
    overflow: 'hidden',
  },
  heroOrbOne: {
    position: 'absolute',
    right: -38,
    top: -12,
    width: 190,
    height: 190,
    borderRadius: 95,
    opacity: 0.24,
  },
  heroOrbTwo: {
    position: 'absolute',
    left: -44,
    bottom: -58,
    width: 210,
    height: 210,
    borderRadius: 105,
    opacity: 0.18,
  },

  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  topBarActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  heroIconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },

  heroContent: {
    marginTop: 18,
  },
  heroBrandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 14,
  },
  heroBrandPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.14)',
  },
  heroBrandPillDark: {
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  heroBrandLogo: {
    width: 20,
    height: 20,
  },
  heroBrandLabel: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  heroBrandLabelDark: {
    color: COLORS.text,
  },
  heroMetaBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.92)',
  },
  heroMetaBadgeDark: {
    backgroundColor: 'rgba(255,255,255,0.14)',
  },
  heroMetaBadgeText: {
    color: COLORS.text,
    fontSize: 11,
    fontWeight: '900',
  },
  heroMetaBadgeTextDark: {
    color: COLORS.text,
  },
  heroTitleRow: {
    flexDirection: 'row',
    gap: 14,
    alignItems: 'center',
  },
  heroMonogram: {
    width: 64,
    height: 64,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroImage: {
    width: '100%',
    height: '100%',
    borderRadius: 22,
  },
  heroMonogramText: {
    color: '#ffffff',
    fontSize: 24,
    fontWeight: '900',
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 30,
    fontWeight: '900',
    lineHeight: 34,
  },
  heroSubtitle: {
    marginTop: 6,
    color: 'rgba(255,255,255,0.88)',
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },
  heroAddress: {
    marginTop: 4,
    color: 'rgba(255,255,255,0.82)',
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },
  heroPillRow: {
    marginTop: 16,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  infoPill: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  infoPillText: {
    fontSize: 12,
    fontWeight: '800',
  },
  heroMetaStrip: {
    marginTop: 14,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 14,
    paddingVertical: 13,
  },
  heroMetaText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },

  content: {
    marginTop: -10,
    backgroundColor: COLORS.page,
    borderTopLeftRadius: 34,
    borderTopRightRadius: 34,
    paddingHorizontal: 16,
    paddingTop: 20,
  },
  contentDark: {
    backgroundColor: COLORS.dark,
  },

  searchBar: {
    minHeight: 58,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    ...createShadow(0.1, 18, 8),
  },
  searchBarDark: {
    borderColor: COLORS.darkBorder,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '600',
  },
  searchInputDark: {
    color: '#ffffff',
  },
  noticeWrap: {
    marginTop: 12,
  },

  warningBanner: {
    marginTop: 12,
    borderRadius: 20,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 13,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  warningBannerDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  warningText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  warningTextDark: {
    color: '#ffffff',
  },

  trustCard: {
    marginTop: 12,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 13,
    ...createShadow(0.05, 12, 6),
  },
  trustCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  trustHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 6,
  },
  trustTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  trustTitleDark: {
    color: '#ffffff',
  },
  trustPoint: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 18,
  },
  trustPointDark: {
    color: COLORS.darkMuted,
  },
  couponCard: {
    marginTop: 12,
    borderRadius: 22,
    backgroundColor: COLORS.primarySoft,
    borderWidth: 1,
    borderColor: '#F8C7CE',
    paddingHorizontal: 14,
    paddingVertical: 12,
    ...createShadow(0.05, 12, 6),
  },
  couponCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  couponHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  couponTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  couponTitleDark: {
    color: '#ffffff',
  },
  couponText: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 18,
  },
  couponTextDark: {
    color: COLORS.darkMuted,
  },
  reviewCard: {
    marginTop: 12,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 12,
    ...createShadow(0.05, 12, 6),
  },
  reviewCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  reviewHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  reviewTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  reviewTitleDark: {
    color: '#ffffff',
  },
  reviewScore: {
    color: COLORS.orange,
    fontSize: 13,
    fontWeight: '900',
  },
  reviewScoreDark: {
    color: '#ffffff',
  },
  reviewLine: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 18,
  },
  reviewLineDark: {
    color: COLORS.darkMuted,
  },
  membershipCard: {
    marginTop: 12,
    borderRadius: 22,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 12,
    ...createShadow(0.05, 12, 6),
  },
  membershipCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  membershipHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  membershipTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  membershipTitleDark: {
    color: '#ffffff',
  },
  membershipText: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 18,
  },
  membershipTextDark: {
    color: COLORS.darkMuted,
  },

  savingsCard: {
    marginTop: 12,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    ...createShadow(0.05, 12, 6),
  },
  savingsCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  savingsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  savingsIcon: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  savingsTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },
  savingsTitleDark: {
    color: '#ffffff',
  },
  savingsSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  savingsSubtitleDark: {
    color: COLORS.darkMuted,
  },

  categoryRail: {
    gap: 10,
    paddingTop: 16,
    paddingBottom: 6,
  },
  categoryChip: {
    borderRadius: 999,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    paddingVertical: 11,
  },
  categoryChipDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  categoryChipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  categoryChipTextDark: {
    color: '#ffffff',
  },

  sectionHeader: {
    marginTop: 20,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
  },
  sectionTitleDark: {
    color: '#ffffff',
  },
  sectionSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  sectionSubtitleDark: {
    color: COLORS.darkMuted,
  },

  recommendedRail: {
    gap: 12,
    paddingBottom: 4,
  },
  recommendedCard: {
    width: 244,
    borderRadius: 28,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    ...createShadow(0.08, 16, 8),
  },
  recommendedCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  recommendedVisual: {
    height: 132,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  recommendedEmoji: {
    fontSize: 44,
  },
  productBadge: {
    color: COLORS.orange,
    fontSize: 12,
    fontWeight: '900',
  },
  productBadgeDark: {
    color: '#ffffff',
  },
  recommendedName: {
    marginTop: 6,
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
    lineHeight: 22,
  },
  recommendedNameDark: {
    color: '#ffffff',
  },
  recommendedDesc: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
    minHeight: 36,
  },
  recommendedDescDark: {
    color: COLORS.darkMuted,
  },
  recommendedFooter: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  recommendedPrice: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  recommendedPriceDark: {
    color: '#ffffff',
  },

  menuCard: {
    marginBottom: 14,
    borderRadius: 26,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    flexDirection: 'row',
    gap: 12,
    ...createShadow(0.07, 14, 7),
  },
  menuCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  menuContent: {
    flex: 1,
  },
  menuName: {
    marginTop: 6,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    lineHeight: 22,
  },
  menuNameDark: {
    color: '#ffffff',
  },
  menuDesc: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },
  menuDescDark: {
    color: COLORS.darkMuted,
  },
  menuPrice: {
    marginTop: 10,
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '900',
  },
  menuPriceDark: {
    color: '#ffffff',
  },
  menuActionCol: {
    width: 112,
    alignItems: 'center',
  },
  menuVisual: {
    width: 112,
    height: 104,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  menuEmoji: {
    fontSize: 34,
  },

  addButton: {
    minWidth: 92,
    height: 40,
    borderRadius: 14,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButtonText: {
    fontSize: 13,
    fontWeight: '900',
  },

  qtyWrap: {
    minWidth: 92,
    height: 40,
    borderRadius: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
  },
  qtyAction: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    fontSize: 13,
    fontWeight: '900',
  },

  feedbackCard: {
    marginTop: 16,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 26,
    paddingHorizontal: 18,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  feedbackCardDark: {
    backgroundColor: COLORS.darkCard,
    borderColor: COLORS.darkBorder,
  },
  feedbackTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  feedbackTitleDark: {
    color: '#ffffff',
  },
  feedbackSubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
    textAlign: 'center',
  },
  feedbackSubtitleDark: {
    color: COLORS.darkMuted,
  },

  floatingCart: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 14,
    borderRadius: 26,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    ...createShadow(0.2, 20, 10),
  },
  floatingCartDark: {},
  floatingCartTitle: {
    fontSize: 16,
    fontWeight: '900',
  },
  floatingCartText: {
    marginTop: 4,
    fontSize: 13,
    fontWeight: '700',
  },

  primaryCta: {
    marginTop: 14,
    alignSelf: 'center',
    borderRadius: 16,
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  primaryCtaText: {
    fontSize: 15,
    fontWeight: '900',
  },
});
