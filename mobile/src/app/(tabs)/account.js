import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useGrabBasket } from '../../../App';

const COLORS = {
  bg: '#f6f7fb',
  card: '#ffffff',
  text: '#111827',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e9edf5',
  shadow: 'rgba(15, 23, 42, 0.08)',

  hero: '#cf5d5c',
  heroDark: '#a83d50',
  heroSoft: 'rgba(255,255,255,0.08)',

  green: '#119b56',
  greenSoft: '#e8f8ee',
  orange: '#ff6d00',
  orangeSoft: '#fff1e7',
  purple: '#4b0f35',
  purpleSoft: '#f8e6ef',
  blue: '#0b57d0',
  blueSoft: '#eaf2ff',
  yellow: '#a16207',
  yellowSoft: '#fff8db',

  black: '#050816',
  blackSoft: '#111827',
  pink: '#b33968',
};

const QUICK_ACTIONS = [
  { key: 'saved-address', icon: 'location-outline', label: 'Saved\nAddress' },
  { key: 'payment-modes', icon: 'wallet-outline', label: 'Payment\nModes' },
  { key: 'refunds', icon: 'reload-outline', label: 'My\nRefunds' },
  { key: 'wallet', icon: 'card-outline', label: 'GrabBasket\nMoney' },
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

function getServiceTone(service = '') {
  const normalized = normalizeService(service);

  if (normalized === 'warehouse') {
    return {
      pillBg: COLORS.blueSoft,
      pillColor: COLORS.blue,
      thumbBg: '#dfeaff',
      actionBg: '#edf4ff',
      actionColor: COLORS.blue,
    };
  }

  if (normalized === 'eatout') {
    return {
      pillBg: COLORS.yellowSoft,
      pillColor: COLORS.yellow,
      thumbBg: '#fff0d1',
      actionBg: '#fff5e6',
      actionColor: COLORS.yellow,
    };
  }

  return {
    pillBg: COLORS.purpleSoft,
    pillColor: COLORS.pink,
    thumbBg: '#f8e8ef',
    actionBg: COLORS.orangeSoft,
    actionColor: COLORS.orange,
  };
}

function getStatusColor(status = '') {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'booked') return '#c2410c';
  if (normalized === 'cancelled') return '#dc2626';
  return COLORS.green;
}

function buildItemLine(order) {
  const firstItem = order?.items?.[0];
  if (!firstItem) return 'Order';
  const moreCount = Math.max(0, (order.items?.length || 0) - 1);
  return `${firstItem.qty || 1} x ${firstItem.name}${moreCount > 0 ? ` +${moreCount} more` : ''}`;
}

function resolveProfile(allOrders, recentSearches) {
  const topOrder = allOrders?.[0];

  return {
    name: 'GrabBasket user',
    phone: topOrder ? 'Fast checkout enabled' : 'Add phone number',
    email:
      recentSearches?.length > 0
        ? `Recent interest: ${recentSearches[0]}`
        : 'Add email to receive invoices',
  };
}

function resolveOrderVendorId(order, vendors = []) {
  if (order?.vendorId) return order.vendorId;

  const match = vendors.find(
    (vendor) =>
      String(vendor?.name || '').trim().toLowerCase() ===
      String(order?.vendorName || '').trim().toLowerCase()
  );

  return match?.id || null;
}

function SectionHeader({ title, actionLabel, onPressAction }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onPressAction}>
          <Text style={styles.sectionAction}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function ProfileHero({ profile, totalOrders, favouriteCount }) {
  return (
    <View style={styles.profileHero}>
      <View style={styles.heroBlobOne} />
      <View style={styles.heroBlobTwo} />
      <View style={styles.heroBlobThree} />

      <View style={styles.profileHeaderRow}>
        <View style={styles.profileAvatar}>
          <Text style={styles.profileAvatarText}>{initials(profile.name)}</Text>
        </View>

        <View style={styles.profileBadgeRow}>
          <View style={styles.profileBadge}>
            <Ionicons name="flash-outline" size={14} color="#ffffff" />
            <Text style={styles.profileBadgeText}>one</Text>
          </View>
          <View style={styles.profileBadgeMuted}>
            <Text style={styles.profileBadgeMutedText}>{totalOrders} orders</Text>
          </View>
          <View style={styles.profileBadgeMuted}>
            <Text style={styles.profileBadgeMutedText}>{favouriteCount} favourites</Text>
          </View>
        </View>
      </View>

      <Text style={styles.profileName}>{profile.name}</Text>
      <Text style={styles.profileSubText}>{profile.phone}</Text>
      <Text style={styles.profileSubText}>{profile.email}</Text>
    </View>
  );
}

