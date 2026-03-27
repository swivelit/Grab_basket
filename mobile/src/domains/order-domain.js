import { useCallback, useEffect, useMemo, useState } from 'react';
import * as ExpoLinking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';

import { MAX_ORDERS, mergeOrderCollections, normalizeErrorMessage, normalizeGatewayStatus, normalizeOrderRecord, normalizePaymentMethod, normalizeUserRole, pollGatewayStatus } from './grab-basket-utils';
import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';

WebBrowser.maybeCompleteAuthSession();

export function useOrderDomain({
  appVariantName,
  isCustomerApp,
  sessionReady,
  isAuthenticated,
  activeService,
  authorizedRequest,
  cart,
  cartItems,
  cartVendor,
  clearCart,
  vendors,
  addresses,
  defaultAddress,
}) {
  const [orderHistory, setOrderHistory] = useState([]);
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [ordersError, setOrdersError] = useState('');
  const [placingOrder, setPlacingOrder] = useState(false);
  const [checkoutError, setCheckoutError] = useState('');
  const [checkoutMessage, setCheckoutMessage] = useState('');


  useEffect(() => {
    let cancelled = false;

    readStoredValue(STORAGE_KEYS.orderHistory)
      .then((value) => {
        if (cancelled || !value) return;

        try {
          const parsed = JSON.parse(value);
          if (Array.isArray(parsed)) {
            setOrderHistory(parsed);
          }
        } catch {
          // ignore invalid cache
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    writeStoredValue(STORAGE_KEYS.orderHistory, JSON.stringify(orderHistory)).catch(() => {});
  }, [orderHistory]);

  const loadOrders = useCallback(
    async ({ silent = false } = {}) => {
      if (!isAuthenticated) {
        setOrderHistory([]);
        return [];
      }

      try {
        if (!silent) setOrdersLoading(true);
        const data = await authorizedRequest('/orders/me');
        const parsed = Array.isArray(data)
          ? data.map((item) => normalizeOrderRecord(item, { vendors, addresses })).filter(Boolean)
          : [];
        setOrderHistory((current) => mergeOrderCollections(parsed, current).slice(0, MAX_ORDERS));
        setOrdersError('');
        return parsed;
      } catch (error) {
        setOrdersError(normalizeErrorMessage(error, 'Could not load orders.'));
        return [];
      } finally {
        if (!silent) setOrdersLoading(false);
      }
    },
    [addresses, authorizedRequest, isAuthenticated, vendors]
  );

  useEffect(() => {
    if (!sessionReady || !isAuthenticated) return;
    loadOrders({ silent: true }).catch(() => {});
  }, [isAuthenticated, loadOrders, sessionReady, vendors, addresses]);

  const requestPaymentStatus = useCallback(
    async (orderId) => authorizedRequest(`/payments/${orderId}/status`, { method: 'GET' }),
    [authorizedRequest]
  );

  const placeOrder = useCallback(
    async ({ paymentMethod = 'COD' } = {}) => {
      setCheckoutError('');
      setCheckoutMessage('');

      if (!isCustomerApp) {
        setCheckoutError(`${appVariantName} does not support customer checkout flows.`);
        return false;
      }

      if (cartItems.length === 0) {
        setCheckoutError('Add some items first.');
        return false;
      }

      if (!isAuthenticated) {
        setCheckoutError(`Sign in to ${appVariantName} before placing an order.`);
        return false;
      }

      const normalizedService = String(activeService || 'food').trim().toLowerCase();
      const needsDeliveryAddress = normalizedService === 'food' || normalizedService === 'warehouse';
      const deliveryAddressId = needsDeliveryAddress ? defaultAddress?.id || null : defaultAddress?.id || null;

      if (needsDeliveryAddress && !deliveryAddressId) {
        setCheckoutError(`Add a delivery address in ${appVariantName} before placing this order.`);
        return false;
      }

      if (!cartVendor?.id && !cart?.vendorId) {
        setCheckoutError('We could not resolve the store for this basket.');
        return false;
      }

      try {
        setPlacingOrder(true);

        const normalizedPaymentMethodValue = normalizePaymentMethod(paymentMethod || 'COD');
        const isOnlinePayment = ['UPI', 'CARD'].includes(normalizedPaymentMethodValue);

        const payload = {
          vendor_id: Number(cartVendor?.id ?? cart?.vendorId),
          items: cartItems.map((item) => ({
            product_id: Number(item.id),
            qty: Number(item.qty || 1),
          })),
          payment_method: normalizedPaymentMethodValue,
          ...(deliveryAddressId ? { delivery_address_id: Number(deliveryAddressId) } : {}),
        };

        const response = await authorizedRequest('/orders', {
          method: 'POST',
          body: payload,
        });

        const nextOrder = normalizeOrderRecord(response, {
          vendors,
          addresses,
          serviceHint: normalizedService,
        });

        if (nextOrder) {
          setOrderHistory((current) => mergeOrderCollections([nextOrder], current).slice(0, MAX_ORDERS));
        }

        clearCart();

        if (!isOnlinePayment) {
          setCheckoutMessage(
            normalizedService === 'eatout' || normalizedService === 'scenes'
              ? 'Your booking has been created successfully.'
              : 'Your order has been created successfully.'
          );
          return true;
        }

        const orderId = Number(response?.id);
        if (!Number.isFinite(orderId) || orderId <= 0) {
          setCheckoutMessage('Your order was created, but the payment session could not be started. Retry payment from Account.');
          return true;
        }

        let session = null;
        try {
          session = await authorizedRequest(`/payments/${orderId}/checkout-session`, {
            method: 'POST',
            body: {
              return_url: ExpoLinking.createURL('/cart'),
            },
          });
        } catch (sessionError) {
          setCheckoutMessage(`${normalizeErrorMessage(sessionError)} You can retry secure checkout from Account.`);
          return true;
        }

        const checkoutUrl = String(session?.checkout_url || '').trim();
        if (!checkoutUrl) {
          setCheckoutMessage('The payment gateway did not return a checkout URL. Retry payment from Account.');
          return true;
        }

        try {
          await WebBrowser.openAuthSessionAsync(checkoutUrl, ExpoLinking.createURL('/cart'));
        } catch (browserError) {
          setCheckoutMessage(`${normalizeErrorMessage(browserError)} You can retry the payment from Account.`);
          return true;
        }

        let gatewayStatus = null;
        try {
          gatewayStatus = await pollGatewayStatus(requestPaymentStatus, orderId, {
            attempts: 5,
            delayMs: 1500,
          });
        } catch (statusError) {
          setCheckoutMessage(`${normalizeErrorMessage(statusError)} You can still refresh from Account to confirm the final payment state.`);
          return true;
        }

        const paymentStatus = normalizeUserRole(gatewayStatus?.payment_status || '');
        const checkoutStatus = normalizeGatewayStatus(gatewayStatus?.checkout_status);

        if (paymentStatus === 'PAID') {
          setCheckoutMessage(
            normalizedService === 'eatout' || normalizedService === 'scenes'
              ? 'Your booking is confirmed and the payment was verified on the server.'
              : 'Your order is confirmed and the payment was verified on the server.'
          );
          return true;
        }

        if (paymentStatus === 'FAILED' || ['cancelled', 'expired'].includes(checkoutStatus)) {
          setCheckoutMessage('Your order was created, but the payment did not complete. Reopen secure checkout from Account to finish payment.');
          return true;
        }

        setCheckoutMessage('Your order is waiting for final gateway confirmation. Pull to refresh or reopen the payment from Account in a moment.');
        return true;
      } catch (error) {
        setCheckoutError(normalizeErrorMessage(error, 'Could not place order.'));
        return false;
      } finally {
        setPlacingOrder(false);
      }
    },
    [
      activeService,
      addresses,
      appVariantName,
      authorizedRequest,
      cart,
      cartItems,
      cartVendor,
      clearCart,
      defaultAddress,
      isAuthenticated,
      isCustomerApp,
      requestPaymentStatus,
      vendors,
    ]
  );

  const pastOrders = useMemo(() => orderHistory, [orderHistory]);

  return {
    orderHistory,
    setOrderHistory,
    ordersLoading,
    ordersError,
    setOrdersError,
    placingOrder,
    checkoutError,
    setCheckoutError,
    checkoutMessage,
    setCheckoutMessage,
    loadOrders,
    placeOrder,
    pastOrders,
  };
}
