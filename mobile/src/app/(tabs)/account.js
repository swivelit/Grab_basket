import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
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
  red: '#d92d20',
  redSoft: '#feeceb',
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

function formatAddress(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city, address.pincode].filter(Boolean).join(' · ');
}

function StatCard({ value, label, tint, soft }) {
  return (
    <View style={[styles.statCard, { backgroundColor: soft }]}>
      <Text style={[styles.statValue, { color: tint }]}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function OrderCard({ order, onPress }) {
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

        <Text style={styles.orderStatus}>{order?.status}</Text>
      </View>

      <Text style={styles.orderSummary}>
        {(order?.items || [])
          .slice(0, 2)
          .map((item) => `${item.qty || 1} x ${item.name}`)
          .join(' · ') || 'Order'}
      </Text>
      <Text style={styles.orderMeta}>{order?.orderedAt} · {money(order?.total)}</Text>
    </TouchableOpacity>
  );
}

function VendorCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.vendorCard} onPress={onPress}>
      <View style={styles.vendorAvatar}>
        <Text style={styles.vendorAvatarText}>{initials(vendor?.name)}</Text>
      </View>
      <Text style={styles.vendorName} numberOfLines={1}>{vendor?.name}</Text>
      <Text style={styles.vendorMeta} numberOfLines={1}>{vendor?.address || vendor?.description}</Text>
    </TouchableOpacity>
  );
}