function MembershipCard({ savedEstimate, totalOrders }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.membershipCard}>
      <View style={styles.membershipTopRow}>
        <View style={styles.membershipBrandRow}>
          <Text style={styles.membershipBrandOne}>one</Text>
          <View style={styles.membershipActivePill}>
            <Text style={styles.membershipActivePillText}>ACTIVE</Text>
          </View>
        </View>
        <Ionicons name="chevron-down" size={18} color={COLORS.muted} />
      </View>

      <Text style={styles.membershipSavings}>
        {money(savedEstimate)} saved in 36 days
      </Text>
      <Text style={styles.membershipCaption}>
        {totalOrders > 0
          ? 'Explore all GrabBasket One benefits'
          : 'Start ordering to unlock member savings and faster support'}
      </Text>
    </TouchableOpacity>
  );
}

function PromoBanner() {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.promoBanner}>
      <View style={styles.promoSignal}>
        <Ionicons name="sparkles-outline" size={16} color="#f6d8ff" />
      </View>

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

function MiniStoreCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.miniStoreCard} onPress={onPress}>
      <View style={styles.miniStoreAvatar}>
        <Text style={styles.miniStoreAvatarText}>{initials(vendor?.name)}</Text>
      </View>
      <Text style={styles.miniStoreName} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={styles.miniStoreMeta} numberOfLines={1}>
        {vendor?.distance_km != null ? `${Number(vendor.distance_km).toFixed(1)} km away` : 'Popular near you'}
      </Text>
    </TouchableOpacity>
  );
}

function SearchChip({ label }) {
  return (
    <View style={styles.searchChip}>
      <Ionicons name="search-outline" size={14} color={COLORS.muted} />
      <Text style={styles.searchChipText}>{label}</Text>
    </View>
  );
}

function RatingStars({ label }) {
  return (
    <View style={styles.ratingColumn}>
      <Text style={styles.ratingLabel}>{label}</Text>
      <View style={styles.ratingStarsRow}>
        {[0, 1, 2, 3, 4].map((value) => (
          <Ionicons key={value} name="star-outline" size={15} color="#d1d5db" />
        ))}
      </View>
    </View>
  );
}

function PastOrderCard({ order, onPressPrimary }) {
  const tone = getServiceTone(order.service);
  const isEatout = normalizeService(order.service) === 'eatout';

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderTopRow}>
        <View style={[styles.orderThumb, { backgroundColor: tone.thumbBg }]}>
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

        <Text style={[styles.orderStatus, { color: getStatusColor(order.status) }]}>
          {order.status}
        </Text>
      </View>

      <View style={styles.orderTagRow}>
        <View style={[styles.serviceTag, { backgroundColor: tone.pillBg }]}>
          <Text style={[styles.serviceTagText, { color: tone.pillColor }]}>
            {getServiceLabel(order.service)}
          </Text>
        </View>
      </View>

      <Text style={styles.orderItemLine}>{buildItemLine(order)}</Text>

      <Text style={styles.orderMetaLine}>
        Ordered: {order.orderedAt} · Bill Total: {money(order.total)}
      </Text>

      {!isEatout ? (
        <View style={styles.ratingPanel}>
          <RatingStars label="Your Food Rating" />
          <View style={styles.ratingDivider} />
          <RatingStars label="Delivery Rating" />
        </View>
      ) : (
        <View style={styles.bookingInfoCard}>
          <Ionicons name="calendar-outline" size={18} color={tone.actionColor} />
          <Text style={[styles.bookingInfoText, { color: tone.actionColor }]}>
            Your table booking is saved in history
          </Text>
        </View>
      )}

      <TouchableOpacity
        activeOpacity={0.92}
        style={[styles.orderPrimaryButton, { backgroundColor: tone.actionBg }]}
        onPress={onPressPrimary}>
        <Text style={[styles.orderPrimaryButtonText, { color: tone.actionColor }]}>
          {isEatout ? 'VIEW BOOKING' : 'REORDER'}
        </Text>
        <Ionicons name="chevron-forward" size={16} color={tone.actionColor} />
      </TouchableOpacity>
    </View>
  );
}

