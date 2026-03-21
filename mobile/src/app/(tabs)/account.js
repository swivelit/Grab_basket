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
  bg: '#f5f5f7',
  card: '#ffffff',
  text: '#171717',
  muted: '#6b7280',
  subtle: '#9ca3af',
  border: '#ececec',
  green: '#16a34a',
  greenSoft: '#ecfdf3',
  purple: '#8b1e4f',
  purpleDark: '#7a173f',
  purpleSoft: '#fde8ef',
  orangeSoft: '#fff3ec',
  orangeText: '#f97316',
  blueSoft: '#eaf2ff',
  blueText: '#0b57d0',
  yellowSoft: '#fff8db',
  yellowText: '#a16207',
  black: '#111827',
};

const DEMO_PROFILE = {
  name: 'Hari',
  phone: '+91 - 6238182925',
  email: 'harisajahan@gmail.com',
};

const QUICK_ACTIONS = [
  { key: 'address', icon: 'location-outline', label: 'Saved\nAddress' },
  { key: 'payment', icon: 'wallet-outline', label: 'Payment\nModes' },
  { key: 'refunds', icon: 'reload-outline', label: 'My\nRefunds' },
  { key: 'money', icon: 'card-outline', label: 'GrabBasket\nMoney' },
];

const ACCOUNT_ROWS = [
  { key: 'credit-card', icon: 'pricetag-outline', label: 'GrabBasket HDFC Bank Credit Card' },
  { key: 'vouchers', icon: 'ticket-outline', label: 'My Vouchers' },
  { key: 'statements', icon: 'document-text-outline', label: 'Account Statements' },
  { key: 'train', icon: 'train-outline', label: 'Order Food on Train' },
  { key: 'corporate', icon: 'briefcase-outline', label: 'Corporate Rewards' },
  { key: 'student', icon: 'school-outline', label: 'Student Rewards' },
  { key: 'wishlist', icon: 'bookmark-outline', label: 'My Instamart Wishlist' },
  { key: 'favourites', icon: 'heart-outline', label: 'Favourites' },
  { key: 'partner', icon: 'star-outline', label: 'Partner Rewards' },
  { key: 'contact', icon: 'chatbubble-ellipses-outline', label: 'Allow restaurants to contact you' },
];

const ORDER_FILTERS = [
  { key: 'food', label: 'Food' },
  { key: 'warehouse', label: 'Instamart' },
  { key: 'eatout', label: 'Dineout' },
];

const FALLBACK_ORDERS = [
  {
    id: 'food-1',
    service: 'food',
    vendorName: 'Bakeryt',
    location: 'Manali Rd',
    items: [{ name: 'Chocolate Truffle', qty: 1 }],
    orderedAt: 'February 11, 5:18 PM',
    total: 511,
    status: 'Delivered',
  },
  {
    id: 'food-2',
    service: 'food',
    vendorName: 'Sweet Truth - Cake and Desserts',
    location: 'Manali Rd',
    items: [{ name: 'Red Velvet Jar', qty: 2 }],
    orderedAt: 'February 9, 7:10 PM',
    total: 298,
    status: 'Delivered',
  },
  {
    id: 'warehouse-1',
    service: 'warehouse',
    vendorName: 'Instamart Daily',
    location: 'Great Orchard',
    items: [
      { name: 'Curd', qty: 1 },
      { name: 'Cadbury Dairy Milk', qty: 1 },
    ],
    orderedAt: 'March 18, 2:40 PM',
    total: 109,
    status: 'Delivered',
  },
  {
    id: 'eatout-1',
    service: 'eatout',
    vendorName: 'Cafe Papaya',
    location: 'Kakkanad',
    items: [{ name: 'Table for 2', qty: 1 }],
    orderedAt: 'March 20, 8:15 PM',
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
  if (normalized === 'warehouse') return 'Instamart';
  if (normalized === 'eatout') return 'Dineout';
  return 'Food';
}

function getServicePill(service = '') {
  const normalized = normalizeService(service);
  if (normalized === 'warehouse') {
    return {
      backgroundColor: COLORS.blueSoft,
      color: COLORS.blueText,
    };
  }
  if (normalized === 'eatout') {
    return {
      backgroundColor: COLORS.yellowSoft,
      color: COLORS.yellowText,
    };
  }
  return {
    backgroundColor: '#f3e8ff',
    color: '#6d28d9',
  };
}

function getStatusColor(status = '') {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'booked') return '#c2410c';
  return COLORS.green;
}

function getOrderThumbColor(service = '') {
  const normalized = normalizeService(service);
  if (normalized === 'warehouse') return COLORS.blueSoft;
  if (normalized === 'eatout') return COLORS.yellowSoft;
  return COLORS.purpleSoft;
}

