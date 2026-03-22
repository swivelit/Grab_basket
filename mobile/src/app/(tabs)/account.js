import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useGrabBasket } from '../../../App';
import { buildApiUrl } from '../../config';

const COLORS = {
  bg: '#f6f7fb',
  card: '#ffffff',
  cardAlt: '#f9fafb',
  text: '#111827',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e7ebf3',
  shadow: 'rgba(15, 23, 42, 0.08)',
  hero: '#cf5d5c',
  heroDark: '#a83d50',
  heroSoft: 'rgba(255,255,255,0.12)',
  green: '#119b56',
  greenSoft: '#e8f8ee',
  orange: '#ff6d00',
  orangeSoft: '#fff1e7',
  purple: '#5b3df5',
  purpleSoft: '#f1edff',
  blue: '#0b57d0',
  blueSoft: '#eaf2ff',
  yellow: '#a16207',
  yellowSoft: '#fff8db',
  red: '#d92d20',
  redSoft: '#feeceb',
  black: '#101828',
};

const ORDER_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'food', label: 'Food' },
  { key: 'warehouse', label: 'Instamart' },
  { key: 'eatout', label: 'Dineout' },
  { key: 'scenes', label: 'Scenes' },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function normalizeService(value = '') {
  const normalized = String(value || '').trim().toLowerCase();
  if (normalized === 'instamart') return 'warehouse';
  if (normalized === 'dineout') return 'eatout';
  return normalized || 'food';
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

function safeJsonParse(raw) {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function formatAddress(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city, address.pincode].filter(Boolean).join(' · ');
}

function formatDateTime(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function formatPrettyStatus(value = '') {
  return (
    String(value || '')
      .replace(/_/g, ' ')
      .toLowerCase()
      .replace(/\b\w/g, (char) => char.toUpperCase()) || 'Created'
  );
}

function getOrderTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value.includes('DELIVERED')) {
    return {
      bg: COLORS.greenSoft,
      text: COLORS.green,
      icon: 'checkmark-circle-outline',
    };
  }

  if (value.includes('CANCEL') || value.includes('REJECT')) {
    return {
      bg: COLORS.redSoft,
      text: COLORS.red,
      icon: 'close-circle-outline',
    };
  }

  if (value.includes('PICKED') || value.includes('READY') || value.includes('ASSIGNED')) {
    return {
      bg: COLORS.blueSoft,
      text: COLORS.blue,
      icon: 'bicycle-outline',
    };
  }

  return {
    bg: COLORS.orangeSoft,
    text: COLORS.orange,
    icon: 'time-outline',
  };
}

async function authRequest(path, token, { method = 'GET', body } = {}) {
  const response = await fetch(buildApiUrl(path), {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body,
  });

  const raw = await response.text();
  const payload = safeJsonParse(raw);

  if (!response.ok) {
    throw new Error(payload?.detail || payload?.message || `Request failed with status ${response.status}`);
  }

  return payload;
}

