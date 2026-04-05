import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

import InlineErrorCard from '../components/inline-error-card';
import { MAX_ORDERS } from '../domains/grab-basket-utils';
import { useGrabBasket } from '../providers/grab-basket-provider';

const COLORS = {
  page: '#F3F3F3',
  surface: '#FFFFFF',
  border: '#E5E5E5',
  text: '#171717',
  muted: '#6B7280',
  subtle: '#9CA3AF',
  primary: '#EF4444',
  primarySoft: '#FFE8EA',
  accent: '#F97316',
  success: '#10B981',
  dark: '#231815',
};

const FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'favorites', label: 'Favourites' },
  { key: 'under200', label: 'Price < ₹200' },
  { key: 'between200And350', label: '₹200 - ₹350' },
  { key: 'above350', label: 'Price > ₹350' },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
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

function formatDate(value) {
  if (!value) return 'Recently ordered';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Recently ordered';

  return new Intl.DateTimeFormat('en-IN', {
    day: 'numeric',
    month: 'short',
  }).format(date);
}

function getEtaText(vendor, order) {
  const eta = Number(order?.delivery_eta_minutes || vendor?.estimated_delivery_time_min || 0);
  if (eta > 0) return `${eta}-${eta + 5} mins`;
  return '20-25 mins';
}

function getVendorImage(vendor) {
  return vendor?.cover_image_url || vendor?.banner_image_url || vendor?.logo_image_url || '';
}

function getVendorBadge(vendor, lastOrder) {
  const rating = Number(vendor?.avg_rating || 0);

  if (rating > 0) return `${rating.toFixed(1)} rating`;
  if (vendor?.price_bucket) return `${vendor.price_bucket} store`;
  if (lastOrder) return `Last ordered ${formatDate(lastOrder?.updated_at || lastOrder?.created_at)}`;

  return 'Popular picks';
}

function getVendorOffer(vendor) {
  if (Number(vendor?.min_order_amount || 0) > 0) {
    return `Items from ${money(vendor.min_order_amount)}`;
  }

  if (vendor?.accepts_cod === false) {
    return 'Online payment only';
  }

  if (vendor?.open_now === false) {
    return 'Currently closed';
  }

  return 'Quick reorder picks';
}

function matchesPriceFilter(item, filterKey) {
  const price = Number(item?.price || 0);

  if (filterKey === 'under200') return price > 0 && price < 200;
  if (filterKey === 'between200And350') return price >= 200 && price <= 350;
  if (filterKey === 'above350') return price > 350;

  return true;
}

function scoreProduct(item, recentSignals) {
  let score = 0;

  if (recentSignals.ids.has(String(item?.id))) score += 100;
  if (recentSignals.names.has(normalizeText(item?.name))) score += 80;
  if (Number(item?.original_price || 0) > Number(item?.price || 0)) score += 10;
  if (item?.is_available !== false) score += 6;
  score += Math.max(0, 500 - Number(item?.price || 0)) / 100;

  return score;
}

function buildRecentSignals(orders = []) {
  const ids = new Set();
  const names = new Set();

  (orders || []).forEach((order) => {
    (order?.items || []).forEach((item) => {
      if (item?.product_id != null) ids.add(String(item.product_id));
      if (item?.id != null) ids.add(String(item.id));
      if (item?.name) names.add(normalizeText(item.name));
      if (item?.product_name) names.add(normalizeText(item.product_name));
    });
  });

  return { ids, names };
}

function buildOrderGroups(orders = []) {
  const byVendor = new Map();

  (orders || []).forEach((order) => {
    const key = String(order?.vendor_id || '');
    if (!key) return;

    if (!byVendor.has(key)) byVendor.set(key, []);
    byVendor.get(key).push(order);
  });

  return byVendor;
}

