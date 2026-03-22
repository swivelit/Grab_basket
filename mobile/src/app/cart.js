import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';

import { useGrabBasket } from '../../App';
import { API_CONFIG_ERROR, API_TIMEOUT_MS, buildApiUrl } from '../config';

WebBrowser.maybeCompleteAuthSession();

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
  peach600: '#D97651',
  success: '#2E8B57',
  black: '#2B211A',
};

const NETWORK_TIMEOUT_MS =
  Number.isFinite(Number(API_TIMEOUT_MS)) && Number(API_TIMEOUT_MS) > 0
    ? Number(API_TIMEOUT_MS)
    : 15000;

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function mapLegacyService(value) {
  const service = String(value || '').trim().toLowerCase();
  if (service === 'instamart') return 'warehouse';
  if (service === 'dineout') return 'eatout';
  return service || 'food';
}

function formatAddressShort(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city].filter(Boolean).join(' · ');
}

function createHttpError(message, extras = {}) {
  const error = new Error(message);
  Object.entries(extras).forEach(([key, value]) => {
    error[key] = value;
  });
  return error;
}

function safeJsonParse(raw) {
  try {
    return raw ? JSON.parse(raw) : null;
  } catch {
    return raw;
  }
}

function extractErrorMessage(data, fallback = 'Request failed') {
  if (data && typeof data === 'object') {
    if (typeof data.detail === 'string' && data.detail.trim()) return data.detail.trim();
    if (typeof data.message === 'string' && data.message.trim()) return data.message.trim();
    if (data.error && typeof data.error.message === 'string' && data.error.message.trim()) {
      return data.error.message.trim();
    }
  }

  if (typeof data === 'string' && data.trim()) return data.trim();
  return fallback;
}

function normalizeErrorMessage(error, fallback = 'Something went wrong') {
  if (error instanceof Error && error.message) return error.message;
  if (typeof error === 'string' && error.trim()) return error.trim();
  return fallback;
}

async function apiRequest(path, { method = 'GET', token = '', body, headers = {} } = {}) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), NETWORK_TIMEOUT_MS);

  try {
    const response = await fetch(buildApiUrl(path), {
      method,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...headers,
      },
      body,
      signal: controller.signal,
    });

    const raw = await response.text();
    const data = safeJsonParse(raw);

    if (!response.ok) {
      throw createHttpError(
        extractErrorMessage(data, `Request failed with status ${response.status}`),
        { status: response.status, payload: data }
      );
    }

    return data;
  } catch (error) {
    if (error?.name === 'AbortError') {
      throw createHttpError(`Request timed out after ${Math.round(NETWORK_TIMEOUT_MS / 1000)}s`, {
        code: 'TIMEOUT',
      });
    }

    if (API_CONFIG_ERROR) {
      throw createHttpError(API_CONFIG_ERROR, { code: 'API_CONFIG_ERROR' });
    }

    throw createHttpError(normalizeErrorMessage(error, 'Network request failed'), {
      status: error?.status,
      payload: error?.payload,
      code: error?.code,
    });
  } finally {
    clearTimeout(timeoutId);
  }
}

function PaymentMethodPill({ label, value, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.paymentPill, active && styles.paymentPillActive]}
      onPress={() => onPress(value)}>
      <Text style={[styles.paymentPillText, active && styles.paymentPillTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function QtyControl({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyWrap}>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onRemove}>
        <Ionicons name="remove" size={16} color={COLORS.text} />
      </TouchableOpacity>
      <Text style={styles.qtyValue}>{qty}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyAction} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.text} />
      </TouchableOpacity>
    </View>
  );
}

