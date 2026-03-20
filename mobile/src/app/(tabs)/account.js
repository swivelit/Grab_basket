import React, { useMemo, useRef, useState } from 'react';
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
import { useGrabBasket } from '../../../App';

const COLORS = {
  bg: '#f5f6f8',
  card: '#ffffff',
  text: '#111827',
  muted: '#6b7280',
  subtle: '#9ca3af',
  border: '#e5e7eb',
  green: '#0f9d58',
  greenDark: '#07693b',
  greenSoft: '#ecfdf5',
  purple: '#6d28d9',
  purpleDark: '#4f1bb0',
  purpleSoft: '#ede9fe',
  blueDark: '#082a73',
  blueSoft: '#dbeafe',
  yellowSoft: '#fef3c7',
  peach: '#fff1ea',
  peachText: '#f97316',
  shadow: 'rgba(15, 23, 42, 0.08)',
};

const DEMO_PROFILE = {
  name: 'Guest User',
  phone: '+91 00000 00000',
  email: 'hello@grabbasket.app',
  address: 'Valliachans Place · Great Orchard',
};

const QUICK_ACTIONS = [
  { key: 'orders', icon: 'time-outline', label: 'Past\nOrders' },
  { key: 'address', icon: 'location-outline', label: 'Saved\nAddress' },
  { key: 'payment', icon: 'wallet-outline', label: 'Payment\nModes' },
  { key: 'support', icon: 'help-buoy-outline', label: 'Help &\nSupport' },
];

const ACCOUNT_ROWS = [
  { key: 'plus', icon: 'sparkles-outline', label: 'GrabBasket One' },
  { key: 'wallet', icon: 'card-outline', label: 'GrabBasket Money' },
  { key: 'vouchers', icon: 'ticket-outline', label: 'My Vouchers' },
  { key: 'warehouse-wishlist', icon: 'bookmark-outline', label: 'My Warehouse Wishlist' },
  { key: 'favourites', icon: 'heart-outline', label: 'Favourite Stores' },
  { key: 'eatout', icon: 'restaurant-outline', label: 'Eatout Offers & Bookings' },
  { key: 'statements', icon: 'document-text-outline', label: 'Account Statements' },
  { key: 'notifications', icon: 'notifications-outline', label: 'Notification Preferences' },
  { key: 'contact', icon: 'call-outline', label: 'Restaurant Contact Preferences' },
];

const ORDER_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'food', label: 'Food' },
  { key: 'warehouse', label: 'Warehouse' },
  { key: 'eatout', label: 'Eatout' },
];

const FALLBACK_ORDERS = [
  {
    id: 'food-1',
    service: 'food',
    vendorName: 'Bakeryt',
    location: 'Manali Rd',
    items: [{ name: 'Chocolate Truffle', qty: 1 }],
    orderedAt: 'Feb 11, 5:18 PM',
    total: 511,
    status: 'Delivered',
  },
  {
    id: 'food-2',
    service: 'food',
    vendorName: 'Sweet Truth - Cake & Desserts',
    location: 'Manali Rd',
    items: [{ name: 'Red Velvet Jar', qty: 2 }],
    orderedAt: 'Feb 09, 7:10 PM',
    total: 298,
    status: 'Delivered',
  },
  {
    id: 'warehouse-1',
    service: 'warehouse',
    vendorName: 'Warehouse Daily',
    location: 'Great Orchard',
    items: [
      { name: 'Curd', qty: 1 },
      { name: 'Cadbury Dairy Milk', qty: 1 },
    ],
    orderedAt: 'Mar 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
  {
    id: 'eatout-1',
    service: 'eatout',
    vendorName: 'Cafe Papaya',
    location: 'Kakkanad',
    items: [{ name: 'Table for 2', qty: 1 }],
    orderedAt: 'Mar 20, 8:15 PM',
    total: 799,
    status: 'Booked',
  },
];

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

function normalizeService(value = '') {
  const service = String(value || '').trim().toLowerCase();
  if (service === 'instamart') return 'warehouse';
  if (service === 'dineout') return 'eatout';
  return service || 'food';
}

function getServiceLabel(service = '') {
  const normalized = normalizeService(service);
  if (normalized === 'warehouse') return 'Warehouse';
  if (normalized === 'eatout') return 'Eatout';
  return 'Food';
}

function getServiceAccent(service = '') {
  const normalized = normalizeService(service);
  if (normalized === 'warehouse') return COLORS.blueSoft;
  if (normalized === 'eatout') return COLORS.yellowSoft;
  return COLORS.purpleSoft;
}

function getServicePillStyle(service = '') {
  const normalized = normalizeService(service);
  if (normalized === 'warehouse') {
    return {
      backgroundColor: '#eff6ff',
      textColor: COLORS.blueDark,
    };
  }
  if (normalized === 'eatout') {
    return {
      backgroundColor: '#fff7ed',
      textColor: '#c2410c',
    };
  }
  return {
    backgroundColor: '#f5f3ff',
    textColor: COLORS.purpleDark,
  };
}

function getStatusColor(status = '') {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'booked') return '#c2410c';
  return COLORS.greenDark;
}