function buildItemLine(order) {
  const firstItem = order.items?.[0];
  if (!firstItem) return 'Order';
  const moreCount = Math.max(0, order.items.length - 1);
  return `${firstItem.qty || 1} x ${firstItem.name}${moreCount > 0 ? ` +${moreCount} more` : ''}`;
}

function MembershipCard({ savedEstimate, totalOrders }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.membershipCard}>
      <View style={styles.membershipRowTop}>
        <View style={styles.membershipBrandRow}>
          <Text style={styles.membershipBrandOne}>one</Text>
          <View style={styles.activePill}>
            <Text style={styles.activePillText}>ACTIVE</Text>
          </View>
        </View>
        <Ionicons name="chevron-down" size={18} color={COLORS.muted} />
      </View>

      <Text style={styles.membershipSavings}>{money(savedEstimate)} saved in 36 days</Text>
      <Text style={styles.membershipCaption}>
        {totalOrders > 0 ? 'Explore all GrabBasket One benefits' : 'Start ordering to unlock more member savings'}
      </Text>
    </TouchableOpacity>
  );
}

function PromoBanner() {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.promoBanner}>
      <View style={styles.promoDot} />
      <View style={{ flex: 1 }}>
        <Text style={styles.promoTitle}>Your exclusive invite to upgrade is here!</Text>
        <Text style={styles.promoSubtitle}>Join One BLCK now</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color="#ffffff" />
    </TouchableOpacity>
  );
}

function QuickActionCard({ icon, label, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.quickActionCard} onPress={onPress}>
      <View style={styles.quickActionIconWrap}>
        <Ionicons name={icon} size={21} color={COLORS.text} />
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
        <Ionicons name={icon} size={21} color={COLORS.text} />
        <Text style={styles.accountRowLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
    </TouchableOpacity>
  );
}

function PastOrderCard({ order, onPressPrimary }) {
  const pill = getServicePill(order.service);

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderHeaderRow}>
        <View style={[styles.orderThumb, { backgroundColor: getOrderThumbColor(order.service) }]}>
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

        <Text style={[styles.orderStatus, { color: getStatusColor(order.status) }]}>{order.status}</Text>
      </View>

      <View style={styles.orderPillRow}>
        <View style={[styles.serviceTag, { backgroundColor: pill.backgroundColor }]}>
          <Text style={[styles.serviceTagText, { color: pill.color }]}>{getServiceLabel(order.service)}</Text>
        </View>
      </View>

      <Text style={styles.orderItemText}>{buildItemLine(order)}</Text>
      <Text style={styles.orderFooterText}>
        Ordered: {order.orderedAt} · Bill Total: {money(order.total)}
      </Text>

      <TouchableOpacity activeOpacity={0.92} style={styles.reorderButton} onPress={onPressPrimary}>
        <Text style={styles.reorderButtonText}>{normalizeService(order.service) === 'eatout' ? 'VIEW BOOKING' : 'REORDER'}</Text>
        <Ionicons name="chevron-forward" size={16} color={COLORS.orangeText} />
      </TouchableOpacity>
    </View>
  );
}

