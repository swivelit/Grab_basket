import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

import InlineErrorCard from './src/components/inline-error-card';
import { useAddressDomain } from './src/domains/address-domain';
import { useAuthDomain } from './src/domains/auth-domain';
import { useCartDomain } from './src/domains/cart-domain';
import {
  MAX_ORDERS,
  mapLegacyService,
  normalizeErrorMessage,
} from './src/domains/grab-basket-utils';
import { useOrderDomain } from './src/domains/order-domain';
import { usePricingDomain } from './src/domains/pricing-domain';
import { useVendorDomain } from './src/domains/vendor-domain';
import { getAppShellConfig, getAppVariant } from './src/constants/app-shell';

export { GrabBasketProvider, useGrabBasket } from './src/providers/grab-basket-provider';
export { ReorderScreen } from './src/screens/reorder-screen';

export default function AppBridge() {
  return null;
}

const GrabBasketContext = createContext(null);

const APP_VARIANT = getAppVariant();
const APP_SHELL = getAppShellConfig(APP_VARIANT);
const APP_VARIANT_NAME = APP_SHELL.appName || 'Grab Basket';
const APP_ALLOWED_ROLES = [String(APP_SHELL.role || 'CUSTOMER').trim().toUpperCase()];
const APP_PRIMARY_ROLE = APP_ALLOWED_ROLES[0];