function buildItemLine(order) {
  const firstItem = order.items?.[0];
  if (!firstItem) return 'Order';
  const moreCount = Math.max(0, order.items.length - 1);
  return `${firstItem.qty || 1} x ${firstItem.name}${moreCount > 0 ? ` +${moreCount} more` : ''}`;
}

function StatCard({ label, value, tint }) {
  return (
    <View style={[styles.statCard, { backgroundColor: tint }]}>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function QuickActionCard({ icon, label, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.quickActionCard} onPress={onPress}>
      <View style={styles.quickActionIconWrap}>
        <Ionicons name={icon} size={22} color={COLORS.text} />
      </View>
      <Text style={styles.quickActionText}>{label}</Text>
    </TouchableOpacity>
  );
}

function AccountListRow({ icon, label, isLast = false, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.accountRow, !isLast && styles.accountRowBorder]}
      onPress={onPress}>
      <View style={styles.accountRowLeft}>
        <Ionicons name={icon} size={20} color={COLORS.text} />
        <Text style={styles.accountRowLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
    </TouchableOpacity>
  );
}

function EmptyOrdersState({ activeFilter }) {
  return (
    <View style={styles.emptyOrdersCard}>
      <Text style={styles.emptyOrdersTitle}>
        No {activeFilter === 'all' ? '' : `${getServiceLabel(activeFilter)} `}orders yet
      </Text>
      <Text style={styles.emptyOrdersText}>
        Demo orders and bookings placed from cart will show up here with the new Food, Warehouse and Eatout flows.
      </Text>
    </View>
  );
}