function EmptyOrdersState({ activeFilter }) {
  return (
    <View style={styles.emptyOrdersCard}>
      <Text style={styles.emptyOrdersTitle}>No {getServiceLabel(activeFilter).toLowerCase()} orders yet</Text>
      <Text style={styles.emptyOrdersText}>Orders placed from your cart will appear here.</Text>
    </View>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const scrollRef = useRef(null);
  const [pastOrdersY, setPastOrdersY] = useState(0);
  const [activeFilter, setActiveFilter] = useState('food');

  const { orderHistory, favorites, cartCount, cartTotal } = useGrabBasket();

  const allOrders = useMemo(() => {
    const source = orderHistory?.length > 0 ? orderHistory : FALLBACK_ORDERS;
    return source.map((order) => ({
      ...order,
      service: normalizeService(order.service),
    }));
  }, [orderHistory]);

  const filteredOrders = useMemo(
    () => allOrders.filter((order) => normalizeService(order.service) === activeFilter),
    [activeFilter, allOrders]
  );

  const stats = useMemo(() => {
    const totalOrders = allOrders.length;
    const favouriteCount = Object.values(favorites || {}).filter(Boolean).length;
    const savedEstimate = allOrders.reduce((sum, order) => sum + Math.round(Number(order.total || 0) * 0.04), 0);

    return {
      totalOrders,
      favouriteCount,
      savedEstimate,
    };
  }, [allOrders, favorites]);

  const handleBack = () => {
    if (router.canGoBack()) {
      router.back();
      return;
    }
    router.replace('/');
  };

  const scrollToPastOrders = () => {
    scrollRef.current?.scrollTo({
      y: Math.max(0, pastOrdersY - 12),
      animated: true,
    });
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <View style={styles.topBar}>
        <TouchableOpacity activeOpacity={0.92} style={styles.topIconButton} onPress={handleBack}>
          <Ionicons name="arrow-back" size={22} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.topBarTitle}>MY ACCOUNT</Text>

        <View style={styles.topBarRight}>
          <TouchableOpacity style={styles.helpPill} activeOpacity={0.92}>
            <Text style={styles.helpPillText}>Help</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.topIconButton} activeOpacity={0.92}>
            <Ionicons name="ellipsis-vertical" size={20} color={COLORS.text} />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        ref={scrollRef}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}>
        <View style={styles.profileHero}>
          <View style={styles.heroBlobOne} />
          <View style={styles.heroBlobTwo} />
          <View style={styles.heroBlobThree} />

          <Text style={styles.profileName}>{DEMO_PROFILE.name}</Text>
          <Text style={styles.profileSubText}>{DEMO_PROFILE.phone}</Text>
          <Text style={styles.profileSubText}>{DEMO_PROFILE.email}</Text>
        </View>

        <MembershipCard savedEstimate={stats.savedEstimate} totalOrders={stats.totalOrders} />
        <PromoBanner />

        <View style={styles.quickActionsRow}>
          {QUICK_ACTIONS.map((action) => (
            <QuickActionCard
              key={action.key}
              icon={action.icon}
              label={action.label}
              onPress={() => {
                if (action.key === 'payment') return;
                if (action.key === 'refunds') return;
                if (action.key === 'money') return;
                scrollToPastOrders();
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

        {cartCount > 0 ? (
          <TouchableOpacity activeOpacity={0.92} style={styles.liveBasketCard} onPress={() => router.push('/cart')}>
            <View style={styles.liveBasketIcon}>
              <Ionicons name="bag-handle-outline" size={20} color={COLORS.green} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.liveBasketTitle}>Active basket</Text>
              <Text style={styles.liveBasketSubtitle}>{cartCount} items · {money(cartTotal)}</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={COLORS.green} />
          </TouchableOpacity>
        ) : null}

        <TouchableOpacity activeOpacity={0.92} style={styles.browsePastOrdersButton} onPress={scrollToPastOrders}>
          <Text style={styles.browsePastOrdersText}>BROWSE PAST ORDERS</Text>
        </TouchableOpacity>

        <View onLayout={(event) => setPastOrdersY(event.nativeEvent.layout.y)}>
          <Text style={styles.sectionTitle}>PAST ORDERS</Text>

          <View style={styles.segmentWrap}>
            {ORDER_FILTERS.map((item) => {
              const active = activeFilter === item.key;
              return (
                <TouchableOpacity
                  key={item.key}
                  activeOpacity={0.92}
                  style={[styles.segmentButton, active && styles.segmentButtonActive]}
                  onPress={() => setActiveFilter(item.key)}>
                  <Text style={[styles.segmentButtonText, active && styles.segmentButtonTextActive]}>{item.label}</Text>
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

        <View style={styles.footerStatsRow}>
          <View style={[styles.footerStatCard, { backgroundColor: COLORS.greenSoft }]}>
            <Text style={styles.footerStatValue}>{stats.totalOrders}</Text>
            <Text style={styles.footerStatLabel}>Orders</Text>
          </View>
          <View style={[styles.footerStatCard, { backgroundColor: COLORS.blueSoft }]}>
            <Text style={styles.footerStatValue}>{stats.favouriteCount}</Text>
            <Text style={styles.footerStatLabel}>Favourites</Text>
          </View>
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
    height: 62,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.bg,
  },
  topIconButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  topBarTitle: {
    flex: 1,
    marginLeft: 8,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  topBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  helpPill: {
    marginRight: 6,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 18,
    backgroundColor: COLORS.orangeSoft,
  },
  helpPillText: {
    color: COLORS.orangeText,
    fontSize: 14,
    fontWeight: '800',
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 44,
  },
  profileHero: {
    minHeight: 190,
    borderRadius: 30,
    overflow: 'hidden',
    backgroundColor: '#d25959',
    paddingHorizontal: 20,
    paddingTop: 28,
    paddingBottom: 22,
    justifyContent: 'flex-end',
  },
  heroBlobOne: {
    position: 'absolute',
    right: -28,
    top: 26,
    width: 180,
    height: 180,
    borderRadius: 90,
    backgroundColor: 'rgba(109, 20, 49, 0.16)',
  },
  heroBlobTwo: {
    position: 'absolute',
    right: 44,
    top: 48,
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: 'rgba(109, 20, 49, 0.14)',
  },
  heroBlobThree: {
    position: 'absolute',
    left: -40,
    bottom: -54,
    width: 210,
    height: 210,
    borderRadius: 105,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  profileName: {
    color: '#ffffff',
    fontSize: 24,
    fontWeight: '900',
    marginBottom: 8,
  },
  profileSubText: {
    color: 'rgba(255,255,255,0.92)',
    fontSize: 15,
    lineHeight: 22,
    fontWeight: '500',
  },
  membershipCard: {
    marginTop: 14,
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 18,
    paddingVertical: 16,
  },
  membershipRowTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  membershipBrandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  membershipBrandOne: {
    color: '#f97316',
    fontSize: 22,
    fontWeight: '900',
  },
  activePill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: COLORS.greenSoft,
  },
  activePillText: {
    color: COLORS.green,
    fontSize: 11,
    fontWeight: '900',
  },
  membershipSavings: {
    marginTop: 12,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  membershipCaption: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },
  promoBanner: {
    marginTop: 12,
    borderRadius: 18,
    backgroundColor: '#2d0125',
    paddingHorizontal: 16,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  promoDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#c084fc',
  },
  promoTitle: {
    color: '#f8d8ff',
    fontSize: 13,
    fontWeight: '700',
  },
  promoSubtitle: {
    color: '#ffffff',
    fontSize: 20,
    fontWeight: '900',
    marginTop: 2,
  },
  quickActionsRow: {
    marginTop: 14,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  quickActionCard: {
    width: '22%',
    minHeight: 108,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 14,
    justifyContent: 'space-between',
  },
  quickActionIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 16,
    backgroundColor: '#f4f4f5',
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickActionText: {
    color: COLORS.text,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
  },
  accountListCard: {
    marginTop: 14,
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
  },
  accountRow: {
    minHeight: 60,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  accountRowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    flex: 1,
    paddingRight: 12,
  },
  accountRowLabel: {
    flex: 1,
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '600',
  },
  liveBasketCard: {
    marginTop: 14,
    borderRadius: 22,
    backgroundColor: COLORS.greenSoft,
    borderWidth: 1,
    borderColor: '#bbf7d0',
    paddingHorizontal: 16,
    paddingVertical: 14,
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
    color: COLORS.green,
    fontSize: 15,
    fontWeight: '900',
  },
  liveBasketSubtitle: {
    marginTop: 4,
    color: COLORS.green,
    fontSize: 12,
    fontWeight: '700',
  },
  browsePastOrdersButton: {
    marginTop: 16,
    alignSelf: 'center',
    borderRadius: 999,
    backgroundColor: '#050816',
    paddingHorizontal: 24,
    paddingVertical: 14,
  },
  browsePastOrdersText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  sectionTitle: {
    marginTop: 24,
    marginBottom: 12,
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  segmentWrap: {
    flexDirection: 'row',
    borderRadius: 18,
    backgroundColor: '#ececf1',
    padding: 4,
    marginBottom: 14,
  },
  segmentButton: {
    flex: 1,
    minHeight: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  segmentButtonActive: {
    backgroundColor: '#050816',
  },
  segmentButtonText: {
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '800',
  },
  segmentButtonTextActive: {
    color: '#ffffff',
  },
  orderCard: {
    marginBottom: 14,
    borderRadius: 24,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
  orderHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderThumb: {
    width: 54,
    height: 54,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderThumbText: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  orderMetaBlock: {
    flex: 1,
    paddingRight: 8,
  },
  orderStoreName: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  orderStoreLocation: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },
  orderStatus: {
    fontSize: 16,
    fontWeight: '900',
  },
  orderPillRow: {
    marginTop: 14,
    marginBottom: 8,
  },
  serviceTag: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  serviceTagText: {
    fontSize: 12,
    fontWeight: '900',
  },
  orderItemText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '600',
  },
  orderFooterText: {
    marginTop: 12,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },
  reorderButton: {
    marginTop: 16,
    minHeight: 48,
    borderRadius: 16,
    backgroundColor: COLORS.orangeSoft,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
  },
  reorderButtonText: {
    color: COLORS.orangeText,
    fontSize: 15,
    fontWeight: '900',
  },
  emptyOrdersCard: {
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
  },
  emptyOrdersTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  emptyOrdersText: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  footerStatsRow: {
    marginTop: 8,
    flexDirection: 'row',
    gap: 12,
  },
  footerStatCard: {
    flex: 1,
    borderRadius: 18,
    padding: 16,
  },
  footerStatValue: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  footerStatLabel: {
    marginTop: 6,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '700',
  },
});