function EmptyState({ title, subtitle }) {
  return (
    <View style={styles.emptyState}>
      <Ionicons name="basket-outline" size={28} color={COLORS.peach600} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function normalizeGatewayStatus(value) {
  return String(value || '').trim().toLowerCase();
}

function isOrderPaymentPending(order) {
  return String(order?.status || '').toUpperCase() === 'PAYMENT_PENDING';
}

function hasSameItems(order, cartItems = []) {
  const orderItems = Array.isArray(order?.items) ? order.items : [];
  if (orderItems.length !== cartItems.length) return false;

  const signature = (list, idKey) =>
    [...list]
      .map((item) => `${Number(item?.[idKey] || 0)}:${Number(item?.qty || 0)}`)
      .sort()
      .join('|');

  return signature(orderItems, 'product_id') === signature(cartItems, 'id');
}

function findReusablePendingOrder(orders = [], { vendorId, paymentMethod, cartItems }) {
  return (
    (orders || []).find(
      (order) =>
        Number(order?.vendor_id || 0) === Number(vendorId || 0) &&
        String(order?.payment_method || '').toUpperCase() === String(paymentMethod || '').toUpperCase() &&
        isOrderPaymentPending(order) &&
        hasSameItems(order, cartItems)
    ) || null
  );
}

async function pollGatewayStatus(orderId, authToken, { attempts = 4, delayMs = 1500 } = {}) {
  let last = null;

  for (let index = 0; index < attempts; index += 1) {
    last = await apiRequest(`/payments/${orderId}/status`, {
      method: 'GET',
      token: authToken,
    });

    const checkoutStatus = normalizeGatewayStatus(last?.checkout_status);
    const paymentStatus = String(last?.payment_status || '').toUpperCase();

    if (paymentStatus === 'PAID') return last;
    if (paymentStatus === 'FAILED') return last;
    if (['cancelled', 'expired'].includes(checkoutStatus)) return last;

    if (index < attempts - 1) {
      await sleep(delayMs);
    }
  }

  return last;
}

export default function CartScreen() {
  const router = useRouter();
  const {
    activeService,
    cart,
    cartItems,
    cartVendor,
    cartSubtotal,
    deliveryFeeAmount,
    platformFeeAmount,
    cartTotal,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    addToCart,
    updateQty,
    clearCart,
    isAuthenticated,
    authToken,
    defaultAddress,
    loadOrders,
  } = useGrabBasket();

  const [paymentMethod, setPaymentMethod] = useState('COD');
  const [submitting, setSubmitting] = useState(false);

  const normalizedService = mapLegacyService(activeService);
  const isBooking = normalizedService === 'eatout' || normalizedService === 'scenes';

  const paymentHelperText = useMemo(() => {
    if (paymentMethod === 'UPI') {
      return 'UPI payments now open a hosted Razorpay checkout from your backend. The app no longer collects or verifies UPI IDs manually.';
    }

    if (paymentMethod === 'CARD') {
      return 'Card payments now run on a hosted Razorpay checkout page, with server-side signature verification, callback handling, and webhook reconciliation.';
    }

    return 'Cash is collected on delivery. Delivery app and seller app will still see the correct payment status.';
  }, [paymentMethod]);

  const submitCheckout = async () => {
    if (cartItems.length === 0) {
      Alert.alert('Basket is empty', 'Add some items first.');
      return;
    }

    if (!isAuthenticated || !authToken) {
      Alert.alert('Sign in required', 'Please sign in from the Account tab before placing an order.');
      return;
    }

    if (API_CONFIG_ERROR) {
      Alert.alert('Configuration issue', API_CONFIG_ERROR);
      return;
    }

    const needsDeliveryAddress = normalizedService === 'food' || normalizedService === 'warehouse';
    const deliveryAddressId = needsDeliveryAddress ? defaultAddress?.id || null : defaultAddress?.id || null;

    if (needsDeliveryAddress && !deliveryAddressId) {
      Alert.alert('Add delivery address', 'Add a delivery address from Account before placing this order.');
      return;
    }

    if (!cartVendor?.id && !cart?.vendorId) {
      Alert.alert('Store unavailable', 'We could not resolve the store for this basket.');
      return;
    }

    const vendorId = Number(cartVendor?.id ?? cart?.vendorId);
    const isOnlinePayment = paymentMethod !== 'COD';

    let order = null;
    let createdFreshPendingOrder = false;
    let checkoutSessionOpened = false;

    try {
      setSubmitting(true);

      if (isOnlinePayment) {
        const existingOrders = await apiRequest('/orders/me', {
          method: 'GET',
          token: authToken,
        }).catch(() => []);

        order = findReusablePendingOrder(existingOrders, {
          vendorId,
          paymentMethod,
          cartItems,
        });
      }

      if (!order) {
        const orderPayload = {
          vendor_id: vendorId,
          items: cartItems.map((item) => ({
            product_id: Number(item.id),
            qty: Number(item.qty || 1),
          })),
          payment_method: paymentMethod,
          ...(deliveryAddressId ? { delivery_address_id: Number(deliveryAddressId) } : {}),
        };

        order = await apiRequest('/orders', {
          method: 'POST',
          token: authToken,
          body: JSON.stringify(orderPayload),
        });
        createdFreshPendingOrder = isOnlinePayment;
      }

      if (!isOnlinePayment) {
        clearCart();
        await loadOrders().catch(() => {});

        Alert.alert(
          isBooking ? 'Booking confirmed' : 'Order placed',
          'Your order has been created successfully.'
        );

        router.replace('/reorder');
        return;
      }

      const returnUrl = Linking.createURL('/cart');
      const session = await apiRequest(`/payments/${order.id}/checkout-session`, {
        method: 'POST',
        token: authToken,
        body: JSON.stringify({ return_url: returnUrl }),
      });

      const checkoutUrl = String(session?.checkout_url || '').trim();
      if (!checkoutUrl) {
        throw new Error('The payment gateway did not return a checkout URL.');
      }

      checkoutSessionOpened = true;
      const browserResult = await WebBrowser.openAuthSessionAsync(checkoutUrl, returnUrl);

      const gatewayStatus = await pollGatewayStatus(order.id, authToken, {
        attempts: browserResult?.type === 'success' ? 3 : 4,
        delayMs: browserResult?.type === 'success' ? 1200 : 1800,
      });

      const paymentStatus = String(gatewayStatus?.payment_status || '').toUpperCase();
      const checkoutStatus = normalizeGatewayStatus(gatewayStatus?.checkout_status);
      const providerPaymentId = gatewayStatus?.provider_payment_id;

      await loadOrders().catch(() => {});

      if (paymentStatus === 'PAID') {
        clearCart();
        Alert.alert(
          isBooking ? 'Booking confirmed' : 'Payment successful',
          providerPaymentId
            ? `Payment captured and verified on the server. Payment ID: ${providerPaymentId}`
            : 'Payment captured and verified on the server.'
        );
        router.replace('/reorder');
        return;
      }

      if (paymentStatus === 'FAILED' || ['cancelled', 'expired'].includes(checkoutStatus)) {
        await apiRequest(`/orders/${order.id}/cancel?reason=Gateway%20payment%20not%20completed`, {
          method: 'POST',
          token: authToken,
        }).catch(() => {});

        await loadOrders().catch(() => {});

        Alert.alert(
          'Payment not completed',
          'No amount was confirmed by the gateway. The pending order has been cancelled and your basket is still intact so you can retry safely.'
        );
        return;
      }

      Alert.alert(
        'Payment still processing',
        `Order #${order.id} is waiting for final gateway confirmation. Do not place the same basket again until this status settles in your Orders view.`
      );
    } catch (error) {
      if (createdFreshPendingOrder && order?.id && isOnlinePayment && !checkoutSessionOpened) {
        await apiRequest(`/orders/${order.id}/cancel?reason=Gateway%20session%20creation%20failed`, {
          method: 'POST',
          token: authToken,
        }).catch(() => {});
      }

      Alert.alert('Could not place order', normalizeErrorMessage(error));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />
      <View style={styles.topBar}>
        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.iconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back" size={22} color={COLORS.text} />
        </TouchableOpacity>

        <View style={{ flex: 1 }}>
          <Text style={styles.topBarTitle}>{isBooking ? 'Booking' : 'Cart'}</Text>
          <Text style={styles.topBarSubtitle}>{cartVendor?.name || 'Your basket'}</Text>
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.screenContent}>
        {cartItems.length === 0 ? (
          <EmptyState
            title="Your basket is empty"
            subtitle="Add items from one store and they will appear here."
          />
        ) : (
          <>
            {!isAuthenticated ? (
              <View style={styles.noticeCard}>
                <View style={styles.noticeIcon}>
                  <Ionicons name="lock-closed-outline" size={18} color={COLORS.peach600} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.noticeTitle}>Sign in before checkout</Text>
                  <Text style={styles.noticeSubtitle}>
                    Use the Account tab to sign in or create a customer account.
                  </Text>
                </View>
                <TouchableOpacity
                  activeOpacity={0.92}
                  style={styles.noticeAction}
                  onPress={() => router.push('/account')}>
                  <Text style={styles.noticeActionText}>Open Account</Text>
                </TouchableOpacity>
              </View>
            ) : null}

            {!isBooking ? (
              <View style={styles.billCard}>
                <Text style={styles.billCardTitle}>Free delivery progress</Text>
                <Text style={styles.billCardSubtitle}>
                  {freeDeliveryRemaining > 0
                    ? `Add ${money(freeDeliveryRemaining)} more to unlock free delivery.`
                    : 'Free delivery unlocked for this basket.'}
                </Text>

                <View style={styles.progressTrack}>
                  <View
                    style={[
                      styles.progressFill,
                      {
                        width:
                          freeDeliveryProgress === 0
                            ? '0%'
                            : `${Math.max(10, freeDeliveryProgress * 100)}%`,
                      },
                    ]}
                  />
                </View>
              </View>
            ) : null}

            {!isBooking ? (
              <View style={styles.billCard}>
                <Text style={styles.billCardTitle}>Delivery address</Text>
                {defaultAddress ? (
                  <>
                    <Text style={styles.billCardSubtitle}>{formatAddressShort(defaultAddress)}</Text>
                    <Text style={styles.helperText}>
                      Lat {Number(defaultAddress.lat).toFixed(4)} · Lng {Number(defaultAddress.lng).toFixed(4)}
                    </Text>
                  </>
                ) : (
                  <Text style={styles.billCardSubtitle}>
                    No delivery address selected yet. Add one from Account to place a food or grocery order.
                  </Text>
                )}
                <TouchableOpacity
                  activeOpacity={0.92}
                  style={styles.inlineGhostButton}
                  onPress={() => router.push('/account')}>
                  <Text style={styles.inlineGhostButtonText}>Manage address</Text>
                </TouchableOpacity>
              </View>
            ) : null}

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>Payment method</Text>
              <View style={styles.paymentRow}>
                <PaymentMethodPill
                  label="Cash on delivery"
                  value="COD"
                  active={paymentMethod === 'COD'}
                  onPress={setPaymentMethod}
                />
                <PaymentMethodPill
                  label="UPI"
                  value="UPI"
                  active={paymentMethod === 'UPI'}
                  onPress={setPaymentMethod}
                />
                <PaymentMethodPill
                  label="Card"
                  value="CARD"
                  active={paymentMethod === 'CARD'}
                  onPress={setPaymentMethod}
                />
              </View>

              {paymentMethod !== 'COD' ? (
                <View style={styles.noticeCard}>
                  <View style={styles.noticeIcon}>
                    <Ionicons name="shield-checkmark-outline" size={18} color={COLORS.peach600} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.noticeTitle}>Hosted secure checkout</Text>
                    <Text style={styles.noticeSubtitle}>
                      The app will open a secure gateway page and wait for your backend callback + webhook confirmation before the order is marked paid.
                    </Text>
                  </View>
                </View>
              ) : null}

              <Text style={styles.helperText}>{paymentHelperText}</Text>
            </View>

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>{isBooking ? 'Selection' : 'Items in basket'}</Text>
              {cartItems.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineMeta}>{money(item.price)} each</Text>
                  </View>
                  <QtyControl
                    qty={item.qty}
                    onAdd={() => addToCart(item)}
                    onRemove={() => updateQty(item, -1)}
                  />
                </View>
              ))}
            </View>

            <View style={styles.billCard}>
              <Text style={styles.billCardTitle}>{isBooking ? 'Booking details' : 'Bill details'}</Text>

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Subtotal</Text>
                <Text style={styles.summaryValue}>{money(cartSubtotal)}</Text>
              </View>

              {!isBooking ? (
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Estimated delivery fee</Text>
                  <Text style={styles.summaryValue}>
                    {deliveryFeeAmount === 0 ? 'FREE' : money(deliveryFeeAmount)}
                  </Text>
                </View>
              ) : null}

              {platformFeeAmount > 0 ? (
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Platform fee</Text>
                  <Text style={styles.summaryValue}>{money(platformFeeAmount)}</Text>
                </View>
              ) : null}

              <View style={styles.summaryDivider} />

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabelStrong}>Estimated total</Text>
                <Text style={styles.summaryValueStrong}>{money(cartTotal)}</Text>
              </View>
            </View>

            <TouchableOpacity
              activeOpacity={submitting ? 1 : 0.92}
              disabled={submitting}
              style={[styles.primaryButton, { opacity: submitting ? 0.75 : 1 }]}
              onPress={submitCheckout}>
              {submitting ? <ActivityIndicator color="#FFFFFF" /> : null}
              <Text style={styles.primaryButtonText}>
                {submitting
                  ? paymentMethod === 'COD'
                    ? 'Placing order...'
                    : 'Opening secure checkout...'
                  : isBooking
                    ? 'Confirm booking'
                    : paymentMethod === 'COD'
                      ? 'Place order'
                      : 'Pay securely'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear basket</Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 6,
    paddingBottom: 8,
    gap: 12,
  },
  iconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  topBarTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.text,
  },
  topBarSubtitle: {
    fontSize: 13,
    color: COLORS.muted,
    marginTop: 2,
  },
  screenContent: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 28,
    gap: 14,
  },
  emptyState: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 24,
    alignItems: 'center',
    gap: 10,
  },
  emptyTitle: {
    fontSize: 19,
    fontWeight: '800',
    color: COLORS.text,
  },
  emptySubtitle: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.muted,
    textAlign: 'center',
  },
  noticeCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  noticeIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  noticeTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  noticeSubtitle: {
    fontSize: 13,
    lineHeight: 18,
    color: COLORS.muted,
    marginTop: 2,
  },
  noticeAction: {
    borderRadius: 14,
    backgroundColor: COLORS.peach600,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  noticeActionText: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  billCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 12,
  },
  billCardTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: COLORS.text,
  },
  billCardSubtitle: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.muted,
  },
  helperText: {
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.subtle,
  },
  inlineGhostButton: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 14,
    backgroundColor: COLORS.cardAlt,
  },
  inlineGhostButtonText: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.peach600,
  },
  progressTrack: {
    height: 10,
    borderRadius: 999,
    backgroundColor: COLORS.line,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
    backgroundColor: COLORS.peach600,
  },
  paymentRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  paymentPill: {
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
  },
  paymentPillActive: {
    borderColor: COLORS.peach600,
    backgroundColor: COLORS.peach50,
  },
  paymentPillText: {
    color: COLORS.muted,
    fontWeight: '700',
  },
  paymentPillTextActive: {
    color: COLORS.peach600,
  },
  formSection: {
    gap: 10,
  },
  input: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: COLORS.cardAlt,
    color: COLORS.text,
    fontSize: 14,
  },
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.line,
  },
  cartLineTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.text,
  },
  cartLineMeta: {
    fontSize: 13,
    color: COLORS.muted,
    marginTop: 4,
  },
  qtyWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.cardAlt,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  qtyAction: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  qtyValue: {
    minWidth: 18,
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 14,
    color: COLORS.muted,
  },
  summaryValue: {
    fontSize: 14,
    color: COLORS.text,
    fontWeight: '700',
  },
  summaryDivider: {
    height: 1,
    backgroundColor: COLORS.line,
  },
  summaryLabelStrong: {
    fontSize: 16,
    color: COLORS.text,
    fontWeight: '800',
  },
  summaryValueStrong: {
    fontSize: 18,
    color: COLORS.text,
    fontWeight: '900',
  },
  primaryButton: {
    marginTop: 4,
    backgroundColor: COLORS.black,
    borderRadius: 18,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 10,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
  secondaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 18,
    paddingVertical: 15,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  secondaryButtonText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
});