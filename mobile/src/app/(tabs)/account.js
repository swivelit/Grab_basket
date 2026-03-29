import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import InlineErrorCard from '../../components/inline-error-card';
import InlineNoticeCard from '../../components/inline-notice-card';
import { useGrabBasket } from '../../../App';

const QUICK_ACTIONS = [
  { key: 'orders', label: 'Your orders', icon: 'receipt-outline' },
  { key: 'addresses', label: 'Saved addresses', icon: 'location-outline' },
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'help', label: 'Help & support', icon: 'help-circle-outline' },
];

const GUEST_ACTIONS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'feedback', label: 'Feedback', icon: 'chatbox-ellipses-outline' },
  { key: 'support', label: 'Need help?', icon: 'headset-outline' },
];

const KOCHI_LAT = '9.9672';
const KOCHI_LNG = '76.2911';

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleDateString();
}

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function mapOrderStatus(order) {
  const raw = String(order?.status || order?.payment_status || 'Placed');
  return raw.replace(/_/g, ' ');
}

function initials(value = '') {
  return String(value || '')
    .split(/[\s@._-]+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function QuickActionCard({ label, icon, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.quickActionCard} onPress={onPress}>
      <View style={styles.quickActionIconWrap}>
        <Ionicons name={icon} size={20} color={BrandPalette.primary} />
      </View>
      <Text style={styles.quickActionLabel}>{label}</Text>
      <Ionicons name="chevron-forward" size={16} color={BrandPalette.subtle} />
    </TouchableOpacity>
  );
}

function Field({
  value,
  onChangeText,
  placeholder,
  secureTextEntry = false,
  keyboardType = 'default',
}) {
  return (
    <TextInput
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor="#9A9A9A"
      secureTextEntry={secureTextEntry}
      keyboardType={keyboardType}
      autoCapitalize="none"
      style={styles.input}
    />
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const {
    appVariantName,
    sessionReady,
    isAuthenticated,
    authEmail,
    authLoading,
    login,
    register,
    logout,
    profile,
    deviceId,
    addresses,
    addressesLoading,
    createAddress,
    setDefaultAddress,
    defaultAddress,
    pastOrders,
    loadOrders,
    ordersLoading,
    loadAddresses,
    inlineErrors,
  } = useGrabBasket();

  const [authMode, setAuthMode] = useState('login');
  const [email, setEmail] = useState(authEmail || '');
  const [password, setPassword] = useState('password');
  const [addressForm, setAddressForm] = useState({
    label: 'Home',
    line1: '',
    city: 'Kochi',
    pincode: '',
    lat: KOCHI_LAT,
    lng: KOCHI_LNG,
  });
  const [addressSaving, setAddressSaving] = useState(false);
  const [localNotice, setLocalNotice] = useState('');

  useEffect(() => {
    if (authEmail) {
      setEmail(authEmail);
    }
  }, [authEmail]);

  useEffect(() => {
    if (!isAuthenticated) return;
    loadAddresses().catch(() => {});
    loadOrders({ silent: true }).catch(() => {});
  }, [isAuthenticated, loadAddresses, loadOrders]);

  const memberSince = useMemo(() => formatDate(profile?.created_at), [profile?.created_at]);

  const handleAuth = async () => {
    if (!email.trim() || !password.trim()) return;

    const ok =
      authMode === 'login'
        ? await login({ email, password })
        : await register({ email, password });

    if (ok) {
      setLocalNotice(authMode === 'login' ? 'You are now signed in.' : 'Your account is ready.');
    }
  };

  const handleAddAddress = async () => {
    setAddressSaving(true);
    const next = await createAddress({
      ...addressForm,
      lat: Number(addressForm.lat),
      lng: Number(addressForm.lng),
      is_default: addresses.length === 0,
    });
    setAddressSaving(false);

    if (next) {
      setAddressForm({
        label: 'Home',
        line1: '',
        city: 'Kochi',
        pincode: '',
        lat: KOCHI_LAT,
        lng: KOCHI_LNG,
      });
      setLocalNotice('Address saved successfully.');
    }
  };

  const handleQuickAction = (key) => {
    if (key === 'orders') {
      router.push('/(tabs)/reorder');
      return;
    }

    if (key === 'addresses') {
      loadAddresses().catch(() => {});
      return;
    }

    setLocalNotice(
      key === 'offers'
        ? 'Offers section will be expanded next.'
        : 'Support tools will be connected next.'
    );
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.loadingState}>
          <ActivityIndicator color={BrandPalette.primary} />
          <Text style={styles.loadingText}>Preparing your account...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        {!isAuthenticated ? (
          <>
            <View style={styles.guestHero}>
              <View style={styles.guestHeroBadge}>
                <View style={styles.guestLogoWrap}>
                  <Ionicons name="bag-handle-outline" size={24} color="#FFFFFF" />
                </View>
                <Text style={styles.guestHeroBadgeText}>{appVariantName}</Text>
              </View>

              <Text style={styles.guestHeroTitle}>
                One app for food, grocery, dining and more in mins!
              </Text>
              <Text style={styles.guestHeroSubtitle}>
                Sign in to unlock fast checkout, saved addresses, and cleaner reorders.
              </Text>
            </View>

            <View style={styles.sheetCard}>
              <View style={styles.authToggleRow}>
                <TouchableOpacity
                  activeOpacity={0.92}
                  style={[styles.authToggle, authMode === 'login' && styles.authToggleActive]}
                  onPress={() => setAuthMode('login')}>
                  <Text
                    style={[
                      styles.authToggleText,
                      authMode === 'login' && styles.authToggleTextActive,
                    ]}>
                    Login
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  activeOpacity={0.92}
                  style={[styles.authToggle, authMode === 'register' && styles.authToggleActive]}
                  onPress={() => setAuthMode('register')}>
                  <Text
                    style={[
                      styles.authToggleText,
                      authMode === 'register' && styles.authToggleTextActive,
                    ]}>
                    Create account
                  </Text>
                </TouchableOpacity>
              </View>

              {inlineErrors?.auth ? (
                <InlineErrorCard title="Could not continue" message={inlineErrors.auth} />
              ) : null}

              {localNotice ? (
                <InlineNoticeCard
                  title="Done"
                  message={localNotice}
                  onDismiss={() => setLocalNotice('')}
                />
              ) : null}

              <Field
                value={email}
                onChangeText={setEmail}
                placeholder="Email address"
                keyboardType="email-address"
              />
              <Field
                value={password}
                onChangeText={setPassword}
                placeholder="Password"
                secureTextEntry
              />

              <TouchableOpacity
                activeOpacity={0.92}
                style={styles.primaryButton}
                onPress={handleAuth}
                disabled={authLoading}>
                <Text style={styles.primaryButtonText}>
                  {authLoading
                    ? 'Please wait...'
                    : authMode === 'login'
                      ? 'Login'
                      : 'Create account'}
                </Text>
              </TouchableOpacity>

              <View style={styles.demoCard}>
                <Text style={styles.demoTitle}>Demo sign-in</Text>
                <Text style={styles.demoText}>customer@demo.com</Text>
                <Text style={styles.demoText}>password</Text>
              </View>

              <View style={styles.linkList}>
                {GUEST_ACTIONS.map((item) => (
                  <QuickActionCard
                    key={item.key}
                    label={item.label}
                    icon={item.icon}
                    onPress={() => handleQuickAction(item.key)}
                  />
                ))}
              </View>

              <Text style={styles.versionText}>App version 4.103.4 · consumer build</Text>
            </View>
          </>
        ) : (
          <View style={styles.authenticatedWrap}>
            <View style={styles.profileHero}>
              <View style={styles.profileHeroTopRow}>
                <View style={styles.avatarCircle}>
                  <Text style={styles.avatarText}>{initials(authEmail)}</Text>
                </View>

                <TouchableOpacity activeOpacity={0.92} style={styles.logoutButton} onPress={logout}>
                  <Ionicons name="log-out-outline" size={16} color={BrandPalette.primary} />
                  <Text style={styles.logoutButtonText}>Logout</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.profileTitle}>Welcome back</Text>
              <Text style={styles.profileEmail}>{authEmail}</Text>

              <View style={styles.profileStatsRow}>
                <View style={styles.profileStatCard}>
                  <Text style={styles.profileStatLabel}>Orders</Text>
                  <Text style={styles.profileStatValue}>{pastOrders.length}</Text>
                </View>

                <View style={styles.profileStatCard}>
                  <Text style={styles.profileStatLabel}>Addresses</Text>
                  <Text style={styles.profileStatValue}>{addresses.length}</Text>
                </View>

                <View style={styles.profileStatCard}>
                  <Text style={styles.profileStatLabel}>Member since</Text>
                  <Text style={styles.profileStatValueSmall}>{memberSince || 'Today'}</Text>
                </View>
              </View>
            </View>

            <View style={styles.contentWrap}>
              {localNotice ? (
                <InlineNoticeCard
                  title="Updated"
                  message={localNotice}
                  onDismiss={() => setLocalNotice('')}
                />
              ) : null}

              {inlineErrors?.addresses ? (
                <InlineErrorCard title="Address issue" message={inlineErrors.addresses} />
              ) : null}

              {inlineErrors?.orders ? (
                <InlineErrorCard title="Orders could not be refreshed" message={inlineErrors.orders} />
              ) : null}

              <View style={styles.sectionCard}>
                <Text style={styles.sectionTitle}>Quick actions</Text>
                <View style={styles.quickActionList}>
                  {QUICK_ACTIONS.map((item) => (
                    <QuickActionCard
                      key={item.key}
                      label={item.label}
                      icon={item.icon}
                      onPress={() => handleQuickAction(item.key)}
                    />
                  ))}
                </View>
              </View>

              <View style={styles.sectionCard}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Saved addresses</Text>
                  {addressesLoading ? <ActivityIndicator size="small" color={BrandPalette.primary} /> : null}
                </View>

                {defaultAddress ? (
                  <View style={styles.defaultAddressBanner}>
                    <Ionicons name="navigate" size={18} color={BrandPalette.primary} />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.defaultAddressTitle}>{defaultAddress.label}</Text>
                      <Text style={styles.defaultAddressText}>
                        {[defaultAddress.line1, defaultAddress.city, defaultAddress.pincode]
                          .filter(Boolean)
                          .join(', ')}
                      </Text>
                    </View>
                  </View>
                ) : null}

                <View style={styles.addressList}>
                  {addresses.map((item) => {
                    const isDefault = String(defaultAddress?.id) === String(item.id);

                    return (
                      <TouchableOpacity
                        key={item.id}
                        activeOpacity={0.92}
                        style={styles.addressCard}
                        onPress={() => setDefaultAddress(item.id)}>
                        <View style={{ flex: 1 }}>
                          <View style={styles.addressHeadRow}>
                            <Text style={styles.addressTitle}>{item.label}</Text>
                            {isDefault ? (
                              <View style={styles.defaultChip}>
                                <Text style={styles.defaultChipText}>Default</Text>
                              </View>
                            ) : null}
                          </View>
                          <Text style={styles.addressText}>
                            {[item.line1, item.city, item.pincode].filter(Boolean).join(', ')}
                          </Text>
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>

                <View style={styles.formWrap}>
                  <Text style={styles.formTitle}>Add a new address</Text>
                  <Field
                    value={addressForm.label}
                    onChangeText={(value) =>
                      setAddressForm((current) => ({ ...current, label: value }))
                    }
                    placeholder="Label"
                  />
                  <Field
                    value={addressForm.line1}
                    onChangeText={(value) =>
                      setAddressForm((current) => ({ ...current, line1: value }))
                    }
                    placeholder="Address line 1"
                  />

                  <View style={styles.rowInputs}>
                    <View style={{ flex: 1 }}>
                      <Field
                        value={addressForm.city}
                        onChangeText={(value) =>
                          setAddressForm((current) => ({ ...current, city: value }))
                        }
                        placeholder="City"
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Field
                        value={addressForm.pincode}
                        onChangeText={(value) =>
                          setAddressForm((current) => ({ ...current, pincode: value }))
                        }
                        placeholder="Pincode"
                        keyboardType="number-pad"
                      />
                    </View>
                  </View>

                  <Text style={styles.coordsHint}>
                    Delivery pin defaults to Kochi demo coordinates so checkout works immediately.
                  </Text>

                  <TouchableOpacity
                    activeOpacity={0.92}
                    style={styles.secondaryButton}
                    onPress={handleAddAddress}
                    disabled={addressSaving}>
                    <Text style={styles.secondaryButtonText}>
                      {addressSaving ? 'Saving...' : 'Save address'}
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>

              <View style={styles.sectionCard}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Recent orders</Text>
                  {ordersLoading ? <ActivityIndicator size="small" color={BrandPalette.primary} /> : null}
                </View>

                {pastOrders.length ? (
                  <View style={styles.orderList}>
                    {pastOrders.slice(0, 4).map((order) => (
                      <View key={order.id} style={styles.orderCard}>
                        <View style={styles.orderTopRow}>
                          <Text style={styles.orderVendor}>
                            {order.vendor_name || order.vendor?.name || 'GrabBasket order'}
                          </Text>
                          <Text style={styles.orderAmount}>
                            {money(order.total_amount || order.amount || 0)}
                          </Text>
                        </View>
                        <Text style={styles.orderMeta}>
                          {mapOrderStatus(order)} · {formatDate(order.created_at) || 'Just now'}
                        </Text>
                      </View>
                    ))}
                  </View>
                ) : (
                  <View style={styles.emptyStateCard}>
                    <Ionicons name="receipt-outline" size={20} color={BrandPalette.primary} />
                    <Text style={styles.emptyStateTitle}>No orders yet</Text>
                    <Text style={styles.emptyStateText}>
                      Your first checkout will show up here for quick reorder.
                    </Text>
                  </View>
                )}
              </View>

              <View style={styles.sectionCard}>
                <Text style={styles.sectionTitle}>App details</Text>
                <Text style={styles.metaText}>Device ID: {deviceId || 'Not available'}</Text>
                <Text style={styles.metaText}>Build: consumer · production prep</Text>
              </View>
            </View>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8F8F8',
  },
  loadingState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    fontWeight: '700',
  },
  guestHero: {
    backgroundColor: BrandPalette.primary,
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 28,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
  },
  guestHeroBadge: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginBottom: 18,
  },
  guestLogoWrap: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  guestHeroBadgeText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  guestHeroTitle: {
    color: '#FFFFFF',
    fontSize: 34,
    lineHeight: 40,
    fontWeight: '900',
    marginBottom: 10,
    maxWidth: '92%',
  },
  guestHeroSubtitle: {
    color: 'rgba(255,255,255,0.82)',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '600',
    maxWidth: '92%',
  },
  sheetCard: {
    marginTop: -10,
    marginHorizontal: 14,
    backgroundColor: '#FFFFFF',
    borderRadius: 28,
    padding: 16,
    gap: 14,
    ...createShadow(0.08, 16, 8),
  },
  authToggleRow: {
    flexDirection: 'row',
    backgroundColor: '#F5F5F5',
    borderRadius: 18,
    padding: 4,
    gap: 4,
  },
  authToggle: {
    flex: 1,
    borderRadius: 14,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  authToggleActive: {
    backgroundColor: '#FFFFFF',
    ...createShadow(0.06, 10, 4),
  },
  authToggleText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    fontWeight: '800',
  },
  authToggleTextActive: {
    color: BrandPalette.text,
  },
  input: {
    borderRadius: 18,
    backgroundColor: '#F8F8F8',
    borderWidth: 1,
    borderColor: '#ECECEC',
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '600',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  primaryButton: {
    backgroundColor: BrandPalette.primary,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 15,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '900',
  },
  demoCard: {
    borderRadius: 20,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    backgroundColor: '#FFF8F6',
    padding: 14,
    gap: 4,
  },
  demoTitle: {
    color: BrandPalette.text,
    fontSize: 13,
    fontWeight: '900',
    marginBottom: 4,
  },
  demoText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    fontWeight: '700',
  },
  linkList: {
    gap: 10,
  },
  quickActionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#EFEFEF',
    padding: 14,
  },
  quickActionIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickActionLabel: {
    flex: 1,
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '800',
  },
  versionText: {
    color: BrandPalette.subtle,
    fontSize: 12,
    fontWeight: '700',
    textAlign: 'center',
    marginTop: 4,
  },
  authenticatedWrap: {
    gap: 18,
  },
  profileHero: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 18,
    paddingTop: 14,
    paddingBottom: 22,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
    ...createShadow(0.06, 12, 6),
  },
  profileHeroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  avatarCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: BrandPalette.primary,
    fontSize: 20,
    fontWeight: '900',
  },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#F0C9CC',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  logoutButtonText: {
    color: BrandPalette.primary,
    fontSize: 12,
    fontWeight: '900',
  },
  profileTitle: {
    color: BrandPalette.text,
    fontSize: 26,
    fontWeight: '900',
    marginBottom: 6,
  },
  profileEmail: {
    color: BrandPalette.textMuted,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 16,
  },
  profileStatsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  profileStatCard: {
    flex: 1,
    backgroundColor: '#F8F8F8',
    borderRadius: 20,
    padding: 12,
    minHeight: 76,
  },
  profileStatLabel: {
    color: BrandPalette.textMuted,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 8,
  },
  profileStatValue: {
    color: BrandPalette.text,
    fontSize: 22,
    fontWeight: '900',
  },
  profileStatValueSmall: {
    color: BrandPalette.text,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '900',
  },
  contentWrap: {
    paddingHorizontal: 16,
    gap: 16,
  },
  sectionCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 26,
    padding: 16,
    gap: 14,
    ...createShadow(0.06, 12, 6),
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  sectionTitle: {
    color: BrandPalette.text,
    fontSize: 18,
    fontWeight: '900',
  },
  quickActionList: {
    gap: 10,
  },
  defaultAddressBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 20,
    backgroundColor: '#FFF8F6',
    borderWidth: 1,
    borderColor: '#F4DEDF',
    padding: 14,
  },
  defaultAddressTitle: {
    color: BrandPalette.text,
    fontSize: 14,
    fontWeight: '900',
    marginBottom: 4,
  },
  defaultAddressText: {
    color: BrandPalette.textMuted,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
  },
  addressList: {
    gap: 10,
  },
  addressCard: {
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#EFEFEF',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  addressHeadRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 6,
  },
  addressTitle: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '800',
  },
  defaultChip: {
    borderRadius: 999,
    backgroundColor: BrandPalette.primarySoft,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  defaultChipText: {
    color: BrandPalette.primary,
    fontSize: 11,
    fontWeight: '900',
  },
  addressText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  formWrap: {
    gap: 12,
    paddingTop: 6,
  },
  formTitle: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '900',
  },
  rowInputs: {
    flexDirection: 'row',
    gap: 10,
  },
  coordsHint: {
    color: BrandPalette.subtle,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
  },
  secondaryButton: {
    alignSelf: 'flex-start',
    backgroundColor: '#FFF8F6',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#F4DEDF',
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  secondaryButtonText: {
    color: BrandPalette.primary,
    fontSize: 13,
    fontWeight: '900',
  },
  orderList: {
    gap: 10,
  },
  orderCard: {
    borderRadius: 20,
    backgroundColor: '#F8F8F8',
    padding: 14,
  },
  orderTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 6,
  },
  orderVendor: {
    flex: 1,
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '800',
  },
  orderAmount: {
    color: BrandPalette.text,
    fontSize: 14,
    fontWeight: '900',
  },
  orderMeta: {
    color: BrandPalette.textMuted,
    fontSize: 12,
    fontWeight: '600',
  },
  emptyStateCard: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    borderRadius: 22,
    backgroundColor: '#F8F8F8',
    paddingVertical: 28,
    paddingHorizontal: 18,
  },
  emptyStateTitle: {
    color: BrandPalette.text,
    fontSize: 16,
    fontWeight: '900',
  },
  emptyStateText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '600',
    textAlign: 'center',
  },
  metaText: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '600',
  },
});