function PastOrderCard({ order, onPressPrimary }) {
  const normalizedService = normalizeService(order.service);
  const pill = getServicePillStyle(normalizedService);
  const itemLine = buildItemLine(order);

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderHeaderRow}>
        <View style={[styles.orderThumb, { backgroundColor: getServiceAccent(normalizedService) }]}>
          <Text style={styles.orderThumbText}>{initials(order.vendorName)}</Text>
        </View>

        <View style={styles.orderMetaBlock}>
          <Text style={styles.orderStoreName} numberOfLines={1}>
            {order.vendorName}
          </Text>
          <Text style={styles.orderStoreLocation} numberOfLines={1}>
            {order.location}
          </Text>
        </View>

        <View style={styles.orderStatusWrap}>
          <Text style={[styles.orderStatus, { color: getStatusColor(order.status) }]}>{order.status}</Text>
        </View>
      </View>

      <View style={styles.orderPillRow}>
        <View style={[styles.serviceTag, { backgroundColor: pill.backgroundColor }]}>
          <Text style={[styles.serviceTagText, { color: pill.textColor }]}>{getServiceLabel(normalizedService)}</Text>
        </View>
      </View>

      <Text style={styles.orderItemText}>{itemLine}</Text>
      <Text style={styles.orderFooterText}>
        Ordered: {order.orderedAt} · Bill Total: {money(order.total)}
      </Text>

      <View style={styles.orderActionRow}>
        <TouchableOpacity activeOpacity={0.92} style={styles.reorderButton} onPress={onPressPrimary}>
          <Text style={styles.reorderButtonText}>
            {normalizedService === 'eatout' ? 'VIEW BOOKING' : 'REORDER'}
          </Text>
          <Ionicons name="chevron-forward" size={16} color={COLORS.peachText} />
        </TouchableOpacity>
      </View>
    </View>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const scrollRef = useRef(null);
  const [pastOrdersY, setPastOrdersY] = useState(0);
  const [activeFilter, setActiveFilter] = useState('all');

  const { orderHistory, recentSearches, favorites, cartCount, cartTotal } = useGrabBasket();

  const allOrders = useMemo(() => {
    const source = orderHistory?.length > 0 ? orderHistory : FALLBACK_ORDERS;
    return source.map((order) => ({
      ...order,
      service: normalizeService(order.service),
    }));
  }, [orderHistory]);

  const filteredOrders = useMemo(() => {
    if (activeFilter === 'all') return allOrders;
    return allOrders.filter((order) => normalizeService(order.service) === activeFilter);
  }, [activeFilter, allOrders]);

  const stats = useMemo(() => {
    const totalOrders = allOrders.length;
    const favouriteCount = Object.values(favorites || {}).filter(Boolean).length;
    const searchCount = recentSearches?.length || 0;
    const savedEstimate = allOrders.reduce((sum, order) => sum + Math.round(Number(order.total || 0) * 0.04), 0);

    return {
      totalOrders,
      favouriteCount,
      searchCount,
      savedEstimate,
    };
  }, [allOrders, favorites, recentSearches]);

  const handleBack = () => {
    if (router.canGoBack()) {
      router.back();
      return;
    }
    router.replace('/');
  };

  const scrollToPastOrders = () => {
    scrollRef.current?.scrollTo({
      y: Math.max(0, pastOrdersY - 16),
      animated: true,
    });
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <View style={styles.topBar}>
        <TouchableOpacity style={styles.topIconButton} onPress={handleBack}>
          <Ionicons name="arrow-back" size={22} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.topBarTitle}>My Account</Text>

        <View style={styles.topBarRight}>
          <TouchableOpacity style={styles.helpPill} activeOpacity={0.92}>
            <Text style={styles.helpPillText}>Help</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.topIconButton} activeOpacity={0.92}>
            <Ionicons name="ellipsis-horizontal" size={20} color={COLORS.text} />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        ref={scrollRef}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}>
        <View style={styles.profileHero}>
          <View style={styles.heroPatternOne} />
          <View style={styles.heroPatternTwo} />
          <View style={styles.heroPatternThree} />

          <View style={styles.profileTopRow}>
            <View style={styles.avatarCircle}>
              <Text style={styles.avatarText}>{initials(DEMO_PROFILE.name)}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.profileName}>{DEMO_PROFILE.name}</Text>
              <Text style={styles.profileSubText}>{DEMO_PROFILE.phone}</Text>
              <Text style={styles.profileSubText}>{DEMO_PROFILE.email}</Text>
              <Text style={styles.profileAddress}>{DEMO_PROFILE.address}</Text>
            </View>
          </View>

          <View style={styles.profileBadgeRow}>
            <View style={styles.heroBadge}>
              <Ionicons name="shield-checkmark-outline" size={14} color="#ffffff" />
              <Text style={styles.heroBadgeText}>Guest mode</Text>
            </View>
            <View style={styles.heroBadge}>
              <Ionicons name="bag-handle-outline" size={14} color="#ffffff" />
              <Text style={styles.heroBadgeText}>{stats.totalOrders} orders</Text>
            </View>
          </View>
        </View>

        <View style={styles.statsRow}>
          <StatCard label="Orders" value={String(stats.totalOrders)} tint="#ffffff" />
          <StatCard label="Saved est." value={money(stats.savedEstimate)} tint="#ecfdf5" />
          <StatCard label="Favourites" value={String(stats.favouriteCount)} tint="#eff6ff" />
          <StatCard label="Searches" value={String(stats.searchCount)} tint="#fef3c7" />
        </View>

        <View style={styles.membershipCard}>
          <View style={styles.membershipTopRow}>
            <View>
              <Text style={styles.membershipEyebrow}>GrabBasket One</Text>
              <Text style={styles.membershipTitle}>Production-ready loyalty surface</Text>
            </View>
            <View style={styles.activeBadge}>
              <Text style={styles.activeBadgeText}>ACTIVE</Text>
            </View>
          </View>

          <Text style={styles.membershipSubtitle}>
            Consistent savings messaging, clearer rewards language and cleaner hierarchy.
          </Text>

          <View style={styles.membershipFeatureRow}>
            <View style={styles.membershipFeature}>
              <Ionicons name="flash-outline" size={16} color={COLORS.greenDark} />
              <Text style={styles.membershipFeatureText}>Faster checkout feel</Text>
            </View>
            <View style={styles.membershipFeature}>
              <Ionicons name="pricetag-outline" size={16} color={COLORS.greenDark} />
              <Text style={styles.membershipFeatureText}>Offer-led surfaces</Text>
            </View>
          </View>
        </View>

        {cartCount > 0 ? (
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.liveBasketCard}
            onPress={() => router.push('/cart')}>
            <View style={styles.liveBasketIcon}>
              <Ionicons name="bag-handle-outline" size={20} color={COLORS.greenDark} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.liveBasketTitle}>You have an active basket</Text>
              <Text style={styles.liveBasketSubtitle}>
                {cartCount} items · {money(cartTotal)}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={COLORS.greenDark} />
          </TouchableOpacity>
        ) : null}

        <View style={styles.quickActionsRow}>
          {QUICK_ACTIONS.map((action) => (
            <QuickActionCard
              key={action.key}
              icon={action.icon}
              label={action.label}
              onPress={() => {
                if (action.key === 'orders') {
                  scrollToPastOrders();
                  return;
                }
                if (action.key === 'support') {
                  return;
                }
                if (action.key === 'payment') {
                  return;
                }
                if (action.key === 'address') {
                  return;
                }
              }}
            />
          ))}
        </View>

        <View style={styles.accountListCard}>
          {ACCOUNT_ROWS.map((row, index) => (
            <AccountListRow
              key={row.key}
              icon={row.icon}
              label={row.label}
              isLast={index === ACCOUNT_ROWS.length - 1}
              onPress={() => {}}
            />
          ))}
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.browsePastOrdersButton} onPress={scrollToPastOrders}>
          <Text style={styles.browsePastOrdersText}>Browse past orders</Text>
          <Ionicons name="arrow-down" size={16} color={COLORS.text} />
        </TouchableOpacity>

        <View onLayout={(event) => setPastOrdersY(event.nativeEvent.layout.y)}>
          <View style={styles.sectionHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sectionTitle}>Past Orders</Text>
              <Text style={styles.sectionSubtitle}>
                Cleanly separated across Food, Warehouse and Eatout.
              </Text>
            </View>
          </View>

          <View style={styles.segmentWrap}>
            {ORDER_FILTERS.map((item) => {
              const active = activeFilter === item.key;
              return (
                <TouchableOpacity
                  key={item.key}
                  activeOpacity={0.92}
                  style={[styles.segmentButton, active && styles.segmentButtonActive]}
                  onPress={() => setActiveFilter(item.key)}>
                  <Text
                    style={[
                      styles.segmentButtonText,
                      active && styles.segmentButtonTextActive,
                    ]}>
                    {item.label}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>

          {filteredOrders.length > 0 ? (
            filteredOrders.map((order) => (
              <PastOrderCard
                key={order.id}
                order={order}
                onPressPrimary={() => router.push('/reorder')}
              />
            ))
          ) : (
            <EmptyOrdersState activeFilter={activeFilter} />
          )}
        </View>
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
    height: 64,
    paddingHorizontal: 16,
    backgroundColor: COLORS.bg,
    flexDirection: 'row',
    alignItems: 'center',
  },
  topBarTitle: {
    flex: 1,
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
    marginLeft: 8,
  },
  topBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  topIconButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  helpPill: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 18,
    backgroundColor: COLORS.peach,
    marginRight: 6,
  },
  helpPillText: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.peachText,
  },

  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 44,
  },

  profileHero: {
    minHeight: 206,
    borderRadius: 30,
    overflow: 'hidden',
    backgroundColor: COLORS.purpleDark,
    marginBottom: 16,
    paddingHorizontal: 20,
    paddingVertical: 20,
    justifyContent: 'space-between',
  },
  heroPatternOne: {
    position: 'absolute',
    right: -20,
    top: 18,
    width: 170,
    height: 170,
    borderRadius: 85,
    backgroundColor: 'rgba(255,255,255,0.06)',
  },
  heroPatternTwo: {
    position: 'absolute',
    right: 52,
    top: 52,
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  heroPatternThree: {
    position: 'absolute',
    left: -42,
    bottom: -60,
    width: 220,
    height: 220,
    borderRadius: 110,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  profileTopRow: {
    zIndex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  avatarCircle: {
    width: 62,
    height: 62,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '900',
  },
  profileName: {
    fontSize: 22,
    fontWeight: '900',
    color: '#ffffff',
  },
  profileSubText: {
    fontSize: 13,
    lineHeight: 18,
    color: 'rgba(255,255,255,0.88)',
    marginTop: 4,
  },
  profileAddress: {
    fontSize: 13,
    lineHeight: 18,
    color: '#ddd6fe',
    marginTop: 6,
    fontWeight: '700',
  },
  profileBadgeRow: {
    zIndex: 1,
    flexDirection: 'row',
    gap: 10,
    flexWrap: 'wrap',
  },
  heroBadge: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.12)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  heroBadgeText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '800',
  },

  statsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 16,
  },
  statCard: {
    width: '47%',
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  statValue: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
  },
  statLabel: {
    color: COLORS.muted,
    fontSize: 13,
    marginTop: 6,
    fontWeight: '700',
  },

  membershipCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    paddingHorizontal: 18,
    paddingVertical: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 14,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 6 },
    elevation: 1,
  },
  membershipTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
  membershipEyebrow: {
    color: COLORS.greenDark,
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'uppercase',
  },
  membershipTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    marginTop: 6,
  },
  membershipSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 19,
    marginTop: 10,
  },
  membershipFeatureRow: {
    flexDirection: 'row',
    gap: 12,
    flexWrap: 'wrap',
    marginTop: 14,
  },
  membershipFeature: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.greenSoft,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  membershipFeatureText: {
    color: COLORS.greenDark,
    fontSize: 12,
    fontWeight: '800',
  },
  activeBadge: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: COLORS.greenSoft,
  },
  activeBadgeText: {
    color: COLORS.greenDark,
    fontSize: 11,
    fontWeight: '900',
  },

  liveBasketCard: {
    marginBottom: 14,
    borderRadius: 22,
    backgroundColor: COLORS.greenSoft,
    borderWidth: 1,
    borderColor: '#bbf7d0',
    paddingHorizontal: 16,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  liveBasketIcon: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#dcfce7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  liveBasketTitle: {
    color: COLORS.greenDark,
    fontSize: 15,
    fontWeight: '900',
  },
  liveBasketSubtitle: {
    color: COLORS.greenDark,
    fontSize: 12,
    marginTop: 4,
    fontWeight: '700',
  },

  quickActionsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 14,
  },
  quickActionCard: {
    width: '47%',
    backgroundColor: COLORS.card,
    borderRadius: 22,
    paddingHorizontal: 14,
    paddingVertical: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    minHeight: 108,
    justifyContent: 'space-between',
  },
  quickActionIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickActionText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
    lineHeight: 19,
  },

  accountListCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    marginBottom: 14,
  },
  accountRow: {
    minHeight: 58,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  accountRowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingRight: 10,
    flex: 1,
  },
  accountRowLabel: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    flex: 1,
  },

  browsePastOrdersButton: {
    height: 52,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 18,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
  },
  browsePastOrdersText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '900',
    textTransform: 'uppercase',
  },

  sectionHeader: {
    marginBottom: 14,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '900',
  },
  sectionSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 18,
    marginTop: 4,
  },

  segmentWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 16,
  },
  segmentButton: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  segmentButtonActive: {
    backgroundColor: COLORS.text,
    borderColor: COLORS.text,
  },
  segmentButtonText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },
  segmentButtonTextActive: {
    color: '#ffffff',
  },

  orderCard: {
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    marginBottom: 14,
    shadowColor: '#000',
    shadowOpacity: 0.02,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
    elevation: 1,
  },
  orderHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderThumb: {
    width: 52,
    height: 52,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderThumbText: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  orderMetaBlock: {
    flex: 1,
  },
  orderStoreName: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  orderStoreLocation: {
    color: COLORS.muted,
    fontSize: 12,
    marginTop: 4,
  },
  orderStatusWrap: {
    alignItems: 'flex-end',
  },
  orderStatus: {
    fontSize: 12,
    fontWeight: '900',
  },
  orderPillRow: {
    flexDirection: 'row',
    marginTop: 14,
  },
  serviceTag: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  serviceTagText: {
    fontSize: 11,
    fontWeight: '900',
  },
  orderItemText: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 12,
  },
  orderFooterText: {
    color: COLORS.muted,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 8,
  },
  orderActionRow: {
    marginTop: 14,
  },
  reorderButton: {
    alignSelf: 'flex-start',
    paddingHorizontal: 14,
    paddingVertical: 11,
    borderRadius: 14,
    backgroundColor: COLORS.peach,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  reorderButtonText: {
    color: COLORS.peachText,
    fontSize: 13,
    fontWeight: '900',
  },

  emptyOrdersCard: {
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
    marginBottom: 14,
  },
  emptyOrdersTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  emptyOrdersText: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 19,
    marginTop: 8,
  },
});