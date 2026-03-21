import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
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
import { useGrabBasket } from '../../../App';

const COLORS = {
  page: '#f6f7fb',
  card: '#ffffff',
  text: '#111827',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e8ecf3',
  success: '#119b56',
  successSoft: '#e8f8ee',
  orange: '#ff6d00',
  orangeSoft: '#fff1e7',
  yellow: '#ffcf33',
  blue: '#0b57d0',
  blueSoft: '#eaf2ff',
  purple: '#6d28d9',
  dark: '#050816',
  darkCard: '#10182c',
  darkBorder: '#1f2c48',
  darkMuted: '#c7d2e8',
};

const SERVICE_THEME = {
  food: {
    page: '#f6f7fb',
    hero: '#5b18cf',
    heroAlt: '#7b36f1',
    heroAccent: '#ffd84d',
    primary: '#ff6d00',
    primarySoft: '#fff1e7',
    searchBg: '#ffffff',
    textOnHero: '#ffffff',
    pillBg: 'rgba(255,255,255,0.14)',
    pillText: '#ffffff',
    cartBg: '#111827',
  },
  warehouse: {
    page: '#f7fbff',
    hero: '#0a2f75',
    heroAlt: '#1548a0',
    heroAccent: '#cfe0ff',
    primary: '#0b57d0',
    primarySoft: '#edf4ff',
    searchBg: '#ffffff',
    textOnHero: '#ffffff',
    pillBg: 'rgba(255,255,255,0.14)',
    pillText: '#ffffff',
    cartBg: '#0b57d0',
  },
  eatout: {
    page: '#fbfbfd',
    hero: '#5d21b5',
    heroAlt: '#7f46e7',
    heroAccent: '#ffd6ad',
    primary: '#ff7a00',
    primarySoft: '#fff3e8',
    searchBg: '#ffffff',
    textOnHero: '#ffffff',
    pillBg: 'rgba(255,255,255,0.14)',
    pillText: '#ffffff',
    cartBg: '#111827',
  },
  scenes: {
    page: '#050816',
    hero: '#0d1222',
    heroAlt: '#1a2440',
    heroAccent: '#ff7cab',
    primary: '#ffffff',
    primarySoft: 'rgba(255,255,255,0.10)',
    searchBg: '#10182c',
    textOnHero: '#ffffff',
    pillBg: 'rgba(255,255,255,0.10)',
    pillText: '#ffffff',
    cartBg: '#ffffff',
  },
};

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
  if (service === 'eatout') return 'Table in 10-15 mins';
  if (service === 'scenes') return 'Entry slots today';
  if (service === 'warehouse') return '5-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function getDeliveryLabel(vendor, service = 'food') {
  if (service === 'eatout') return 'Extra bank offers';
  if (service === 'scenes') return 'Instant confirmation';
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return '₹19 delivery';
  return '₹29 delivery';
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

function pickEmoji(name = '', service = 'food') {
  const value = String(name || '').toLowerCase();

  if (service === 'eatout' && /(table|booking|seat)/.test(value)) return '🍽️';
  if (service === 'scenes' && /(ticket|entry|pass)/.test(value)) return '🎟️';
  if (/(curd|milk|paneer|dairy|yogurt)/.test(value)) return '🥛';
  if (/(chip|snack|cracker)/.test(value)) return '🥔';
  if (/(jam|berry|fruit)/.test(value)) return '🍓';
  if (/(chocolate|candy|bar)/.test(value)) return '🍫';
  if (/(bread|toast|bun|bakery|naan)/.test(value)) return '🍞';
  if (/(drink|juice|cola|water|tea|coffee)/.test(value)) return '🥤';
  if (/(vegetable|tomato|onion|potato)/.test(value)) return '🥬';
  if (/(rice|dal|flour|atta)/.test(value)) return '🍚';
  if (/(beauty|cream|soap|shampoo|sunscreen)/.test(value)) return '🧴';
  if (/(pizza|burger|biryani|sandwich|meal|paneer butter masala|fries)/.test(value)) return '🍔';
  if (/(dessert|cake|brownie|ice cream|truffle)/.test(value)) return '🍰';
  if (/(pottery|workshop|maker)/.test(value)) return '🏺';
  if (/(comedy|show|performance)/.test(value)) return '🎤';

  return service === 'warehouse' ? '🛍️' : service === 'scenes' ? '🎟️' : '🍽️';
}

