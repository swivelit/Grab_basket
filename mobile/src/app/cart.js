import React, { useMemo, useState } from 'react';
import { Image } from 'expo-image';
import {
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { buildApiUrl } from '../config';
import InlineErrorCard from '../components/inline-error-card';
import InlineNoticeCard from '../components/inline-notice-card';
import { useGrabBasket } from '../../App';

const COLORS = {
  page: '#F8F8F8',
  card: '#FFFFFF',
  border: '#ECECEC',
  text: BrandPalette.text,
  muted: BrandPalette.textMuted,
  subtle: BrandPalette.subtle,
  primary: BrandPalette.primary,
  primarySoft: BrandPalette.primarySoft,
  success: BrandPalette.success,
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

function QtyStepper({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyStepper}>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyStepperButton} onPress={onRemove}>
        <Ionicons name="remove" size={16} color={COLORS.primary} />
      </TouchableOpacity>
      <Text style={styles.qtyStepperValue}>{qty}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.qtyStepperButton} onPress={onAdd}>
        <Ionicons name="add" size={16} color={COLORS.primary} />
      </TouchableOpacity>
    </View>
  );
}

function PaymentPill({ label, value, selected, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.paymentPill, selected && styles.paymentPillActive]}
      onPress={() => onPress(value)}>
      <Text style={[styles.paymentPillText, selected && styles.paymentPillTextActive]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function EmptyBasket({ onBrowse }) {
  return (
    <View style={styles.emptyWrap}>
      <View style={styles.emptyIconWrap}>
        <Ionicons name="bag-handle-outline" size={34} color={COLORS.primary} />
      </View>
      <Text style={styles.emptyTitle}>Your basket is empty</Text>
      <Text style={styles.emptySubtitle}>
        Add something delicious or useful to continue checkout.
      </Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={onBrowse}>
        <Text style={styles.primaryButtonText}>Browse stores</Text>
      </TouchableOpacity>
    </View>
  );
}

function getLatestTrackedOrder(orders = [], service = '') {
  const normalizedService = String(service || '').trim().toLowerCase();
  const list = Array.isArray(orders) ? orders : [];

  const preferred = list.filter(
    (item) => String(item?.service || '').trim().toLowerCase() === normalizedService
  );

  const source = preferred.length ? preferred : list;

  return [...source].sort((left, right) => {
    const rightTime = Date.parse(right?.created_at || right?.updated_at || 0) || 0;
    const leftTime = Date.parse(left?.created_at || left?.updated_at || 0) || 0;
    return rightTime - leftTime;
  })[0] || null;
}

export default function CartScreen() {
  const router = useRouter();
  const {
    activeService,
    cartItems,
    cartVendor,
    cartSubtotal,
    deliveryFeeAmount,
    platformFeeAmount,
    cartTotal,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    updateQty,
    clearCart,
    defaultAddress,
    placeOrder,
    placingOrder,
    loadOrders,
    inlineErrors,
    isAuthenticated,
  } = useGrabBasket();

  const [paymentMethod, setPaymentMethod] = useState('COD');
  const isBooking = ['eatout', 'scenes'].includes(String(activeService || '').trim().toLowerCase());

  const bannerText = useMemo(() => {
    if (freeDeliveryRemaining > 0) {
      return `${money(freeDeliveryRemaining)} away from free delivery`;
    }

    return isBooking ? 'Booking perks unlocked' : 'Free delivery unlocked';
  }, [freeDeliveryRemaining, isBooking]);

  const handleCheckout = async () => {
    const ok = await placeOrder({ paymentMethod });
    if (!ok) return;

    const latestOrders = await loadOrders({ silent: true });
    const latestOrder = getLatestTrackedOrder(latestOrders, activeService);

    if (latestOrder?.id) {
      router.replace(`/order/${latestOrder.id}`);
      return;
    }

    router.replace('/(tabs)/account');
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <View style={styles.header}>
        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.headerIconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="chevron-back" size={20} color={COLORS.text} />
        </TouchableOpacity>

        <View style={styles.headerCopy}>
          <Text style={styles.headerEyebrow}>Checkout</Text>
          <Text style={styles.headerTitle}>
            {isBooking ? 'Confirm booking' : 'Review your basket'}
          </Text>
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.headerIconButton} onPress={clearCart}>
          <Ionicons name="trash-outline" size={18} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      {!cartItems.length ? (
        <EmptyBasket onBrowse={() => router.replace('/')} />
      ) : (
        <>
          <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.content}>
            {inlineErrors?.checkout ? (
              <InlineErrorCard title="Checkout blocked" message={inlineErrors.checkout} />
            ) : null}

            {inlineErrors?.checkoutMessage ? (
              <InlineNoticeCard
                title="Checkout status"
                message={inlineErrors.checkoutMessage}
                tone="success"
              />
            ) : null}

            {!isAuthenticated ? (
              <InlineNoticeCard
                title="Sign in required"
                message="Please log in from Profile before placing this order."
                tone="warning"
                actionLabel="Open profile"
                onAction={() => router.push('/(tabs)/account')}
              />
            ) : null}

            <View style={styles.heroBanner}>
              <View style={styles.heroBannerIcon}>
                <Ionicons
                  name={isBooking ? 'calendar-outline' : 'bicycle-outline'}
                  size={20}
                  color={COLORS.primary}
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.heroBannerTitle}>{bannerText}</Text>
                <View style={styles.progressTrack}>
                  <View
                    style={[
                      styles.progressFill,
                      {
                        width: `${Math.max(
                          10,
                          Math.min(100, freeDeliveryProgress * 100)
                        )}%`,
                      },
                    ]}
                  />
                </View>
              </View>
            </View>

            <View style={styles.sectionCard}>
              <Text style={styles.sectionEyebrow}>{isBooking ? 'Venue' : 'Store'}</Text>
              <Text style={styles.sectionTitle}>{cartVendor?.name || 'GrabBasket order'}</Text>
              <Text style={styles.sectionSubtitle}>
                {cartVendor?.description || cartVendor?.address || 'Ready to serve.'}
              </Text>
            </View>

            <View style={styles.sectionCard}>
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.sectionTitleSmall}>
                  {isBooking ? 'Booking details' : 'Delivery address'}
                </Text>
                <TouchableOpacity activeOpacity={0.92} onPress={() => router.push('/(tabs)/account')}>
                  <Text style={styles.inlineLink}>Manage</Text>
                </TouchableOpacity>
              </View>

              {defaultAddress ? (
                <View style={styles.addressCard}>
                  <Ionicons name="location-outline" size={18} color={COLORS.primary} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.addressLabel}>{defaultAddress.label}</Text>
                    <Text style={styles.addressText}>
                      {[defaultAddress.line1, defaultAddress.city, defaultAddress.pincode]
                        .filter(Boolean)
                        .join(', ')}
                    </Text>
                  </View>
                </View>
              ) : (
                <View style={styles.addressCard}>
                  <Ionicons name="alert-circle-outline" size={18} color={COLORS.primary} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.addressLabel}>No address selected</Text>
                    <Text style={styles.addressText}>
                      Add one from Profile to place this order.
                    </Text>
                  </View>
                </View>
              )}
            </View>

            <View style={styles.sectionCard}>
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.sectionTitleSmall}>Items</Text>
                <Text style={styles.itemCountText}>{cartItems.length} items</Text>
              </View>

              <View style={styles.itemList}>
                {cartItems.map((item) => {
                  const imageUri = resolveMediaUrl(item?.image_url);

                  return (
                    <View key={item.id} style={styles.itemRow}>
                      <View style={styles.itemImageWrap}>
                        {imageUri ? (
                          <Image
                            source={{ uri: imageUri }}
                            style={styles.itemImage}
                            contentFit="cover"
                            transition={180}
                          />
                        ) : (
                          <View style={styles.itemImageFallback}>
                            <Ionicons name="cube-outline" size={20} color={COLORS.primary} />
                          </View>
                        )}
                      </View>

                      <View style={styles.itemMeta}>
                        <Text numberOfLines={2} style={styles.itemName}>
                          {item.name}
                        </Text>
                        <Text style={styles.itemPrice}>{money(item.price)}</Text>
                      </View>

                      <QtyStepper
                        qty={Number(item.qty || 0)}
                        onAdd={() => updateQty(item.id, Number(item.qty || 0) + 1)}
                        onRemove={() => updateQty(item.id, Number(item.qty || 0) - 1)}
                      />
                    </View>
                  );
                })}
              </View>
            </View>

            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitleSmall}>Pay with</Text>
              <View style={styles.paymentRow}>
                <PaymentPill
                  label="Cash"
                  value="COD"
                  selected={paymentMethod === 'COD'}
                  onPress={setPaymentMethod}
                />
                <PaymentPill
                  label="UPI"
                  value="UPI"
                  selected={paymentMethod === 'UPI'}
                  onPress={setPaymentMethod}
                />
                <PaymentPill
                  label="Card"
                  value="CARD"
                  selected={paymentMethod === 'CARD'}
                  onPress={setPaymentMethod}
                />
              </View>
            </View>

            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitleSmall}>Bill details</Text>

              <View style={styles.billRow}>
                <Text style={styles.billLabel}>Item total</Text>
                <Text style={styles.billValue}>{money(cartSubtotal)}</Text>
              </View>

              <View style={styles.billRow}>
                <Text style={styles.billLabel}>Delivery fee</Text>
                <Text style={styles.billValue}>{money(deliveryFeeAmount)}</Text>
              </View>

              <View style={styles.billRow}>
                <Text style={styles.billLabel}>Platform fee</Text>
                <Text style={styles.billValue}>{money(platformFeeAmount)}</Text>
              </View>

              <View style={[styles.billRow, styles.billTotalRow]}>
                <Text style={styles.billTotalLabel}>To pay</Text>
                <Text style={styles.billTotalValue}>{money(cartTotal)}</Text>
              </View>
            </View>
          </ScrollView>

          <View style={styles.bottomBar}>
            <View>
              <Text style={styles.bottomBarLabel}>Total payable</Text>
              <Text style={styles.bottomBarValue}>{money(cartTotal)}</Text>
            </View>

            <TouchableOpacity
              activeOpacity={0.92}
              style={[styles.checkoutButton, placingOrder && styles.checkoutButtonDisabled]}
              onPress={handleCheckout}
              disabled={placingOrder}>
              <Text style={styles.checkoutButtonText}>
                {placingOrder
                  ? 'Processing...'
                  : isBooking
                    ? 'Confirm booking'
                    : 'Place order'}
              </Text>
            </TouchableOpacity>
          </View>
        </>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.page,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 16,
  },
  headerIconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
    ...createShadow(0.05, 12, 6),
  },
  headerCopy: {
    alignItems: 'center',
    gap: 4,
  },
  headerEyebrow: {
    color: COLORS.primary,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  headerTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  emptyWrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 26,
  },
  emptyIconWrap: {
    width: 84,
    height: 84,
    borderRadius: 42,
    backgroundColor: COLORS.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 18,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
    marginBottom: 10,
  },
  emptySubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '600',
    textAlign: 'center',
    marginBottom: 18,
  },
  primaryButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 18,
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '900',
  },
  content: {
    paddingHorizontal: 16,
    paddingBottom: 120,
    gap: 16,
  },
  heroBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 24,
    backgroundColor: '#FFF8F6',
    borderWidth: 1,
    borderColor: '#F4DCDD',
    padding: 14,
  },
  heroBannerIcon: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroBannerTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
    marginBottom: 8,
  },
  progressTrack: {
    height: 8,
    borderRadius: 999,
    backgroundColor: '#F0E1E2',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
    backgroundColor: COLORS.success,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 26,
    padding: 16,
    gap: 12,
    ...createShadow(0.06, 12, 6),
  },
  sectionEyebrow: {
    color: COLORS.primary,
    fontSize: 11,
    fontWeight: '900',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 22,
    lineHeight: 28,
    fontWeight: '900',
  },
  sectionSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  sectionTitleSmall: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  inlineLink: {
    color: COLORS.primary,
    fontSize: 12,
    fontWeight: '900',
  },
  addressCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    borderRadius: 20,
    backgroundColor: '#F8F8F8',
    padding: 14,
  },
  addressLabel: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
    marginBottom: 4,
  },
  addressText: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  itemCountText: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  itemList: {
    gap: 12,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  itemImageWrap: {
    width: 68,
    height: 68,
    borderRadius: 18,
    backgroundColor: '#F3F3F3',
    overflow: 'hidden',
  },
  itemImage: {
    width: '100%',
    height: '100%',
  },
  itemImageFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  itemMeta: {
    flex: 1,
  },
  itemName: {
    color: COLORS.text,
    fontSize: 15,
    lineHeight: 20,
    fontWeight: '800',
    marginBottom: 6,
  },
  itemPrice: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  qtyStepper: {
    minWidth: 92,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#F0C9CC',
    backgroundColor: '#FFF8F8',
    overflow: 'hidden',
  },
  qtyStepperButton: {
    width: 30,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyStepperValue: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
  },
  paymentRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  paymentPill: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#F8F8F8',
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  paymentPillActive: {
    backgroundColor: COLORS.primarySoft,
    borderColor: '#F2CDD0',
  },
  paymentPillText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  paymentPillTextActive: {
    color: COLORS.primary,
  },
  billRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  billLabel: {
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  billValue: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  billTotalRow: {
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingTop: 12,
    marginTop: 2,
  },
  billTotalLabel: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },
  billTotalValue: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  bottomBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 20,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  bottomBarLabel: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 2,
  },
  bottomBarValue: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  checkoutButton: {
    minWidth: 170,
    borderRadius: 18,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
  },
  checkoutButtonDisabled: {
    opacity: 0.7,
  },
  checkoutButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '900',
  },
});