function buildVendorPool({ orderHistory = [], recentVendors = [], vendors = [] } = {}) {
  const byId = new Map();
  const orderedIds = [];

  (orderHistory || []).forEach((order) => {
    const key = String(order?.vendor_id || '');
    if (key) orderedIds.push(key);
  });

  [...recentVendors, ...vendors].forEach((vendor) => {
    const key = String(vendor?.id || '');
    if (key && !byId.has(key)) byId.set(key, vendor);
  });

  const orderedVendors = [];
  orderedIds.forEach((id) => {
    const vendor = byId.get(id);
    if (vendor && !orderedVendors.some((item) => String(item?.id) === id)) {
      orderedVendors.push(vendor);
    }
  });

  const fallback = [...recentVendors, ...vendors].filter(
    (vendor) => !orderedVendors.some((item) => String(item?.id) === String(vendor?.id))
  );

  return [...orderedVendors, ...fallback].filter(Boolean).slice(0, 10);
}

function SearchBar({ value, onChangeText }) {
  return (
    <View style={styles.searchWrap}>
      <Ionicons name="search-outline" size={22} color={COLORS.subtle} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder="Search by restaurant or dish"
        placeholderTextColor={COLORS.subtle}
        style={styles.searchInput}
      />
    </View>
  );
}

function FilterChip({ label, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.9}
      onPress={onPress}
      style={[styles.filterChip, active && styles.filterChipActive]}>
      <Text style={[styles.filterChipText, active && styles.filterChipTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function QtyButton({ vendor, item, qty, onAdd, onRemove }) {
  if (!item?.id) {
    return (
      <TouchableOpacity activeOpacity={0.9} onPress={() => onAdd(vendor, item)} style={styles.addButton}>
        <Text style={styles.addButtonText}>VIEW</Text>
      </TouchableOpacity>
    );
  }

  if (qty > 0) {
    return (
      <View style={styles.qtyControl}>
        <TouchableOpacity activeOpacity={0.9} onPress={() => onRemove(item)} style={styles.qtyAction}>
          <Ionicons name="remove" size={16} color={COLORS.success} />
        </TouchableOpacity>
        <Text style={styles.qtyValue}>{qty}</Text>
        <TouchableOpacity activeOpacity={0.9} onPress={() => onAdd(vendor, item)} style={styles.qtyAction}>
          <Ionicons name="add" size={16} color={COLORS.success} />
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <TouchableOpacity activeOpacity={0.9} onPress={() => onAdd(vendor, item)} style={styles.addButton}>
      <Text style={styles.addButtonText}>ADD</Text>
    </TouchableOpacity>
  );
}

function MenuRow({ vendor, item, qty, onAdd, onRemove }) {
  const hasOffer = Number(item?.original_price || 0) > Number(item?.price || 0);

  return (
    <View style={styles.menuRow}>
      <View style={styles.menuCopy}>
        <View style={styles.menuTitleRow}>
          <View style={styles.foodDotOuter}>
            <View style={styles.foodDotInner} />
          </View>

          <Text numberOfLines={2} style={styles.menuTitle}>
            {item?.name || 'Recommended item'}
          </Text>
        </View>

        <View style={styles.priceRow}>
          <Text style={styles.menuPrice}>{money(item?.price)}</Text>
          {hasOffer ? <Text style={styles.menuStrike}>{money(item?.original_price)}</Text> : null}
        </View>
      </View>

      <QtyButton vendor={vendor} item={item} qty={qty} onAdd={onAdd} onRemove={onRemove} />
    </View>
  );
}

function RestaurantCard({
  vendor,
  items,
  favorite,
  onToggleFavorite,
  onOpenStore,
  onAdd,
  onRemove,
  getQty,
  lastOrder,
  hasForeignCart,
}) {
  const imageUri = getVendorImage(vendor);
  const etaText = getEtaText(vendor, lastOrder);
  const badgeText = getVendorBadge(vendor, lastOrder);
  const offerText = getVendorOffer(vendor);

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.restaurantCard} onPress={onOpenStore}>
      <View style={styles.restaurantHeader}>
        <View style={styles.restaurantHeaderLeft}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={styles.restaurantImage} resizeMode="cover" />
          ) : (
            <View style={styles.restaurantImageFallback}>
              <Text style={styles.restaurantImageFallbackText}>{initials(vendor?.name)}</Text>
            </View>
          )}

          <View style={styles.restaurantMeta}>
            <Text numberOfLines={1} style={styles.restaurantName}>
              {vendor?.name || 'Store'}
            </Text>
            <Text style={styles.restaurantEta}>{etaText}</Text>

            <View style={styles.restaurantOfferRow}>
              <Ionicons name="pricetag" size={14} color={COLORS.accent} />
              <Text numberOfLines={1} style={styles.restaurantOfferText}>
                {offerText} • {badgeText}
              </Text>
            </View>
          </View>
        </View>

        <TouchableOpacity activeOpacity={0.92} onPress={onToggleFavorite} style={styles.favoriteButton}>
          <Ionicons
            name={favorite ? 'heart' : 'heart-outline'}
            size={18}
            color={favorite ? COLORS.primary : '#BDBDBD'}
          />
        </TouchableOpacity>
      </View>

      <View style={styles.menuList}>
        {items.map((item) => (
          <MenuRow
            key={`${vendor?.id}-${item?.id || item?.name}`}
            vendor={vendor}
            item={item}
            qty={getQty(item)}
            onAdd={onAdd}
            onRemove={onRemove}
          />
        ))}
      </View>

      {hasForeignCart ? (
        <Text style={styles.cartHint}>Adding from another store will replace your current cart.</Text>
      ) : null}
    </TouchableOpacity>
  );
}

