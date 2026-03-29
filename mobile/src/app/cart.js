import React, { useState } from 'react';
import {
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
import { BrandPalette, createShadow } from '@/constants/theme';

import { useGrabBasket } from '../../App';
import InlineErrorCard from '../components/inline-error-card';

const COLORS = {
  ...BrandPalette,
  bg: BrandPalette.page,
  card: BrandPalette.surface,
  cardAlt: BrandPalette.surfaceAlt,
  text: BrandPalette.text,
  muted: BrandPalette.textMuted,
  subtle: BrandPalette.subtle,
  border: BrandPalette.border,
  line: BrandPalette.line,
  peach50: BrandPalette.primarySoft,
  peach600: BrandPalette.primary,
  success: BrandPalette.success,
  successSoft: BrandPalette.successSoft,
  black: BrandPalette.ink,
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
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

function EmptyState({ title, subtitle, onBrowse }) {
  return (
    <View style={styles.emptyState}>
      <Ionicons name="basket-outline" size={28} color={COLORS.peach600} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.primaryAction} onPress={onBrowse}>
        <Text style={styles.primaryActionText}>Browse</Text>
      </TouchableOpacity>
    </View>
  );
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
    inlineErrors,
  } = useGrabBasket();

  const [paymentMethod, setPaymentMethod] = useState('COD');

  const isBooking = ['eatout', 'scenes'].includes(String(activeService || '').trim().toLowerCase());

  const checkoutInfo = inlineErrors?.checkoutMessage;
  const checkoutError = inlineErrors?.checkout;

  const submitCheckout = async () => {
    const ok = await placeOrder({ paymentMethod });

    if (ok && !['UPI', 'CARD'].includes(paymentMethod)) {
      router.replace('/(tabs)/reorder');
    }
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="chevron-back" size={20} color={COLORS.text} />
        </TouchableOpacity>
        <View style={styles.headerCopy}>
          <Text style={styles.headerEyebrow}>Checkout</Text>
          <Text style={styles.headerTitle}>{isBooking ? 'Booking' : 'Your basket'}</Text>
        </View>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={clearCart}>
          <Ionicons name="trash-outline" size={18} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      {!cartItems.length ? (
        <EmptyState
          title={isBooking ? 'No booking' : 'Basket empty'}
          subtitle={isBooking ? 'Add a plan.' : 'Add items.'}
          onBrowse={() => router.replace('/')}
        />
      ) : (
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.content}>
          {checkoutError ? (
            <InlineErrorCard title="Checkout blocked" message={checkoutError} onDismiss={() => {}} />
          ) : null}

          {checkoutInfo ? (
            <View style={styles.infoCard}>
              <Ionicons name="information-circle-outline" size={18} color={COLORS.success} />
              <Text style={styles.infoText}>{checkoutInfo}</Text>
            </View>
          ) : null}

          <View style={styles.sectionCard}>
            <Text style={styles.sectionLabel}>{isBooking ? 'Venue' : 'Store'}</Text>
            <Text style={styles.vendorTitle}>{cartVendor?.name || 'Selected store'}</Text>
            <Text style={styles.vendorSubtitle} numberOfLines={1}>
              {cartVendor?.description || cartVendor?.address || 'Ready'}
            </Text>
          </View>

          <View style={styles.sectionCard}>
            <View style={styles.sectionHeaderRow}>
              <Text style={styles.sectionTitle}>Items</Text>
              <Text style={styles.sectionSubtle}>{cartItems.length} items</Text>
            </View>

            {cartItems.map((item) => (
              <View key={item.id} style={styles.itemRow}>
                <View style={styles.itemMeta}>
                  <Text style={styles.itemName}>{item.name}</Text>
                  <Text style={styles.itemPrice}>{money(item.price)}</Text>
                </View>
                <QtyControl
                  qty={Number(item.qty || 0)}
                  onAdd={() => updateQty(item.id, Number(item.qty || 0) + 1)}
                  onRemove={() => updateQty(item.id, Number(item.qty || 0) - 1)}
                />
              </View>
            ))}
          </View>

          <View style={styles.sectionCard}>
            <View style={styles.sectionHeaderRow}>
              <Text style={styles.sectionTitle}>{isBooking ? 'Details' : 'Address'}</Text>
              <TouchableOpacity onPress={() => router.push('/(tabs)/account')}>
                <Text style={styles.inlineLink}>Manage</Text>
              </TouchableOpacity>
            </View>
            <Text style={styles.addressLabel}>{defaultAddress?.label || 'No address'}</Text>
            <Text style={styles.addressValue}>
              {defaultAddress
                ? [defaultAddress.line1, defaultAddress.city, defaultAddress.pincode].filter(Boolean).join(', ')
                : 'Add in Account'}
            </Text>
          </View>

          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Pay with</Text>
            <View style={styles.paymentRow}>
              <PaymentMethodPill label="Cash" value="COD" active={paymentMethod === 'COD'} onPress={setPaymentMethod} />
              <PaymentMethodPill label="UPI" value="UPI" active={paymentMethod === 'UPI'} onPress={setPaymentMethod} />
              <PaymentMethodPill label="Card" value="CARD" active={paymentMethod === 'CARD'} onPress={setPaymentMethod} />
            </View>
          </View>

          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Bill</Text>

            <View style={styles.billRow}>
              <Text style={styles.billLabel}>Items</Text>
              <Text style={styles.billValue}>{money(cartSubtotal)}</Text>
            </View>
            <View style={styles.billRow}>
              <Text style={styles.billLabel}>Delivery</Text>
              <Text style={styles.billValue}>{money(deliveryFeeAmount)}</Text>
            </View>
            <View style={styles.billRow}>
              <Text style={styles.billLabel}>Fee</Text>
              <Text style={styles.billValue}>{money(platformFeeAmount)}</Text>
            </View>
            <View style={[styles.billRow, styles.billDivider]}>
              <Text style={styles.totalLabel}>Total</Text>
              <Text style={styles.totalValue}>{money(cartTotal)}</Text>
            </View>

            <View style={styles.progressCard}>
              <Text style={styles.progressTitle}>
                {freeDeliveryRemaining > 0
                  ? `${money(freeDeliveryRemaining)} to free`
                  : 'Free unlocked'}
              </Text>
              <View style={styles.progressTrack}>
                <View style={[styles.progressFill, { width: `${Math.max(8, freeDeliveryProgress * 100)}%` }]} />
              </View>
            </View>
          </View>

          <TouchableOpacity
            activeOpacity={0.92}
            style={[styles.checkoutButton, placingOrder && styles.checkoutButtonDisabled]}
            onPress={submitCheckout}
            disabled={placingOrder}>
            <Text style={styles.checkoutText}>
              {placingOrder ? 'Processing…' : isBooking ? `Confirm booking · ${money(cartTotal)}` : `Place order · ${money(cartTotal)}`}
            </Text>
          </TouchableOpacity>
        </ScrollView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 18,
  },
  headerCopy: {
    alignItems: 'center',
    gap: 3,
  },
  headerEyebrow: {
    color: COLORS.peach600,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
    ...createShadow(0.08, 12, 5),
  },
  headerTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },
  content: {
    paddingHorizontal: 18,
    paddingBottom: 30,
    gap: 16,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 28,
    padding: 18,
    gap: 12,
    ...createShadow(0.08, 16, 8),
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  sectionLabel: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 0.9,
  },
  vendorTitle: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  vendorSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  sectionSubtle: {
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '700',
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 2,
  },
  itemMeta: {
    flex: 1,
    paddingRight: 12,
  },
  itemName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  itemPrice: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
  },
  qtyWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E8C8CB',
    borderRadius: 18,
    overflow: 'hidden',
    backgroundColor: '#FFF4F4',
  },
  qtyAction: {
    width: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(217,44,58,0.08)',
  },
  qtyValue: {
    minWidth: 28,
    textAlign: 'center',
    color: COLORS.text,
    fontWeight: '800',
  },
  addressLabel: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  addressValue: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  inlineLink: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
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
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 16,
    paddingVertical: 11,
  },
  paymentPillActive: {
    backgroundColor: COLORS.peach50,
    borderColor: '#F3BBC6',
  },
  paymentPillText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  paymentPillTextActive: {
    color: COLORS.peach600,
  },
  billRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  billLabel: {
    color: COLORS.muted,
    fontSize: 13,
  },
  billValue: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  billDivider: {
    marginTop: 4,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: COLORS.line,
  },
  totalLabel: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  totalValue: {
    color: COLORS.black,
    fontSize: 18,
    fontWeight: '900',
  },
  progressCard: {
    marginTop: 6,
    gap: 8,
    padding: 15,
    borderRadius: 20,
    backgroundColor: COLORS.cardAlt,
  },
  progressTitle: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  progressTrack: {
    height: 8,
    backgroundColor: COLORS.line,
    borderRadius: 999,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: COLORS.peach600,
    borderRadius: 999,
  },
  checkoutButton: {
    marginTop: 4,
    backgroundColor: COLORS.peach600,
    borderRadius: 22,
    paddingVertical: 17,
    alignItems: 'center',
    justifyContent: 'center',
    ...createShadow(0.2, 18, 9),
  },
  checkoutButtonDisabled: {
    opacity: 0.55,
  },
  checkoutText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
  emptyState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 28,
    gap: 10,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  emptySubtitle: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 22,
    textAlign: 'center',
  },
  primaryAction: {
    marginTop: 10,
    backgroundColor: COLORS.peach600,
    borderRadius: 18,
    paddingHorizontal: 18,
    paddingVertical: 13,
    ...createShadow(0.18, 16, 8),
  },
  primaryActionText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  infoCard: {
    backgroundColor: COLORS.successSoft,
    borderRadius: 24,
    padding: 14,
    gap: 8,
    flexDirection: 'row',
    alignItems: 'flex-start',
    borderWidth: 1,
    borderColor: '#CBEBD7',
  },
  infoText: {
    flex: 1,
    color: COLORS.success,
    fontSize: 13,
    lineHeight: 20,
  },
});
