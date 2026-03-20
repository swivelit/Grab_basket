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
  border: '#e7e7ea',
  shadow: 'rgba(15, 23, 42, 0.06)',
  peach: '#fff1ea',
  peachText: '#f97316',
  green: '#10b981',
  greenSoft: '#dcfce7',
  blackPurple: '#2a0222',
  blackPurpleSoft: '#4b003f',
  orderStatus: '#10b981',
  chipBg: '#efeff4',
  chipActive: '#000000',
  chipActiveText: '#ffffff',
  headerFrom: '#d95a54',
  headerTo: '#b33c59',
};

const DEMO_PROFILE = {
  name: 'Guest User',
  phone: '+91 - 0000000000',
  email: 'hello@grabbasket.app',
};

const QUICK_ACTIONS = [
  { key: 'address', icon: 'location-outline', label: 'Saved\nAddress' },
  { key: 'payment', icon: 'wallet-outline', label: 'Payment\nModes' },
  { key: 'refunds', icon: 'chatbubble-ellipses-outline', label: 'My\nRefunds' },
  { key: 'wallet', icon: 'card-outline', label: 'GrabBasket\nMoney' },
];

const ACCOUNT_ROWS = [
  { key: 'card', icon: 'card-outline', label: 'GrabBasket Credit Card' },
  { key: 'voucher', icon: 'ticket-outline', label: 'My Vouchers' },
  { key: 'statement', icon: 'document-text-outline', label: 'Account Statements' },
  { key: 'train', icon: 'train-outline', label: 'Order Food on Train' },
  { key: 'corporate', icon: 'briefcase-outline', label: 'Corporate Rewards' },
  { key: 'student', icon: 'school-outline', label: 'Student Rewards' },
  { key: 'wishlist', icon: 'bookmark-outline', label: 'My Instamart Wishlist' },
  { key: 'favourites', icon: 'heart-outline', label: 'Favourites' },
  { key: 'partner', icon: 'sparkles-outline', label: 'Partner Rewards' },
  { key: 'contact', icon: 'call-outline', label: 'Allow restaurants to contact you' },
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
    id: 'instamart-1',
    service: 'instamart',
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
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return String(name)
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getOrderAccent(service) {
  return service === 'instamart' ? '#dff6e8' : '#fde9e3';
}

function getOrderTag(service) {
  return service === 'instamart' ? 'Instamart' : 'Food';
}

function RatingStars() {
  return (
    <View style={styles.starsRow}>
      {[0, 1, 2, 3, 4].map((star) => (
        <Ionicons key={star} name="star-outline" size={18} color="#d1d5db" />
      ))}
    </View>
  );
}

function QuickActionCard({ icon, label, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.9} style={styles.quickActionCard} onPress={onPress}>
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
      activeOpacity={0.9}
      style={[styles.accountRow, !isLast && styles.accountRowBorder]}
      onPress={onPress}>
      <View style={styles.accountRowLeft}>
        <Ionicons name={icon} size={22} color={COLORS.text} />
        <Text style={styles.accountRowLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
    </TouchableOpacity>
  );
}

function PastOrderCard({ order, onReorder }) {
  const firstItem = order.items?.[0];
  const itemLine = firstItem
    ? `${firstItem.qty || 1} x ${firstItem.name}${order.items.length > 1 ? ` +${order.items.length - 1} more` : ''}`
    : 'Order';

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderHeaderRow}>
        <View style={[styles.orderThumb, { backgroundColor: getOrderAccent(order.service) }]}>
          <Text style={styles.orderThumbText}>{initials(order.vendorName)}</Text>
        </View>

        <View style={styles.orderMetaBlock}>
          <Text style={styles.orderStoreName} numberOfLines={1}>
            {order.vendorName}
          </Text>
          <Text style={styles.orderStoreLocation}>{order.location}</Text>
        </View>

        <View style={styles.orderStatusWrap}>
          <Text style={styles.orderStatus}>{order.status}</Text>
          <Ionicons name="checkmark-circle" size={16} color={COLORS.orderStatus} />
        </View>
      </View>

      <View style={styles.orderItemRow}>
        <View style={styles.orderQtyPill}>
          <Text style={styles.orderQtyPillText}>{firstItem?.qty || 1} x</Text>
        </View>
        <Text style={styles.orderItemText} numberOfLines={1}>
          {itemLine.replace(/^\d+\s+x\s+/i, '')}
        </Text>
      </View>

      <View style={styles.ratingSection}>
        <View style={styles.ratingCol}>
          <Text style={styles.ratingLabel}>Your {getOrderTag(order.service)} Rating</Text>
          <RatingStars />
        </View>

        <View style={styles.ratingDivider} />

        <View style={styles.ratingCol}>
          <Text style={styles.ratingLabel}>Delivery Rating</Text>
          <RatingStars />
        </View>
      </View>

      <TouchableOpacity activeOpacity={0.9} style={styles.reorderButton} onPress={onReorder}>
        <Text style={styles.reorderButtonText}>REORDER</Text>
        <Ionicons name="chevron-forward" size={16} color={COLORS.peachText} />
      </TouchableOpacity>

      <Text style={styles.orderFooterText}>
        Ordered: {order.orderedAt} • Bill Total: {money(order.total)}
      </Text>
    </View>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const scrollRef = useRef(null);
  const [pastOrdersY, setPastOrdersY] = useState(0);
  const [activeTab, setActiveTab] = useState('food');

  const { orderHistory } = useGrabBasket();

  const allOrders = useMemo(() => {
    return orderHistory?.length > 0 ? orderHistory : FALLBACK_ORDERS;
  }, [orderHistory]);

  const filteredOrders = useMemo(() => {
    return allOrders.filter((order) => order.service === activeTab);
  }, [activeTab, allOrders]);

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
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />

      <View style={styles.topBar}>
        <TouchableOpacity style={styles.topIconButton} onPress={handleBack}>
          <Ionicons name="arrow-back" size={24} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.topBarTitle}>MY ACCOUNT</Text>

        <View style={styles.topBarRight}>
          <TouchableOpacity style={styles.helpPill} activeOpacity={0.9}>
            <Text style={styles.helpPillText}>Help</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.topIconButton} activeOpacity={0.9}>
            <Ionicons name="ellipsis-vertical" size={20} color={COLORS.text} />
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

          <View style={styles.profileTextBlock}>
            <Text style={styles.profileName}>{DEMO_PROFILE.name}</Text>
            <Text style={styles.profileSubText}>{DEMO_PROFILE.phone}</Text>
            <Text style={styles.profileSubText}>{DEMO_PROFILE.email}</Text>
          </View>
        </View>

        <View style={styles.membershipCard}>
          <View style={styles.membershipTopRow}>
            <Text style={styles.membershipBrand}>one</Text>
            <View style={styles.activeBadge}>
              <Text style={styles.activeBadgeText}>ACTIVE</Text>
            </View>
          </View>

          <Text style={styles.membershipTitle}>₹35 saved in 36 days</Text>
          <Text style={styles.membershipSubtitle}>Explore all GrabBasket One benefits</Text>

          <View style={styles.membershipChevron}>
            <Ionicons name="chevron-down" size={20} color={COLORS.subtle} />
          </View>
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.blackBanner}>
          <View style={styles.blackBannerBadge}>
            <Text style={styles.blackBannerBadgeText}>BLCK</Text>
          </View>

          <View style={styles.blackBannerContent}>
            <Text style={styles.blackBannerTitle}>Your exclusive invite to upgrade is here!</Text>
            <Text style={styles.blackBannerSubtitle}>Join One BLCK now</Text>
          </View>

          <Ionicons name="chevron-forward" size={18} color="#ffffff" />
        </TouchableOpacity>

        <View style={styles.quickActionsRow}>
          {QUICK_ACTIONS.map((action) => (
            <QuickActionCard
              key={action.key}
              icon={action.icon}
              label={action.label}
              onPress={() => {}}
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
          <Text style={styles.browsePastOrdersText}>BROWSE PAST ORDERS</Text>
        </TouchableOpacity>

        <View onLayout={(event) => setPastOrdersY(event.nativeEvent.layout.y)}>
          <Text style={styles.pastOrdersTitle}>PAST ORDERS</Text>

          <View style={styles.segmentWrap}>
            <TouchableOpacity
              activeOpacity={0.92}
              style={[styles.segmentButton, activeTab === 'food' && styles.segmentButtonActive]}
              onPress={() => setActiveTab('food')}>
              <Text
                style={[
                  styles.segmentButtonText,
                  activeTab === 'food' && styles.segmentButtonTextActive,
                ]}>
                Food
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              activeOpacity={0.92}
              style={[styles.segmentButton, activeTab === 'instamart' && styles.segmentButtonActive]}
              onPress={() => setActiveTab('instamart')}>
              <Text
                style={[
                  styles.segmentButtonText,
                  activeTab === 'instamart' && styles.segmentButtonTextActive,
                ]}>
                Instamart
              </Text>
            </TouchableOpacity>
          </View>

          {filteredOrders.length > 0 ? (
            filteredOrders.map((order) => (
              <PastOrderCard
                key={order.id}
                order={order}
                onReorder={() => router.push('/reorder')}
              />
            ))
          ) : (
            <View style={styles.emptyOrdersCard}>
              <Text style={styles.emptyOrdersTitle}>No {getOrderTag(activeTab)} orders yet</Text>
              <Text style={styles.emptyOrdersText}>
                Once demo orders are placed, they will show up here in a Swiggy-style past orders flow.
              </Text>
            </View>
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
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f2',
    flexDirection: 'row',
    alignItems: 'center',
  },
  topBarTitle: {
    flex: 1,
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
    letterSpacing: 0.4,
    marginLeft: 8,
  },
  topBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  topIconButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
  },
  helpPill: {
    paddingHorizontal: 16,
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
    paddingTop: 14,
    paddingBottom: 40,
  },
  profileHero: {
    height: 182,
    borderRadius: 28,
    overflow: 'hidden',
    backgroundColor: COLORS.headerTo,
    marginBottom: 16,
    paddingHorizontal: 22,
    paddingVertical: 22,
    justifyContent: 'flex-end',
  },
  heroPatternOne: {
    position: 'absolute',
    right: -18,
    top: 18,
    width: 180,
    height: 180,
    borderRadius: 90,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  heroPatternTwo: {
    position: 'absolute',
    right: 56,
    top: 46,
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  heroPatternThree: {
    position: 'absolute',
    left: -36,
    bottom: -56,
    width: 220,
    height: 220,
    borderRadius: 110,
    backgroundColor: 'rgba(255,255,255,0.06)',
  },
  profileTextBlock: {
    zIndex: 1,
  },
  profileName: {
    fontSize: 20,
    fontWeight: '900',
    color: '#ffffff',
    marginBottom: 8,
  },
  profileSubText: {
    fontSize: 13,
    lineHeight: 19,
    color: 'rgba(255,255,255,0.88)',
    fontWeight: '500',
  },
  membershipCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    paddingHorizontal: 18,
    paddingVertical: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 14,
    position: 'relative',
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 6 },
    elevation: 1,
  },
  membershipTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  membershipBrand: {
    fontSize: 18,
    fontWeight: '900',
    color: '#f97316',
    marginRight: 10,
  },
  activeBadge: {
    backgroundColor: COLORS.green,
    borderRadius: 14,
    paddingHorizontal: 12,
    paddingVertical: 5,
  },
  activeBadgeText: {
    color: '#ffffff',
    fontWeight: '900',
    fontSize: 12,
    letterSpacing: 0.2,
  },
  membershipTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
    marginBottom: 4,
  },
  membershipSubtitle: {
    fontSize: 14,
    color: COLORS.muted,
    fontWeight: '500',
  },
  membershipChevron: {
    position: 'absolute',
    right: 16,
    top: 30,
  },
  blackBanner: {
    borderRadius: 18,
    backgroundColor: COLORS.blackPurple,
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 18,
  },
  blackBannerBadge: {
    minWidth: 48,
    height: 24,
    borderRadius: 12,
    backgroundColor: COLORS.blackPurpleSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
    paddingHorizontal: 8,
  },
  blackBannerBadgeText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  blackBannerContent: {
    flex: 1,
  },
  blackBannerTitle: {
    color: '#f3e8ff',
    fontSize: 13,
    fontWeight: '800',
    marginBottom: 2,
  },
  blackBannerSubtitle: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '900',
  },
  quickActionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 18,
  },
  quickActionCard: {
    width: '23.4%',
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 10,
    paddingTop: 14,
    paddingBottom: 14,
    minHeight: 136,
  },
  quickActionIconWrap: {
    marginBottom: 12,
  },
  quickActionText: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
    color: '#4b5563',
  },
  accountListCard: {
    backgroundColor: COLORS.card,
    borderRadius: 26,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
    marginBottom: 18,
  },
  accountRow: {
    minHeight: 62,
    paddingHorizontal: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: '#efeff2',
  },
  accountRowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    paddingRight: 16,
  },
  accountRowLabel: {
    marginLeft: 16,
    fontSize: 16,
    fontWeight: '600',
    color: '#4b5563',
    flex: 1,
  },
  browsePastOrdersButton: {
    alignSelf: 'center',
    backgroundColor: '#040816',
    borderRadius: 999,
    paddingHorizontal: 24,
    paddingVertical: 12,
    marginBottom: 22,
  },
  browsePastOrdersText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 0.4,
  },
  pastOrdersTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: COLORS.text,
    letterSpacing: 0.5,
    marginBottom: 14,
  },
  segmentWrap: {
    backgroundColor: COLORS.chipBg,
    borderRadius: 999,
    padding: 4,
    flexDirection: 'row',
    marginBottom: 18,
  },
  segmentButton: {
    flex: 1,
    height: 44,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  segmentButtonActive: {
    backgroundColor: COLORS.chipActive,
  },
  segmentButtonText: {
    fontSize: 17,
    fontWeight: '700',
    color: '#4b5563',
  },
  segmentButtonTextActive: {
    color: COLORS.chipActiveText,
  },
  orderCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 6 },
    elevation: 1,
  },
  orderHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 14,
  },
  orderThumb: {
    width: 58,
    height: 58,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  orderThumbText: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
  },
  orderMetaBlock: {
    flex: 1,
    paddingRight: 10,
  },
  orderStoreName: {
    fontSize: 17,
    fontWeight: '800',
    color: COLORS.text,
    marginBottom: 2,
  },
  orderStoreLocation: {
    fontSize: 14,
    color: COLORS.muted,
    fontWeight: '500',
  },
  orderStatusWrap: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  orderStatus: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.orderStatus,
    marginRight: 4,
  },
  orderItemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 14,
  },
  orderQtyPill: {
    minWidth: 36,
    height: 28,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
    paddingHorizontal: 8,
  },
  orderQtyPillText: {
    color: '#6b7280',
    fontWeight: '800',
    fontSize: 13,
  },
  orderItemText: {
    flex: 1,
    fontSize: 16,
    fontWeight: '500',
    color: '#374151',
  },
  ratingSection: {
    borderTopWidth: 1,
    borderTopColor: '#efeff2',
    borderBottomWidth: 1,
    borderBottomColor: '#efeff2',
    paddingVertical: 14,
    flexDirection: 'row',
    marginBottom: 14,
  },
  ratingCol: {
    flex: 1,
  },
  ratingDivider: {
    width: 1,
    backgroundColor: '#efeff2',
    marginHorizontal: 12,
  },
  ratingLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.muted,
    marginBottom: 8,
  },
  starsRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  reorderButton: {
    height: 48,
    borderRadius: 14,
    backgroundColor: COLORS.peach,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    marginBottom: 12,
  },
  reorderButtonText: {
    color: COLORS.peachText,
    fontSize: 17,
    fontWeight: '900',
    marginRight: 4,
  },
  orderFooterText: {
    fontSize: 14,
    color: COLORS.muted,
    fontWeight: '500',
  },
  emptyOrdersCard: {
    backgroundColor: COLORS.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
  },
  emptyOrdersTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.text,
    marginBottom: 6,
  },
  emptyOrdersText: {
    fontSize: 14,
    lineHeight: 21,
    color: COLORS.muted,
  },
});