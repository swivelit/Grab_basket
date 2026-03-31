import React, { useMemo } from 'react';
import { Image } from 'expo-image';
import {
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

import { BrandPalette, createShadow } from '@/constants/theme';
import { buildApiUrl } from '../../config';
import { useGrabBasket } from '../../../App';

const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');

const SERVICE_TABS = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline' },
  { key: 'warehouse', label: 'Warehouse', icon: 'basket-outline' },
  { key: 'eatout', label: 'Eatout', icon: 'restaurant-outline' },
];

const SERVICE_COPY = {
  food: {
    heroTitle: 'Cravings, offers and fast delivery — all in one clean flow.',
    heroSubtitle: 'Build the GrabBasket experience like a polished consumer app, not an internal dashboard.',
    searchPlaceholder: "Search for 'Cake'",
    headline: 'CRAVEATHON',
    subheadline: 'Flat ₹200 off & more across top local picks',
    cta: 'Order now',
    etaFallback: '7 mins',
    categoryTiles: [
      { key: 'biryani', label: 'Biryani', icon: 'flame-outline' },
      { key: 'pizza', label: 'Pizza', icon: 'pizza-outline' },
      { key: 'desserts', label: 'Desserts', icon: 'ice-cream-outline' },
      { key: 'healthy', label: 'Healthy', icon: 'leaf-outline' },
    ],
    chips: ['All', 'Fast delivery', 'Top rated', 'Offers', 'Biryani'],
    stripCards: [
      { key: 'offer-1', title: 'Binge worthy deals', value: 'Flat ₹200', caption: 'Off & more' },
      { key: 'offer-2', title: 'Summer carnival', value: 'Up to 60%', caption: 'Off & more' },
      { key: 'offer-3', title: 'EatRight', value: 'Win ₹300', caption: 'Cashback' },
    ],
  },
  warehouse: {
    heroTitle: 'Groceries with the same premium shell, fast enough for a 7-minute promise.',
    heroSubtitle: 'Keep the app unmistakably GrabBasket, but structure it like a quick-commerce product customers trust.',
    searchPlaceholder: 'Search for Cold drinks',
    headline: 'MOST SHOPPED NEAR YOU',
    subheadline: 'Fresh staples, instant snacks, gifting and daily essentials',
    cta: 'Shop now',
    etaFallback: '7 mins',
    categoryTiles: [
      { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
      { key: 'snacks', label: 'Snacks', icon: 'fast-food-outline' },
      { key: 'gifting', label: 'Gifting', icon: 'gift-outline' },
      { key: 'daily', label: 'Daily needs', icon: 'cube-outline' },
    ],
    chips: ['All', 'Fresh', 'Instant snacks', 'Gifting', 'Daily needs'],
    stripCards: [
      { key: 'mart-1', title: 'Biryani & feasting corner', value: 'Festive picks', caption: 'Quick baskets' },
      { key: 'mart-2', title: 'Instant snacks & drinks', value: 'Fast add-ons', caption: 'Cold drinks, chips' },
      { key: 'mart-3', title: 'Dates & gifting needs', value: 'Premium picks', caption: 'Seasonal essentials' },
    ],
  },
  eatout: {
    heroTitle: 'Dining discovery should feel premium, playful and reservation-first.',
    heroSubtitle: 'Structure the page like a polished dineout product with strong promos, category blocks and venue cards.',
    searchPlaceholder: 'Search for restaurants',
    headline: 'BILL HALF PARTY FULL',
    subheadline: 'Flat 50% off, family-friendly spots and premium table bookings',
    cta: 'Book now',
    etaFallback: 'Discover',
    categoryTiles: [
      { key: 'offers', label: 'Flat 50%', icon: 'pricetag-outline' },
      { key: 'hall', label: 'Hall of fame', icon: 'trophy-outline' },
      { key: 'family', label: 'Family spots', icon: 'people-outline' },
      { key: 'cafes', label: 'Cafes', icon: 'cafe-outline' },
    ],
    chips: ['Offers', 'Family-friendly', 'Cafes', 'Quick bites', 'Freebies'],
    stripCards: [
      { key: 'dine-1', title: 'Flat 50% off', value: 'Top tables', caption: 'Across Kochi' },
      { key: 'dine-2', title: 'Family-friendly spots', value: 'Weekend picks', caption: 'Larger groups' },
      { key: 'dine-3', title: 'Cafes & quick bites', value: 'Easy plans', caption: 'After work' },
    ],
  },
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
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
    return raw;
  }
}

