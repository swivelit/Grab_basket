import React, { useEffect, useMemo, useState } from 'react';
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
import { buildApiUrl } from '../../config';
import InlineErrorCard from '../../components/inline-error-card';
import { useGrabBasket } from '../../../App';

const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');

const SERVICE_TABS = {
  food: ['Recommended', 'Bestsellers', 'Meals', 'Combos'],
  warehouse: ['Top picks', 'Snacks', 'Fresh', 'Essentials'],
  eatout: ['Popular', 'Chef picks', 'Desserts', 'Beverages'],
  scenes: ['Popular', 'Tonight', 'Weekend', 'Premium'],
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function resolveMediaUrl(value = '') {
  const raw = String(value || '').trim();
  if (!raw) return '';
  if (/^https?:\/\//i.test(raw)) return raw;

  try {
    return buildApiUrl(raw.startsWith('/') ? raw : `/${raw}`);
  } catch {
    return raw;
  }
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

function getRating(vendor) {
  const rating = Number(vendor?.avg_rating || 0);
  if (Number.isFinite(rating) && rating > 0) return rating.toFixed(1);
  return '4.3';
}

function getRatingCount(vendor) {
  const total = Number(vendor?.total_ratings || 0);
  if (total > 0) return `${total}+ ratings`;
  return '100+ ratings';
}

function getEta(vendor, service = 'food') {
  const eta = Number(vendor?.estimated_delivery_time_min || 0);

  if (service === 'eatout') {
    return eta > 0 ? `Table in ${Math.max(10, eta)} mins` : 'Book instantly';
  }

  if (service === 'scenes') return 'Instant confirmation';

  if (eta > 0) {
    if (eta <= 15) return `${Math.round(eta)} mins`;
    return `${Math.max(10, Math.round(eta - 5))}-${Math.round(eta)} mins`;
  }

  return service === 'warehouse' ? '7-15 mins' : '20-30 mins';
}

function getOfferCopy(service = 'food') {
  if (service === 'warehouse') return 'Free delivery above ₹199';
  if (service === 'eatout') return 'Flat 50% off on dining bills';
  if (service === 'scenes') return 'Instant booking with premium access';
  return 'Free delivery on your first order';
}

function getVendorSubtitle(vendor) {
  return [vendor?.cuisine_tags, vendor?.description, vendor?.address]
    .filter(Boolean)
    .join(' · ');
}

function getProductTags(item, service = 'food') {
  const tags = [];

  if (item?.badge_text) tags.push(String(item.badge_text).trim());
  if (item?.is_featured) tags.push('Recommended');
  if (Number(item?.avg_rating || 0) >= 4.5) tags.push('Top rated');
  if (service === 'warehouse' && Number(item?.stock_qty || 0) > 0) tags.push('In stock');
  if (service === 'food' && Number(item?.original_price || 0) > Number(item?.price || 0)) {
    tags.push('Best value');
  }

  return tags.slice(0, 2);
}

function deriveSections(products = [], service = 'food') {
  const categoryMap = new Map();

  products.forEach((item) => {
    const category = String(item?.category || item?.subcategory || '').trim();
    const fallback = service === 'warehouse' ? 'Essentials' : 'Recommended';
    const key = category || fallback;

    if (!categoryMap.has(key)) {
      categoryMap.set(key, []);
    }

    categoryMap.get(key).push(item);
  });

  const sections = Array.from(categoryMap.entries()).map(([title, items]) => ({
    title,
    items,
  }));

  if (!sections.length) {
    return [
      {
        title: service === 'warehouse' ? 'Essentials' : 'Recommended',
        items: [],
      },
    ];
  }

  return sections;
}

function ProductCard({ item, qty, onAdd, onRemove, service, theme }) {
  const imageUri = resolveMediaUrl(item?.image_url || item?.thumbnail_url);
  const hasOffer = Number(item?.original_price || 0) > Number(item?.price || 0);
  const tags = getProductTags(item, service);

  return (
    <View style={styles.productCard}>
      <View style={styles.productCopy}>
        {tags.length ? (
          <View style={styles.tagRow}>
            {tags.map((tag) => (
              <View
                key={`${item?.id}-${tag}`}
                style={[styles.tagPill, { backgroundColor: theme.primarySoft }]}>
                <Text style={[styles.tagPillText, { color: theme.primary }]}>{tag}</Text>
              </View>
            ))}
          </View>
        ) : null}

        <Text numberOfLines={2} style={styles.productTitle}>
          {item?.name}
        </Text>

        <Text numberOfLines={2} style={styles.productDescription}>
          {item?.description ||
            (service === 'warehouse'
              ? 'Quick everyday essential.'
              : 'Freshly prepared and packed with care.')}
        </Text>

        <View style={styles.priceRow}>
          <Text style={styles.productPrice}>{money(item?.price)}</Text>
          {hasOffer ? (
            <Text style={styles.productPriceStrike}>{money(item?.original_price)}</Text>
          ) : null}
        </View>
      </View>

      <View style={styles.productVisualCol}>
        <View
          style={[
            styles.productImageWrap,
            { backgroundColor: theme.heroAlt || '#F3F3F3' },
          ]}>
          {imageUri ? (
            <Image
              source={{ uri: imageUri }}
              style={styles.productImage}
              contentFit="cover"
              transition={180}
            />
          ) : (
            <View style={styles.productImageFallback}>
              <Ionicons name="restaurant-outline" size={24} color={theme.primary} />
            </View>
          )}
        </View>

        {qty > 0 ? (
          <View style={styles.qtyControl}>
            <TouchableOpacity activeOpacity={0.92} onPress={onRemove} style={styles.qtyAction}>
              <Ionicons name="remove" size={16} color={BrandPalette.primary} />
            </TouchableOpacity>
            <Text style={styles.qtyValue}>{qty}</Text>
            <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.qtyAction}>
              <Ionicons name="add" size={16} color={BrandPalette.primary} />
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.addButton}>
            <Text style={styles.addButtonText}>ADD</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

export default function StoreScreen() {
  const router = useRouter();
  const { vendorId } = useLocalSearchParams();
  const {
    activeService,
    vendors,
    loadProducts,
    loadVendors,
    favorites,
    toggleFavorite,
    addToCart,
    cart,
    cartCount,
    cartTotal,
    updateQty,
    rememberStore,
    inlineErrors,
  } = useGrabBasket();

  const theme = StoreServiceThemes[activeService] || StoreServiceThemes.food;
  const isLightHero = activeService !== 'food';
  const heroTextColor = theme.textOnHero || '#FFFFFF';
  const heroSubColor = isLightHero
    ? 'rgba(20,18,16,0.68)'
    : 'rgba(255,255,255,0.76)';
  const heroCardBg = isLightHero
    ? 'rgba(255,255,255,0.6)'
    : 'rgba(255,255,255,0.12)';
  const softPillBg = isLightHero
    ? 'rgba(20,18,16,0.08)'
    : 'rgba(255,255,255,0.14)';
  const iconButtonBg = isLightHero
    ? 'rgba(255,255,255,0.54)'
    : 'rgba(255,255,255,0.14)';
  const cartBarBg =
    activeService === 'scenes' ? BrandPalette.accent : theme.cartBg || BrandPalette.ink;
  const cartBarTextColor =
    activeService === 'scenes' ? BrandPalette.text : '#FFFFFF';
  const cartBarSubColor =
    activeService === 'scenes'
      ? 'rgba(20,18,16,0.7)'
      : 'rgba(255,255,255,0.78)';

  const [searchValue, setSearchValue] = useState('');
  const [activeTab, setActiveTab] = useState(
    (SERVICE_TABS[activeService] || SERVICE_TABS.food)[0]
  );
  const [loading, setLoading] = useState(true);
  const [products, setProducts] = useState([]);

  const vendor = useMemo(() => {
    return (Array.isArray(vendors) ? vendors : []).find(
      (item) => String(item?.id) === String(vendorId)
    ) || null;
  }, [vendorId, vendors]);

  useEffect(() => {
    setActiveTab((SERVICE_TABS[activeService] || SERVICE_TABS.food)[0]);
  }, [activeService]);

  useEffect(() => {
    let active = true;

    if (vendor) return undefined;

    (async () => {
      setLoading(true);
      await loadVendors().catch(() => {});
      if (active) setLoading(false);
    })();

    return () => {
      active = false;
    };
  }, [loadVendors, vendor]);

  useEffect(() => {
    if (!vendor) return undefined;

    let active = true;

    (async () => {
      setLoading(true);
      rememberStore(vendor.id);
      const nextProducts = await loadProducts(vendor, searchValue);
      if (active) {
        setProducts(Array.isArray(nextProducts) ? nextProducts : []);
        setLoading(false);
      }
    })();

    return () => {
      active = false;
    };
  }, [loadProducts, rememberStore, searchValue, vendor]);

  const sections = useMemo(
    () => deriveSections(products, activeService),
    [activeService, products]
  );

  const featuredProducts = useMemo(() => products.slice(0, 8), [products]);

  const heroImage = resolveMediaUrl(
    vendor?.cover_image_url || vendor?.banner_image_url || vendor?.logo_image_url
  );
  const favorite = Boolean(favorites?.[vendor?.id]);
  const hasForeignCart = cart?.vendorId && String(cart.vendorId) !== String(vendor?.id);

  const handleShare = async () => {
    if (!vendor) return;

    try {
      await Share.share({
        message: `${vendor.name}\n${getVendorSubtitle(vendor)}\nOrder on GrabBasket.`,
      });
    } catch {
      // ignore share cancellation
    }
  };

  const handleAdd = (item) => {
    if (!vendor || !item) return;
    addToCart(vendor, item);
  };

  const handleRemove = (item) => {
    const currentQty = Number(cart.items?.[item.id]?.qty || 0);
    updateQty(item.id, currentQty - 1);
  };

  if (!vendor && loading) {
    return (
      <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
        <StatusBar barStyle={activeService === 'food' ? 'light-content' : 'dark-content'} />
        <View style={styles.loaderState}>
          <ActivityIndicator color={theme.primary} />
          <Text style={styles.loaderText}>Loading store...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!vendor && !loading) {
    return (
      <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.centerState}>
          <Text style={styles.centerTitle}>Store not found</Text>
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.primaryCta}
            onPress={() => router.replace('/')}>
            <Text style={styles.primaryCtaText}>Back to home</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle={activeService === 'food' ? 'light-content' : 'dark-content'} />

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        <View style={[styles.heroSection, { backgroundColor: theme.hero }]}>
          <View style={styles.heroTopRow}>
            <TouchableOpacity
              activeOpacity={0.92}
              style={[styles.roundIconButton, { backgroundColor: iconButtonBg }]}
              onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
              <Ionicons name="chevron-back" size={20} color={heroTextColor} />
            </TouchableOpacity>

            <View style={styles.heroTopActions}>
              <TouchableOpacity
                activeOpacity={0.92}
                style={[styles.roundIconButton, { backgroundColor: iconButtonBg }]}
                onPress={handleShare}>
                <Ionicons name="share-social-outline" size={18} color={heroTextColor} />
              </TouchableOpacity>

              <TouchableOpacity
                activeOpacity={0.92}
                style={[styles.roundIconButton, { backgroundColor: iconButtonBg }]}
                onPress={() => vendor?.id && toggleFavorite(vendor.id)}>
                <Ionicons
                  name={favorite ? 'heart' : 'heart-outline'}
                  size={18}
                  color={favorite ? '#FF6D77' : heroTextColor}
                />
              </TouchableOpacity>
            </View>
          </View>

          <View style={[styles.heroCard, { backgroundColor: heroCardBg }]}>
            <View style={styles.heroIdentityRow}>
              <View style={styles.logoChip}>
                {heroImage ? (
                  <Image
                    source={{ uri: heroImage }}
                    style={styles.logoImage}
                    contentFit="cover"
                    transition={180}
                  />
                ) : (
                  <Text style={styles.logoFallback}>{initials(vendor?.name)}</Text>
                )}
              </View>

              <View style={{ flex: 1 }}>
                <Text numberOfLines={2} style={[styles.vendorTitle, { color: heroTextColor }]}>
                  {vendor?.name}
                </Text>
                <Text
                  numberOfLines={2}
                  style={[styles.vendorSubtitle, { color: heroSubColor }]}>
                  {getVendorSubtitle(vendor)}
                </Text>
              </View>
            </View>

            <View style={styles.heroMetricsRow}>
              <View style={styles.metricPill}>
                <Ionicons name="star" size={14} color="#FFFFFF" />
                <Text style={styles.metricPillText}>
                  {getRating(vendor)} · {getRatingCount(vendor)}
                </Text>
              </View>

              <View style={[styles.metricSoftPill, { backgroundColor: softPillBg }]}>
                <Text style={[styles.metricSoftText, { color: heroTextColor }]}>
                  {getEta(vendor, activeService)}
                </Text>
              </View>

              <View style={[styles.metricSoftPill, { backgroundColor: softPillBg }]}>
                <Text style={[styles.metricSoftText, { color: heroTextColor }]}>
                  {vendor?.distance_km
                    ? `${Number(vendor.distance_km).toFixed(1)} km`
                    : 'Around you'}
                </Text>
              </View>
            </View>

            <View style={styles.offerBanner}>
              <View style={styles.offerLogoWrap}>
                <Image source={BRAND_LOGO} style={styles.offerLogo} contentFit="contain" />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.offerTitle}>{getOfferCopy(activeService)}</Text>
                <Text style={styles.offerSubtitle}>
                  Extra savings, cleaner menu and faster checkout.
                </Text>
              </View>
            </View>
          </View>
        </View>

        <View style={styles.bodySection}>
          {hasForeignCart ? (
            <InlineErrorCard
              title="Your cart has items from another store"
              message="Adding items here will replace the current basket automatically."
            />
          ) : null}

          {inlineErrors?.products ? (
            <InlineErrorCard
              title="Products could not be refreshed"
              message={inlineErrors.products}
            />
          ) : null}

          <View style={styles.searchWrap}>
            <Ionicons name="search-outline" size={20} color="#7B7B7B" />
            <TextInput
              value={searchValue}
              onChangeText={setSearchValue}
              placeholder={
                activeService === 'warehouse'
                  ? 'Search essentials, snacks, brands'
                  : 'Search dishes and items'
              }
              placeholderTextColor="#8E8E8E"
              style={styles.searchInput}
            />
            {searchValue ? (
              <TouchableOpacity activeOpacity={0.92} onPress={() => setSearchValue('')}>
                <Ionicons name="close-circle" size={18} color="#9A9A9A" />
              </TouchableOpacity>
            ) : null}
          </View>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabRow}>
            {(SERVICE_TABS[activeService] || SERVICE_TABS.food).map((tab) => {
              const active = tab === activeTab;
              return (
                <TouchableOpacity
                  key={tab}
                  activeOpacity={0.92}
                  onPress={() => setActiveTab(tab)}
                  style={[
                    styles.categoryChip,
                    active && {
                      backgroundColor: theme.primarySoft,
                      borderColor: theme.primary,
                    },
                  ]}>
                  <Text
                    style={[
                      styles.categoryChipText,
                      active && { color: theme.primary },
                    ]}>
                    {tab}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          {loading ? (
            <View style={styles.loaderState}>
              <ActivityIndicator color={theme.primary} />
              <Text style={styles.loaderText}>Loading menu...</Text>
            </View>
          ) : (
            <>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Recommended for you</Text>
                <Text style={styles.sectionAction}>{products.length} items</Text>
              </View>

              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.featuredRail}>
                {featuredProducts.map((item) => {
                  const qty = Number(cart.items?.[item.id]?.qty || 0);

                  return (
                    <View key={item.id} style={styles.featuredCard}>
                      <View
                        style={[
                          styles.featuredIconWrap,
                          { backgroundColor: theme.primarySoft },
                        ]}>
                        <Ionicons name="sparkles-outline" size={18} color={theme.primary} />
                      </View>
                      <Text numberOfLines={2} style={styles.featuredTitle}>
                        {item?.name}
                      </Text>
                      <Text style={styles.featuredPrice}>{money(item?.price)}</Text>
                      {qty > 0 ? (
                        <Text style={styles.featuredQty}>In cart · {qty}</Text>
                      ) : (
                        <Text style={styles.featuredHint}>Popular pick</Text>
                      )}
                    </View>
                  );
                })}
              </ScrollView>

              {sections.map((section) => (
                <View key={section.title} style={styles.menuSection}>
                  <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>{section.title}</Text>
                    <Text style={styles.sectionAction}>{section.items.length} items</Text>
                  </View>

                  {section.items.length ? (
                    section.items.map((item) => (
                      <ProductCard
                        key={item.id}
                        item={item}
                        service={activeService}
                        theme={theme}
                        qty={Number(cart.items?.[item.id]?.qty || 0)}
                        onAdd={() => handleAdd(item)}
                        onRemove={() => handleRemove(item)}
                      />
                    ))
                  ) : (
                    <View style={styles.emptyMenuCard}>
                      <Text style={styles.emptyMenuTitle}>No items in this section yet</Text>
                      <Text style={styles.emptyMenuSubtitle}>
                        Try another tab or clear the search.
                      </Text>
                    </View>
                  )}
                </View>
              ))}
            </>
          )}
        </View>
      </ScrollView>

      {cartCount > 0 ? (
        <TouchableOpacity
          activeOpacity={0.94}
          style={[styles.cartBar, { backgroundColor: cartBarBg }]}
          onPress={() => router.push('/cart')}>
          <View>
            <Text style={[styles.cartBarTitle, { color: cartBarTextColor }]}>
              {cartCount} items added
            </Text>
            <Text style={[styles.cartBarSubtitle, { color: cartBarSubColor }]}>
              View cart · {money(cartTotal)}
            </Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color={cartBarTextColor} />
        </TouchableOpacity>
      ) : null}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 100,
  },
  heroSection: {
    paddingHorizontal: 18,
    paddingBottom: 28,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 18,
  },
  heroTopActions: {
    flexDirection: 'row',
    gap: 10,
  },
  roundIconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroCard: {
    borderRadius: 28,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.16)',
  },
  heroIdentityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    marginBottom: 16,
  },
  logoChip: {
    width: 68,
    height: 68,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  logoImage: {
    width: '100%',
    height: '100%',
  },
  logoFallback: {
    color: BrandPalette.text,
    fontSize: 26,
    fontWeight: '900',
  },
  vendorTitle: {
    fontSize: 26,
    lineHeight: 30,
    fontWeight: '900',
    marginBottom: 6,
  },
  vendorSubtitle: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  heroMetricsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 16,
  },
  metricPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    backgroundColor: '#18A558',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  metricPillText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  metricSoftPill: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  metricSoftText: {
    fontSize: 12,
    fontWeight: '700',
  },
  offerBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 14,
  },
  offerLogoWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  offerLogo: {
    width: 22,
    height: 22,
  },
  offerTitle: {
    color: BrandPalette.text,
    fontSize: 14,
    fontWeight: '900',
    marginBottom: 3,
  },
  offerSubtitle: {
    color: BrandPalette.textMuted,
    fontSize: 12,
    fontWeight: '600',
  },
  bodySection: {
    paddingHorizontal: 18,
    paddingTop: 18,
    gap: 16,
  },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
    paddingVertical: 14,
    ...createShadow(0.06, 12, 6),
  },
  searchInput: {
    flex: 1,
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '600',
  },
  tabRow: {
    gap: 10,
    paddingBottom: 2,
  },
  categoryChip: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  categoryChipText: {
    color: BrandPalette.textMuted,
    fontSize: 12,
    fontWeight: '800',
  },
  loaderState: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingVertical: 36,
  },
  loaderText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    fontWeight: '700',
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  sectionTitle: {
    color: BrandPalette.text,
    fontSize: 20,
    fontWeight: '900',
  },
  sectionAction: {
    color: BrandPalette.primary,
    fontSize: 12,
    fontWeight: '800',
  },
  featuredRail: {
    gap: 12,
  },
  featuredCard: {
    width: 154,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 14,
    ...createShadow(0.06, 12, 6),
  },
  featuredIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  featuredTitle: {
    color: BrandPalette.text,
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '800',
    minHeight: 40,
    marginBottom: 8,
  },
  featuredPrice: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '900',
    marginBottom: 6,
  },
  featuredQty: {
    color: BrandPalette.primary,
    fontSize: 12,
    fontWeight: '800',
  },
  featuredHint: {
    color: BrandPalette.textMuted,
    fontSize: 12,
    fontWeight: '700',
  },
  menuSection: {
    gap: 12,
  },
  productCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 16,
    flexDirection: 'row',
    gap: 14,
    ...createShadow(0.06, 14, 8),
  },
  productCopy: {
    flex: 1,
  },
  tagRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 10,
  },
  tagPill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  tagPillText: {
    fontSize: 11,
    fontWeight: '800',
  },
  productTitle: {
    color: BrandPalette.text,
    fontSize: 17,
    lineHeight: 22,
    fontWeight: '900',
    marginBottom: 6,
  },
  productDescription: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
    marginBottom: 10,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  productPrice: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '900',
  },
  productPriceStrike: {
    color: '#A0A0A0',
    fontSize: 12,
    fontWeight: '700',
    textDecorationLine: 'line-through',
  },
  productVisualCol: {
    width: 112,
    alignItems: 'center',
  },
  productImageWrap: {
    width: 112,
    height: 104,
    borderRadius: 18,
    overflow: 'hidden',
    marginBottom: 12,
  },
  productImage: {
    width: '100%',
    height: '100%',
  },
  productImageFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButton: {
    minWidth: 92,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: BrandPalette.primary,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
  },
  addButtonText: {
    color: BrandPalette.primary,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyControl: {
    minWidth: 92,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#F3C9CB',
    backgroundColor: '#FFF7F7',
    overflow: 'hidden',
  },
  qtyAction: {
    width: 30,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyValue: {
    color: BrandPalette.text,
    fontSize: 14,
    fontWeight: '900',
  },
  emptyMenuCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 18,
    ...createShadow(0.05, 10, 5),
  },
  emptyMenuTitle: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: 5,
  },
  emptyMenuSubtitle: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  cartBar: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 16,
    borderRadius: 24,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    ...createShadow(0.18, 18, 10),
  },
  cartBarTitle: {
    fontSize: 15,
    fontWeight: '900',
    marginBottom: 3,
  },
  cartBarSubtitle: {
    fontSize: 12,
    fontWeight: '700',
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  centerTitle: {
    color: BrandPalette.text,
    fontSize: 22,
    fontWeight: '900',
    marginBottom: 16,
  },
  primaryCta: {
    backgroundColor: BrandPalette.primary,
    borderRadius: 16,
    paddingHorizontal: 18,
    paddingVertical: 12,
  },
  primaryCtaText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '900',
  },
});