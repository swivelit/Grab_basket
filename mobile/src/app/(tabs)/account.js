import React, { useEffect, useMemo, useRef, useState } from 'react';
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
import * as Application from 'expo-application';
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

const GUEST_LINKS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'feedback', label: 'Feedback', icon: 'chatbox-ellipses-outline' },
  { key: 'privacy', label: 'Privacy & terms', icon: 'shield-checkmark-outline' },
];

const KOCHI_LAT = '9.9672';
const KOCHI_LNG = '76.2911';

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
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

function Field({ value, onChangeText, placeholder, secureTextEntry = false, keyboardType = 'default' }) {
  return (
    <TextInput
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor={BrandPalette.subtle}
      secureTextEntry={secureTextEntry}
      keyboardType={keyboardType}
      autoCapitalize="none"
      style={styles.input}
    />
  );
}

function RowCard({ label, icon, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.rowCard} onPress={onPress}>
      <View style={styles.rowCardLeft}>
        <View style={styles.rowCardIconWrap}>
          <Ionicons name={icon} size={18} color={BrandPalette.primary} />
        </View>
        <Text style={styles.rowCardLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={16} color={BrandPalette.subtle} />
    </TouchableOpacity>
  );
}

function StatPill({ label, value }) {
  return (
    <View style={styles.statPill}>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
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
  const [loginIdentifier, setLoginIdentifier] = useState(authEmail || '');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
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
  const hasHydratedAccountDataRef = useRef(false);
  const loadAddressesRef = useRef(loadAddresses);
  const loadOrdersRef = useRef(loadOrders);

  useEffect(() => {
    loadAddressesRef.current = loadAddresses;
  }, [loadAddresses]);

  useEffect(() => {
    loadOrdersRef.current = loadOrders;
  }, [loadOrders]);

  useEffect(() => {
    if (authEmail) setLoginIdentifier(authEmail);
  }, [authEmail]);

  useEffect(() => {
    if (!sessionReady) return;

    if (!isAuthenticated) {
      hasHydratedAccountDataRef.current = false;
      return;
    }

    if (hasHydratedAccountDataRef.current) return;

    hasHydratedAccountDataRef.current = true;
    loadAddressesRef.current?.().catch(() => {});
    loadOrdersRef.current?.({ silent: true }).catch(() => {});
  }, [isAuthenticated, sessionReady]);

  const versionText = useMemo(() => {
    const version = Application.nativeApplicationVersion || '1.0.0';
    const build = Application.nativeBuildVersion || '1';
    return `App version ${version} (${build})`;
  }, []);

  const memberSince = useMemo(() => formatDate(profile?.created_at), [profile?.created_at]);
  const activeAddressLabel = defaultAddress?.label || defaultAddress?.line1 || 'No default address';
  const orderCount = Array.isArray(pastOrders) ? pastOrders.length : 0;

  const handleAuth = async () => {
    if (authMode === 'login') {
      if (!loginIdentifier.trim() || !password.trim()) {
        setLocalNotice('Enter your email or phone number and password.');
        return;
      }

      const ok = await login({ identifier: loginIdentifier, password });
      if (ok) {
        setLocalNotice('You are now signed in.');
      }
      return;
    }

    if (!email.trim() || !phone.trim() || !password.trim()) {
      setLocalNotice('Enter your email, phone number and password.');
      return;
    }

    const ok = await register({ email, phone, password });
    if (ok) {
      setLoginIdentifier(phone.trim() || email.trim());
      setLocalNotice('Your account is ready.');
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
      setLocalNotice('Addresses refreshed.');
      return;
    }

    setLocalNotice(
      key === 'offers'
        ? 'Offers section is ready for the next production sprint.'
        : 'Support workflows can be wired next.'
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
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        {!isAuthenticated ? (
          <>
            <View style={styles.guestHero}>
              <Text style={styles.guestEyebrow}>{appVariantName}</Text>
              <Text style={styles.guestTitle}>One app for food, grocery, dining and more in minutes.</Text>
              <Text style={styles.guestSubtitle}>
                Match the clean, conversion-first flow from leading consumer apps while keeping the GrabBasket brand language.
              </Text>
            </View>

            <View style={styles.sheetCard}>
              <View style={styles.authToggleRow}>
                <TouchableOpacity
                  activeOpacity={0.94}
                  style={[styles.authToggle, authMode === 'login' && styles.authToggleActive]}
                  onPress={() => setAuthMode('login')}>
                  <Text style={[styles.authToggleText, authMode === 'login' && styles.authToggleTextActive]}>Login</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  activeOpacity={0.94}
                  style={[styles.authToggle, authMode === 'register' && styles.authToggleActive]}
                  onPress={() => setAuthMode('register')}>
                  <Text style={[styles.authToggleText, authMode === 'register' && styles.authToggleTextActive]}>Sign up</Text>
                </TouchableOpacity>
              </View>

              {inlineErrors.auth ? <InlineErrorCard title="Authentication issue" message={inlineErrors.auth} /> : null}
              {localNotice ? <InlineNoticeCard title="Ready" message={localNotice} onDismiss={() => setLocalNotice('')} /> : null}

              {authMode === 'login' ? (
                <>
                  <Field
                    value={loginIdentifier}
                    onChangeText={setLoginIdentifier}
                    placeholder="Email or phone number"
                    keyboardType="default"
                  />
                  <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
                  <Text style={styles.authHintText}>Use your email address or mobile number to sign in.</Text>
                </>
              ) : (
                <>
                  <Field value={email} onChangeText={setEmail} placeholder="Email address" keyboardType="email-address" />
                  <Field value={phone} onChangeText={setPhone} placeholder="Phone number" keyboardType="phone-pad" />
                  <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
                  <Text style={styles.authHintText}>Sign up now collects both email and mobile number.</Text>
                </>
              )}

              <TouchableOpacity activeOpacity={0.95} style={styles.primaryButton} onPress={handleAuth} disabled={authLoading}>
                {authLoading ? (
                  <ActivityIndicator color="#FFFFFF" size="small" />
                ) : (
                  <Text style={styles.primaryButtonText}>{authMode === 'login' ? 'Login' : 'Create account'}</Text>
                )}
              </TouchableOpacity>

              <Text style={styles.legalText}>
                By tapping in, you agree to our terms of service and privacy policy.
              </Text>

              <View style={styles.guestLinkGroup}>
                {GUEST_LINKS.map((item) => (
                  <RowCard key={item.key} label={item.label} icon={item.icon} onPress={() => setLocalNotice(`${item.label} can be expanded next.`)} />
                ))}
              </View>

              <Text style={styles.versionText}>{versionText}</Text>
            </View>
          </>
        ) : (
          <View style={styles.pageBody}>
            <View style={styles.profileCard}>
              <View style={styles.profileTopRow}>
                <View style={styles.avatarWrap}>
                  <Text style={styles.avatarText}>{initials(authEmail || profile?.email || 'GB')}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.profileTitle}>{profile?.full_name || profile?.phone || profile?.email || authEmail || 'GrabBasket member'}</Text>
                  <Text style={styles.profileSubtitle}>{memberSince !== '—' ? `Member since ${memberSince}` : 'Production-ready account shell'}</Text>
                  <Text style={styles.profileMeta}>{profile?.phone || profile?.email || activeAddressLabel}</Text>
                </View>
              </View>

              <View style={styles.statsRow}>
                <StatPill label="Orders" value={String(orderCount)} />
                <StatPill label="Addresses" value={String(addresses.length || 0)} />
                <StatPill label="Saved" value={defaultAddress ? '1' : '0'} />
              </View>
            </View>

            {localNotice ? <InlineNoticeCard title="Updated" message={localNotice} onDismiss={() => setLocalNotice('')} /> : null}
            {inlineErrors.orders ? <InlineErrorCard title="Orders issue" message={inlineErrors.orders} /> : null}
            {inlineErrors.addresses ? <InlineErrorCard title="Address issue" message={inlineErrors.addresses} /> : null}

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Quick actions</Text>
              <View style={styles.quickActionList}>
                {QUICK_ACTIONS.map((item) => (
                  <RowCard key={item.key} label={item.label} icon={item.icon} onPress={() => handleQuickAction(item.key)} />
                ))}
              </View>
            </View>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Add a saved address</Text>
              <View style={styles.cardSurface}>
                <Field value={addressForm.label} onChangeText={(value) => setAddressForm((current) => ({ ...current, label: value }))} placeholder="Label" />
                <Field value={addressForm.line1} onChangeText={(value) => setAddressForm((current) => ({ ...current, line1: value }))} placeholder="Address line" />
                <View style={styles.inlineFields}>
                  <View style={{ flex: 1 }}>
                    <Field value={addressForm.city} onChangeText={(value) => setAddressForm((current) => ({ ...current, city: value }))} placeholder="City" />
                  </View>
                  <View style={{ width: 120 }}>
                    <Field value={addressForm.pincode} onChangeText={(value) => setAddressForm((current) => ({ ...current, pincode: value }))} placeholder="Pincode" keyboardType="number-pad" />
                  </View>
                </View>

                <TouchableOpacity activeOpacity={0.95} style={styles.primaryButton} onPress={handleAddAddress} disabled={addressSaving}>
                  {addressSaving ? <ActivityIndicator color="#FFFFFF" size="small" /> : <Text style={styles.primaryButtonText}>Save address</Text>}
                </TouchableOpacity>
              </View>
            </View>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Saved addresses</Text>
              {addressesLoading ? (
                <View style={styles.cardSurface}><ActivityIndicator color={BrandPalette.primary} /></View>
              ) : addresses.length ? (
                <View style={styles.addressList}>
                  {addresses.slice(0, 4).map((address) => {
                    const isDefault = String(address?.id) === String(defaultAddress?.id);
                    return (
                      <TouchableOpacity
                        key={address.id}
                        activeOpacity={0.94}
                        style={[styles.addressCard, isDefault && styles.addressCardActive]}
                        onPress={() => setDefaultAddress(address.id)}>
                        <View style={styles.addressHeader}>
                          <Text style={styles.addressLabel}>{address.label || 'Saved address'}</Text>
                          {isDefault ? <Text style={styles.addressBadge}>Default</Text> : null}
                        </View>
                        <Text style={styles.addressLine}>{address.line1}</Text>
                        <Text style={styles.addressMeta}>{[address.city, address.pincode].filter(Boolean).join(' · ')}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              ) : (
                <View style={styles.cardSurface}><Text style={styles.helperText}>No saved addresses yet.</Text></View>
              )}
            </View>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Recent orders</Text>
              {ordersLoading ? (
                <View style={styles.cardSurface}><ActivityIndicator color={BrandPalette.primary} /></View>
              ) : pastOrders.length ? (
                <View style={styles.orderList}>
                  {pastOrders.slice(0, 5).map((order) => (
                    <TouchableOpacity key={order.id} activeOpacity={0.94} style={styles.orderCard} onPress={() => router.push('/(tabs)/reorder')}>
                      <View style={styles.orderCardTop}>
                        <Text style={styles.orderCardTitle}>Order #{order.id}</Text>
                        <Text style={styles.orderCardAmount}>{money(order?.total_amount || 0)}</Text>
                      </View>
                      <Text style={styles.orderCardStatus}>{mapOrderStatus(order)}</Text>
                      <Text style={styles.orderCardMeta}>{formatDate(order?.created_at)}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              ) : (
                <View style={styles.cardSurface}><Text style={styles.helperText}>Your order history will appear here.</Text></View>
              )}
            </View>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Device</Text>
              <View style={styles.cardSurface}>
                <Text style={styles.helperText}>Device ID: {deviceId || 'Unavailable'}</Text>
              </View>
            </View>

            <TouchableOpacity
              activeOpacity={0.95}
              style={[styles.primaryButton, styles.logoutButton]}
              onPress={async () => {
                await logout();
                setLocalNotice('You have been signed out.');
              }}>
              <Text style={styles.primaryButtonText}>Logout</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: BrandPalette.page,
  },
  loadingState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingText: {
    fontSize: 15,
    lineHeight: 19,
    color: BrandPalette.textMuted,
  },
  guestHero: {
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 28,
    backgroundColor: BrandPalette.primary,
  },
  guestEyebrow: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '900',
    color: 'rgba(255,255,255,0.88)',
    textTransform: 'uppercase',
  },
  guestTitle: {
    marginTop: 14,
    fontSize: 34,
    lineHeight: 39,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  guestSubtitle: {
    marginTop: 12,
    fontSize: 14,
    lineHeight: 20,
    color: 'rgba(255,255,255,0.84)',
  },
  sheetCard: {
    marginTop: -12,
    marginHorizontal: 16,
    borderRadius: 30,
    backgroundColor: BrandPalette.white,
    padding: 18,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    ...createShadow(0.08, 14, 6),
  },
  authToggleRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
  },
  authToggle: {
    flex: 1,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    backgroundColor: BrandPalette.backgroundAlt,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
  },
  authToggleActive: {
    backgroundColor: BrandPalette.primary,
    borderColor: BrandPalette.primary,
  },
  authToggleText: {
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  authToggleTextActive: {
    color: BrandPalette.white,
  },
  authHintText: {
    marginTop: -2,
    marginBottom: 12,
    fontSize: 12,
    lineHeight: 17,
    color: BrandPalette.textMuted,
  },
  input: {
    height: 52,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    backgroundColor: BrandPalette.backgroundAlt,
    paddingHorizontal: 14,
    fontSize: 15,
    lineHeight: 19,
    color: BrandPalette.text,
    marginBottom: 12,
  },
  primaryButton: {
    height: 54,
    borderRadius: 18,
    backgroundColor: BrandPalette.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 4,
  },
  primaryButtonText: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  legalText: {
    marginTop: 16,
    fontSize: 12,
    lineHeight: 17,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  guestLinkGroup: {
    marginTop: 20,
    gap: 10,
  },
  rowCard: {
    borderRadius: 18,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    backgroundColor: BrandPalette.white,
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  rowCardLeft: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  rowCardIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowCardLabel: {
    flex: 1,
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  versionText: {
    marginTop: 20,
    textAlign: 'center',
    fontSize: 12,
    lineHeight: 16,
    color: BrandPalette.subtle,
  },
  pageBody: {
    paddingHorizontal: 16,
    paddingTop: 10,
    gap: 16,
  },
  profileCard: {
    borderRadius: 28,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 18,
    ...createShadow(0.08, 14, 6),
  },
  profileTopRow: {
    flexDirection: 'row',
    gap: 14,
  },
  avatarWrap: {
    width: 62,
    height: 62,
    borderRadius: 31,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 22,
    lineHeight: 24,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  profileTitle: {
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  profileSubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.textMuted,
  },
  profileMeta: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.inkSoft,
    fontWeight: '700',
  },
  statsRow: {
    marginTop: 18,
    flexDirection: 'row',
    gap: 10,
  },
  statPill: {
    flex: 1,
    borderRadius: 18,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statValue: {
    fontSize: 19,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  statLabel: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 14,
    color: BrandPalette.textMuted,
  },
  sectionBlock: {
    gap: 10,
  },
  sectionTitle: {
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  quickActionList: {
    gap: 10,
  },
  cardSurface: {
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 16,
    gap: 12,
  },
  inlineFields: {
    flexDirection: 'row',
    gap: 10,
  },
  addressList: {
    gap: 10,
  },
  addressCard: {
    borderRadius: 18,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 14,
  },
  addressCardActive: {
    borderColor: BrandPalette.primary,
    backgroundColor: '#FFF5F5',
  },
  addressHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  addressLabel: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  addressBadge: {
    fontSize: 11,
    lineHeight: 13,
    fontWeight: '900',
    color: BrandPalette.primary,
    textTransform: 'uppercase',
  },
  addressLine: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 18,
    color: BrandPalette.text,
  },
  addressMeta: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 15,
    color: BrandPalette.textMuted,
  },
  orderList: {
    gap: 10,
  },
  orderCard: {
    borderRadius: 18,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 14,
  },
  orderCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  orderCardTitle: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  orderCardAmount: {
    fontSize: 14,
    lineHeight: 17,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  orderCardStatus: {
    marginTop: 8,
    fontSize: 13,
    lineHeight: 16,
    color: BrandPalette.inkSoft,
    fontWeight: '700',
  },
  orderCardMeta: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 15,
    color: BrandPalette.textMuted,
  },
  helperText: {
    fontSize: 13,
    lineHeight: 18,
    color: BrandPalette.textMuted,
  },
  logoutButton: {
    marginTop: 4,
    marginBottom: 12,
  },
});