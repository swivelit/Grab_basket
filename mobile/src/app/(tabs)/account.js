import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
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
import { mapLegacyService } from '@/domains/grab-basket-utils';
import { useGrabBasket } from '../../../App';

const GUEST_LINKS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'feedback', label: 'Feedback', icon: 'chatbox-ellipses-outline' },
  { key: 'privacy', label: 'Privacy & terms', icon: 'shield-checkmark-outline' },
];

const ACCOUNT_SHORTCUTS = [
  {
    key: 'saved_addresses',
    label: 'Saved Address',
    icon: 'location-outline',
    subtitle: 'Manage delivery places',
  },
  {
    key: 'payment_modes',
    label: 'Payment Modes',
    icon: 'card-outline',
    subtitle: 'Cards, UPI and more',
  },
  {
    key: 'refunds',
    label: 'My Refunds',
    icon: 'refresh-circle-outline',
    subtitle: 'Refunds and updates',
  },
  {
    key: 'wallet',
    label: 'My Wallet',
    icon: 'wallet-outline',
    subtitle: 'Wallet balance & credits',
  },
];

const ACCOUNT_LINKS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'help', label: 'Help & support', icon: 'help-circle-outline' },
  { key: 'wishlist', label: 'My Wishlist', icon: 'bookmark-outline' },
  { key: 'favourites', label: 'Favourites', icon: 'heart-outline' },
  { key: 'statements', label: 'Account Statements', icon: 'document-text-outline' },
  { key: 'corporate_rewards', label: 'Corporate Rewards', icon: 'briefcase-outline' },
  { key: 'student_rewards', label: 'Student Rewards', icon: 'school-outline' },
  { key: 'partner_rewards', label: 'Partner Rewards', icon: 'ribbon-outline' },
  { key: 'contact_pref', label: 'Allow stores to contact you', icon: 'chatbubbles-outline' },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatOrderDateTime(value) {
  if (!value) return 'Recently';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Recently';
  return date.toLocaleString('en-IN', {
    day: 'numeric',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  });
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

function getProfileTitle(profile, authEmail) {
  const name = String(profile?.full_name || '').trim();
  if (name) return name;

  const phone = String(profile?.phone || '').trim();
  if (phone) return phone;

  const email = String(profile?.email || authEmail || '').trim();
  if (!email) return 'GrabBasket Member';

  const localPart = email.split('@')[0];
  if (!localPart) return email;

  return localPart
    .replace(/[._-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function getProfileMeta(profile, authEmail) {
  const values = [profile?.phone, profile?.email, authEmail]
    .map((item) => String(item || '').trim())
    .filter(Boolean);

  return [...new Set(values)];
}

function getOrderStatusTone(order) {
  const status = String(order?.status_label || order?.status || order?.payment_status || '')
    .trim()
    .toLowerCase();

  if (/deliver|complete|paid|success/.test(status)) {
    return {
      label: order?.status_label || 'Delivered',
      color: BrandPalette.success,
      backgroundColor: BrandPalette.successSoft,
    };
  }

  if (/refund/.test(status)) {
    return {
      label: order?.status_label || 'Refund in progress',
      color: BrandPalette.warning,
      backgroundColor: BrandPalette.warningSoft,
    };
  }

  if (/cancel|fail/.test(status)) {
    return {
      label: order?.status_label || 'Cancelled',
      color: BrandPalette.danger,
      backgroundColor: BrandPalette.dangerSoft,
    };
  }

  return {
    label: order?.status_label || 'Processing',
    color: BrandPalette.warning,
    backgroundColor: BrandPalette.warningSoft,
  };
}

function getOrderItemsLabel(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  const names = items
    .map((item) => String(item?.name || item?.product_name || '').trim())
    .filter(Boolean);

  if (names.length >= 2) return `${names[0]} +${names.length - 1} more`;
  if (names.length === 1) return names[0];

  const totalItems = Number(order?.item_count || items.length || 0);
  if (totalItems > 0) return `${totalItems} item${totalItems > 1 ? 's' : ''}`;

  return 'Order details available in history';
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

function ActionTile({ label, subtitle, icon, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.actionTile} onPress={onPress}>
      <View style={styles.actionTileIconWrap}>
        <Ionicons name={icon} size={22} color={BrandPalette.text} />
      </View>
      <Text style={styles.actionTileLabel}>{label}</Text>
      <Text style={styles.actionTileSubtitle}>{subtitle}</Text>
    </TouchableOpacity>
  );
}

function AccountLinkRow({ label, icon, onPress, isLast = false }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={[styles.accountLinkRow, !isLast && styles.accountLinkRowBorder]} onPress={onPress}>
      <View style={styles.accountLinkLeft}>
        <Ionicons name={icon} size={21} color={BrandPalette.text} />
        <Text style={styles.accountLinkLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={BrandPalette.subtle} />
    </TouchableOpacity>
  );
}

function SegmentButton({ label, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={[styles.segmentButton, active && styles.segmentButtonActive]} onPress={onPress}>
      <Text style={[styles.segmentButtonText, active && styles.segmentButtonTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function PastOrderCard({ order, onPressPrimary, onPressCard }) {
  const statusTone = getOrderStatusTone(order);
  const hasRefund = /refund/.test(String(order?.status_label || order?.status || '').toLowerCase());
  const imageUri = String(order?.vendor_image_url || '').trim();
  const primaryLabel = hasRefund ? 'Refund Details' : 'Reorder';

  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.orderCard} onPress={onPressCard}>
      <View style={styles.orderHeader}>
        <View style={styles.orderIdentityRow}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={styles.orderImage} />
          ) : (
            <View style={styles.orderImageFallback}>
              <Text style={styles.orderImageFallbackText}>{initials(order?.vendor_name || 'GB')}</Text>
            </View>
          )}

          <View style={styles.orderTitleWrap}>
            <Text numberOfLines={1} style={styles.orderVendorName}>
              {order?.vendor_name || 'Store'}
            </Text>
            <Text numberOfLines={1} style={styles.orderItemsLabel}>
              {getOrderItemsLabel(order)}
            </Text>
          </View>
        </View>

        <View style={[styles.orderStatusPill, { backgroundColor: statusTone.backgroundColor }]}>
          <Text style={[styles.orderStatusText, { color: statusTone.color }]}>{statusTone.label}</Text>
        </View>
      </View>

      <TouchableOpacity activeOpacity={0.94} style={styles.orderPrimaryButton} onPress={onPressPrimary}>
        <Text style={styles.orderPrimaryButtonText}>{primaryLabel}</Text>
      </TouchableOpacity>

      <Text style={styles.orderFooterText}>
        Ordered: {formatOrderDateTime(order?.created_at || order?.updated_at)} • Bill Total: {money(order?.total_amount || 0)}
      </Text>
    </TouchableOpacity>
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
    defaultAddress,
    orderHistory,
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
  const [localNotice, setLocalNotice] = useState('');
  const [accountPastOrderTab, setAccountPastOrderTab] = useState('food');
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
  const profileTitle = useMemo(() => getProfileTitle(profile, authEmail), [profile, authEmail]);
  const profileMeta = useMemo(() => getProfileMeta(profile, authEmail), [profile, authEmail]);

  const addressCount = Array.isArray(addresses) ? addresses.length : 0;
  const refundCount = useMemo(
    () =>
      (Array.isArray(orderHistory) ? orderHistory : []).filter((order) =>
        /refund/.test(String(order?.status_label || order?.status || '').toLowerCase())
      ).length,
    [orderHistory]
  );

  const filteredOrders = useMemo(() => {
    const orders = Array.isArray(orderHistory) ? orderHistory : [];
    return orders.filter((order) => mapLegacyService(order?.service) === accountPastOrderTab);
  }, [accountPastOrderTab, orderHistory]);

  const highlightedAddress = useMemo(() => {
    if (defaultAddress?.label || defaultAddress?.line1) {
      return [defaultAddress?.label, defaultAddress?.line1].filter(Boolean).join(' · ');
    }

    return addressCount ? `${addressCount} saved addresses` : 'No saved addresses yet';
  }, [addressCount, defaultAddress]);

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

  const handleShortcutPress = async (key) => {
    if (key === 'saved_addresses') {
      await loadAddresses().catch(() => {});
      setLocalNotice(addressCount ? `${addressCount} saved address${addressCount > 1 ? 'es are' : ' is'} available.` : 'No saved addresses yet.');
      return;
    }

    if (key === 'refunds') {
      setLocalNotice(refundCount ? `${refundCount} refund update${refundCount > 1 ? 's are' : ' is'} available in your order history.` : 'No refund updates at the moment.');
      return;
    }

    if (key === 'payment_modes') {
      setLocalNotice('Payment Modes is ready for UPI, cards and wallet integration.');
      return;
    }

    if (key === 'wallet') {
      setLocalNotice('My Wallet is ready for balance, credits and cashback integration.');
    }
  };

  const handleLinkPress = (key) => {
    if (key === 'offers') {
      setLocalNotice('Offers can be connected to the promotions flow next.');
      return;
    }

    if (key === 'help') {
      setLocalNotice('Help & support can be connected to support tickets or chat next.');
      return;
    }

    if (key === 'wishlist') {
      setLocalNotice('My Wishlist is ready for saved products and stores.');
      return;
    }

    if (key === 'favourites') {
      setLocalNotice('Favourites can show your saved stores and frequent picks.');
      return;
    }

    if (key === 'statements') {
      setLocalNotice('Account Statements can list invoices, payments and credits.');
      return;
    }

    if (key === 'corporate_rewards') {
      setLocalNotice('Corporate Rewards is ready for business perks and credits.');
      return;
    }

    if (key === 'student_rewards') {
      setLocalNotice('Student Rewards is ready for student-only offers.');
      return;
    }

    if (key === 'partner_rewards') {
      setLocalNotice('Partner Rewards is ready for brand and merchant collaborations.');
      return;
    }

    if (key === 'contact_pref') {
      setLocalNotice('Allow stores to contact you can be connected to communication preferences.');
    }
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

              <Text style={styles.legalText}>By tapping in, you agree to our terms of service and privacy policy.</Text>

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
            <View style={styles.accountHeroCard}>
              <View style={styles.accountHeroTopRow}>
                <View style={styles.avatarWrap}>
                  <Text style={styles.avatarText}>{initials(profileTitle || authEmail || 'GB')}</Text>
                </View>

                <TouchableOpacity activeOpacity={0.94} style={styles.helpChip} onPress={() => handleLinkPress('help')}>
                  <Text style={styles.helpChipText}>Help</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.profileTitle}>{profileTitle}</Text>
              {profileMeta.map((item) => (
                <Text key={item} style={styles.profileMetaLine}>
                  {item}
                </Text>
              ))}

              <View style={styles.memberRow}>
                <Ionicons name="sparkles-outline" size={14} color={BrandPalette.primary} />
                <Text style={styles.memberRowText}>{memberSince !== '—' ? `Member since ${memberSince}` : 'GrabBasket member'}</Text>
              </View>
            </View>

            <View style={styles.accountSummaryCard}>
              <View style={styles.accountSummaryRow}>
                <View style={styles.accountSummaryItem}>
                  <Text style={styles.accountSummaryValue}>{Array.isArray(orderHistory) ? orderHistory.length : 0}</Text>
                  <Text style={styles.accountSummaryLabel}>Orders</Text>
                </View>
                <View style={styles.accountSummaryDivider} />
                <View style={styles.accountSummaryItem}>
                  <Text style={styles.accountSummaryValue}>{addressCount}</Text>
                  <Text style={styles.accountSummaryLabel}>Saved addresses</Text>
                </View>
              </View>
              <Text style={styles.accountSummaryFootnote}>{highlightedAddress}</Text>
            </View>

            {localNotice ? <InlineNoticeCard title="Updated" message={localNotice} onDismiss={() => setLocalNotice('')} /> : null}
            {inlineErrors.orders ? <InlineErrorCard title="Orders issue" message={inlineErrors.orders} /> : null}
            {inlineErrors.addresses ? <InlineErrorCard title="Address issue" message={inlineErrors.addresses} /> : null}

            <View style={styles.actionGrid}>
              {ACCOUNT_SHORTCUTS.map((item) => (
                <ActionTile key={item.key} label={item.label} subtitle={item.subtitle} icon={item.icon} onPress={() => handleShortcutPress(item.key)} />
              ))}
            </View>

            <View style={styles.linksCard}>
              {ACCOUNT_LINKS.map((item, index) => (
                <AccountLinkRow
                  key={item.key}
                  label={item.label}
                  icon={item.icon}
                  onPress={() => handleLinkPress(item.key)}
                  isLast={index === ACCOUNT_LINKS.length - 1}
                />
              ))}
            </View>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionTitle}>Past orders</Text>

              <View style={styles.segmentWrap}>
                <SegmentButton label="Food" active={accountPastOrderTab === 'food'} onPress={() => setAccountPastOrderTab('food')} />
                <SegmentButton label="Warehouse" active={accountPastOrderTab === 'warehouse'} onPress={() => setAccountPastOrderTab('warehouse')} />
              </View>

              {ordersLoading ? (
                <View style={styles.cardSurface}>
                  <ActivityIndicator color={BrandPalette.primary} />
                </View>
              ) : filteredOrders.length ? (
                <View style={styles.orderList}>
                  {filteredOrders.slice(0, 4).map((order) => (
                    <PastOrderCard
                      key={order.id}
                      order={order}
                      onPressCard={() => router.push('/(tabs)/reorder')}
                      onPressPrimary={() => {
                        if (/refund/.test(String(order?.status_label || order?.status || '').toLowerCase())) {
                          setLocalNotice(`Refund details for order #${order.id} can be opened next.`);
                          return;
                        }

                        router.push('/(tabs)/reorder');
                      }}
                    />
                  ))}
                </View>
              ) : (
                <View style={styles.cardSurface}>
                  <Text style={styles.helperText}>
                    {accountPastOrderTab === 'warehouse'
                      ? 'Your warehouse order history will appear here.'
                      : 'Your food order history will appear here.'}
                  </Text>
                </View>
              )}
            </View>

            <View style={styles.footerMetaCard}>
              <Text style={styles.footerMetaText}>Device ID: {deviceId || 'Unavailable'}</Text>
              <Text style={styles.footerMetaSubtext}>{versionText}</Text>
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
    backgroundColor: BrandPalette.white,
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
    paddingTop: 12,
    gap: 16,
    backgroundColor: '#FAFAFA',
  },
  accountHeroCard: {
    borderRadius: 28,
    backgroundColor: BrandPalette.white,
    padding: 18,
    borderWidth: 1,
    borderColor: '#ECECEC',
    ...createShadow(0.06, 10, 4),
  },
  accountHeroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  helpChip: {
    minHeight: 38,
    paddingHorizontal: 16,
    borderRadius: 18,
    backgroundColor: '#FFF1E8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  helpChipText: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: '#E66A00',
  },
  avatarWrap: {
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 24,
    lineHeight: 28,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  profileTitle: {
    fontSize: 26,
    lineHeight: 30,
    fontWeight: '900',
    color: '#1C2240',
  },
  profileMetaLine: {
    marginTop: 4,
    fontSize: 16,
    lineHeight: 20,
    color: '#666A78',
  },
  memberRow: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  memberRowText: {
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.textMuted,
    fontWeight: '700',
  },
  accountSummaryCard: {
    borderRadius: 24,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    paddingHorizontal: 16,
    paddingVertical: 14,
    ...createShadow(0.04, 8, 3),
  },
  accountSummaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  accountSummaryItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  accountSummaryDivider: {
    width: 1,
    height: 38,
    backgroundColor: '#ECECEC',
  },
  accountSummaryValue: {
    fontSize: 22,
    lineHeight: 26,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  accountSummaryLabel: {
    fontSize: 13,
    lineHeight: 16,
    color: BrandPalette.textMuted,
  },
  accountSummaryFootnote: {
    marginTop: 12,
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  actionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  actionTile: {
    width: '48.2%',
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    padding: 16,
    minHeight: 126,
    ...createShadow(0.04, 8, 3),
  },
  actionTileIconWrap: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#F7F7F7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionTileLabel: {
    marginTop: 16,
    fontSize: 17,
    lineHeight: 20,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  actionTileSubtitle: {
    marginTop: 6,
    fontSize: 12,
    lineHeight: 16,
    color: BrandPalette.textMuted,
  },
  linksCard: {
    borderRadius: 26,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    paddingHorizontal: 16,
    ...createShadow(0.04, 8, 3),
  },
  accountLinkRow: {
    minHeight: 62,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountLinkRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: '#F0F0F0',
  },
  accountLinkLeft: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    paddingRight: 12,
  },
  accountLinkLabel: {
    flex: 1,
    fontSize: 16,
    lineHeight: 20,
    color: '#5D606D',
    fontWeight: '600',
  },
  sectionBlock: {
    gap: 12,
  },
  sectionTitle: {
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  segmentWrap: {
    flexDirection: 'row',
    backgroundColor: '#EEEEF4',
    borderRadius: 22,
    padding: 4,
  },
  segmentButton: {
    flex: 1,
    minHeight: 46,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  segmentButtonActive: {
    backgroundColor: BrandPalette.text,
  },
  segmentButtonText: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '800',
    color: '#4F5360',
  },
  segmentButtonTextActive: {
    color: BrandPalette.white,
  },
  cardSurface: {
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    padding: 16,
    minHeight: 76,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderList: {
    gap: 12,
  },
  orderCard: {
    borderRadius: 24,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    padding: 14,
    ...createShadow(0.04, 8, 3),
  },
  orderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
  },
  orderIdentityRow: {
    flex: 1,
    flexDirection: 'row',
    gap: 12,
  },
  orderImage: {
    width: 58,
    height: 58,
    borderRadius: 14,
    backgroundColor: '#F4F4F4',
  },
  orderImageFallback: {
    width: 58,
    height: 58,
    borderRadius: 14,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderImageFallbackText: {
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  orderTitleWrap: {
    flex: 1,
    justifyContent: 'center',
  },
  orderVendorName: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  orderItemsLabel: {
    marginTop: 4,
    fontSize: 14,
    lineHeight: 18,
    color: '#7B7F8B',
  },
  orderStatusPill: {
    alignSelf: 'flex-start',
    borderRadius: 14,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  orderStatusText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  orderPrimaryButton: {
    marginTop: 16,
    minHeight: 50,
    borderRadius: 16,
    backgroundColor: '#FFF1E8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderPrimaryButtonText: {
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    color: '#E66A00',
  },
  orderFooterText: {
    marginTop: 14,
    fontSize: 13,
    lineHeight: 17,
    color: '#7B7F8B',
  },
  helperText: {
    fontSize: 14,
    lineHeight: 18,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  footerMetaCard: {
    borderRadius: 20,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: '#ECECEC',
    paddingHorizontal: 16,
    paddingVertical: 14,
    gap: 4,
  },
  footerMetaText: {
    fontSize: 13,
    lineHeight: 17,
    color: BrandPalette.textMuted,
  },
  footerMetaSubtext: {
    fontSize: 12,
    lineHeight: 16,
    color: BrandPalette.subtle,
  },
  logoutButton: {
    marginTop: 2,
    marginBottom: 12,
  },
});