function getProductBadge(product, service = 'food') {
  const name = String(product?.name || '').toLowerCase();
  const price = Number(product?.price || 0);

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

function deriveCategory(product, service = 'food') {
  const value = `${product?.name || ''} ${product?.description || ''}`.toLowerCase();

  if (service === 'warehouse') {
    if (/(milk|curd|paneer|dairy|egg)/.test(value)) return 'Daily essentials';
    if (/(fruit|vegetable|greens|tomato|onion|potato)/.test(value)) return 'Fresh';
    if (/(chip|snack|cracker|chocolate|biscuit)/.test(value)) return 'Snacks';
    if (/(drink|juice|cola|water|tea|coffee)/.test(value)) return 'Beverages';
    if (/(soap|shampoo|cream|beauty)/.test(value)) return 'Personal care';
    if (/(bread|toast|bun|bakery)/.test(value)) return 'Bakery';
    return 'Essentials';
  }

  if (service === 'eatout') {
    if (/(drink|juice|mocktail|coffee|tea)/.test(value)) return 'Drinks';
    if (/(dessert|cake|brownie|ice cream)/.test(value)) return 'Desserts';
    if (/(starter|fries|side)/.test(value)) return 'Starters';
    if (/(combo|platter|meal|burger|biryani|pizza|pasta|naan|paneer|curry)/.test(value)) return 'Mains';
    return 'Chef picks';
  }

  if (service === 'scenes') {
    if (/(vip|premium)/.test(value)) return 'Premium';
    if (/(workshop|class|lab)/.test(value)) return 'Workshops';
    if (/(show|night|comedy|music|gig|entry|ticket|pass)/.test(value)) return 'Tickets';
    return 'Experiences';
  }

  if (/(drink|juice|coffee|tea)/.test(value)) return 'Beverages';
  if (/(dessert|cake|brownie|ice cream|sweet|truffle)/.test(value)) return 'Desserts';
  if (/(naan|bread|bun|roll)/.test(value)) return 'Breads';
  if (/(fries|starter|side)/.test(value)) return 'Sides';
  return 'Mains';
}

function sortRecommended(products = []) {
  return [...products].sort((a, b) => {
    const aScore = Number(a?.price || 0) + String(a?.name || '').length * 0.1;
    const bScore = Number(b?.price || 0) + String(b?.name || '').length * 0.1;
    return aScore - bScore;
  });
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

  useEffect(() => {
    if (vendor?.id) rememberStore(vendor.id);
  }, [vendor, rememberStore]);

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

  const recommendedProducts = useMemo(() => sortRecommended(products).slice(0, 4), [products]);

  const sameVendorCart = cartCount > 0 && String(cart.vendorId) === String(vendor?.id);
  const otherVendorCart = cartCount > 0 && cart.vendorId && String(cart.vendorId) !== String(vendor?.id);

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
                onPress={() =>
                  Alert.alert('Share', 'Connect this action to native share for deep-linking the store.')
                }>
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
            <View style={styles.heroTitleRow}>
              <View style={[styles.heroMonogram, { backgroundColor: theme.pillBg }]}>
                <Text style={styles.heroMonogramText}>{initials(vendor.name)}</Text>
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
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.10)',
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  heroMetaText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },

  content: {
    marginTop: -10,
    backgroundColor: COLORS.page,
    borderTopLeftRadius: 26,
    borderTopRightRadius: 26,
    paddingHorizontal: 16,
    paddingTop: 16,
  },
  contentDark: {
    backgroundColor: COLORS.dark,
  },

  searchBar: {
    minHeight: 56,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
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

  warningBanner: {
    marginTop: 12,
    borderRadius: 16,
    backgroundColor: '#fff7ed',
    borderWidth: 1,
    borderColor: '#fed7aa',
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

  savingsCard: {
    marginTop: 12,
    borderRadius: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
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
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 10,
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
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
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
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    flexDirection: 'row',
    gap: 12,
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
    height: 38,
    borderRadius: 12,
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
    height: 38,
    borderRadius: 12,
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
    borderRadius: 22,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#0f172a',
    shadowOpacity: 0.18,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 14,
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