function EmptyState({ title, subtitle, buttonLabel, onPress }) {
  return (
    <View style={styles.emptyCard}>
      <Ionicons name="refresh-circle-outline" size={28} color={COLORS.primary} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>

      <TouchableOpacity activeOpacity={0.92} style={styles.emptyButton} onPress={onPress}>
        <Text style={styles.emptyButtonText}>{buttonLabel}</Text>
      </TouchableOpacity>
    </View>
  );
}

export function ReorderScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const {
    orderHistory,
    recentVendors,
    vendors,
    favorites,
    toggleFavorite,
    loadOrders,
    ordersLoading,
    inlineErrors,
    loadProducts,
    addToCart,
    updateQty,
    cart,
    rememberStore,
  } = useGrabBasket();

  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');
  const [productsByVendor, setProductsByVendor] = useState({});
  const [loadingVendorIds, setLoadingVendorIds] = useState({});

  const visibleOrders = useMemo(() => (orderHistory || []).slice(0, MAX_ORDERS), [orderHistory]);
  const ordersByVendor = useMemo(() => buildOrderGroups(visibleOrders), [visibleOrders]);

  const vendorPool = useMemo(
    () => buildVendorPool({ orderHistory: visibleOrders, recentVendors, vendors }),
    [recentVendors, vendors, visibleOrders]
  );

  useEffect(() => {
    let cancelled = false;

    const vendorIdsToLoad = vendorPool
      .map((vendor) => String(vendor?.id || ''))
      .filter(Boolean)
      .filter((vendorId) => !Array.isArray(productsByVendor[vendorId]));

    if (!vendorIdsToLoad.length) return undefined;

    setLoadingVendorIds((current) => {
      const next = { ...current };
      vendorIdsToLoad.forEach((vendorId) => {
        next[vendorId] = true;
      });
      return next;
    });

    Promise.all(
      vendorIdsToLoad.map(async (vendorId) => {
        const vendor = vendorPool.find((item) => String(item?.id) === vendorId);
        const items = vendor ? await loadProducts(vendor) : [];
        return [vendorId, Array.isArray(items) ? items : []];
      })
    )
      .then((entries) => {
        if (cancelled) return;

        setProductsByVendor((current) => {
          const next = { ...current };
          entries.forEach(([vendorId, items]) => {
            next[vendorId] = items;
          });
          return next;
        });
      })
      .finally(() => {
        if (cancelled) return;

        setLoadingVendorIds((current) => {
          const next = { ...current };
          vendorIdsToLoad.forEach((vendorId) => {
            delete next[vendorId];
          });
          return next;
        });
      });

    return () => {
      cancelled = true;
    };
  }, [loadProducts, productsByVendor, vendorPool]);

  const cards = useMemo(() => {
    const query = normalizeText(search);

    return vendorPool
      .map((vendor) => {
        const vendorId = String(vendor?.id || '');
        const vendorOrders = ordersByVendor.get(vendorId) || [];
        const recentSignals = buildRecentSignals(vendorOrders);
        const vendorProducts = Array.isArray(productsByVendor[vendorId]) ? productsByVendor[vendorId] : [];

        const orderedItems = [...vendorProducts]
          .filter((item) => item?.is_available !== false)
          .sort((left, right) => scoreProduct(right, recentSignals) - scoreProduct(left, recentSignals))
          .filter((item) => matchesPriceFilter(item, activeFilter))
          .slice(0, 3);

        const matchesSearch =
          !query ||
          normalizeText(vendor?.name).includes(query) ||
          orderedItems.some((item) => normalizeText(item?.name).includes(query)) ||
          vendorProducts.some((item) => normalizeText(item?.name).includes(query));

        const matchesFavorites = activeFilter !== 'favorites' || Boolean(favorites?.[vendor?.id]);

        return {
          vendor,
          items: orderedItems,
          favorite: Boolean(favorites?.[vendor?.id]),
          loading: Boolean(loadingVendorIds[vendorId]),
          lastOrder: vendorOrders[0] || null,
          visible:
            matchesSearch &&
            matchesFavorites &&
            (orderedItems.length > 0 || Boolean(loadingVendorIds[vendorId])),
        };
      })
      .filter((item) => item.visible);
  }, [activeFilter, favorites, loadingVendorIds, ordersByVendor, productsByVendor, search, vendorPool]);

  const handleOpenStore = (vendorId) => {
    if (!vendorId) return;

    rememberStore(vendorId);
    router.push({
      pathname: '/store/[vendorId]',
      params: { vendorId: String(vendorId) },
    });
  };

  const handleAdd = (vendor, item) => {
    if (!vendor?.id) return;

    if (!item?.id) {
      handleOpenStore(vendor?.id);
      return;
    }

    addToCart(vendor, item);
  };

  const handleRemove = (item) => {
    const currentQty = Number(cart?.items?.[item?.id]?.qty || 0);
    updateQty(item?.id, currentQty - 1);
  };

  const hasContent = cards.length > 0;

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={Boolean(ordersLoading)} onRefresh={() => loadOrders()} />}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        <View style={styles.content}>
          <Text style={styles.headerTitle}>REORDER</Text>

          <SearchBar value={search} onChangeText={setSearch} />

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRow}>
            {FILTERS.map((filter) => (
              <FilterChip
                key={filter.key}
                label={filter.label}
                active={activeFilter === filter.key}
                onPress={() => setActiveFilter(filter.key)}
              />
            ))}
          </ScrollView>

          {inlineErrors?.orders ? (
            <View style={styles.errorWrap}>
              <InlineErrorCard title="Orders are stale" message={inlineErrors.orders} onRetry={() => loadOrders()} />
            </View>
          ) : null}

          {hasContent ? (
            cards.map(({ vendor, items, favorite, lastOrder, loading }) => {
              const hasForeignCart = Boolean(cart?.vendorId) && String(cart.vendorId) !== String(vendor?.id);

              if (loading && !items.length) {
                return (
                  <View key={`vendor-loading-${vendor?.id}`} style={styles.loadingCard}>
                    <ActivityIndicator color={COLORS.primary} />
                    <Text style={styles.loadingText}>
                      Loading reorder picks for {vendor?.name || 'store'}...
                    </Text>
                  </View>
                );
              }

              return (
                <RestaurantCard
                  key={`vendor-${vendor?.id}`}
                  vendor={vendor}
                  items={items}
                  favorite={favorite}
                  lastOrder={lastOrder}
                  hasForeignCart={hasForeignCart}
                  onToggleFavorite={() => toggleFavorite(vendor?.id)}
                  onOpenStore={() => handleOpenStore(vendor?.id)}
                  onAdd={handleAdd}
                  onRemove={handleRemove}
                  getQty={(item) => Number(cart?.items?.[item?.id]?.qty || 0)}
                />
              );
            })
          ) : ordersLoading ? (
            <View style={styles.loadingCard}>
              <ActivityIndicator color={COLORS.primary} />
              <Text style={styles.loadingText}>Loading reorder suggestions...</Text>
            </View>
          ) : (
            <EmptyState
              title="No reorder suggestions yet"
              subtitle="Once customers place a few orders, this tab can show a much cleaner reorder flow like Swiggy — simple search, filter chips, and quick add buttons."
              buttonLabel="Browse stores"
              onPress={() => router.replace('/')}
            />
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: COLORS.page,
  },
  content: {
    paddingHorizontal: 16,
    paddingTop: 8,
    gap: 14,
  },
  headerTitle: {
    alignSelf: 'center',
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: 0.6,
    marginBottom: 2,
  },
  searchWrap: {
    minHeight: 58,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    paddingHorizontal: 18,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '500',
    paddingVertical: 14,
  },
  filterRow: {
    paddingVertical: 2,
    gap: 10,
  },
  filterChip: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: '#D9D9D9',
    borderRadius: 16,
    paddingHorizontal: 18,
    paddingVertical: 12,
  },
  filterChipActive: {
    backgroundColor: COLORS.primarySoft,
    borderColor: '#FFC7CE',
  },
  filterChipText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '600',
  },
  filterChipTextActive: {
    color: COLORS.primary,
  },
  errorWrap: {
    marginTop: 4,
  },
  restaurantCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    gap: 14,
  },
  restaurantHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
  },
  restaurantHeaderLeft: {
    flex: 1,
    flexDirection: 'row',
    gap: 12,
  },
  restaurantImage: {
    width: 72,
    height: 72,
    borderRadius: 18,
    backgroundColor: '#F3F4F6',
  },
  restaurantImageFallback: {
    width: 72,
    height: 72,
    borderRadius: 18,
    backgroundColor: '#FDE7E7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  restaurantImageFallbackText: {
    color: COLORS.primary,
    fontSize: 20,
    fontWeight: '800',
  },
  restaurantMeta: {
    flex: 1,
    justifyContent: 'center',
    gap: 3,
  },
  restaurantName: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  restaurantEta: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  restaurantOfferRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  restaurantOfferText: {
    flex: 1,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  favoriteButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuList: {
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#EFEFEF',
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    paddingHorizontal: 14,
    paddingVertical: 14,
    backgroundColor: COLORS.surface,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F1F1',
  },
  menuCopy: {
    flex: 1,
    gap: 6,
    paddingRight: 8,
  },
  menuTitleRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
  },
  foodDotOuter: {
    width: 16,
    height: 16,
    borderWidth: 1.6,
    borderColor: COLORS.primary,
    borderRadius: 4,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  foodDotInner: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: COLORS.primary,
  },
  menuTitle: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    lineHeight: 21,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  menuPrice: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  menuStrike: {
    color: COLORS.subtle,
    fontSize: 14,
    textDecorationLine: 'line-through',
  },
  addButton: {
    minWidth: 64,
    height: 42,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#D8EFD8',
    backgroundColor: '#F6FFF7',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 14,
  },
  addButtonText: {
    color: COLORS.success,
    fontSize: 14,
    fontWeight: '800',
  },
  qtyControl: {
    minWidth: 88,
    height: 42,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#D8EFD8',
    backgroundColor: '#F6FFF7',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
  },
  qtyAction: {
    width: 24,
    height: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyValue: {
    color: COLORS.success,
    fontSize: 15,
    fontWeight: '800',
  },
  cartHint: {
    color: COLORS.muted,
    fontSize: 12,
    lineHeight: 18,
    marginTop: -2,
  },
  loadingCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 26,
    paddingHorizontal: 16,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  loadingText: {
    color: COLORS.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  emptyCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 24,
    alignItems: 'center',
    gap: 10,
    marginTop: 12,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
    textAlign: 'center',
  },
  emptySubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 22,
    textAlign: 'center',
  },
  emptyButton: {
    marginTop: 4,
    backgroundColor: COLORS.dark,
    paddingHorizontal: 18,
    paddingVertical: 12,
    borderRadius: 14,
  },
  emptyButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
});