const COLORS = {
  bg: '#FFF9F3',
  card: '#FFFFFF',
  cardAlt: '#FFF6EC',
  text: '#2F241C',
  muted: '#756354',
  subtle: '#A18C7B',
  border: '#F2DDC7',
  line: '#F4E6D7',
  peach50: '#FFF7EE',
  peach100: '#FFF0DE',
  peach600: '#D97651',
  success: '#2E8B57',
  successSoft: '#EAF7EF',
  black: '#2B211A',
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

export function useGrabBasket() {
  const value = useContext(GrabBasketContext);
  if (!value) throw new Error('useGrabBasket must be used inside GrabBasketProvider');
  return value;
}

function GlobalErrorRail({ items = [] }) {
  const visibleItems = items.filter((item) => item && item.message);
  if (!visibleItems.length) return null;

  return (
    <View pointerEvents="box-none" style={styles.errorRailWrap}>
      <SafeAreaView pointerEvents="box-none" edges={['top']}>
        <View style={styles.errorRailStack}>
          {visibleItems.map((item) => (
            <InlineErrorCard
              key={item.key}
              title={item.title}
              message={item.message}
              onRetry={item.onRetry}
              onDismiss={item.onDismiss}
            />
          ))}
        </View>
      </SafeAreaView>
    </View>
  );
}

export function GrabBasketProvider({ children }) {
  const [activeService, setActiveService] = useState('food');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
  const [pastOrderFilter, setPastOrderFilter] = useState('all');

  const auth = useAuthDomain({
    appVariantName: APP_VARIANT_NAME,
    appAllowedRoles: APP_ALLOWED_ROLES,
    appPrimaryRole: APP_PRIMARY_ROLE,
  });

  const addresses = useAddressDomain({
    isCustomerApp: APP_ALLOWED_ROLES.includes('CUSTOMER'),
    appVariantName: APP_VARIANT_NAME,
    sessionReady: auth.sessionReady,
    isAuthenticated: auth.isAuthenticated,
    authorizedRequest: auth.authorizedRequest,
  });

  const vendors = useVendorDomain({
    activeService,
    activeShortcut,
    homeSearch,
    defaultAddress: addresses.defaultAddress,
  });

  const cart = useCartDomain();

  const pricing = usePricingDomain({
    cart: cart.cart,
    cartItems: cart.cartItems,
    vendors: vendors.vendors,
  });

  const orders = useOrderDomain({
    appVariantName: APP_VARIANT_NAME,
    isCustomerApp: APP_ALLOWED_ROLES.includes('CUSTOMER'),
    sessionReady: auth.sessionReady,
    isAuthenticated: auth.isAuthenticated,
    activeService,
    authorizedRequest: auth.authorizedRequest,
    cart: cart.cart,
    cartItems: cart.cartItems,
    cartVendor: pricing.cartVendor,
    clearCart: cart.clearCart,
    vendors: vendors.vendors,
    addresses: addresses.addresses,
    defaultAddress: addresses.defaultAddress,
  });

  useEffect(() => {
    if (!auth.sessionReady || !auth.isAuthenticated) return;
    auth.refreshProfile().catch(() => {});
  }, [auth]);

  const pastOrders = useMemo(() => {
    if (pastOrderFilter === 'all') return orders.orderHistory;
    return orders.orderHistory.filter((item) => mapLegacyService(item?.service) === pastOrderFilter);
  }, [orders.orderHistory, pastOrderFilter]);

  const errorItems = useMemo(
    () => [
      {
        key: 'auth',
        title: `${APP_VARIANT_NAME} account issue`,
        message: auth.authError,
        onDismiss: () => auth.setAuthError(''),
      },
      {
        key: 'vendors',
        title: 'Could not refresh stores',
        message: vendors.vendorsError,
        onRetry: () => vendors.loadVendors({ pullToRefresh: true }),
        onDismiss: () => vendors.setVendorsError(''),
      },
      {
        key: 'products',
        title: 'Could not refresh products',
        message: vendors.productsError,
        onDismiss: () => vendors.setProductsError(''),
      },
      {
        key: 'addresses',
        title: 'Address issue',
        message: addresses.addressesError,
        onRetry: () => addresses.loadAddresses(),
        onDismiss: () => addresses.setAddressesError(''),
      },
      {
        key: 'orders',
        title: 'Orders are stale',
        message: orders.ordersError,
        onRetry: () => orders.loadOrders(),
        onDismiss: () => orders.setOrdersError(''),
      },
      {
        key: 'checkout',
        title: 'Checkout needs attention',
        message: orders.checkoutError || orders.checkoutMessage,
        onDismiss: () => {
          orders.setCheckoutError('');
          orders.setCheckoutMessage('');
        },
      },
    ],
    [
      addresses,
      auth,
      orders,
      vendors,
    ]
  );

  const value = {
    appVariant: APP_VARIANT,
    appVariantName: APP_VARIANT_NAME,
    appAllowedRoles: APP_ALLOWED_ROLES,
    activeService,
    setActiveService,
    activeShortcut,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    storeFilter,
    setStoreFilter,
    pastOrderFilter,
    setPastOrderFilter,
    vendors: vendors.vendors,
    vendorsLoading: vendors.vendorsLoading,
    refreshing: vendors.refreshing,
    loadVendors: vendors.loadVendors,
    homeDeals: vendors.homeDeals,
    homeDealsLoading: vendors.homeDealsLoading,
    loadProducts: vendors.loadProducts,
    favorites: cart.favorites,
    toggleFavorite: cart.toggleFavorite,
    recentStoreIds: vendors.recentStoreIds,
    recentSearches: vendors.recentSearches,
    orderHistory: orders.orderHistory,
    featuredVendors: vendors.featuredVendors,
    recentVendors: vendors.recentVendors,
    suggestionPool: vendors.suggestionPool,
    cart: cart.cart,
    cartItems: cart.cartItems,
    cartCount: cart.cartCount,
    cartSubtotal: pricing.cartSubtotal,
    cartVendor: pricing.cartVendor,
    deliveryFeeAmount: pricing.deliveryFeeAmount,
    platformFeeAmount: pricing.platformFeeAmount,
    cartTotal: pricing.cartTotal,
    freeDeliveryRemaining: pricing.freeDeliveryRemaining,
    freeDeliveryProgress: pricing.freeDeliveryProgress,
    rememberStore: vendors.rememberStore,
    rememberSearch: vendors.rememberSearch,
    addToCart: cart.addToCart,
    updateQty: cart.updateQty,
    clearCart: cart.clearCart,
    placeDemoOrder: orders.placeOrder,
    placeOrder: orders.placeOrder,
    pastOrders,
    sessionReady: auth.sessionReady,
    isAuthenticated: auth.isAuthenticated,
    authToken: auth.authToken,
    authEmail: auth.authEmail,
    authRole: auth.authRole,
    profile: auth.profile,
    authLoading: auth.authLoading,
    login: auth.login,
    register: auth.register,
    logout: auth.logout,
    addresses: addresses.addresses,
    addressesLoading: addresses.addressesLoading,
    selectedAddressId: addresses.selectedAddressId,
    setSelectedAddressId: addresses.setSelectedAddressId,
    defaultAddress: addresses.defaultAddress,
    createAddress: addresses.createAddress,
    setDefaultAddress: addresses.setDefaultAddress,
    loadAddresses: addresses.loadAddresses,
    loadOrders: orders.loadOrders,
    ordersLoading: orders.ordersLoading,
    placingOrder: orders.placingOrder,
    inlineErrors: {
      auth: auth.authError,
      vendors: vendors.vendorsError,
      products: vendors.productsError,
      addresses: addresses.addressesError,
      orders: orders.ordersError,
      checkout: orders.checkoutError,
      checkoutMessage: orders.checkoutMessage,
    },
  };

  return (
    <GrabBasketContext.Provider value={value}>
      <View style={styles.providerRoot}>
        {children}
        <GlobalErrorRail items={errorItems} />
      </View>
    </GrabBasketContext.Provider>
  );
}

function ReorderOrderCard({ order, onPress }) {
  const itemCount = Number(order?.item_count || 0);
  const itemLabel = itemCount === 1 ? 'item' : 'items';

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.orderCard} onPress={onPress}>
      <View style={styles.orderCardHeader}>
        <View style={styles.orderCardIcon}>
          <Ionicons name="refresh-outline" size={18} color={COLORS.peach600} />
        </View>
        <View style={styles.orderCardMeta}>
          <Text style={styles.orderVendor}>{order?.vendor_name || 'Store'}</Text>
          <Text style={styles.orderSubtitle}>
            #{order?.id} · {itemCount} {itemLabel}
          </Text>
        </View>
        <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
      </View>
      <Text style={styles.orderStatus}>{order?.status_label || 'Recent order'}</Text>
    </TouchableOpacity>
  );
}

function RecentVendorCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.vendorCard} onPress={onPress}>
      <View style={styles.vendorBadge}>
        <Ionicons name="storefront-outline" size={16} color={COLORS.peach600} />
      </View>
      <View style={styles.vendorMeta}>
        <Text numberOfLines={1} style={styles.vendorName}>{vendor?.name || 'Store'}</Text>
        <Text numberOfLines={1} style={styles.vendorHint}>{vendor?.description || vendor?.address || 'Tap to browse again'}</Text>
      </View>
      <Ionicons name="arrow-forward" size={16} color={COLORS.subtle} />
    </TouchableOpacity>
  );
}

export function ReorderScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const { pastOrders, recentVendors, ordersLoading, inlineErrors, loadOrders } = useGrabBasket();

  const visibleOrders = useMemo(() => (pastOrders || []).slice(0, MAX_ORDERS), [pastOrders]);

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={Boolean(ordersLoading)} onRefresh={() => loadOrders()} />}
        contentContainerStyle={{ padding: 20, paddingBottom: 28 + tabBarHeight }}>
        <Text style={styles.eyebrow}>Reorder faster</Text>
        <Text style={styles.pageTitle}>Your recent activity</Text>
        <Text style={styles.pageSubtitle}>Recent stores and orders stay available even while background refresh is running.</Text>

        {inlineErrors?.orders ? (
          <View style={styles.sectionGap}>
            <InlineErrorCard title="Orders are stale" message={inlineErrors.orders} onRetry={() => loadOrders()} />
          </View>
        ) : null}

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Recent stores</Text>
          {recentVendors?.length ? (
            recentVendors.map((vendor) => (
              <RecentVendorCard
                key={`vendor-${vendor?.id}`}
                vendor={vendor}
                onPress={() => router.push({ pathname: '/store/[vendorId]', params: { vendorId: vendor?.id } })}
              />
            ))
          ) : (
            <View style={styles.emptyCard}>
              <Text style={styles.emptyTitle}>No recent stores yet</Text>
              <Text style={styles.emptySubtitle}>Place an order once and your last stores will appear here.</Text>
            </View>
          )}
        </View>

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Recent orders</Text>
          {ordersLoading && !visibleOrders.length ? (
            <View style={styles.loadingWrap}>
              <ActivityIndicator color={COLORS.peach600} />
            </View>
          ) : visibleOrders.length ? (
            visibleOrders.map((order) => (
              <ReorderOrderCard
                key={`order-${order?.id}`}
                order={order}
                onPress={() => router.push({ pathname: '/store/[vendorId]', params: { vendorId: order?.vendor_id } })}
              />
            ))
          ) : (
            <View style={styles.emptyCard}>
              <Text style={styles.emptyTitle}>No orders yet</Text>
              <Text style={styles.emptySubtitle}>Once you place an order, you can come back here to reorder in one tap.</Text>
            </View>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

export default function AppBridge() {
  return null;
}

const styles = StyleSheet.create({
  providerRoot: {
    flex: 1,
  },
  errorRailWrap: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 40,
  },
  errorRailStack: {
    paddingHorizontal: 16,
    paddingTop: 8,
    gap: 10,
  },
  screen: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  eyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  pageTitle: {
    marginTop: 10,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
  },
  pageSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 22,
  },
  sectionGap: {
    marginTop: 24,
    gap: 12,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },
  emptyCard: {
    backgroundColor: COLORS.card,
    borderRadius: 22,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  emptySubtitle: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  orderCard: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    gap: 10,
  },
  orderCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderCardIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderCardMeta: {
    flex: 1,
  },
  orderVendor: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  orderSubtitle: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 12,
  },
  orderStatus: {
    color: COLORS.peach600,
    fontSize: 13,
    fontWeight: '700',
  },
  vendorCard: {
    backgroundColor: COLORS.cardAlt,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  vendorBadge: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMeta: {
    flex: 1,
  },
  vendorName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  vendorHint: {
    marginTop: 3,
    color: COLORS.muted,
    fontSize: 12,
  },
  loadingWrap: {
    paddingVertical: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
});