function EmptyOrdersState({ activeFilter }) {
  return (
    <View style={styles.emptyOrdersCard}>
      <Text style={styles.emptyOrdersTitle}>
        No {getServiceLabel(activeFilter).toLowerCase()} orders yet
      </Text>
      <Text style={styles.emptyOrdersText}>
        Orders placed from your basket will appear here.
      </Text>
    </View>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const scrollRef = useRef(null);

  const [ordersY, setOrdersY] = useState(0);
  const [favouritesY, setFavouritesY] = useState(0);
  const [activeFilter, setActiveFilter] = useState('food');

  const {
    activeService,
    vendors,
    favorites,
    recentVendors,
    recentSearches,
    orderHistory,
    cartCount,
    cartTotal,
    rememberStore,
  } = useGrabBasket();

  useEffect(() => {
    const nextFilter =
      activeService === 'warehouse'
        ? 'warehouse'
        : activeService === 'eatout'
          ? 'eatout'
          : 'food';

    setActiveFilter(nextFilter);
  }, [activeService]);

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

  const favouriteVendors = useMemo(() => {
    return (vendors || []).filter((vendor) => Boolean(favorites?.[vendor.id])).slice(0, 8);
  }, [vendors, favorites]);

  const stats = useMemo(() => {
    const totalOrders = allOrders.length;
    const favouriteCount = Object.values(favorites || {}).filter(Boolean).length;
    const savedEstimate = allOrders.reduce(
      (sum, order) => sum + Math.round(Number(order.total || 0) * 0.04),
      0
    );

    return {
      totalOrders,
      favouriteCount,
      savedEstimate,
    };
  }, [allOrders, favorites]);

  const profile = useMemo(
    () => resolveProfile(allOrders, recentSearches),
    [allOrders, recentSearches]
  );

  const handleBack = () => {
    if (router.canGoBack()) {
      router.back();
      return;
    }
    router.replace('/');
  };

  const openVendor = (vendor) => {
    rememberStore(vendor.id);
    router.push({
      pathname: '/store/[vendorId]',
      params: { vendorId: String(vendor.id) },
    });
  };

  const scrollToY = (yValue) => {
    scrollRef.current?.scrollTo({
      y: Math.max(0, yValue - 12),
      animated: true,
    });
  };

  const handleHelp = () => {
    Alert.alert(
      'Support',
      'Wire this to your help center, live chat, FAQs, and order issue flows.'
    );
  };

  const handleQuickAction = (key) => {
    if (key === 'refunds') {
      scrollToY(ordersY);
      return;
    }

    if (key === 'saved-address') {
      Alert.alert(
        'Saved Address',
        'Create an address management screen and connect it to your backend profile.'
      );
      return;
    }

    if (key === 'payment-modes') {
      Alert.alert(
        'Payment Modes',
        'Add saved cards, UPI, wallet, and preferred payment selection here.'
      );
      return;
    }

    Alert.alert(
      'GrabBasket Money',
      cartCount > 0
        ? 'An active basket is available. You can also show wallet balance, cashback, and credits here.'
        : 'Add wallet balance, cashback, and credits here.'
    );
  };

  const handleAccountRowPress = (rowKey) => {
    if (rowKey === 'favourites') {
      scrollToY(favouritesY);
      return;
    }

    if (rowKey === 'wishlist') {
      Alert.alert(
        'Instamart Wishlist',
        'Create a wishlist collection screen for saved grocery products.'
      );
      return;
    }

    if (rowKey === 'statements') {
      Alert.alert(
        'Account Statements',
        'Use this entry for invoices, GST bills, and downloadable statements.'
      );
      return;
    }

    if (rowKey === 'contact') {
      Alert.alert(
        'Restaurant Contact Preferences',
        'Add a privacy toggle here and sync it with your user profile.'
      );
      return;
    }

    Alert.alert(
      'Coming soon',
      'This row is styled and ready. Connect it to the real flow when that feature is built.'
    );
  };

  const handleOrderPrimary = (order) => {
    const matchedVendorId = resolveOrderVendorId(order, vendors);

    if (matchedVendorId) {
      const matchedVendor = (vendors || []).find(
        (vendor) => String(vendor.id) === String(matchedVendorId)
      );

      if (matchedVendor) {
        openVendor(matchedVendor);
        return;
      }

      router.push({
        pathname: '/store/[vendorId]',
        params: { vendorId: String(matchedVendorId) },
      });
      return;
    }

    router.push('/reorder');
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
          <TouchableOpacity activeOpacity={0.92} style={styles.helpPill} onPress={handleHelp}>
            <Text style={styles.helpPillText}>Help</Text>
          </TouchableOpacity>

          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.topIconButton}
            onPress={() =>
              Alert.alert(
                'More actions',
                'Use this menu for logout, account settings, notifications, and privacy controls.'
              )
            }>
            <Ionicons name="ellipsis-vertical" size={20} color={COLORS.text} />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        ref={scrollRef}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[styles.scrollContent, { paddingBottom: tabBarHeight + 28 }]}>
        <ProfileHero
          profile={profile}
          totalOrders={stats.totalOrders}
          favouriteCount={stats.favouriteCount}
        />

        <MembershipCard
          savedEstimate={stats.savedEstimate}
          totalOrders={stats.totalOrders}
        />

        <PromoBanner />

        <View style={styles.quickActionsRow}>
          {QUICK_ACTIONS.map((action) => (
            <QuickActionCard
              key={action.key}
              icon={action.icon}
              label={action.label}
              onPress={() => handleQuickAction(action.key)}
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
              onPress={() => handleAccountRowPress(row.key)}
            />
          ))}
        </View>

        {cartCount > 0 ? (
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.liveBasketCard}
            onPress={() => router.push('/cart')}>
            <View style={styles.liveBasketIcon}>
              <Ionicons name="bag-handle-outline" size={20} color={COLORS.green} />
            </View>

            <View style={{ flex: 1 }}>
              <Text style={styles.liveBasketTitle}>Active basket</Text>
              <Text style={styles.liveBasketSubtitle}>
                {cartCount} items · {money(cartTotal)}
              </Text>
            </View>

            <Ionicons name="chevron-forward" size={18} color={COLORS.green} />
          </TouchableOpacity>
        ) : null}

        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.browsePastOrdersButton}
          onPress={() => scrollToY(ordersY)}>
          <Text style={styles.browsePastOrdersText}>BROWSE PAST ORDERS</Text>
        </TouchableOpacity>

        {favouriteVendors.length > 0 ? (
          <View onLayout={(event) => setFavouritesY(event.nativeEvent.layout.y)}>
            <SectionHeader title="YOUR FAVOURITES" actionLabel="View all" onPressAction={() => {}} />
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.horizontalRail}>
              {favouriteVendors.map((vendor) => (
                <MiniStoreCard
                  key={vendor.id}
                  vendor={vendor}
                  onPress={() => openVendor(vendor)}
                />
              ))}
            </ScrollView>
          </View>
        ) : null}

        {recentSearches?.length > 0 ? (
          <View>
            <SectionHeader title="RECENT SEARCHES" />
            <View style={styles.searchChipWrap}>
              {recentSearches.slice(0, 6).map((item) => (
                <SearchChip key={item} label={item} />
              ))}
            </View>
          </View>
        ) : null}

        <View onLayout={(event) => setOrdersY(event.nativeEvent.layout.y)}>
          <SectionHeader title="PAST ORDERS" />

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
                onPressPrimary={() => handleOrderPrimary(order)}
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
    color: COLORS.orange,
    fontSize: 14,
    fontWeight: '800',
  },

  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 8,
  },

  profileHero: {
    minHeight: 200,
    borderRadius: 30,
    overflow: 'hidden',
    backgroundColor: COLORS.hero,
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 22,
    justifyContent: 'flex-end',
  },
  heroBlobOne: {
    position: 'absolute',
    right: -30,
    top: 24,
    width: 190,
    height: 190,
    borderRadius: 95,
    backgroundColor: 'rgba(109, 20, 49, 0.14)',
  },
  heroBlobTwo: {
    position: 'absolute',
    right: 40,
    top: 52,
    width: 104,
    height: 104,
    borderRadius: 52,
    backgroundColor: 'rgba(109, 20, 49, 0.12)',
  },
  heroBlobThree: {
    position: 'absolute',
    left: -44,
    bottom: -58,
    width: 220,
    height: 220,
    borderRadius: 110,
    backgroundColor: COLORS.heroSoft,
  },
  profileHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  profileAvatar: {
    width: 60,
    height: 60,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileAvatarText: {
    color: '#ffffff',
    fontSize: 24,
    fontWeight: '900',
  },
  profileBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
    justifyContent: 'flex-end',
  },
  profileBadge: {
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.16)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  profileBadgeText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '900',
  },
  profileBadgeMuted: {
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  profileBadgeMutedText: {
    color: 'rgba(255,255,255,0.94)',
    fontSize: 12,
    fontWeight: '800',
  },
  profileName: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '900',
    marginBottom: 6,
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
  membershipTopRow: {
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
    color: COLORS.orange,
    fontSize: 22,
    fontWeight: '900',
  },
  membershipActivePill: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: COLORS.greenSoft,
  },
  membershipActivePillText: {
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
    backgroundColor: COLORS.purple,
    paddingHorizontal: 16,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  promoSignal: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  promoTitle: {
    color: '#f3d8e8',
    fontSize: 13,
    fontWeight: '700',
  },
  promoSubtitle: {
    color: '#ffffff',
    fontSize: 19,
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
    backgroundColor: '#f4f5f7',
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
    backgroundColor: COLORS.black,
    paddingHorizontal: 24,
    paddingVertical: 14,
  },
  browsePastOrdersText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '900',
    letterSpacing: 0.3,
  },

  sectionHeader: {
    marginTop: 24,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 24,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  sectionAction: {
    color: COLORS.orange,
    fontSize: 14,
    fontWeight: '800',
  },

  horizontalRail: {
    gap: 12,
    paddingBottom: 2,
  },
  miniStoreCard: {
    width: 144,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
  },
  miniStoreAvatar: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: '#f4ecff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  miniStoreAvatarText: {
    color: '#6d28d9',
    fontSize: 18,
    fontWeight: '900',
  },
  miniStoreName: {
    marginTop: 12,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  miniStoreMeta: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
  },

  searchChipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  searchChip: {
    borderRadius: 999,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  searchChipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
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
    backgroundColor: COLORS.black,
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
  orderTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderThumb: {
    width: 56,
    height: 56,
    borderRadius: 18,
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
    fontSize: 15,
    fontWeight: '900',
  },
  orderTagRow: {
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
  orderItemLine: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  orderMetaLine: {
    marginTop: 12,
    color: COLORS.muted,
    fontSize: 14,
    fontWeight: '500',
  },

  ratingPanel: {
    marginTop: 16,
    borderRadius: 18,
    backgroundColor: '#fbfbfd',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingColumn: {
    flex: 1,
  },
  ratingLabel: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 8,
  },
  ratingStarsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
  },
  ratingDivider: {
    width: 1,
    alignSelf: 'stretch',
    backgroundColor: COLORS.border,
    marginHorizontal: 12,
  },

  bookingInfoCard: {
    marginTop: 16,
    borderRadius: 16,
    backgroundColor: '#fffaf2',
    borderWidth: 1,
    borderColor: '#fde8c2',
    paddingHorizontal: 12,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  bookingInfoText: {
    flex: 1,
    fontSize: 13,
    fontWeight: '700',
  },

  orderPrimaryButton: {
    marginTop: 16,
    minHeight: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
  },
  orderPrimaryButtonText: {
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