function InputField({ label, value, onChangeText, placeholder, secureTextEntry = false, keyboardType = 'default' }) {
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

export default function AccountScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const [authMode, setAuthMode] = useState('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');

  const [label, setLabel] = useState('Home');
  const [line1, setLine1] = useState('');
  const [line2, setLine2] = useState('');
  const [city, setCity] = useState('');
  const [pincode, setPincode] = useState('');
  const [lat, setLat] = useState('');
  const [lng, setLng] = useState('');

  const {
    activeService,
    favorites,
    recentVendors,
    vendors,
    orderHistory,
    cartCount,
    cartTotal,
    rememberStore,
    isAuthenticated,
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

  const openVendor = (vendor) => {
    if (!vendor?.id) return;
    rememberStore(vendor.id);
    router.push({
      pathname: '/store/[vendorId]',
      params: { vendorId: String(vendor.id) },
    });
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

        <TouchableOpacity
          activeOpacity={0.92}
          style={styles.topIconButton}
          onPress={() => {
            loadOrders({ silent: false });
            loadAddresses({ silent: false });
          }}>
          <Ionicons name="refresh-outline" size={20} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[styles.scrollContent, { paddingBottom: tabBarHeight + 28 }]}>
        <View style={styles.heroCard}>
          <View style={styles.heroOrb} />
          <Text style={styles.heroEyebrow}>PRODUCTION ACCOUNT</Text>
          <Text style={styles.heroTitle}>Move the customer journey off demo mode.</Text>
          <Text style={styles.heroSubtitle}>
            This tab now handles sign-in, address management, and live order/account data.
          </Text>

          <View style={styles.statsRow}>
            <StatCard value={String(stats.totalOrders)} label="Orders" tint={COLORS.green} soft={COLORS.greenSoft} />
            <StatCard value={String(stats.favouriteCount)} label="Favourites" tint={COLORS.blue} soft={COLORS.blueSoft} />
            <StatCard value={money(stats.savedEstimate)} label="Saved" tint={COLORS.purple} soft={COLORS.purpleSoft} />
          </View>
        </View>

        {cartCount > 0 ? (
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.activeCartCard}
            onPress={() => router.push('/cart')}>
            <View style={styles.activeCartIcon}>
              <Ionicons name="bag-handle-outline" size={18} color={COLORS.heroDark} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.activeCartTitle}>Active basket</Text>
              <Text style={styles.activeCartSubtitle}>{cartCount} items · {money(cartTotal)}</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={COLORS.heroDark} />
          </TouchableOpacity>
        ) : null}

        {!isAuthenticated ? (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Sign in or create account</Text>
            <Text style={styles.sectionSubtitle}>
              Swiggy-grade apps depend on authenticated orders, addresses, and account history.
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

            <InputField label="Email" value={email} onChangeText={setEmail} placeholder="customer@example.com" keyboardType="email-address" />
            <InputField label="Password" value={password} onChangeText={setPassword} placeholder="At least 6 characters" secureTextEntry />

            <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={handleAuth}>
              {authLoading ? <ActivityIndicator color="#fff" /> : null}
              <Text style={styles.primaryButtonText}>
                {authLoading ? 'Please wait...' : authMode === 'login' ? 'Sign in' : 'Create account'}
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
                  {authRole || profile?.role || 'CUSTOMER'} · {defaultAddress ? formatAddress(defaultAddress) : 'No default address'}
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
                This is now wired to the backend. A map picker is the next upgrade.
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
                Add one below so cart checkout can create real food and grocery orders.
              </Text>
            </View>
          )}

          <View style={styles.formDivider} />
          <Text style={styles.formTitle}>Add address</Text>
          <InputField label="Label" value={label} onChangeText={setLabel} placeholder="Home / Work" />
          <InputField label="Line 1" value={line1} onChangeText={setLine1} placeholder="Flat, street, building" />
          <InputField label="Line 2" value={line2} onChangeText={setLine2} placeholder="Area, landmark" />
          <InputField label="City" value={city} onChangeText={setCity} placeholder="Bengaluru" />
          <InputField label="Pincode" value={pincode} onChangeText={setPincode} placeholder="560001" keyboardType="number-pad" />

          <View style={styles.coordRow}>
            <View style={{ flex: 1 }}>
              <InputField label="Latitude" value={lat} onChangeText={setLat} placeholder="12.9716" keyboardType="decimal-pad" />
            </View>
            <View style={{ width: 12 }} />
            <View style={{ flex: 1 }}>
              <InputField label="Longitude" value={lng} onChangeText={setLng} placeholder="77.5946" keyboardType="decimal-pad" />
            </View>
          </View>

          <TouchableOpacity activeOpacity={0.92} style={styles.primaryButton} onPress={handleCreateAddress}>
            <Text style={styles.primaryButtonText}>Save address</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.sectionCard}>
          <View style={styles.sectionHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sectionTitle}>Orders</Text>
              <Text style={styles.sectionSubtitle}>
                Live orders from your backend API are filtered below.
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
                  <Text style={[styles.filterPillText, active && styles.filterPillTextActive]}>{item.label}</Text>
                </TouchableOpacity>
              );
            })}
          </View>

          {filteredOrders.length > 0 ? (
            filteredOrders.map((order) => (
              <OrderCard
                key={order.id}
                order={order}
                onPress={() => {
                  const matchedVendor = (vendors || []).find(
                    (vendor) => String(vendor.id) === String(order.vendorId)
                  );

                  if (matchedVendor) {
                    openVendor(matchedVendor);
                    return;
                  }

                  router.push('/reorder');
                }}
              />
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
          <Text style={styles.sectionTitle}>Favourites</Text>
          <Text style={styles.sectionSubtitle}>These are the vendors the user has explicitly liked.</Text>

          {favouriteVendors.length > 0 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
              {favouriteVendors.map((vendor) => (
                <VendorCard key={vendor.id} vendor={vendor} onPress={() => openVendor(vendor)} />
              ))}
            </ScrollView>
          ) : (
            <View style={styles.emptyPanel}>
              <Text style={styles.emptyTitle}>No favourites yet</Text>
              <Text style={styles.emptySubtitle}>Tap hearts on the home and store screens to collect favourites here.</Text>
            </View>
          )}
        </View>

        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>What is still missing for Swiggy-level production?</Text>
          <View style={styles.checkRow}>
            <Ionicons name="checkmark-circle" size={18} color={COLORS.green} />
            <Text style={styles.checkText}>Customer auth, order sync, and address API wiring are now in place.</Text>
          </View>
          <View style={styles.checkRow}>
            <Ionicons name="ellipse-outline" size={18} color={COLORS.orange} />
            <Text style={styles.checkText}>Next: GPS/map address picker, payment gateway, push notifications, and support flows.</Text>
          </View>
          <View style={styles.checkRow}>
            <Ionicons name="ellipse-outline" size={18} color={COLORS.orange} />
            <Text style={styles.checkText}>Then: analytics, crash reporting, QA automation, retries, and release hardening.</Text>
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
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 10,
  },
  topBarTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
  },
  topIconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingBottom: 32,
  },
  heroCard: {
    backgroundColor: COLORS.hero,
    borderRadius: 30,
    padding: 22,
    overflow: 'hidden',
    marginBottom: 16,
  },
  heroOrb: {
    position: 'absolute',
    width: 180,
    height: 180,
    borderRadius: 90,
    backgroundColor: COLORS.heroSoft,
    top: -44,
    right: -40,
  },
  heroEyebrow: {
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 1,
    color: '#fff5f3',
  },
  heroTitle: {
    marginTop: 8,
    fontSize: 26,
    lineHeight: 32,
    fontWeight: '900',
    color: '#fff',
  },
  heroSubtitle: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 21,
    color: '#ffe9e7',
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
    paddingHorizontal: 12,
  },
  statValue: {
    fontSize: 17,
    fontWeight: '900',
  },
  statLabel: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
    fontWeight: '700',
  },
  activeCartCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.orangeSoft,
    borderWidth: 1,
    borderColor: '#ffd9c1',
    borderRadius: 22,
    padding: 16,
    marginBottom: 16,
  },
  activeCartIcon: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  activeCartTitle: {
    fontSize: 15,
    fontWeight: '900',
    color: COLORS.text,
  },
  activeCartSubtitle: {
    marginTop: 4,
    fontSize: 13,
    color: COLORS.muted,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
    marginBottom: 16,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.text,
  },
  sectionSubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 20,
    color: COLORS.muted,
  },
  modeRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 16,
    marginBottom: 6,
  },
  modePill: {
    flex: 1,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#f8fafc',
    paddingVertical: 12,
    alignItems: 'center',
  },
  modePillActive: {
    backgroundColor: '#fff0ee',
    borderColor: '#f3c3bb',
  },
  modePillText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.muted,
  },
  modePillTextActive: {
    color: COLORS.heroDark,
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
    minHeight: 48,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#fbfbfd',
    paddingHorizontal: 14,
    fontSize: 14,
    color: COLORS.text,
  },
  primaryButton: {
    minHeight: 50,
    borderRadius: 16,
    backgroundColor: COLORS.heroDark,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
    flexDirection: 'row',
    gap: 10,
  },
  primaryButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '900',
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  profileAvatar: {
    width: 50,
    height: 50,
    borderRadius: 18,
    backgroundColor: '#fff0ee',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileAvatarText: {
    fontSize: 16,
    fontWeight: '900',
    color: COLORS.heroDark,
  },
  profileName: {
    fontSize: 15,
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
    borderRadius: 12,
    backgroundColor: COLORS.redSoft,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  logoutButtonText: {
    fontSize: 12,
    fontWeight: '900',
    color: COLORS.red,
  },
  addressCard: {
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#fbfbfd',
    padding: 14,
    marginTop: 12,
  },
  addressTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  addressTitle: {
    flex: 1,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '800',
    color: COLORS.text,
  },
  defaultBadge: {
    fontSize: 10,
    fontWeight: '900',
    color: COLORS.green,
    backgroundColor: COLORS.greenSoft,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },
  addressMeta: {
    marginTop: 8,
    fontSize: 12,
    color: COLORS.muted,
  },
  inlineButton: {
    alignSelf: 'flex-start',
    marginTop: 12,
    borderRadius: 12,
    backgroundColor: COLORS.blueSoft,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  inlineButtonText: {
    fontSize: 12,
    fontWeight: '900',
    color: COLORS.blue,
  },
  emptyPanel: {
    marginTop: 12,
    borderRadius: 18,
    backgroundColor: '#fbfbfd',
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
  emptyTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  emptySubtitle: {
    marginTop: 6,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  formDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginTop: 18,
    marginBottom: 18,
  },
  formTitle: {
    fontSize: 15,
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
    gap: 10,
    marginTop: 4,
    marginBottom: 8,
  },
  filterPill: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#fbfbfd',
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  filterPillActive: {
    backgroundColor: '#fff0ee',
    borderColor: '#f3c3bb',
  },
  filterPillText: {
    fontSize: 12,
    fontWeight: '800',
    color: COLORS.muted,
  },
  filterPillTextActive: {
    color: COLORS.heroDark,
  },
  orderCard: {
    marginTop: 12,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#fbfbfd',
    padding: 14,
  },
  orderCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderAvatar: {
    width: 42,
    height: 42,
    borderRadius: 16,
    backgroundColor: '#fff0ee',
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderAvatarText: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.heroDark,
  },
  orderVendorName: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.text,
  },
  orderVendorMeta: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
  },
  orderStatus: {
    fontSize: 11,
    fontWeight: '900',
    color: COLORS.green,
  },
  orderSummary: {
    marginTop: 12,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.text,
    fontWeight: '700',
  },
  orderMeta: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
  },
  horizontalRail: {
    gap: 12,
    paddingTop: 12,
    paddingBottom: 4,
  },
  vendorCard: {
    width: 148,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#fbfbfd',
    padding: 14,
  },
  vendorAvatar: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#fff0ee',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  vendorAvatarText: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.heroDark,
  },
  vendorName: {
    fontSize: 13,
    fontWeight: '900',
    color: COLORS.text,
  },
  vendorMeta: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    marginTop: 14,
  },
  checkText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.text,
  },
});