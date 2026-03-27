import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import InlineErrorCard from '../components/inline-error-card';
import { getAppShellConfig, getAppVariant } from '../constants/app-shell';
import { useAddressDomain } from '../domains/address-domain';
import { useAuthDomain } from '../domains/auth-domain';
import { useCartDomain } from '../domains/cart-domain';
import { mapLegacyService } from '../domains/grab-basket-utils';
import { useOrderDomain } from '../domains/order-domain';
import { usePricingDomain } from '../domains/pricing-domain';
import { useVendorDomain } from '../domains/vendor-domain';

const GrabBasketContext = createContext(null);

const APP_VARIANT = getAppVariant();
const APP_SHELL = getAppShellConfig(APP_VARIANT);
const APP_VARIANT_NAME = APP_SHELL.appName || 'Grab Basket';
const APP_ALLOWED_ROLES = [String(APP_SHELL.role || 'CUSTOMER').trim().toUpperCase()];
const APP_PRIMARY_ROLE = APP_ALLOWED_ROLES[0];

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

export function useGrabBasket() {
  const value = useContext(GrabBasketContext);
  if (!value) {
    throw new Error('useGrabBasket must be used inside GrabBasketProvider');
  }
  return value;
}

export function GrabBasketProvider({ children }) {
  const [activeService, setActiveService] = useState('food');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [storeFilter, setStoreFilter] = useState('All');
  const [pastOrderFilter, setPastOrderFilter] = useState('all');

  const isCustomerApp = APP_ALLOWED_ROLES.includes('CUSTOMER');

  const auth = useAuthDomain({
    appVariantName: APP_VARIANT_NAME,
    appAllowedRoles: APP_ALLOWED_ROLES,
    appPrimaryRole: APP_PRIMARY_ROLE,
  });

  const addresses = useAddressDomain({
    isCustomerApp,
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
    isCustomerApp,
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
  }, [auth.sessionReady, auth.isAuthenticated, auth.refreshProfile]);

  const pastOrders = useMemo(() => {
    if (pastOrderFilter === 'all') return orders.orderHistory;
    return orders.orderHistory.filter((item) => mapLegacyService(item?.service) === pastOrderFilter);
  }, [orders.orderHistory, pastOrderFilter]);

  const errorItems = [
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
  ];

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
    featuredVendors: vendors.featuredVendors,
    recentVendors: vendors.recentVendors,
    recentStoreIds: vendors.recentStoreIds,
    recentSearches: vendors.recentSearches,
    suggestionPool: vendors.suggestionPool,
    rememberStore: vendors.rememberStore,
    rememberSearch: vendors.rememberSearch,

    favorites: cart.favorites,
    toggleFavorite: cart.toggleFavorite,
    cart: cart.cart,
    cartItems: cart.cartItems,
    cartCount: cart.cartCount,
    addToCart: cart.addToCart,
    updateQty: cart.updateQty,
    clearCart: cart.clearCart,

    cartSubtotal: pricing.cartSubtotal,
    cartVendor: pricing.cartVendor,
    deliveryFeeAmount: pricing.deliveryFeeAmount,
    platformFeeAmount: pricing.platformFeeAmount,
    cartTotal: pricing.cartTotal,
    freeDeliveryRemaining: pricing.freeDeliveryRemaining,
    freeDeliveryProgress: pricing.freeDeliveryProgress,

    orderHistory: orders.orderHistory,
    pastOrders,
    loadOrders: orders.loadOrders,
    ordersLoading: orders.ordersLoading,
    placingOrder: orders.placingOrder,
    placeDemoOrder: orders.placeOrder,
    placeOrder: orders.placeOrder,

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

const styles = StyleSheet.create({
  providerRoot: {
    flex: 1,
  },
  errorRailWrap: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 50,
  },
  errorRailStack: {
    paddingHorizontal: 16,
    paddingTop: 8,
    gap: 10,
  },
});