function initials(name = '') {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .map((item) => item[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getVendorRating(vendor) {
  const rating = Number(vendor?.avg_rating || 0);
  if (Number.isFinite(rating) && rating > 0) return rating.toFixed(1);
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 3;
  return (4.1 + (seed % 7) * 0.1).toFixed(1);
}

function getVendorEta(vendor, service) {
  const eta = Number(vendor?.estimated_delivery_time_min || 0);

  if (service === 'warehouse') return eta > 0 ? `${eta} mins` : '7 mins';
  if (service === 'eatout') return eta > 0 ? `Table in ${Math.max(10, eta)} mins` : 'Book now';
  if (eta > 0) return eta <= 15 ? `${eta} mins` : `${Math.max(10, eta - 5)}-${eta} mins`;
  return '23 mins';
}

function getFastestEta(vendors, fallback) {
  const mins = (Array.isArray(vendors) ? vendors : [])
    .map((item) => Number(item?.estimated_delivery_time_min || 0))
    .filter((value) => Number.isFinite(value) && value > 0);

  if (!mins.length) return fallback;
  return `${Math.max(5, Math.min(...mins))} mins`;
}

function getVendorMeta(vendor, service) {
  if (service === 'warehouse') {
    return vendor?.description || 'Fresh, quick, everyday essentials';
  }

  if (service === 'eatout') {
    return vendor?.description || 'Tables, offers and premium dining';
  }

  return vendor?.cuisine_tags || vendor?.description || vendor?.address || 'Fast delivery and trusted quality';
}

function getVendorOffer(vendor, service) {
  if (vendor?.open_now === false) return 'Closed now';
  if (service === 'warehouse') return 'Free delivery above ₹199';
  if (service === 'eatout') return 'Flat 50% off on dining bills';
  return Number(vendor?.total_ratings || 0) > 100 ? 'Top rated around you' : 'Free delivery on first order';
}

function getLocationTitle(defaultAddress) {
  return defaultAddress?.city || 'Select location';
}

function getAddressLine(defaultAddress) {
  const line = [defaultAddress?.line1, defaultAddress?.line2].filter(Boolean).join(', ');
  return line || 'Add your delivery address';
}

function getServiceTitle(service) {
  if (service === 'warehouse') return 'Warehouse';
  if (service === 'eatout') return 'Eatout';
  return 'Food';
}

function HeaderBlock({ activeService, etaText, locationTitle, addressLine, onOpenAccount }) {
  return (
    <View style={styles.headerBlock}>
      <View style={{ flex: 1, paddingRight: 12 }}>
        <Text style={styles.etaText}>{etaText}</Text>
        <View style={styles.locationRow}>
          <Ionicons name={activeService === 'warehouse' ? 'time-outline' : 'navigate'} size={15} color="#FFFFFF" />
          <Text numberOfLines={1} style={styles.locationTitle}>
            {locationTitle}
          </Text>
          <Ionicons name="chevron-down" size={14} color="#FFFFFF" />
        </View>
        <Text numberOfLines={1} style={styles.locationSubtitle}>
          {addressLine}
        </Text>
      </View>

      <TouchableOpacity activeOpacity={0.92} onPress={onOpenAccount} style={styles.headerAvatarButton}>
        <Ionicons name="person" size={20} color={BrandPalette.primary} />
      </TouchableOpacity>
    </View>
  );
}

function ServiceTabRail({ activeService, onChange }) {
  return (
    <View style={styles.serviceTabRail}>
      {SERVICE_TABS.map((item) => {
        const active = item.key === activeService;
        return (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.95}
            onPress={() => onChange(item.key)}
            style={[styles.serviceTab, active && styles.serviceTabActive]}>
            <View style={[styles.serviceTabIconWrap, active && styles.serviceTabIconWrapActive]}>
              <Ionicons
                name={item.icon}
                size={18}
                color={active ? BrandPalette.primary : BrandPalette.white}
              />
            </View>
            <Text style={[styles.serviceTabLabel, active && styles.serviceTabLabelActive]}>{item.label}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

function SearchBar({ value, onChangeText, onSubmit, placeholder }) {
  return (
    <View style={styles.searchRow}>
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={20} color={BrandPalette.subtle} />
        <TextInput
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          placeholder={placeholder}
          placeholderTextColor={BrandPalette.subtle}
          style={styles.searchInput}
          returnKeyType="search"
        />
        <Ionicons name="mic-outline" size={20} color={BrandPalette.primary} />
      </View>
      <TouchableOpacity activeOpacity={0.92} style={styles.searchAction}>
        <Ionicons name="receipt-outline" size={18} color={BrandPalette.inkSoft} />
      </TouchableOpacity>
    </View>
  );
}

function ChipRail({ items }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRailContent}>
      {items.map((item) => (
        <View key={item} style={styles.chipPill}>
          <Text style={styles.chipPillText}>{item}</Text>
        </View>
      ))}
    </ScrollView>
  );
}

function HeroCard({ copy, activeService }) {
  return (
    <View style={styles.heroCard}>
      <View style={styles.heroBadgeRow}>
        <View style={styles.heroBadge}>
          <Image source={BRAND_LOGO} style={styles.heroBadgeLogo} contentFit="contain" />
          <Text style={styles.heroBadgeText}>{getServiceTitle(activeService).toUpperCase()}</Text>
        </View>
      </View>

      <Text style={styles.heroEyebrow}>{copy.headline}</Text>
      <Text style={styles.heroTitle}>{copy.heroTitle}</Text>
      <Text style={styles.heroSubtitle}>{copy.subheadline}</Text>
      <View style={styles.heroFooter}>
        <TouchableOpacity activeOpacity={0.94} style={styles.heroPrimaryButton}>
          <Text style={styles.heroPrimaryButtonText}>{copy.cta}</Text>
        </TouchableOpacity>
        <View style={styles.heroMetricCard}>
          <Text style={styles.heroMetricLabel}>Built for</Text>
          <Text style={styles.heroMetricValue}>Clean checkout</Text>
        </View>
      </View>
    </View>
  );
}

function SectionHeader({ title, actionLabel }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {actionLabel ? <Text style={styles.sectionAction}>{actionLabel}</Text> : null}
    </View>
  );
}

function CategoryTile({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.categoryTile}>
      <View style={styles.categoryIconWrap}>
        <Ionicons name={item.icon} size={20} color={BrandPalette.primary} />
      </View>
      <Text style={styles.categoryTileLabel}>{item.label}</Text>
    </TouchableOpacity>
  );
}

function PromoTile({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.promoTile}>
      <Text style={styles.promoTileTitle}>{item.title}</Text>
      <Text style={styles.promoTileValue}>{item.value}</Text>
      <Text style={styles.promoTileCaption}>{item.caption}</Text>
    </TouchableOpacity>
  );
}

function DealCard({ item, onAdd }) {
  return (
    <View style={styles.dealCard}>
      <View style={styles.dealVisual}>
        <Ionicons name="bag-handle-outline" size={20} color={BrandPalette.primary} />
      </View>
      <Text numberOfLines={1} style={styles.dealBrand}>
        {item?.vendorName || 'GrabBasket'}
      </Text>
      <Text numberOfLines={2} style={styles.dealName}>
        {item?.name || 'Featured deal'}
      </Text>
      <View style={styles.dealFooter}>
        <Text style={styles.dealPrice}>{money(item?.price || 99)}</Text>
        <TouchableOpacity activeOpacity={0.94} onPress={onAdd} style={styles.addMiniButton}>
          <Text style={styles.addMiniButtonText}>ADD</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

function StoreCard({ vendor, service, favorite, onToggleFavorite, onPress }) {
  const imageUri = resolveMediaUrl(vendor?.cover_image_url || vendor?.image_url || vendor?.logo_url);

  return (
    <TouchableOpacity activeOpacity={0.95} onPress={onPress} style={styles.storeCard}>
      <View style={styles.storeVisualWrap}>
        {imageUri ? (
          <Image source={{ uri: imageUri }} style={styles.storeImage} contentFit="cover" transition={180} />
        ) : (
          <View style={styles.storeImageFallback}>
            <Text style={styles.storeImageFallbackText}>{initials(vendor?.name)}</Text>
          </View>
        )}

        <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite} style={styles.favoriteButton}>
          <Ionicons
            name={favorite ? 'heart' : 'heart-outline'}
            size={18}
            color={favorite ? BrandPalette.primary : BrandPalette.inkSoft}
          />
        </TouchableOpacity>

        <View style={styles.offerBadge}>
          <Text style={styles.offerBadgeText}>{getVendorOffer(vendor, service)}</Text>
        </View>
      </View>

      <View style={styles.storeBody}>
        <View style={styles.storeTitleRow}>
          <Text numberOfLines={1} style={styles.storeName}>
            {vendor?.name || 'Local store'}
          </Text>
          <View style={styles.ratingPill}>
            <Ionicons name="star" size={12} color="#FFFFFF" />
            <Text style={styles.ratingPillText}>{getVendorRating(vendor)}</Text>
          </View>
        </View>

        <Text numberOfLines={2} style={styles.storeMeta}>
          {getVendorMeta(vendor, service)}
        </Text>

        <View style={styles.storeFooter}>
          <Text style={styles.storeEta}>{getVendorEta(vendor, service)}</Text>
          <Text style={styles.storeDot}>•</Text>
          <Text numberOfLines={1} style={styles.storeFee}>
            {vendor?.distance_km ? `${Number(vendor.distance_km).toFixed(1)} km` : 'Fast delivery'}
          </Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function EmptyState({ title, subtitle }) {
  return (
    <View style={styles.emptyCard}>
      <Ionicons name="search-outline" size={22} color={BrandPalette.subtle} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

function CartBanner({ count, total, onPress }) {
  if (!count) return null;

  return (
    <TouchableOpacity activeOpacity={0.95} onPress={onPress} style={styles.cartBanner}>
      <View>
        <Text style={styles.cartBannerTitle}>{count} item{count > 1 ? 's' : ''} in cart</Text>
        <Text style={styles.cartBannerSubtitle}>{money(total)} · Review basket</Text>
      </View>
      <View style={styles.cartBannerAction}>
        <Text style={styles.cartBannerActionText}>View cart</Text>
        <Ionicons name="arrow-forward" size={16} color="#FFFFFF" />
      </View>
    </TouchableOpacity>
  );
}

export default function HomeScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const {
    activeService,
    setActiveService,
    homeSearch,
    setHomeSearch,
    loadVendors,
    refreshing,
    featuredVendors,
    vendors,
    vendorsLoading,
    homeDeals,
    recentVendors,
    rememberStore,
    rememberSearch,
    favorites,
    toggleFavorite,
    addToCart,
    cartCount,
    cartTotal,
    defaultAddress,
  } = useGrabBasket();

  const copy = SERVICE_COPY[activeService] || SERVICE_COPY.food;
  const heroEta = useMemo(
    () => getFastestEta(vendors, copy.etaFallback),
    [copy.etaFallback, vendors]
  );

  const primaryStores = useMemo(() => {
    const list = Array.isArray(featuredVendors) && featuredVendors.length ? featuredVendors : vendors;
    return (Array.isArray(list) ? list : []).slice(0, 6);
  }, [featuredVendors, vendors]);

  const openStore = (vendor) => {
    if (!vendor?.id) return;
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const handleSearchSubmit = () => {
    if (String(homeSearch || '').trim()) {
      rememberSearch(homeSearch);
    }
    router.push('/(tabs)/explore');
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="light-content" />
      <ScrollView
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadVendors({ pullToRefresh: true })}
            tintColor={BrandPalette.primary}
          />
        }
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 104 }}>
        <View style={styles.heroShell}>
          <HeaderBlock
            activeService={activeService}
            etaText={heroEta}
            locationTitle={getLocationTitle(defaultAddress)}
            addressLine={getAddressLine(defaultAddress)}
            onOpenAccount={() => router.push('/(tabs)/account')}
          />

          <ServiceTabRail activeService={activeService} onChange={setActiveService} />

          <SearchBar
            value={homeSearch}
            onChangeText={setHomeSearch}
            onSubmit={handleSearchSubmit}
            placeholder={copy.searchPlaceholder}
          />

          <ChipRail items={copy.chips} />

          <HeroCard copy={copy} activeService={activeService} />
        </View>

        <View style={styles.bodySurface}>
          <SectionHeader title="Quick picks" actionLabel="See all" />
          <View style={styles.categoryGrid}>
            {copy.categoryTiles.map((item) => (
              <CategoryTile key={item.key} item={item} />
            ))}
          </View>

          <SectionHeader
            title={activeService === 'warehouse' ? 'Most shopped near you' : activeService === 'eatout' ? 'Dining moments' : 'Best offers for you'}
            actionLabel="See all"
          />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.promoRail}>
            {copy.stripCards.map((item) => (
              <PromoTile key={item.key} item={item} />
            ))}
          </ScrollView>

          {Array.isArray(homeDeals) && homeDeals.length ? (
            <>
              <SectionHeader title="Top deals" actionLabel="Browse" />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dealRail}>
                {homeDeals.slice(0, 8).map((item, index) => (
                  <DealCard
                    key={`${item?.id || 'deal'}-${index}`}
                    item={item}
                    onAdd={() => addToCart(item, 1)}
                  />
                ))}
              </ScrollView>
            </>
          ) : null}

          {Array.isArray(recentVendors) && recentVendors.length ? (
            <>
              <SectionHeader title="Reorder from recent" actionLabel="See all" />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dealRail}>
                {recentVendors.slice(0, 6).map((vendor) => (
                  <View key={`recent-${vendor.id}`} style={styles.recentStoreCard}>
                    <TouchableOpacity activeOpacity={0.95} onPress={() => openStore(vendor)} style={styles.recentStoreInner}>
                      <View style={styles.recentStoreIcon}>
                        <Text style={styles.recentStoreIconText}>{initials(vendor?.name)}</Text>
                      </View>
                      <Text numberOfLines={1} style={styles.recentStoreName}>{vendor?.name}</Text>
                      <Text numberOfLines={1} style={styles.recentStoreMeta}>{getVendorEta(vendor, activeService)}</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </ScrollView>
            </>
          ) : null}

          <SectionHeader
            title={activeService === 'warehouse' ? 'Popular stores nearby' : activeService === 'eatout' ? 'Top dining picks' : 'Restaurants to explore'}
            actionLabel="See all"
          />

          {vendorsLoading && !primaryStores.length ? (
            <EmptyState title="Loading stores" subtitle="We are preparing a cleaner storefront for your next release." />
          ) : primaryStores.length ? (
            <View style={styles.storeList}>
              {primaryStores.map((vendor) => (
                <StoreCard
                  key={vendor.id}
                  vendor={vendor}
                  service={activeService}
                  favorite={Boolean(favorites?.[vendor?.id])}
                  onToggleFavorite={() => toggleFavorite(vendor.id)}
                  onPress={() => openStore(vendor)}
                />
              ))}
            </View>
          ) : (
            <EmptyState
              title="No stores found"
              subtitle="Connect the backend or seed demo vendors to preview the production storefront."
            />
          )}
        </View>
      </ScrollView>

      <CartBanner count={cartCount} total={cartTotal} onPress={() => router.push('/cart')} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: BrandPalette.ink,
  },
  heroShell: {
    backgroundColor: BrandPalette.ink,
    paddingHorizontal: 16,
    paddingBottom: 22,
  },
  headerBlock: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 6,
    marginBottom: 18,
  },
  etaText: {
    fontSize: 38,
    lineHeight: 42,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  locationRow: {
    marginTop: 4,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  locationTitle: {
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '800',
    color: BrandPalette.white,
  },
  locationSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 17,
    color: 'rgba(255,255,255,0.74)',
  },
  headerAvatarButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: BrandPalette.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceTabRail: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
  },
  serviceTab: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 12,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  serviceTabActive: {
    backgroundColor: BrandPalette.primary,
    borderColor: BrandPalette.primary,
  },
  serviceTabIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.12)',
    marginBottom: 6,
  },
  serviceTabIconWrapActive: {
    backgroundColor: BrandPalette.white,
  },
  serviceTabLabel: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '800',
    color: BrandPalette.white,
  },
  serviceTabLabelActive: {
    color: BrandPalette.white,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 12,
  },
  searchBar: {
    flex: 1,
    height: 56,
    borderRadius: 18,
    backgroundColor: BrandPalette.white,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 17,
    lineHeight: 21,
    color: BrandPalette.text,
  },
  searchAction: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: BrandPalette.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  chipRailContent: {
    paddingRight: 10,
    gap: 10,
    paddingBottom: 10,
  },
  chipPill: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  chipPillText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '700',
    color: BrandPalette.white,
  },
  heroCard: {
    marginTop: 6,
    borderRadius: 28,
    backgroundColor: BrandPalette.primary,
    padding: 20,
    overflow: 'hidden',
    ...createShadow(0.18, 18, 0),
  },
  heroBadgeRow: {
    flexDirection: 'row',
    marginBottom: 18,
  },
  heroBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  heroBadgeLogo: {
    width: 22,
    height: 22,
  },
  heroBadgeText: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '800',
    color: BrandPalette.white,
  },
  heroEyebrow: {
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '900',
    color: 'rgba(255,255,255,0.85)',
    marginBottom: 10,
  },
  heroTitle: {
    fontSize: 30,
    lineHeight: 36,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  heroSubtitle: {
    marginTop: 16,
    fontSize: 15,
    lineHeight: 21,
    color: 'rgba(255,255,255,0.84)',
    maxWidth: '92%',
  },
  heroFooter: {
    marginTop: 22,
    flexDirection: 'row',
    gap: 12,
  },
  heroPrimaryButton: {
    minWidth: 132,
    borderRadius: 18,
    backgroundColor: BrandPalette.white,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 15,
  },
  heroPrimaryButtonText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '900',
    color: BrandPalette.primary,
    textTransform: 'uppercase',
  },
  heroMetricCard: {
    flex: 1,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 18,
    paddingVertical: 14,
    justifyContent: 'center',
  },
  heroMetricLabel: {
    fontSize: 12,
    lineHeight: 14,
    color: 'rgba(255,255,255,0.8)',
  },
  heroMetricValue: {
    marginTop: 6,
    fontSize: 15,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  bodySurface: {
    marginTop: 16,
    borderTopLeftRadius: 34,
    borderTopRightRadius: 34,
    backgroundColor: BrandPalette.page,
    paddingHorizontal: 16,
    paddingTop: 22,
    paddingBottom: 24,
    minHeight: 620,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  sectionTitle: {
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.ink,
  },
  sectionAction: {
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '800',
    color: BrandPalette.primary,
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 24,
  },
  categoryTile: {
    width: '47%',
    borderRadius: 24,
    paddingVertical: 18,
    paddingHorizontal: 18,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F2E5DB',
    ...createShadow(0.05, 12, 0),
  },
  categoryIconWrap: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
    marginBottom: 14,
  },
  categoryTileLabel: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '800',
    color: BrandPalette.ink,
  },
  promoRail: {
    gap: 12,
    paddingRight: 12,
    marginBottom: 24,
  },
  promoTile: {
    width: 182,
    borderRadius: 22,
    padding: 18,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F1E6DE',
  },
  promoTileTitle: {
    fontSize: 12,
    lineHeight: 16,
    color: BrandPalette.subtle,
  },
  promoTileValue: {
    marginTop: 10,
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.ink,
  },
  promoTileCaption: {
    marginTop: 8,
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.subtle,
  },
  dealRail: {
    gap: 12,
    paddingRight: 12,
    marginBottom: 26,
  },
  dealCard: {
    width: 160,
    borderRadius: 24,
    padding: 16,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F1E6DE',
  },
  dealVisual: {
    width: 54,
    height: 54,
    borderRadius: 27,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  dealBrand: {
    fontSize: 12,
    lineHeight: 14,
    color: BrandPalette.subtle,
    marginBottom: 8,
  },
  dealName: {
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '800',
    color: BrandPalette.ink,
    minHeight: 38,
  },
  dealFooter: {
    marginTop: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  dealPrice: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.ink,
  },
  addMiniButton: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: BrandPalette.primary,
  },
  addMiniButtonText: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  recentStoreCard: {
    width: 122,
  },
  recentStoreInner: {
    borderRadius: 22,
    padding: 16,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F1E6DE',
    alignItems: 'flex-start',
  },
  recentStoreIcon: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  recentStoreIconText: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  recentStoreName: {
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '800',
    color: BrandPalette.ink,
    width: '100%',
  },
  recentStoreMeta: {
    marginTop: 6,
    fontSize: 12,
    lineHeight: 15,
    color: BrandPalette.subtle,
    width: '100%',
  },
  storeList: {
    gap: 16,
  },
  storeCard: {
    borderRadius: 28,
    overflow: 'hidden',
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F1E5DC',
    ...createShadow(0.06, 14, 0),
  },
  storeVisualWrap: {
    height: 164,
    backgroundColor: '#F4E8DE',
    position: 'relative',
  },
  storeImage: {
    width: '100%',
    height: '100%',
  },
  storeImageFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
  },
  storeImageFallbackText: {
    fontSize: 32,
    lineHeight: 36,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  favoriteButton: {
    position: 'absolute',
    right: 14,
    top: 14,
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.92)',
  },
  offerBadge: {
    position: 'absolute',
    left: 14,
    bottom: 14,
    borderRadius: 999,
    backgroundColor: 'rgba(11,13,10,0.72)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  offerBadgeText: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '800',
    color: BrandPalette.white,
  },
  storeBody: {
    padding: 16,
  },
  storeTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  storeName: {
    flex: 1,
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.ink,
  },
  ratingPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: 999,
    backgroundColor: BrandPalette.success,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  ratingPillText: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  storeMeta: {
    marginTop: 10,
    fontSize: 14,
    lineHeight: 19,
    color: BrandPalette.subtle,
  },
  storeFooter: {
    marginTop: 14,
    flexDirection: 'row',
    alignItems: 'center',
  },
  storeEta: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '800',
    color: BrandPalette.ink,
  },
  storeDot: {
    marginHorizontal: 8,
    color: BrandPalette.subtle,
  },
  storeFee: {
    flex: 1,
    fontSize: 13,
    lineHeight: 16,
    color: BrandPalette.subtle,
  },
  emptyCard: {
    borderRadius: 24,
    padding: 24,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#F1E5DC',
    alignItems: 'center',
    gap: 10,
  },
  emptyTitle: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.ink,
  },
  emptySubtitle: {
    fontSize: 13,
    lineHeight: 18,
    color: BrandPalette.subtle,
    textAlign: 'center',
  },
  cartBanner: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 18,
    borderRadius: 22,
    backgroundColor: BrandPalette.ink,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    ...createShadow(0.2, 16, 0),
  },
  cartBannerTitle: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  cartBannerSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 16,
    color: 'rgba(255,255,255,0.72)',
  },
  cartBannerAction: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  cartBannerActionText: {
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.white,
  },
});