function StatCard({ value, label, tint, soft }) {
  return (
    <View style={[styles.statCard, { backgroundColor: soft }]}>
      <Text style={[styles.statValue, { color: tint }]}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function InputField({
  label,
  value,
  onChangeText,
  placeholder,
  secureTextEntry = false,
  keyboardType = 'default',
}) {
  return (
    <View style={styles.fieldBlock}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.subtle}
        secureTextEntry={secureTextEntry}
        keyboardType={keyboardType}
        autoCapitalize="none"
        style={styles.input}
      />
    </View>
  );
}

function StatusPill({ status }) {
  const tone = getOrderTone(status);
  return (
    <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
      <Ionicons name={tone.icon} size={14} color={tone.text} />
      <Text style={[styles.statusPillText, { color: tone.text }]}>{formatPrettyStatus(status)}</Text>
    </View>
  );
}

function Timeline({ events = [] }) {
  if (!events.length) {
    return (
      <View style={styles.emptyInlinePanel}>
        <Text style={styles.emptyInlineTitle}>No timeline events yet</Text>
        <Text style={styles.emptyInlineSubtitle}>
          Your backend will start filling this as order state changes happen.
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.timelineWrap}>
      {events.map((event, index) => {
        const tone = getOrderTone(event?.status);
        const isLast = index === events.length - 1;

        return (
          <View key={`${event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.text }]} />
              {!isLast ? <View style={styles.timelineLine} /> : null}
            </View>

            <View style={styles.timelineCopy}>
              <Text style={styles.timelineTitle}>{formatPrettyStatus(event?.status)}</Text>
              <Text style={styles.timelineMeta}>{formatDateTime(event?.created_at)}</Text>
              {event?.note ? <Text style={styles.timelineNote}>{event.note}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}

function OrderCard({ order, onPress }) {
  const tone = getOrderTone(order?.status);

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.orderCard} onPress={onPress}>
      <View style={styles.orderCardTop}>
        <View style={styles.orderAvatar}>
          <Text style={styles.orderAvatarText}>{initials(order?.vendorName)}</Text>
        </View>

        <View style={{ flex: 1 }}>
          <Text style={styles.orderVendorName} numberOfLines={1}>
            {order?.vendorName}
          </Text>
          <Text style={styles.orderVendorMeta} numberOfLines={1}>
            {order?.location}
          </Text>
        </View>

        <View style={[styles.orderStatusBadge, { backgroundColor: tone.bg }]}>
          <Text style={[styles.orderStatus, { color: tone.text }]}>{order?.status}</Text>
        </View>
      </View>

      <Text style={styles.orderSummary}>
        {(order?.items || [])
          .slice(0, 2)
          .map((item) => `${item.qty || 1} x ${item.name}`)
          .join(' · ') || 'Order'}
      </Text>

      <Text style={styles.orderMeta}>
        {order?.orderedAt} · {money(order?.total)}
      </Text>

      <View style={styles.orderFooterRow}>
        <View style={styles.orderHintPill}>
          <Ionicons name="pulse-outline" size={14} color={COLORS.blue} />
          <Text style={styles.orderHintText}>Track order</Text>
        </View>
        <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
      </View>
    </TouchableOpacity>
  );
}

function VendorCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.vendorCard} onPress={onPress}>
      <View style={styles.vendorAvatar}>
        <Text style={styles.vendorAvatarText}>{initials(vendor?.name)}</Text>
      </View>
      <Text style={styles.vendorName} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={styles.vendorMeta} numberOfLines={1}>
        {vendor?.address || vendor?.description}
      </Text>
    </TouchableOpacity>
  );
}

function OrderDetailsSheet({
  visible,
  onClose,
  order,
  detail,
  loading,
  actionLoading,
  onRefresh,
  onCancel,
  onOpenStore,
}) {
  const detailOrder = detail?.order || null;
  const status = detailOrder?.status || order?.status || 'CREATED';
  const partnerLocation = detail?.partner_latest_location || null;
  const events = Array.isArray(detailOrder?.events) ? detailOrder.events : [];
  const canCancel = !['PICKED_UP', 'DELIVERED', 'CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER'].includes(
    String(detailOrder?.status || '').toUpperCase()
  );

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.sheetOverlay}>
        <View style={styles.sheetCard}>
          <View style={styles.sheetHandle} />

          <View style={styles.sheetHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sheetTitle}>Order #{order?.id}</Text>
              <Text style={styles.sheetSubtitle}>{order?.vendorName}</Text>
            </View>

            <TouchableOpacity activeOpacity={0.92} style={styles.sheetCloseButton} onPress={onClose}>
              <Ionicons name="close" size={20} color={COLORS.text} />
            </TouchableOpacity>
          </View>

          {loading ? (
            <View style={styles.sheetLoaderWrap}>
              <ActivityIndicator color={COLORS.heroDark} />
              <Text style={styles.sheetLoaderText}>Loading tracking details…</Text>
            </View>
          ) : (
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.sheetBody}>
              <View style={styles.sheetPanel}>
                <View style={styles.sheetPanelTop}>
                  <StatusPill status={status} />
                  <TouchableOpacity activeOpacity={0.92} style={styles.ghostButton} onPress={onRefresh}>
                    <Ionicons name="refresh-outline" size={16} color={COLORS.text} />
                    <Text style={styles.ghostButtonText}>Refresh</Text>
                  </TouchableOpacity>
                </View>

                <View style={styles.metricGrid}>
                  <View style={styles.metricTile}>
                    <Text style={styles.metricLabel}>Total</Text>
                    <Text style={styles.metricValue}>
                      {money(detailOrder?.total_amount ?? order?.total ?? 0)}
                    </Text>
                  </View>

                  <View style={styles.metricTile}>
                    <Text style={styles.metricLabel}>Payment</Text>
                    <Text style={styles.metricValue}>
                      {formatPrettyStatus(detailOrder?.payment_status || order?.paymentStatus)}
                    </Text>
                  </View>
                </View>

                {(order?.items || []).length ? (
                  <View style={styles.inlinePanel}>
                    <Text style={styles.inlinePanelTitle}>Items</Text>
                    {(order.items || []).map((item, index) => (
                      <Text key={`${item?.name}-${index}`} style={styles.inlinePanelText}>
                        {item.qty || 1} x {item.name}
                      </Text>
                    ))}
                  </View>
                ) : null}

                <View style={styles.inlinePanel}>
                  <Text style={styles.inlinePanelTitle}>Live tracking</Text>
                  <Text style={styles.inlinePanelText}>
                    {partnerLocation
                      ? `Partner location updated at ${formatDateTime(partnerLocation.created_at)}`
                      : 'Partner live location not available yet.'}
                  </Text>
                  {partnerLocation ? (
                    <Text style={styles.inlinePanelHint}>
                      Lat {Number(partnerLocation.lat).toFixed(4)} · Lng {Number(partnerLocation.lng).toFixed(4)}
                    </Text>
                  ) : null}
                </View>
              </View>

              <View style={styles.sheetPanel}>
                <Text style={styles.inlinePanelTitle}>Order timeline</Text>
                <Timeline events={events} />
              </View>

              <View style={styles.sheetButtonRow}>
                <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={onOpenStore}>
                  <Ionicons name="storefront-outline" size={16} color={COLORS.text} />
                  <Text style={styles.secondaryButtonText}>Open store</Text>
                </TouchableOpacity>

                {canCancel ? (
                  <TouchableOpacity
                    activeOpacity={0.92}
                    style={[styles.primaryButton, styles.cancelButton]}
                    disabled={actionLoading}
                    onPress={onCancel}>
                    {actionLoading ? (
                      <ActivityIndicator color="#fff" />
                    ) : (
                      <Ionicons name="close-circle-outline" size={16} color="#fff" />
                    )}
                    <Text style={styles.primaryButtonText}>
                      {actionLoading ? 'Cancelling…' : 'Cancel order'}
                    </Text>
                  </TouchableOpacity>
                ) : null}
              </View>
            </ScrollView>
          )}
        </View>
      </View>
    </Modal>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const [authMode, setAuthMode] = useState('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailActionLoading, setDetailActionLoading] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [orderDetail, setOrderDetail] = useState(null);

  const [label, setLabel] = useState('Home');
  const [line1, setLine1] = useState('');
  const [line2, setLine2] = useState('');
  const [city, setCity] = useState('');
  const [pincode, setPincode] = useState('');
  const [lat, setLat] = useState('');
  const [lng, setLng] = useState('');

  const {
    favorites,
    recentVendors,
    vendors,
    orderHistory,
    cartCount,
    cartTotal,
    rememberStore,
    isAuthenticated,
    authToken,
    authEmail,
    authRole,
    profile,
    authLoading,
    login,
    register,
    logout,
    addresses,
    addressesLoading,
    defaultAddress,
    createAddress,
    setDefaultAddress,
    loadOrders,
    loadAddresses,
    ordersLoading,
  } = useGrabBasket();

  const favouriteVendors = useMemo(() => {
    return (vendors || []).filter((vendor) => Boolean(favorites?.[vendor.id])).slice(0, 8);
  }, [vendors, favorites]);

  const filteredOrders = useMemo(() => {
    const source = (orderHistory || []).map((order) => ({
      ...order,
      service: normalizeService(order.service),
    }));

    if (activeFilter === 'all') return source;
    return source.filter((order) => order.service === activeFilter);
  }, [activeFilter, orderHistory]);

  const stats = useMemo(() => {
    const totalOrders = orderHistory.length;
    const favouriteCount = Object.values(favorites || {}).filter(Boolean).length;
    const savedEstimate = orderHistory.reduce(
      (sum, order) => sum + Math.round(Number(order.total || 0) * 0.04),
      0
    );

    return {
      totalOrders,
      favouriteCount,
      savedEstimate,
    };
  }, [favorites, orderHistory]);

  const refreshAll = useCallback(() => {
    loadOrders({ silent: false });
    loadAddresses({ silent: false });
  }, [loadAddresses, loadOrders]);

  const handleAuth = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert('Missing details', 'Email and password are required.');
      return;
    }

    const ok =
      authMode === 'login'
        ? await login({ email, password })
        : await register({ email, password });

    if (ok) {
      setPassword('');
      loadAddresses({ silent: true });
      loadOrders({ silent: true });
    }
  };

  const handleCreateAddress = async () => {
    const next = await createAddress({
      label,
      line1,
      line2,
      city,
      pincode,
      lat,
      lng,
      is_default: !addresses.length,
    });

    if (next) {
      setLabel('Home');
      setLine1('');
      setLine2('');
      setCity('');
      setPincode('');
      setLat('');
      setLng('');
    }
  };

  const openVendor = useCallback(
    (vendor) => {
      if (!vendor?.id) return;
      rememberStore(vendor.id);
      router.push({
        pathname: '/store/[vendorId]',
        params: { vendorId: String(vendor.id) },
      });
    },
    [rememberStore, router]
  );

  const loadOrderDetail = useCallback(
    async (order) => {
      if (!authToken || !order?.id) return;

      try {
        setSelectedOrder(order);
        setDetailLoading(true);
        const payload = await authRequest(`/orders/${order.id}/tracking`, authToken);
        setOrderDetail(payload || null);
      } catch (error) {
        Alert.alert('Could not load tracking', error?.message || 'Please try again.');
      } finally {
        setDetailLoading(false);
      }
    },
    [authToken]
  );

  const cancelOrder = useCallback(async () => {
    if (!authToken || !selectedOrder?.id) return;

    Alert.alert('Cancel order', `Cancel order #${selectedOrder.id}?`, [
      { text: 'Keep order', style: 'cancel' },
      {
        text: 'Cancel order',
        style: 'destructive',
        onPress: async () => {
          try {
            setDetailActionLoading(true);

            await authRequest(
              `/orders/${selectedOrder.id}/cancel?reason=Cancelled%20from%20customer%20app`,
              authToken,
              {
                method: 'POST',
              }
            );

            const payload = await authRequest(`/orders/${selectedOrder.id}/tracking`, authToken);
            setOrderDetail(payload || null);
            loadOrders({ silent: true });

            Alert.alert('Order cancelled', `Order #${selectedOrder.id} has been cancelled.`);
          } catch (error) {
            Alert.alert('Could not cancel order', error?.message || 'Please try again.');
          } finally {
            setDetailActionLoading(false);
          }
        },
      },
    ]);
  }, [authToken, loadOrders, selectedOrder]);

  const openStoreFromSheet = useCallback(() => {
    if (!selectedOrder?.vendorId) {
      router.push('/reorder');
      return;
    }

    const matchedVendor = (vendors || []).find(
      (vendor) => String(vendor.id) === String(selectedOrder.vendorId)
    );

    if (matchedVendor) {
      openVendor(matchedVendor);
      return;
    }

    router.push('/reorder');
  }, [openVendor, router, selectedOrder, vendors]);

  const closeSheet = () => {
    setSelectedOrder(null);
    setOrderDetail(null);
    setDetailLoading(false);
    setDetailActionLoading(false);
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <View style={styles.topBar}>
        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.topIconButton}
          onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))}>
          <Ionicons name="arrow-back" size={22} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.topBarTitle}>ACCOUNT</Text>

        <TouchableOpacity activeOpacity={0.92} style={styles.topIconButton} onPress={refreshAll}>
          <Ionicons name="refresh-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={Boolean(ordersLoading || addressesLoading)}
            onRefresh={refreshAll}
          />
        }
        contentContainerStyle={[styles.scrollContent, { paddingBottom: tabBarHeight + 28 }]}>
        <View style={styles.heroCard}>
          <View style={styles.heroOrb} />
          <Text style={styles.heroEyebrow}>CUSTOMER APP</Text>
          <Text style={styles.heroTitle}>This is better, but still not Swiggy standard yet.</Text>
          <Text style={styles.heroSubtitle}>
            The biggest missing piece was live post-order visibility. This version adds backend-backed
            order tracking and cancellation from the customer app.
          </Text>

          <View style={styles.statsRow}>
            <StatCard
              value={String(stats.totalOrders)}
              label="Orders"
              tint={COLORS.green}
              soft={COLORS.greenSoft}
            />
            <StatCard
              value={String(stats.favouriteCount)}
              label="Favourites"
              tint={COLORS.blue}
              soft={COLORS.blueSoft}
            />
            <StatCard
              value={money(stats.savedEstimate)}
              label="Saved"
              tint={COLORS.purple}
              soft={COLORS.purpleSoft}
            />
          </View>
        </View>

        {cartCount > 0 ? (
          <TouchableOpacity activeOpacity={0.92} style={styles.activeCartCard} onPress={() => router.push('/cart')}>
            <View style={styles.activeCartIcon}>
              <Ionicons name="bag-handle-outline" size={18} color={COLORS.heroDark} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.activeCartTitle}>Active basket</Text>
              <Text style={styles.activeCartSubtitle}>
                {cartCount} items · {money(cartTotal)}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={COLORS.heroDark} />
          </TouchableOpacity>
        ) : null}

        {!isAuthenticated ? (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Sign in or create account</Text>
            <Text style={styles.sectionSubtitle}>
              Swiggy-grade apps depend on authenticated orders, addresses, and real order history.
            </Text>

            <View style={styles.modeRow}>
              {['login', 'register'].map((mode) => {
                const active = authMode === mode;

                return (
                  <TouchableOpacity
                    key={mode}
                    activeOpacity={0.92}
                    style={[styles.modePill, active && styles.modePillActive]}
                    onPress={() => setAuthMode(mode)}>
                    <Text style={[styles.modePillText, active && styles.modePillTextActive]}>
                      {mode === 'login' ? 'Sign in' : 'Create account'}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <InputField
              label="Email"
              value={email}
              onChangeText={setEmail}
              placeholder="customer@example.com"
              keyboardType="email-address"
            />
            <InputField
              label="Password"
              value={password}
              onChangeText={setPassword}
              placeholder="At least 6 characters"
              secureTextEntry
            />

            <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={handleAuth}>
              {authLoading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Ionicons name="person-outline" size={16} color="#fff" />
              )}
              <Text style={styles.primaryButtonText}>
                {authLoading ? 'Please wait…' : authMode === 'login' ? 'Sign in' : 'Create account'}
              </Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View style={styles.sectionCard}>
            <View style={styles.profileRow}>
              <View style={styles.profileAvatar}>
                <Text style={styles.profileAvatarText}>{initials(authEmail || profile?.email || 'GB')}</Text>
              </View>

              <View style={{ flex: 1 }}>
                <Text style={styles.profileName}>{authEmail || profile?.email || 'Customer'}</Text>
                <Text style={styles.profileMeta}>
                  {authRole || profile?.role || 'CUSTOMER'} ·{' '}
                  {defaultAddress ? formatAddress(defaultAddress) : 'No default address'}
                </Text>
              </View>

              <TouchableOpacity activeOpacity={0.92} style={styles.logoutButton} onPress={logout}>
                <Text style={styles.logoutButtonText}>Logout</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}

        <View style={styles.sectionCard}>
          <View style={styles.sectionHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sectionTitle}>Delivery addresses</Text>
              <Text style={styles.sectionSubtitle}>
                Backend-backed now. Map picker and saved landmarks should be the next upgrade.
              </Text>
            </View>
            {addressesLoading ? <ActivityIndicator color={COLORS.heroDark} /> : null}
          </View>

          {addresses.length > 0 ? (
            addresses.map((address) => {
              const isDefault = Boolean(address.is_default);

              return (
                <View key={address.id} style={styles.addressCard}>
                  <View style={styles.addressTopRow}>
                    <Text style={styles.addressTitle}>{formatAddress(address)}</Text>
                    {isDefault ? <Text style={styles.defaultBadge}>DEFAULT</Text> : null}
                  </View>

                  <Text style={styles.addressMeta}>
                    Lat {Number(address.lat).toFixed(4)} · Lng {Number(address.lng).toFixed(4)}
                  </Text>

                  {!isDefault ? (
                    <TouchableOpacity
                      activeOpacity={0.92}
                      style={styles.inlineButton}
                      onPress={() => setDefaultAddress(address.id)}>
                      <Text style={styles.inlineButtonText}>Set as default</Text>
                    </TouchableOpacity>
                  ) : null}
                </View>
              );
            })
          ) : (
            <View style={styles.emptyPanel}>
              <Text style={styles.emptyTitle}>No saved addresses</Text>
              <Text style={styles.emptySubtitle}>
                Add one below so checkout can create real delivery orders.
              </Text>
            </View>
          )}

          <View style={styles.formDivider} />
          <Text style={styles.formTitle}>Add address</Text>

          <InputField label="Label" value={label} onChangeText={setLabel} placeholder="Home / Work" />
          <InputField label="Line 1" value={line1} onChangeText={setLine1} placeholder="Flat, street, building" />
          <InputField label="Line 2" value={line2} onChangeText={setLine2} placeholder="Area, landmark" />
          <InputField label="City" value={city} onChangeText={setCity} placeholder="Bengaluru" />
          <InputField
            label="Pincode"
            value={pincode}
            onChangeText={setPincode}
            placeholder="560001"
            keyboardType="number-pad"
          />

          <View style={styles.coordRow}>
            <View style={{ flex: 1 }}>
              <InputField
                label="Latitude"
                value={lat}
                onChangeText={setLat}
                placeholder="12.9716"
                keyboardType="decimal-pad"
              />
            </View>

            <View style={{ width: 12 }} />

            <View style={{ flex: 1 }}>
              <InputField
                label="Longitude"
                value={lng}
                onChangeText={setLng}
                placeholder="77.5946"
                keyboardType="decimal-pad"
              />
            </View>
          </View>

          <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={handleCreateAddress}>
            <Ionicons name="location-outline" size={16} color="#fff" />
            <Text style={styles.primaryButtonText}>Save address</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.sectionCard}>
          <View style={styles.sectionHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sectionTitle}>Orders</Text>
              <Text style={styles.sectionSubtitle}>
                Tap any order to view backend events, live status, and cancel while it is still allowed.
              </Text>
            </View>
            {ordersLoading ? <ActivityIndicator color={COLORS.heroDark} /> : null}
          </View>

          <View style={styles.filterRow}>
            {ORDER_FILTERS.map((item) => {
              const active = activeFilter === item.key;

              return (
                <TouchableOpacity
                  key={item.key}
                  activeOpacity={0.92}
                  style={[styles.filterPill, active && styles.filterPillActive]}
                  onPress={() => setActiveFilter(item.key)}>
                  <Text style={[styles.filterPillText, active && styles.filterPillTextActive]}>
                    {item.label}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>

          {filteredOrders.length > 0 ? (
            filteredOrders.map((order) => (
              <OrderCard key={order.id} order={order} onPress={() => loadOrderDetail(order)} />
            ))
          ) : (
            <View style={styles.emptyPanel}>
              <Text style={styles.emptyTitle}>No orders yet</Text>
              <Text style={styles.emptySubtitle}>
                Place a real order from cart after signing in to populate this section.
              </Text>
            </View>
          )}
        </View>

        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Recent stores</Text>
          <Text style={styles.sectionSubtitle}>Use this as a fast way back into vendor detail pages.</Text>

          {recentVendors.length > 0 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
              {recentVendors.map((vendor) => (
                <VendorCard key={vendor.id} vendor={vendor} onPress={() => openVendor(vendor)} />
              ))}
            </ScrollView>
          ) : (
            <View style={styles.emptyPanel}>
              <Text style={styles.emptyTitle}>No recent stores</Text>
              <Text style={styles.emptySubtitle}>Open a vendor to build this rail.</Text>
            </View>
          )}
        </View>

        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Favourite stores</Text>
          <Text style={styles.sectionSubtitle}>
            This is the start of retention, but ratings, reviews, and collections are still missing.
          </Text>

          {favouriteVendors.length > 0 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
              {favouriteVendors.map((vendor) => (
                <VendorCard key={vendor.id} vendor={vendor} onPress={() => openVendor(vendor)} />
              ))}
            </ScrollView>
          ) : (
            <View style={styles.emptyPanel}>
              <Text style={styles.emptyTitle}>No favourites yet</Text>
              <Text style={styles.emptySubtitle}>
                Tap hearts in the discovery flow to populate this section.
              </Text>
            </View>
          )}
        </View>

        <View style={styles.checklistCard}>
          <Text style={styles.checklistTitle}>What still stops this app from feeling like Swiggy</Text>
          <Text style={styles.checkText}>• GPS map picker and reverse geocoding for addresses</Text>
          <Text style={styles.checkText}>• Payment gateway verification, not demo-only payment references</Text>
          <Text style={styles.checkText}>• Push notifications, support/help center, and issue resolution</Text>
          <Text style={styles.checkText}>• Restaurant media, ratings, search ranking, and personalization</Text>
        </View>
      </ScrollView>

      <OrderDetailsSheet
        visible={Boolean(selectedOrder)}
        onClose={closeSheet}
        order={selectedOrder}
        detail={orderDetail}
        loading={detailLoading}
        actionLoading={detailActionLoading}
        onRefresh={() => loadOrderDetail(selectedOrder)}
        onCancel={cancelOrder}
        onOpenStore={openStoreFromSheet}
      />
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
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingBottom: 10,
  },
  topIconButton: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  topBarTitle: {
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 1.6,
    color: COLORS.text,
  },
  scrollContent: {
    paddingHorizontal: 16,
    gap: 14,
  },
  heroCard: {
    backgroundColor: COLORS.hero,
    borderRadius: 28,
    padding: 20,
    overflow: 'hidden',
  },
  heroOrb: {
    position: 'absolute',
    width: 180,
    height: 180,
    borderRadius: 90,
    top: -40,
    right: -40,
    backgroundColor: COLORS.heroSoft,
  },
  heroEyebrow: {
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1.2,
    color: '#ffe1da',
  },
  heroTitle: {
    marginTop: 8,
    fontSize: 26,
    lineHeight: 32,
    fontWeight: '900',
    color: '#fff',
  },
  heroSubtitle: {
    marginTop: 10,
    fontSize: 14,
    lineHeight: 21,
    color: '#ffece8',
  },
  statsRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 18,
  },
  statCard: {
    flex: 1,
    borderRadius: 18,
    paddingVertical: 14,
    paddingHorizontal: 10,
  },
  statValue: {
    fontSize: 20,
    fontWeight: '900',
  },
  statLabel: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
    fontWeight: '700',
  },
  activeCartCard: {
    backgroundColor: '#fff1ef',
    borderWidth: 1,
    borderColor: '#ffd4d0',
    borderRadius: 20,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  activeCartIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  activeCartTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  activeCartSubtitle: {
    marginTop: 3,
    fontSize: 13,
    color: COLORS.muted,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    shadowColor: COLORS.shadow,
    shadowOpacity: 1,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 2,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
  },
  sectionSubtitle: {
    marginTop: 5,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  modeRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 16,
    marginBottom: 4,
  },
  modePill: {
    flex: 1,
    height: 42,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.cardAlt,
  },
  modePillActive: {
    backgroundColor: '#1f2937',
    borderColor: '#1f2937',
  },
  modePillText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.text,
  },
  modePillTextActive: {
    color: '#fff',
  },
  fieldBlock: {
    marginTop: 14,
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.muted,
    marginBottom: 8,
  },
  input: {
    minHeight: 50,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 14,
    fontSize: 15,
    color: COLORS.text,
  },
  primaryButton: {
    marginTop: 16,
    minHeight: 50,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
    backgroundColor: COLORS.heroDark,
  },
  primaryButtonText: {
    fontSize: 14,
    fontWeight: '900',
    color: '#fff',
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  profileAvatar: {
    width: 56,
    height: 56,
    borderRadius: 20,
    backgroundColor: '#ffe7e4',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileAvatarText: {
    fontSize: 20,
    fontWeight: '900',
    color: COLORS.heroDark,
  },
  profileName: {
    fontSize: 16,
    fontWeight: '900',
    color: COLORS.text,
  },
  profileMeta: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  logoutButton: {
    minHeight: 40,
    borderRadius: 12,
    paddingHorizontal: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.redSoft,
  },
  logoutButtonText: {
    fontSize: 12,
    fontWeight: '900',
    color: COLORS.red,
  },
  addressCard: {
    marginTop: 14,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
  },
  addressTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 10,
  },
  addressTitle: {
    flex: 1,
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  defaultBadge: {
    fontSize: 11,
    fontWeight: '900',
    color: COLORS.green,
    backgroundColor: COLORS.greenSoft,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  addressMeta: {
    marginTop: 8,
    fontSize: 12,
    color: COLORS.muted,
  },
  inlineButton: {
    marginTop: 10,
    alignSelf: 'flex-start',
    borderRadius: 12,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  inlineButtonText: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.text,
  },
  formDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginTop: 16,
    marginBottom: 12,
  },
  formTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  coordRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  filterRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 16,
    marginBottom: 4,
  },
  filterPill: {
    height: 36,
    borderRadius: 12,
    paddingHorizontal: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.cardAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  filterPillActive: {
    backgroundColor: '#1f2937',
    borderColor: '#1f2937',
  },
  filterPillText: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.text,
  },
  filterPillTextActive: {
    color: '#fff',
  },
  orderCard: {
    marginTop: 14,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
  },
  orderCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderAvatar: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#ffe8dc',
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderAvatarText: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.heroDark,
  },
  orderVendorName: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.text,
  },
  orderVendorMeta: {
    marginTop: 2,
    fontSize: 12,
    color: COLORS.muted,
  },
  orderStatusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 999,
  },
  orderStatus: {
    fontSize: 11,
    fontWeight: '900',
  },
  orderSummary: {
    marginTop: 12,
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.text,
    fontWeight: '700',
  },
  orderMeta: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
  },
  orderFooterRow: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  orderHintPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 8,
    backgroundColor: COLORS.blueSoft,
    borderRadius: 999,
  },
  orderHintText: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.blue,
  },
  horizontalRail: {
    paddingTop: 14,
    paddingRight: 4,
    gap: 12,
  },
  vendorCard: {
    width: 150,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
  },
  vendorAvatar: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#eef2ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorAvatarText: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.purple,
  },
  vendorName: {
    marginTop: 12,
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  vendorMeta: {
    marginTop: 5,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  emptyPanel: {
    marginTop: 14,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    padding: 16,
  },
  emptyTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  emptySubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  checklistCard: {
    backgroundColor: '#111827',
    borderRadius: 24,
    padding: 18,
    marginBottom: 4,
  },
  checklistTitle: {
    fontSize: 17,
    fontWeight: '900',
    color: '#fff',
    marginBottom: 10,
  },
  checkText: {
    fontSize: 13,
    lineHeight: 20,
    color: '#d1d5db',
    marginTop: 4,
  },
  sheetOverlay: {
    flex: 1,
    backgroundColor: 'rgba(17, 24, 39, 0.42)',
    justifyContent: 'flex-end',
  },
  sheetCard: {
    maxHeight: '88%',
    backgroundColor: COLORS.bg,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 24,
  },
  sheetHandle: {
    alignSelf: 'center',
    width: 54,
    height: 6,
    borderRadius: 999,
    backgroundColor: '#d0d5dd',
  },
  sheetHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginTop: 14,
    marginBottom: 10,
  },
  sheetTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: COLORS.text,
  },
  sheetSubtitle: {
    marginTop: 3,
    fontSize: 13,
    color: COLORS.muted,
  },
  sheetCloseButton: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetLoaderWrap: {
    paddingVertical: 48,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  sheetLoaderText: {
    fontSize: 13,
    color: COLORS.muted,
    fontWeight: '700',
  },
  sheetBody: {
    gap: 12,
    paddingBottom: 24,
  },
  sheetPanel: {
    backgroundColor: COLORS.card,
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sheetPanelTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 14,
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
  },
  statusPillText: {
    fontSize: 12,
    fontWeight: '900',
  },
  ghostButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  ghostButtonText: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.text,
  },
  metricGrid: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 12,
  },
  metricTile: {
    flex: 1,
    borderRadius: 16,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
  },
  metricLabel: {
    fontSize: 12,
    color: COLORS.muted,
    fontWeight: '700',
  },
  metricValue: {
    marginTop: 6,
    fontSize: 15,
    color: COLORS.text,
    fontWeight: '900',
  },
  inlinePanel: {
    borderRadius: 16,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
    marginTop: 10,
  },
  inlinePanelTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
    marginBottom: 6,
  },
  inlinePanelText: {
    fontSize: 13,
    lineHeight: 20,
    color: COLORS.text,
    marginTop: 3,
  },
  inlinePanelHint: {
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
    marginTop: 6,
  },
  emptyInlinePanel: {
    borderRadius: 14,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
    marginTop: 10,
  },
  emptyInlineTitle: {
    fontSize: 13,
    fontWeight: '900',
    color: COLORS.text,
  },
  emptyInlineSubtitle: {
    marginTop: 5,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  timelineWrap: {
    marginTop: 6,
  },
  timelineRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    minHeight: 64,
  },
  timelineRail: {
    width: 18,
    alignItems: 'center',
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginTop: 6,
  },
  timelineLine: {
    width: 2,
    flex: 1,
    marginTop: 6,
    backgroundColor: COLORS.border,
  },
  timelineCopy: {
    flex: 1,
    paddingBottom: 12,
  },
  timelineTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  timelineMeta: {
    marginTop: 3,
    fontSize: 12,
    color: COLORS.muted,
  },
  timelineNote: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.text,
  },
  sheetButtonRow: {
    flexDirection: 'row',
    gap: 10,
  },
  secondaryButton: {
    flex: 1,
    minHeight: 48,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
  },
  secondaryButtonText: {
    fontSize: 13,
    fontWeight: '900',
    color: COLORS.text,
  },
  cancelButton: {
    flex: 1,
    backgroundColor: COLORS.red,
    marginTop: 0,
  },
});