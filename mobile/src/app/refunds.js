import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
import InlineErrorCard from '@/components/inline-error-card';
import { useGrabBasket } from '../../App';

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function formatDate(value) {
  if (!value) return '—';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';

  return date.toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function addDays(value, days = 0) {
  const date = new Date(value || Date.now());
  if (Number.isNaN(date.getTime())) return null;
  date.setDate(date.getDate() + days);
  return date;
}

function normalizeRefundStatus(value) {
  return String(value || '').trim().toUpperCase();
}

function normalizeOrderStatus(value) {
  return String(value || '').trim().toLowerCase();
}

function isRefundOrder(order) {
  const refundStatus = normalizeRefundStatus(order?.refund_status);
  const status = normalizeOrderStatus(order?.status || order?.status_label);
  const paymentStatus = String(order?.payment_status || '').trim().toUpperCase();

  if (refundStatus && refundStatus !== 'NOT_APPLICABLE') return true;
  if (/refund/.test(status)) return true;
  return /cancel/.test(status) && paymentStatus === 'PAID';
}

function isCompletedRefund(order) {
  const refundStatus = normalizeRefundStatus(order?.refund_status);
  const status = normalizeOrderStatus(order?.status || order?.status_label);

  if (['COMPLETED', 'SUCCESS', 'PROCESSED', 'REFUNDED', 'CREDITED'].includes(refundStatus)) {
    return true;
  }

  return /refund.*(completed|credited|success)|refunded/.test(status);
}

function getRefundDestination(order) {
  const method = String(order?.payment_method || '').trim().toUpperCase();

  if (method === 'CARD') return 'Card (original source)';
  if (method === 'UPI') return 'UPI (original source)';
  if (method === 'WALLET') return 'GrabBasket Wallet';
  return 'Original payment source';
}

function getRefundBaseDate(order) {
  return order?.cancelled_at || order?.updated_at || order?.created_at || null;
}

function getActiveRefundSteps(order) {
  const initiatedAt = getRefundBaseDate(order);
  const expectedAt = addDays(initiatedAt, 7);

  return [
    {
      key: 'initiated',
      title: 'GrabBasket has initiated your refund',
      dateLabel: formatDate(initiatedAt),
      description: 'Completed',
      state: 'completed',
    },
    {
      key: 'bank_processing',
      title: 'Your bank is processing your refund',
      dateLabel: formatDate(initiatedAt),
      description:
        'In progress - We are working with your bank to process your refund. Your bank takes up to 7 days to credit it to your account.',
      state: 'active',
    },
    {
      key: 'credited',
      title: 'Refund credited to your account',
      dateLabel: expectedAt ? formatDate(expectedAt) : 'Pending',
      description: 'Pending',
      state: 'pending',
    },
  ];
}

function TimelineStep({ item, isLast = false }) {
  const isPending = item.state === 'pending';
  const isActive = item.state === 'active';

  return (
    <View style={styles.timelineStep}>
      <View style={styles.timelineRailWrap}>
        {!isLast ? (
          <View
            style={[
              styles.timelineRail,
              isPending ? styles.timelineRailPending : styles.timelineRailCompleted,
            ]}
          />
        ) : null}
        <View
          style={[
            styles.timelineDot,
            isPending && styles.timelineDotPending,
            isActive && styles.timelineDotActive,
          ]}
        />
      </View>

      <View style={styles.timelineContent}>
        <Text style={[styles.timelineTitle, isPending && styles.timelineTitleMuted]}>
          {item.title}
        </Text>
        <Text style={styles.timelineDate}>{item.dateLabel}</Text>
        {item.description ? (
          <Text
            style={[
              styles.timelineDescription,
              isPending && styles.timelineDescriptionMuted,
            ]}>
            {item.description}
          </Text>
        ) : null}
      </View>
    </View>
  );
}

function ActiveRefundCard({ order }) {
  const steps = getActiveRefundSteps(order);
  const expectedBy = addDays(getRefundBaseDate(order), 7);

  return (
    <View style={styles.refundCard}>
      <View style={styles.refundCardTopRow}>
        <View style={styles.refundCardTitleWrap}>
          <Text style={styles.refundVendorName}>{order?.vendor_name || 'Store'}</Text>
          <Text style={styles.refundMetaText}>To: {getRefundDestination(order)}</Text>
          <Text style={styles.refundMetaText}>
            Expected by: {expectedBy ? formatDate(expectedBy) : 'Pending'}
          </Text>
        </View>

        <View style={styles.refundAmountWrap}>
          <View style={styles.processingPill}>
            <Text style={styles.processingPillText}>Processing</Text>
          </View>
          <Text style={styles.refundAmount}>{money(order?.total_amount || 0)}</Text>
        </View>
      </View>

      <View style={styles.orderIdRow}>
        <Text style={styles.orderIdLabel}>Order ID: #{order?.id || '—'}</Text>
        <Ionicons name="chevron-forward" size={18} color={BrandPalette.subtle} />
      </View>

      <View style={styles.timelineWrap}>
        {steps.map((item, index) => (
          <TimelineStep
            key={item.key}
            item={item}
            isLast={index === steps.length - 1}
          />
        ))}
      </View>
    </View>
  );
}

function CompletedRefundCard({ order, expanded, onToggle }) {
  return (
    <View style={styles.refundCard}>
      <View style={styles.refundCardTopRow}>
        <View style={styles.refundCardTitleWrap}>
          <Text style={styles.refundVendorName}>{order?.vendor_name || 'Store'}</Text>
          <Text style={styles.refundMetaText}>To: {getRefundDestination(order)}</Text>
          <Text style={styles.refundMetaText}>
            Completed On: {formatDate(getRefundBaseDate(order))}
          </Text>
        </View>

        <View style={styles.refundAmountWrap}>
          <View style={styles.completedPill}>
            <Text style={styles.completedPillText}>Completed</Text>
          </View>
          <Text style={styles.refundAmount}>{money(order?.total_amount || 0)}</Text>
        </View>
      </View>

      <TouchableOpacity activeOpacity={0.9} style={styles.detailsButton} onPress={onToggle}>
        <Text style={styles.detailsButtonText}>
          {expanded ? 'Hide Details' : 'See Details'}
        </Text>
      </TouchableOpacity>

      {expanded ? (
        <View style={styles.completedDetailsWrap}>
          <Text style={styles.completedDetailsText}>Order ID: #{order?.id || '—'}</Text>
          <Text style={styles.completedDetailsText}>
            Payment method: {order?.payment_method || 'Original source'}
          </Text>
          {order?.refund_ref ? (
            <Text style={styles.completedDetailsText}>Refund ref: {order.refund_ref}</Text>
          ) : null}
        </View>
      ) : null}
    </View>
  );
}

function EmptyState({ title, subtitle, icon = 'wallet-outline' }) {
  return (
    <View style={styles.emptyCard}>
      <View style={styles.emptyIconWrap}>
        <Ionicons name={icon} size={22} color={BrandPalette.subtle} />
      </View>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

export default function RefundsScreen() {
  const router = useRouter();
  const {
    sessionReady,
    isAuthenticated,
    orderHistory,
    loadOrders,
    ordersLoading,
    inlineErrors,
  } = useGrabBasket();

  const [expandedCompletedId, setExpandedCompletedId] = useState('');
  const [showAllCompleted, setShowAllCompleted] = useState(false);

  useEffect(() => {
    if (!sessionReady || !isAuthenticated) return;
    loadOrders?.().catch(() => {});
  }, [isAuthenticated, loadOrders, sessionReady]);

  const refundOrders = useMemo(() => {
    const orders = Array.isArray(orderHistory) ? orderHistory : [];
    return orders.filter(isRefundOrder);
  }, [orderHistory]);

  const activeRefunds = useMemo(
    () => refundOrders.filter((order) => !isCompletedRefund(order)),
    [refundOrders]
  );

  const completedRefunds = useMemo(
    () => refundOrders.filter((order) => isCompletedRefund(order)),
    [refundOrders]
  );

  const visibleCompletedRefunds = useMemo(() => {
    if (showAllCompleted) return completedRefunds;
    return completedRefunds.slice(0, 2);
  }, [completedRefunds, showAllCompleted]);

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.loadingState}>
          <ActivityIndicator color={BrandPalette.primary} />
          <Text style={styles.loadingText}>Preparing refunds...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.header}>
          <TouchableOpacity
            activeOpacity={0.85}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={28} color={BrandPalette.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Refund Status</Text>
          <View style={styles.headerSpacer} />
        </View>

        <View style={styles.centerState}>
          <Ionicons name="lock-closed-outline" size={28} color={BrandPalette.subtle} />
          <Text style={styles.centerStateTitle}>Sign in to view your refunds</Text>
          <Text style={styles.centerStateSubtitle}>
            Any active or completed refunds will appear here after login.
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <View style={styles.header}>
        <TouchableOpacity
          activeOpacity={0.85}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={28} color={BrandPalette.text} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Refund Status</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        <View style={styles.infoCard}>
          <View style={styles.infoCopyWrap}>
            <Text style={styles.infoCardText}>
              Due to some ongoing enhancements to GrabBasket Wallet, your refunds will be
              directed to the original payment source.
            </Text>
          </View>

          <View style={styles.infoIconWrap}>
            <Ionicons name="reload-outline" size={26} color={BrandPalette.text} />
          </View>
        </View>

        {inlineErrors.orders ? (
          <InlineErrorCard title="Refund issue" message={inlineErrors.orders} />
        ) : null}

        <Text style={styles.sectionTitle}>Active Refunds</Text>

        {ordersLoading && !refundOrders.length ? (
          <View style={styles.loadingCard}>
            <ActivityIndicator color={BrandPalette.primary} />
            <Text style={styles.loadingText}>Loading your refund updates...</Text>
          </View>
        ) : activeRefunds.length ? (
          <View style={styles.cardStack}>
            {activeRefunds.map((order) => (
              <ActiveRefundCard key={String(order?.id)} order={order} />
            ))}
          </View>
        ) : (
          <EmptyState
            icon="checkmark-circle-outline"
            title="No active refunds"
            subtitle="Any refund that is still being processed by GrabBasket or your bank will show up here."
          />
        )}

        <Text style={styles.sectionTitle}>Completed Refunds</Text>

        {visibleCompletedRefunds.length ? (
          <View style={styles.cardStack}>
            {visibleCompletedRefunds.map((order) => {
              const isExpanded = expandedCompletedId === String(order?.id);

              return (
                <CompletedRefundCard
                  key={String(order?.id)}
                  order={order}
                  expanded={isExpanded}
                  onToggle={() =>
                    setExpandedCompletedId(isExpanded ? '' : String(order?.id))
                  }
                />
              );
            })}
          </View>
        ) : (
          <EmptyState
            icon="receipt-outline"
            title="No completed refunds yet"
            subtitle="When a refund is fully credited back to your original payment source, it will appear here."
          />
        )}

        {completedRefunds.length > 2 ? (
          <TouchableOpacity
            activeOpacity={0.94}
            style={styles.olderRefundsButton}
            onPress={() => setShowAllCompleted((current) => !current)}>
            <Text style={styles.olderRefundsButtonText}>
              {showAllCompleted ? 'Show Recent Refunds' : 'Show Older Refunds'}
            </Text>
          </TouchableOpacity>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F6F6F6',
  },
  header: {
    height: 64,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: BrandPalette.white,
    borderBottomWidth: 1,
    borderBottomColor: '#E9E9E9',
  },
  headerTitle: {
    flex: 1,
    marginLeft: 12,
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  headerSpacer: {
    width: 28,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  loadingState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingCard: {
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    paddingVertical: 28,
    paddingHorizontal: 20,
    alignItems: 'center',
    gap: 12,
    borderWidth: 1,
    borderColor: '#ECECEC',
    ...createShadow(0.06, 12, 6),
  },
  loadingText: {
    fontSize: 15,
    lineHeight: 20,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 28,
    gap: 10,
  },
  centerStateTitle: {
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '800',
    color: BrandPalette.text,
    textAlign: 'center',
  },
  centerStateSubtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  infoCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 16,
    borderRadius: 24,
    backgroundColor: BrandPalette.white,
    paddingHorizontal: 18,
    paddingVertical: 20,
    borderWidth: 1,
    borderColor: '#ECECEC',
    ...createShadow(0.06, 12, 6),
  },
  infoCopyWrap: {
    flex: 1,
  },
  infoCardText: {
    fontSize: 15,
    lineHeight: 22,
    color: '#615E5A',
    fontWeight: '500',
  },
  infoIconWrap: {
    width: 58,
    height: 58,
    borderRadius: 29,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F7F7F1',
  },
  sectionTitle: {
    marginTop: 28,
    marginBottom: 14,
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  cardStack: {
    gap: 16,
  },
  refundCard: {
    borderRadius: 26,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    overflow: 'hidden',
    ...createShadow(0.06, 12, 6),
  },
  refundCardTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
    paddingHorizontal: 18,
    paddingTop: 18,
    paddingBottom: 16,
  },
  refundCardTitleWrap: {
    flex: 1,
    gap: 6,
  },
  refundVendorName: {
    fontSize: 17,
    lineHeight: 21,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  refundMetaText: {
    fontSize: 14,
    lineHeight: 19,
    color: '#66625D',
  },
  refundAmountWrap: {
    alignItems: 'flex-end',
    gap: 12,
  },
  refundAmount: {
    fontSize: 17,
    lineHeight: 21,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  processingPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    backgroundColor: '#F7EAB7',
  },
  processingPillText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '800',
    color: '#A46C00',
  },
  completedPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    backgroundColor: '#E6F7EE',
  },
  completedPillText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '800',
    color: '#169C60',
  },
  orderIdRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: '#EFEFEF',
    borderBottomWidth: 1,
    borderBottomColor: '#EFEFEF',
  },
  orderIdLabel: {
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '700',
    color: '#494540',
  },
  timelineWrap: {
    paddingHorizontal: 18,
    paddingVertical: 18,
    gap: 4,
  },
  timelineStep: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 14,
    minHeight: 74,
  },
  timelineRailWrap: {
    width: 22,
    alignItems: 'center',
    position: 'relative',
    paddingTop: 3,
  },
  timelineRail: {
    position: 'absolute',
    top: 16,
    bottom: -16,
    width: 4,
    borderRadius: 999,
  },
  timelineRailCompleted: {
    backgroundColor: '#26A56C',
  },
  timelineRailPending: {
    backgroundColor: '#D59B17',
  },
  timelineDot: {
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: BrandPalette.white,
    borderWidth: 4,
    borderColor: '#26A56C',
    zIndex: 1,
  },
  timelineDotActive: {
    borderColor: '#26A56C',
  },
  timelineDotPending: {
    borderColor: '#D59B17',
  },
  timelineContent: {
    flex: 1,
    paddingBottom: 12,
  },
  timelineTitle: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: '#3B3835',
  },
  timelineTitleMuted: {
    color: '#4A4743',
  },
  timelineDate: {
    marginTop: 4,
    fontSize: 14,
    lineHeight: 18,
    color: '#7D7974',
  },
  timelineDescription: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 20,
    color: '#7D7974',
  },
  timelineDescriptionMuted: {
    color: '#88847E',
  },
  detailsButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderTopWidth: 1,
    borderTopColor: '#EFEFEF',
    paddingVertical: 16,
    paddingHorizontal: 18,
  },
  detailsButtonText: {
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '900',
    color: '#F36B00',
  },
  completedDetailsWrap: {
    borderTopWidth: 1,
    borderTopColor: '#EFEFEF',
    paddingHorizontal: 18,
    paddingBottom: 18,
    paddingTop: 14,
    gap: 6,
  },
  completedDetailsText: {
    fontSize: 14,
    lineHeight: 18,
    color: '#66625D',
  },
  emptyCard: {
    borderRadius: 24,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    paddingHorizontal: 20,
    paddingVertical: 24,
    alignItems: 'center',
    ...createShadow(0.06, 12, 6),
  },
  emptyIconWrap: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F7F7F7',
  },
  emptyTitle: {
    marginTop: 14,
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.text,
    textAlign: 'center',
  },
  emptySubtitle: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 20,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  olderRefundsButton: {
    marginTop: 20,
    height: 58,
    borderRadius: 16,
    backgroundColor: '#F36B00',
    alignItems: 'center',
    justifyContent: 'center',
    ...createShadow(0.08, 14, 8),
  },
  olderRefundsButtonText: {
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.white,
  },
});