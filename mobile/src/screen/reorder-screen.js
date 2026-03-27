import React, { useMemo } from 'react';
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

import InlineErrorCard from '../components/inline-error-card';
import { MAX_ORDERS } from '../domains/grab-basket-utils';
import { useGrabBasket } from '../providers/grab-basket-provider';

const COLORS = {
  bg: '#FFF9F3',
  card: '#FFFFFF',
  cardAlt: '#FFF6EC',
  text: '#2F241C',
  muted: '#756354',
  subtle: '#A18C7B',
  border: '#F2DDC7',
  peach50: '#FFF7EE',
  peach600: '#D97651',
};

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
        <Text numberOfLines={1} style={styles.vendorName}>
          {vendor?.name || 'Store'}
        </Text>
        <Text numberOfLines={1} style={styles.vendorHint}>
          {vendor?.description || vendor?.address || 'Tap to browse again'}
        </Text>
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
        contentContainerStyle={{ padding: 20, paddingBottom: 28 + tabBarHeight }}
      >
        <Text style={styles.eyebrow}>Reorder faster</Text>
        <Text style={styles.pageTitle}>Your recent activity</Text>
        <Text style={styles.pageSubtitle}>
          Recent stores and orders stay available even while background refresh is running.
        </Text>

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
                onPress={() =>
                  router.push({
                    pathname: '/store/[vendorId]',
                    params: { vendorId: vendor?.id },
                  })
                }
              />
            ))
          ) : (
            <View style={styles.emptyCard}>
              <Text style={styles.emptyTitle}>No recent stores yet</Text>
              <Text style={styles.emptySubtitle}>
                Place an order once and your last stores will appear here.
              </Text>
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
                onPress={() => router.push('/cart')}
              />
            ))
          ) : (
            <View style={styles.emptyCard}>
              <Text style={styles.emptyTitle}>No recent orders yet</Text>
              <Text style={styles.emptySubtitle}>
                Your latest completed orders will show up here for quick reordering.
              </Text>
            </View>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  eyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
  pageTitle: {
    marginTop: 8,
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
    fontWeight: '900',
  },
  emptyCard: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 15,
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