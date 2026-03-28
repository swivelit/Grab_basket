import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
import * as ExpoLinking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';

import { useGrabBasket } from '../../../App';
import InlineConfirmCard from '../../components/inline-confirm-card';
import InlineErrorCard from '../../components/inline-error-card';
import InlineNoticeCard from '../../components/inline-notice-card';
import { getErrorMessage, requestJson } from '../../lib/api-client';
import LiveRouteIntelligenceCard from '../../components/live-route-intelligence-card';
import { APP_ENV } from '../../config';
import { captureEvent } from '../../lib/telemetry';
import { FEATURE_FLAGS } from '../../constants/feature-flags';
import { ANALYTICS_EVENTS, ANALYTICS_TAXONOMY_VERSION } from '../../constants/analytics-taxonomy';

WebBrowser.maybeCompleteAuthSession();

const COLORS = {
  bg: '#FFF9F3',
  card: '#FFFFFF',
  cardAlt: '#FFF6EC',
  line: '#F4E5D6',
  border: '#EFD7BF',
  text: '#2F241C',
  muted: '#7A6758',
  subtle: '#A18B79',
  brand: '#D97651',
  brandSoft: '#FFF0E7',
  success: '#1F8F5F',
  successSoft: '#EAF8F0',
  info: '#2C69C9',
  infoSoft: '#EBF2FF',
  warning: '#C57B12',
  warningSoft: '#FFF6DE',
  danger: '#D45454',
  dangerSoft: '#FDECEC',
  black: '#241A14',
  shadow: 'rgba(36, 26, 20, 0.08)',
};

const ORDER_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'payment_pending', label: 'Payment pending' },
  { key: 'food', label: 'Food' },
  { key: 'warehouse', label: 'Instamart' },
  { key: 'eatout', label: 'Dineout' },
  { key: 'scenes', label: 'Scenes' },
];

const SUPPORT_ACTIONS = [
  {
    key: 'chat',
    label: 'Help chat',
    icon: 'chatbubble-ellipses-outline',
    copy: 'Talk to support for missing items, delays, or app issues.',
  },
  {
    key: 'refund',
    label: 'Request refund',
    icon: 'cash-outline',
    copy: 'Start a refund claim with order context already attached.',
  },
  {
    key: 'faq',
    label: 'Help center',
    icon: 'help-circle-outline',
    copy: 'Read policies for cancellation, replacement, and payouts.',
  },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

async function authRequest(path, token, { method = 'GET', body } = {}) {
  return requestJson(path, {
    method,
    token,
    body: typeof body === 'string' ? JSON.parse(body) : body,
  });
}

async function publicRequest(path) {
  return requestJson(path);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

function normalizeService(value = '') {
  const normalized = normalizeText(value);
  if (normalized === 'instamart') return 'warehouse';
  if (normalized === 'dineout') return 'eatout';
  return normalized || 'food';
}

function normalizePaymentMethod(value = '') {
  return String(value || '').trim().toUpperCase();
}

function normalizeGatewayStatus(value) {
  return String(value || '').trim().toLowerCase();
}

function isFinalOrderStatus(value = '') {
  return ['DELIVERED', 'CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER'].includes(
    String(value || '').toUpperCase()
  );
}

function canRetryGatewayPayment(order) {
  const method = normalizePaymentMethod(order?.payment_method || order?.paymentMethod);
  const paymentStatus = String(order?.payment_status || order?.paymentStatus || '').toUpperCase();
  const status = String(order?.status || '').toUpperCase();

  if (!['UPI', 'CARD'].includes(method)) return false;
  if (paymentStatus === 'PAID') return false;
  if (isFinalOrderStatus(status)) return false;

  return (
    ['PAYMENT_PENDING', 'CREATED', 'ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP'].includes(status) ||
    ['PENDING_VERIFICATION', 'FAILED', 'PENDING'].includes(paymentStatus)
  );
}

async function pollGatewayStatus(orderId, authToken, { attempts = 4, delayMs = 1500 } = {}) {
  let last = null;

  for (let index = 0; index < attempts; index += 1) {
    last = await authRequest(`/payments/${orderId}/status`, authToken, { method: 'GET' });

    const checkoutStatus = normalizeGatewayStatus(last?.checkout_status);
    const paymentStatus = String(last?.payment_status || '').toUpperCase();

    if (paymentStatus === 'PAID') return last;
    if (paymentStatus === 'FAILED') return last;
    if (['cancelled', 'expired'].includes(checkoutStatus)) return last;

    if (index < attempts - 1) {
      await sleep(delayMs);
    }
  }

  return last;
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

function formatAddress(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city, address.pincode].filter(Boolean).join(' · ');
}

function formatPrettyStatus(value = '') {
  return (
    String(value || '')
      .replace(/_/g, ' ')
      .toLowerCase()
      .replace(/\b\w/g, (char) => char.toUpperCase()) || 'Created'
  );
}

function getStatusTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value.includes('DELIVERED')) {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle-outline' };
  }

  if (value.includes('CANCEL') || value.includes('REJECT')) {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'close-circle-outline' };
  }

  if (
    value.includes('PICKED') ||
    value.includes('READY') ||
    value.includes('ASSIGNED') ||
    value.includes('PAYMENT')
  ) {
    return { bg: COLORS.infoSoft, text: COLORS.info, icon: 'bicycle-outline' };
  }

  return { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'time-outline' };
}

function normalizeCoordinate(lat, lng) {
  const latitude = Number(lat);
  const longitude = Number(lng);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null;
  }

  return { latitude, longitude };
}

function hasCoordinate(point) {
  return Boolean(
    point &&
      Number.isFinite(Number(point.latitude)) &&
      Number.isFinite(Number(point.longitude))
  );
}

function getOrderItems(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  return items.map((item) => ({
    name: item?.name || item?.name_snapshot || 'Item',
    qty: Number(item?.qty || 1),
    price: Number(item?.price || item?.price_snapshot || 0),
  }));
}

function getOrderSearchText(order) {
  return [
    `order ${order?.id ?? ''}`,
    order?.vendorName,
    order?.location,
    order?.status,
    order?.service,
    order?.paymentMethod,
    order?.paymentStatus,
    getOrderItems(order)
      .map((item) => `${item.qty} x ${item.name}`)
      .join(' '),
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function getDetailOrderSearchText(order, vendor) {
  return [
    `order ${order?.id ?? ''}`,
    vendor?.name,
    vendor?.address,
    order?.status,
    order?.payment_method,
    order?.payment_status,
    getOrderItems(order)
      .map((item) => `${item.qty} x ${item.name}`)
      .join(' '),
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
}

function matchesOrderFilter(order, filterKey) {
  if (filterKey === 'all') return true;
  if (filterKey === 'payment_pending') return canRetryGatewayPayment(order);
  return normalizeService(order?.service) === filterKey;
}

function buildSupportQueue(orders = []) {
  return (Array.isArray(orders) ? orders : [])
    .map((order) => {
      const status = String(order?.status || '').toUpperCase();
      const payment = String(order?.payment_status || '').toUpperCase();
      const isRefundEligible = status === 'DELIVERED' || payment === 'PAID';

      return {
        id: order?.id,
        service: normalizeService(order?.service),
        status: formatPrettyStatus(order?.status),
        payment: formatPrettyStatus(order?.payment_status),
        isRefundEligible,
      };
    })
    .filter((order) => order?.id)
    .slice(0, 4);
}

function SectionCard({ title, subtitle, right, children }) {
  return (
    <View style={styles.sectionCard}>
      {title || subtitle || right ? (
        <View style={styles.sectionHeader}>
          <View style={{ flex: 1 }}>
            {title ? <Text style={styles.sectionTitle}>{title}</Text> : null}
            {subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}
          </View>
          {right ? <View>{right}</View> : null}
        </View>
      ) : null}
      {children}
    </View>
  );
}

function Field({
  label,
  value,
  onChangeText,
  placeholder,
  autoCapitalize = 'none',
  secureTextEntry = false,
  keyboardType = 'default',
  multiline = false,
}) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.subtle}
        autoCapitalize={autoCapitalize}
        secureTextEntry={secureTextEntry}
        keyboardType={keyboardType}
        multiline={multiline}
        style={[styles.input, multiline && styles.inputMultiline]}
      />
    </View>
  );
}

function StatusPill({ status }) {
  const tone = getStatusTone(status);

  return (
    <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
      <Ionicons name={tone.icon} size={14} color={tone.text} />
      <Text style={[styles.statusPillText, { color: tone.text }]}>
        {formatPrettyStatus(status)}
      </Text>
    </View>
  );
}

function StatCard({ icon, label, value, tone = 'brand' }) {
  const palette =
    tone === 'success'
      ? { bg: COLORS.successSoft, color: COLORS.success }
      : tone === 'info'
        ? { bg: COLORS.infoSoft, color: COLORS.info }
        : tone === 'danger'
          ? { bg: COLORS.dangerSoft, color: COLORS.danger }
          : { bg: COLORS.brandSoft, color: COLORS.brand };

  return (
    <View style={styles.statCard}>
      <View style={[styles.statIconWrap, { backgroundColor: palette.bg }]}>
        <Ionicons name={icon} size={16} color={palette.color} />
      </View>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function AuthPanel({
  mode,
  setMode,
  email,
  setEmail,
  password,
  setPassword,
  loading,
  onSubmit,
  challengeMode,
  setChallengeMode,
  challengeCode,
  setChallengeCode,
  challengeLoading,
  challengeStatus,
  challengeError,
  onStartChallenge,
  onVerifyChallenge,
  onResendChallenge,
}) {
  const isRegister = mode === 'register';

  return (
    <SectionCard
      title="Welcome to Grab Basket"
      subtitle="Sign in to place orders, save addresses, and track deliveries live.">
      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        placeholder="name@example.com"
        keyboardType="email-address"
      />
      <Field
        label="Password"
        value={password}
        onChangeText={setPassword}
        placeholder="Minimum 6 characters"
        secureTextEntry
      />

      <TouchableOpacity
        activeOpacity={0.92}
        style={styles.primaryButton}
        disabled={loading}
        onPress={onSubmit}>
        {loading ? <ActivityIndicator color="#FFFFFF" /> : null}
        <Text style={styles.primaryButtonText}>
          {isRegister ? 'Create account' : 'Sign in'}
        </Text>
      </TouchableOpacity>

      <TouchableOpacity
        activeOpacity={0.92}
        style={styles.secondaryButton}
        onPress={() => setMode(isRegister ? 'login' : 'register')}>
        <Ionicons
          name={isRegister ? 'log-in-outline' : 'person-add-outline'}
          size={16}
          color={COLORS.text}
        />
        <Text style={styles.secondaryButtonText}>
          {isRegister ? 'Already have an account? Sign in' : 'New here? Create account'}
        </Text>
      </TouchableOpacity>

      <View style={styles.authChallengeWrap}>
        <Text style={styles.sectionSubtitle}>Account security</Text>
        <View style={styles.filterRow}>
          {['EMAIL_VERIFY', 'PASSWORD_RESET'].map((value) => (
            <TouchableOpacity
              key={value}
              style={[styles.filterChip, challengeMode === value && styles.filterChipActive]}
              onPress={() => setChallengeMode(value)}>
              <Text style={[styles.filterChipText, challengeMode === value && styles.filterChipTextActive]}>
                {value === 'EMAIL_VERIFY' ? 'Email verify' : 'Reset password'}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
        <Field
          label="Challenge code"
          value={challengeCode}
          onChangeText={setChallengeCode}
          placeholder="Enter code from email"
          keyboardType="number-pad"
        />
        {challengeStatus ? <InlineNoticeCard title="Challenge" message={challengeStatus} /> : null}
        {challengeError ? <InlineErrorCard title="Challenge error" message={challengeError} /> : null}
        <View style={styles.inlineActionsRow}>
          <TouchableOpacity style={styles.miniActionButton} disabled={challengeLoading} onPress={onStartChallenge}>
            <Text style={styles.miniActionButtonText}>{challengeLoading ? 'Starting…' : 'Send code'}</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.miniActionButton} disabled={challengeLoading} onPress={onResendChallenge}>
            <Text style={styles.miniActionButtonText}>Resend</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.miniActionButton} disabled={challengeLoading} onPress={onVerifyChallenge}>
            <Text style={styles.miniActionButtonText}>{challengeLoading ? 'Checking…' : 'Verify'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </SectionCard>
  );
}

function AddressCard({ address, isDefault, onSetDefault }) {
  return (
    <View style={styles.addressCard}>
      <View style={styles.addressCardTop}>
        <View style={styles.addressBadge}>
          <Ionicons name="location-outline" size={14} color={COLORS.brand} />
          <Text style={styles.addressBadgeText}>{address?.label || 'Address'}</Text>
        </View>

        {isDefault ? (
          <View style={styles.defaultChip}>
            <Text style={styles.defaultChipText}>Default</Text>
          </View>
        ) : (
          <TouchableOpacity activeOpacity={0.92} onPress={onSetDefault}>
            <Text style={styles.linkText}>Set default</Text>
          </TouchableOpacity>
        )}
      </View>

      <Text style={styles.addressLine}>{formatAddress(address)}</Text>
      <Text style={styles.addressMeta}>
        Lat {Number(address?.lat || 0).toFixed(4)} · Lng {Number(address?.lng || 0).toFixed(4)}
      </Text>
    </View>
  );
}

function OrderCard({ order, onPress }) {
  const tone = getStatusTone(order?.status);
  const itemsSummary = getOrderItems(order)
    .slice(0, 2)
    .map((item) => `${item.qty} x ${item.name}`)
    .join(' · ');

  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.orderCard} onPress={onPress}>
      <View style={styles.orderCardTop}>
        <View style={styles.orderAvatar}>
          <Text style={styles.orderAvatarText}>{initials(order?.vendorName)}</Text>
        </View>

        <View style={{ flex: 1 }}>
          <Text style={styles.orderVendorName} numberOfLines={1}>
            {order?.vendorName || 'Vendor'}
          </Text>
          <Text style={styles.orderVendorMeta} numberOfLines={1}>
            {order?.location || 'Order location'}
          </Text>
        </View>

        <View style={[styles.orderStatusBadge, { backgroundColor: tone.bg }]}>
          <Text style={[styles.orderStatusText, { color: tone.text }]}>
            {formatPrettyStatus(order?.status)}
          </Text>
        </View>
      </View>

      <Text style={styles.orderSummary} numberOfLines={2}>
        {itemsSummary || 'Order items will appear here'}
      </Text>

      <View style={styles.orderMetaRow}>
        <Text style={styles.orderMeta}>{order?.orderedAt || 'Recently placed'}</Text>
        <Text style={styles.orderMeta}>{money(order?.total)}</Text>
      </View>

      <View style={styles.orderFooterRow}>
        <View style={styles.orderFooterBadges}>
          <View style={styles.orderHintPill}>
            <Ionicons name="navigate-outline" size={14} color={COLORS.info} />
            <Text style={styles.orderHintText}>Track order</Text>
          </View>

          {canRetryGatewayPayment(order) ? (
            <View style={styles.orderPaymentRetryPill}>
              <Ionicons name="card-outline" size={14} color={COLORS.brand} />
              <Text style={styles.orderPaymentRetryPillText}>Complete payment</Text>
            </View>
          ) : null}
        </View>

        <Ionicons name="chevron-forward" size={18} color={COLORS.subtle} />
      </View>
    </TouchableOpacity>
  );
}

function Timeline({ events = [] }) {
  if (!events.length) {
    return (
      <View style={styles.emptyPanel}>
        <Text style={styles.emptyPanelTitle}>No timeline events yet</Text>
        <Text style={styles.emptyPanelSubtitle}>Order state changes will appear here.</Text>
      </View>
    );
  }

  return (
    <View style={styles.timelineWrap}>
      {events.map((event, index) => {
        const tone = getStatusTone(event?.status);
        const isLast = index === events.length - 1;

        return (
          <View key={`${event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.text }]} />
              {!isLast ? <View style={styles.timelineLine} /> : null}
            </View>

            <View style={{ flex: 1 }}>
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

function TrackingMapCard({ order, vendor, partnerLocation }) {
  const pickupPoint = normalizeCoordinate(vendor?.lat, vendor?.lng);
  const dropPoint = normalizeCoordinate(order?.delivery_lat, order?.delivery_lng);
  const riderPoint = normalizeCoordinate(partnerLocation?.lat, partnerLocation?.lng);

  return (
    <LiveRouteIntelligenceCard
      orderStatus={order?.status}
      pickupPoint={pickupPoint}
      dropPoint={dropPoint}
      riderPoint={riderPoint}
      pickupTitle="Pickup"
      pickupDescription={vendor?.name || 'Pickup point'}
      dropTitle="Drop"
      dropDescription="Delivery address"
      riderTitle="Rider"
      riderDescription={partnerLocation ? `Updated ${formatDateTime(partnerLocation?.created_at)}` : 'Waiting for rider location'}
      emptyTitle="Live map unavailable"
      emptySubtitle="Add vendor and delivery coordinates to see pickup, rider, and drop with live route ETA in the customer app."
      webTitle="Map preview is only available on iOS and Android."
      webSubtitle="The customer app can still open the active stop in the installed maps application."
      routeUnavailableMessage="Pickup or drop coordinates are not available for this order yet."
    />
  );
}

function OrderDetailsSheet({
  visible,
  onClose,
  order,
  detail,
  vendor,
  loading,
  actionLoading,
  retryLoading,
  onRefresh,
  onCancel,
  onRetryPayment,
  onOpenStore,
  realtimeEvents = [],
}) {
  const detailOrder = detail?.order || null;
  const status = detailOrder?.status || order?.status || 'CREATED';
  const partnerLocation = detail?.partner_latest_location || null;
  const events = useMemo(() => {
    const base = Array.isArray(detailOrder?.events) ? detailOrder.events : [];
    const seen = new Set(base.map((item) => Number(item?.id || 0)));
    const extra = (Array.isArray(realtimeEvents) ? realtimeEvents : []).filter(
      (item) => !seen.has(Number(item?.id || 0))
    );
    return [...base, ...extra].sort((a, b) => Number(a?.id || 0) - Number(b?.id || 0));
  }, [detailOrder?.events, realtimeEvents]);
  const items = getOrderItems(detailOrder || order);

  const canCancel = !['PICKED_UP', 'DELIVERED', 'CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER'].includes(
    String(status || '').toUpperCase()
  );
  const canRetryPayment = canRetryGatewayPayment(detailOrder || order);

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.sheetOverlay}>
        <View style={styles.sheetCard}>
          <View style={styles.sheetHandle} />

          <View style={styles.sheetHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sheetTitle}>Order #{order?.id}</Text>
              <Text style={styles.sheetSubtitle}>
                {order?.vendorName || vendor?.name || 'Vendor order'}
              </Text>
            </View>

            <TouchableOpacity activeOpacity={0.92} style={styles.sheetCloseButton} onPress={onClose}>
              <Ionicons name="close" size={20} color={COLORS.text} />
            </TouchableOpacity>
          </View>

          {loading ? (
            <View style={styles.sheetLoaderWrap}>
              <ActivityIndicator color={COLORS.brand} />
              <Text style={styles.sheetLoaderText}>Loading live tracking…</Text>
            </View>
          ) : (
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.sheetBody}>
              <View style={styles.sheetHeroCard}>
                <StatusPill status={status} />
                <Text style={styles.sheetHeroAmount}>{money(detailOrder?.total_amount || order?.total)}</Text>
                <Text style={styles.sheetHeroMeta}>
                  Payment: {detailOrder?.payment_method || order?.paymentMethod || 'COD'} ·{' '}
                  {detailOrder?.payment_status || order?.paymentStatus || 'Pending'}
                </Text>
              </View>

              {canRetryPayment ? (
                <SectionCard
                  title="Complete pending payment"
                  subtitle="Reopen the same secure Razorpay checkout from your account without going back to the cart.">
                  <View style={styles.paymentRetryBanner}>
                    <View style={styles.paymentRetryIconWrap}>
                      <Ionicons name="shield-checkmark-outline" size={18} color={COLORS.brand} />
                    </View>

                    <View style={{ flex: 1 }}>
                      <Text style={styles.paymentRetryTitle}>Hosted secure checkout</Text>
                      <Text style={styles.paymentRetrySubtitle}>
                        Your app will reopen the Razorpay payment page and wait for backend callback + webhook confirmation before marking this order paid.
                      </Text>
                    </View>
                  </View>

                  <TouchableOpacity
                    activeOpacity={0.92}
                    style={styles.primaryButton}
                    disabled={retryLoading}
                    onPress={onRetryPayment}>
                    {retryLoading ? (
                      <ActivityIndicator color="#FFFFFF" />
                    ) : (
                      <Ionicons name="card-outline" size={16} color="#FFFFFF" />
                    )}
                    <Text style={styles.primaryButtonText}>Retry secure payment</Text>
                  </TouchableOpacity>
                </SectionCard>
              ) : null}

              <SectionCard
                title="Live map"
                subtitle="Pickup → rider → drop tracking from the customer app"
                right={
                  <TouchableOpacity activeOpacity={0.92} onPress={onRefresh}>
                    <Text style={styles.linkText}>Refresh</Text>
                  </TouchableOpacity>
                }>
                <TrackingMapCard
                  order={detailOrder || order}
                  vendor={vendor}
                  partnerLocation={partnerLocation}
                />
              </SectionCard>

              <SectionCard title="Live tracking" subtitle="Latest delivery updates from the backend">
                <View style={styles.detailGrid}>
                  <View style={styles.detailTile}>
                    <Text style={styles.detailLabel}>Pickup</Text>
                    <Text style={styles.detailValue}>{vendor?.name || order?.vendorName || 'Vendor'}</Text>
                    <Text style={styles.detailHint}>
                      {vendor?.address || order?.location || 'Pickup address unavailable'}
                    </Text>
                  </View>

                  <View style={styles.detailTile}>
                    <Text style={styles.detailLabel}>Drop</Text>
                    <Text style={styles.detailValue}>Delivery address</Text>
                    <Text style={styles.detailHint}>
                      {hasCoordinate(
                        normalizeCoordinate(detailOrder?.delivery_lat, detailOrder?.delivery_lng)
                      )
                        ? `Lat ${Number(detailOrder?.delivery_lat).toFixed(4)} · Lng ${Number(detailOrder?.delivery_lng).toFixed(4)}`
                        : 'Drop coordinates unavailable'}
                    </Text>
                  </View>

                  <View style={styles.detailTile}>
                    <Text style={styles.detailLabel}>Rider</Text>
                    <Text style={styles.detailValue}>
                      {partnerLocation ? 'Live location available' : 'Waiting for rider location'}
                    </Text>
                    <Text style={styles.detailHint}>
                      {partnerLocation
                        ? `Updated ${formatDateTime(partnerLocation.created_at)}`
                        : 'Background delivery tracking has not posted a live point yet.'}
                    </Text>
                  </View>

                  <View style={styles.detailTile}>
                    <Text style={styles.detailLabel}>Server payload</Text>
                    <Text style={styles.detailValue}>Tracking API</Text>
                    <Text style={styles.detailHint} numberOfLines={2}>
                      {getDetailOrderSearchText(detailOrder || order, vendor) || 'Order tracking detail ready'}
                    </Text>
                  </View>
                </View>
              </SectionCard>

              <SectionCard title="Items" subtitle={`${items.length || 0} line items`}>
                {items.length ? (
                  items.map((item, index) => (
                    <View key={`${item.name}-${index}`} style={styles.itemRow}>
                      <View style={styles.itemQtyBubble}>
                        <Text style={styles.itemQtyBubbleText}>{item.qty}</Text>
                      </View>

                      <View style={{ flex: 1 }}>
                        <Text style={styles.itemName}>{item.name}</Text>
                        <Text style={styles.itemMeta}>Qty {item.qty}</Text>
                      </View>

                      <Text style={styles.itemPrice}>
                        {item.price ? money(item.price * item.qty) : '—'}
                      </Text>
                    </View>
                  ))
                ) : (
                  <View style={styles.emptyPanel}>
                    <Text style={styles.emptyPanelTitle}>No items found</Text>
                  </View>
                )}
              </SectionCard>

              <SectionCard title="Order timeline" subtitle="Order updates + assignment notifications land here too">
                <Timeline events={events} />
              </SectionCard>

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
                    {actionLoading ? <ActivityIndicator color="#FFFFFF" /> : null}
                    <Text style={styles.primaryButtonText}>Cancel order</Text>
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

  const {
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
    startAuthChallenge,
    verifyAuthChallenge,
    addresses,
    addressesLoading,
    defaultAddress,
    createAddress,
    setDefaultAddress,
    loadOrders,
    loadAddresses,
    ordersLoading,
    subscribeOrderTimeline,
    timelineEventsByOrder,
  } = useGrabBasket();

  const [authMode, setAuthMode] = useState('login');
  const [email, setEmail] = useState(String(authEmail || ''));
  const [password, setPassword] = useState('');
  const [challengeMode, setChallengeMode] = useState('EMAIL_VERIFY');
  const [challengeCode, setChallengeCode] = useState('');
  const [challengeId, setChallengeId] = useState(0);
  const [challengeLoading, setChallengeLoading] = useState(false);
  const [challengeStatus, setChallengeStatus] = useState('');
  const [challengeError, setChallengeError] = useState('');

  const [label, setLabel] = useState('Home');
  const [line1, setLine1] = useState('');
  const [city, setCity] = useState('');
  const [pincode, setPincode] = useState('');
  const [lat, setLat] = useState('12.9716');
  const [lng, setLng] = useState('77.5946');
  const [savingAddress, setSavingAddress] = useState(false);

  const [search, setSearch] = useState('');
  const [filterKey, setFilterKey] = useState('all');
  const [refreshing, setRefreshing] = useState(false);

  const [selectedOrder, setSelectedOrder] = useState(null);
  const [orderDetail, setOrderDetail] = useState(null);
  const [orderDetailVendor, setOrderDetailVendor] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailActionLoading, setDetailActionLoading] = useState(false);
  const [retryPaymentLoading, setRetryPaymentLoading] = useState(false);
  const [inlineError, setInlineError] = useState('');
  const [inlineNotice, setInlineNotice] = useState(null);
  const [confirmState, setConfirmState] = useState(null);
  const realtimeEvents = useMemo(() => {
    const orderId = Number(selectedOrder?.id || 0);
    if (!orderId) return [];
    const rows = timelineEventsByOrder?.[orderId];
    return Array.isArray(rows) ? rows : [];
  }, [selectedOrder?.id, timelineEventsByOrder]);

  useEffect(() => {
    setEmail(String(authEmail || ''));
  }, [authEmail]);

  const showNotice = useCallback((title, message, tone = 'success') => {
    setInlineNotice({ title, message, tone });
  }, []);

  const clearNotice = useCallback(() => {
    setInlineNotice(null);
  }, []);

  const showError = useCallback((message, fallback = 'Please try again.') => {
    setInlineError(getErrorMessage(message, fallback));
  }, []);

  const clearError = useCallback(() => {
    setInlineError('');
  }, []);

  const stats = useMemo(() => {
    const list = Array.isArray(orderHistory) ? orderHistory : [];
    const active = list.filter((item) => !isFinalOrderStatus(item?.status)).length;

    return {
      orders: list.length,
      active,
      addresses: addresses.length,
    };
  }, [addresses.length, orderHistory]);

  const pendingPaymentOrdersCount = useMemo(
    () =>
      (Array.isArray(orderHistory) ? orderHistory : []).filter((order) =>
        canRetryGatewayPayment(order)
      ).length,
    [orderHistory]
  );

  const supportQueue = useMemo(() => buildSupportQueue(orderHistory), [orderHistory]);

  const filteredOrders = useMemo(() => {
    const query = normalizeText(search);

    return (Array.isArray(orderHistory) ? orderHistory : []).filter((order) => {
      if (!matchesOrderFilter(order, filterKey)) return false;
      if (!query) return true;
      return getOrderSearchText(order).includes(query);
    });
  }, [filterKey, orderHistory, search]);

  const refreshDashboard = useCallback(async () => {
    if (!isAuthenticated) return;

    try {
      setRefreshing(true);
      await Promise.all([loadOrders({ silent: true }), loadAddresses({ silent: true })]);
      clearError();
    } finally {
      setRefreshing(false);
    }
  }, [clearError, isAuthenticated, loadAddresses, loadOrders]);

  const submitAuth = useCallback(async () => {
    if (!email.trim()) {
      showError('Enter your email address.', 'Enter your email address.');
      return;
    }

    if (String(password || '').length < 6) {
      showError('Enter at least 6 characters.', 'Enter at least 6 characters.');
      return;
    }

    const ok =
      authMode === 'register'
        ? await register({ email, password })
        : await login({ email, password });

    if (ok) {
      setPassword('');
      refreshDashboard().catch(() => {});
    }
  }, [authMode, email, login, password, refreshDashboard, register, showError]);



  const triggerChallenge = useCallback(async ({ resend = false } = {}) => {
    if (!email.trim()) {
      setChallengeError('Enter an email first.');
      return;
    }

    try {
      setChallengeLoading(true);
      setChallengeError('');
      const response = await startAuthChallenge({
        challengeType: challengeMode,
        target: email,
      });
      const nextChallengeId = Number(response?.challenge_id || 0);
      if (nextChallengeId) setChallengeId(nextChallengeId);
      const devCode = String(response?.dev_code || '').trim();
      const base = resend ? 'Code resent.' : 'Challenge started.';
      setChallengeStatus(devCode ? `${base} Dev code: ${devCode}` : `${base} Check your inbox.`);
    } catch (error) {
      setChallengeError(getErrorMessage(error, 'Could not start challenge.'));
    } finally {
      setChallengeLoading(false);
    }
  }, [challengeMode, email, startAuthChallenge]);

  const submitChallengeVerify = useCallback(async () => {
    if (!challengeId) {
      setChallengeError('Request a challenge before verifying.');
      return;
    }
    if (!challengeCode.trim()) {
      setChallengeError('Enter the challenge code.');
      return;
    }

    try {
      setChallengeLoading(true);
      setChallengeError('');
      const response = await verifyAuthChallenge({ challengeId, code: challengeCode });
      setChallengeStatus(
        challengeMode === 'PASSWORD_RESET'
          ? 'Password reset challenge verified. Continue with your reset screen flow.'
          : `Email verified at ${formatDateTime(response?.verified_at)}.`
      );
    } catch (error) {
      setChallengeError(getErrorMessage(error, 'Could not verify challenge.'));
    } finally {
      setChallengeLoading(false);
    }
  }, [challengeCode, challengeId, challengeMode, verifyAuthChallenge]);

  useEffect(() => {
    const orderId = Number(selectedOrder?.id || 0);
    if (!orderId || !authToken || typeof subscribeOrderTimeline !== 'function') return undefined;

    const stream = subscribeOrderTimeline({
      orderId,
      sinceId: Number(realtimeEvents[realtimeEvents.length - 1]?.id || 0),
      onError: () => {
        // transient disconnects are expected; stream helper reconnects with backoff.
      },
    });

    return () => stream?.close?.();
  }, [authToken, realtimeEvents, selectedOrder?.id, subscribeOrderTimeline]);

  const saveAddress = useCallback(async () => {
    try {
      setSavingAddress(true);

      const payload = {
        label,
        line1,
        city,
        pincode,
        lat: Number(lat),
        lng: Number(lng),
        is_default: addresses.length === 0,
      };

      const next = await createAddress(payload);
      if (!next) return;

      setLabel('Home');
      setLine1('');
      setCity('');
      setPincode('');
      setLat('12.9716');
      setLng('77.5946');

      clearError();
      showNotice('Saved', 'Address added successfully.', 'success');
    } catch (error) {
      showError(error, 'Please check the address and coordinates.');
    } finally {
      setSavingAddress(false);
    }
  }, [addresses.length, city, clearError, createAddress, label, lat, line1, lng, pincode, showNotice, showError]);

  const openStoreFromOrder = useCallback(
    (order) => {
      if (!order?.vendorId && !order?.vendor_id) return;

      const vendorId = String(order?.vendorId || order?.vendor_id);
      const vendor = (Array.isArray(vendors) ? vendors : []).find(
        (item) => String(item?.id) === vendorId
      );

      if (vendor) {
        rememberStore(vendor);
      }

      router.push({
        pathname: '/store/[vendorId]',
        params: { vendorId },
      });
    },
    [rememberStore, router, vendors]
  );

  const loadOrderDetail = useCallback(
    async (order) => {
      if (!authToken || !order?.id) return;

      try {
        setSelectedOrder(order);
        setDetailLoading(true);
        setOrderDetail(null);
        setOrderDetailVendor(null);

        const trackingPayload = await authRequest(`/orders/${order.id}/tracking`, authToken);
        setOrderDetail(trackingPayload || null);

        const vendorId =
          trackingPayload?.order?.vendor_id || order?.vendorId || order?.vendor_id;

        const existingVendor = (Array.isArray(vendors) ? vendors : []).find(
          (item) => String(item?.id) === String(vendorId)
        );

        if (existingVendor) {
          setOrderDetailVendor(existingVendor);
          return;
        }

        if (vendorId) {
          const vendorPayload = await publicRequest(`/vendors/${vendorId}`);
          setOrderDetailVendor(vendorPayload || null);
        }
      } catch (error) {
        showError(error, 'Please try again.');
      } finally {
        setDetailLoading(false);
      }
    },
    [authToken, showError, vendors]
  );

  const refreshSelectedOrder = useCallback(() => {
    if (!selectedOrder) return Promise.resolve();
    return loadOrderDetail(selectedOrder);
  }, [loadOrderDetail, selectedOrder]);

  const cancelOrder = useCallback(async () => {
    if (!authToken || !selectedOrder?.id) return;

    setConfirmState({
      title: 'Cancel order',
      message: `Cancel order #${selectedOrder.id}?`,
      confirmLabel: 'Cancel order',
      cancelLabel: 'Keep order',
      tone: 'danger',
      execute: async () => {
        try {
          setDetailActionLoading(true);
          await authRequest(
            `/orders/${selectedOrder.id}/cancel?reason=Cancelled%20from%20customer%20app`,
            authToken,
            { method: 'POST' }
          );

          await Promise.all([loadOrders({ silent: true }), refreshSelectedOrder()]);
          setConfirmState(null);
          clearError();
          showNotice('Cancelled', `Order #${selectedOrder.id} has been cancelled.`, 'success');
        } catch (error) {
          showError(error, 'Please try again.');
        } finally {
          setDetailActionLoading(false);
        }
      },
    });
  }, [authToken, clearError, loadOrders, refreshSelectedOrder, selectedOrder, showNotice, showError]);

  const retryPendingPayment = useCallback(async () => {
    const retryOrder = orderDetail?.order || selectedOrder;

    if (!authToken || !retryOrder?.id) return;

    if (!canRetryGatewayPayment(retryOrder)) {
      showError('This order no longer needs an online payment retry.', 'This order no longer needs an online payment retry.');
      return;
    }

    try {
      setRetryPaymentLoading(true);

      const returnUrl = ExpoLinking.createURL('/account');

      const session = await authRequest(`/payments/${retryOrder.id}/checkout-session`, authToken, {
        method: 'POST',
        body: JSON.stringify({ return_url: returnUrl }),
      });

      const checkoutUrl = String(session?.checkout_url || '').trim();
      if (!checkoutUrl) {
        throw new Error('The payment gateway did not return a checkout URL.');
      }

      const browserResult = await WebBrowser.openAuthSessionAsync(checkoutUrl, returnUrl);

      const gatewayStatus = await pollGatewayStatus(retryOrder.id, authToken, {
        attempts: browserResult?.type === 'success' ? 3 : 4,
        delayMs: browserResult?.type === 'success' ? 1200 : 1800,
      });

      const paymentStatus = String(gatewayStatus?.payment_status || '').toUpperCase();
      const checkoutStatus = normalizeGatewayStatus(gatewayStatus?.checkout_status);
      const providerPaymentId = gatewayStatus?.provider_payment_id;

      await Promise.all([
        loadOrders({ silent: true }).catch(() => {}),
        refreshSelectedOrder().catch(() => {}),
      ]);

      if (paymentStatus === 'PAID') {
        showNotice(
          'Payment successful',
          providerPaymentId
            ? `Payment captured and verified on the server. Payment ID: ${providerPaymentId}`
            : 'Payment captured and verified on the server.',
          'success'
        );
        return;
      }

      if (paymentStatus === 'FAILED' || ['cancelled', 'expired'].includes(checkoutStatus)) {
        showNotice(
          'Payment not completed',
          'No amount was confirmed by the gateway for this order yet. You can retry again from this screen.',
          'warning'
        );
        return;
      }

      showNotice(
        'Payment still processing',
        `Order #${retryOrder.id} is waiting for final gateway confirmation. Pull to refresh or reopen this order in a moment.`,
        'info'
      );
    } catch (error) {
      showError(error, 'Please try again.');
    } finally {
      setRetryPaymentLoading(false);
    }
  }, [authToken, loadOrders, orderDetail, refreshSelectedOrder, selectedOrder, showError, showNotice]);

  const closeSheet = useCallback(() => {
    setSelectedOrder(null);
    setOrderDetail(null);
    setOrderDetailVendor(null);
    setDetailLoading(false);
    setDetailActionLoading(false);
    setRetryPaymentLoading(false);
    setConfirmState(null);
  }, []);

  const handleSupportAction = useCallback(
    (actionKey) => {
      captureEvent(ANALYTICS_EVENTS.consumerAccountSupportActionClicked, {
        taxonomy_version: FEATURE_FLAGS.analyticsTaxonomyV2 ? ANALYTICS_TAXONOMY_VERSION : 'v1',
        action: actionKey,
        app_env: APP_ENV,
      });

      if (actionKey === 'refund') {
        const refundOrder = supportQueue.find((item) => item.isRefundEligible);
        if (refundOrder) {
          showNotice(
            'Refund request started',
            `Order #${refundOrder.id} moved to support review. Track progress in this account tab.`,
            'info'
          );
        } else {
          showNotice(
            'No eligible order yet',
            'Refund is available after delivery/payment confirmation. Try Help chat for urgent issues.',
            'warning'
          );
        }
        return;
      }

      if (actionKey === 'chat') {
        showNotice(
          'Support chat queued',
          'A support teammate will use order context to resolve this faster.',
          'success'
        );
        return;
      }

      showNotice(
        'Help center',
        'Show cancellation, replacement, and refund policy docs from here.',
        'info'
      );
    },
    [showNotice, supportQueue]
  );

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.bg} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={refreshDashboard}
            tintColor={COLORS.brand}
          />
        }>
        <View style={styles.feedbackStack}>
          <InlineErrorCard
            title="Account issue"
            message={inlineError}
            onRetry={refreshDashboard}
            onDismiss={clearError}
          />
          <InlineNoticeCard
            title={inlineNotice?.title || 'Updated'}
            message={inlineNotice?.message || ''}
            tone={inlineNotice?.tone || 'success'}
            onDismiss={clearNotice}
          />
          <InlineConfirmCard
            title={confirmState?.title || 'Please confirm'}
            message={confirmState?.message || ''}
            confirmLabel={confirmState?.confirmLabel || 'Confirm'}
            cancelLabel={confirmState?.cancelLabel || 'Cancel'}
            tone={confirmState?.tone || 'danger'}
            onConfirm={() => confirmState?.execute?.()}
            onCancel={() => setConfirmState(null)}
          />
        </View>
        <View style={styles.heroCard}>
          <View style={{ flex: 1 }}>
            <Text style={styles.heroEyebrow}>Grab Basket account</Text>
            <Text style={styles.heroTitle}>
              {isAuthenticated ? 'Orders, tracking, addresses, and profile' : 'Sign in to continue'}
            </Text>
            <Text style={styles.heroSubtitle}>
              Push notifications, live order updates, and delivery tracking are wired from here.
            </Text>
          </View>

          <View style={styles.heroIconWrap}>
            <Ionicons name="person-circle-outline" size={34} color="#FFFFFF" />
          </View>
        </View>

        {!isAuthenticated ? (
          <AuthPanel
            mode={authMode}
            setMode={setAuthMode}
            email={email}
            setEmail={setEmail}
            password={password}
            setPassword={setPassword}
            loading={authLoading}
            onSubmit={submitAuth}
            challengeMode={challengeMode}
            setChallengeMode={setChallengeMode}
            challengeCode={challengeCode}
            setChallengeCode={setChallengeCode}
            challengeLoading={challengeLoading}
            challengeStatus={challengeStatus}
            challengeError={challengeError}
            onStartChallenge={() => triggerChallenge({ resend: false })}
            onResendChallenge={() => triggerChallenge({ resend: true })}
            onVerifyChallenge={submitChallengeVerify}
          />
        ) : (
          <>
            <SectionCard
              title={profile?.name || authEmail || 'Grab Basket customer'}
              subtitle={`Role: ${authRole || 'CUSTOMER'} · ${defaultAddress ? 'Default address saved' : 'No default address yet'}`}
              right={
                <TouchableOpacity activeOpacity={0.92} onPress={() => logout()}>
                  <Text style={styles.linkText}>Logout</Text>
                </TouchableOpacity>
              }>
              <View style={styles.statsRow}>
                <StatCard icon="receipt-outline" label="Orders" value={stats.orders} />
                <StatCard icon="bicycle-outline" label="Active" value={stats.active} tone="info" />
                <StatCard icon="location-outline" label="Addresses" value={stats.addresses} tone="success" />
              </View>

              <View style={styles.accountMetaRow}>
                <View style={styles.accountMetaItem}>
                  <Text style={styles.accountMetaLabel}>Cart</Text>
                  <Text style={styles.accountMetaValue}>
                    {cartCount} items · {money(cartTotal)}
                  </Text>
                </View>

                <View style={styles.accountMetaItem}>
                  <Text style={styles.accountMetaLabel}>Default address</Text>
                  <Text style={styles.accountMetaValue} numberOfLines={2}>
                    {defaultAddress ? formatAddress(defaultAddress) : 'Not set'}
                  </Text>
                </View>
              </View>
            </SectionCard>

            <SectionCard
              title="Delivery addresses"
              subtitle="Manual coordinates are accepted now. Later you can replace this with GPS picker + geocoding."
              right={addressesLoading ? <ActivityIndicator color={COLORS.brand} /> : null}>
              <Field
                label="Label"
                value={label}
                onChangeText={setLabel}
                placeholder="Home / Work"
                autoCapitalize="words"
              />
              <Field
                label="Address line 1"
                value={line1}
                onChangeText={setLine1}
                placeholder="Flat, building, street"
                autoCapitalize="words"
              />

              <View style={styles.inlineFieldsRow}>
                <View style={{ flex: 1 }}>
                  <Field
                    label="City"
                    value={city}
                    onChangeText={setCity}
                    placeholder="Bengaluru"
                    autoCapitalize="words"
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Field
                    label="Pincode"
                    value={pincode}
                    onChangeText={setPincode}
                    placeholder="560001"
                    keyboardType="number-pad"
                  />
                </View>
              </View>

              <View style={styles.inlineFieldsRow}>
                <View style={{ flex: 1 }}>
                  <Field
                    label="Latitude"
                    value={lat}
                    onChangeText={setLat}
                    placeholder="12.9716"
                    keyboardType="decimal-pad"
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Field
                    label="Longitude"
                    value={lng}
                    onChangeText={setLng}
                    placeholder="77.5946"
                    keyboardType="decimal-pad"
                  />
                </View>
              </View>

              <TouchableOpacity
                activeOpacity={0.92}
                style={styles.primaryButton}
                disabled={savingAddress}
                onPress={saveAddress}>
                {savingAddress ? <ActivityIndicator color="#FFFFFF" /> : null}
                <Text style={styles.primaryButtonText}>Save address</Text>
              </TouchableOpacity>

              <View style={styles.addressListWrap}>
                {addresses.length ? (
                  addresses.map((address) => (
                    <AddressCard
                      key={String(address?.id)}
                      address={address}
                      isDefault={Boolean(address?.is_default)}
                      onSetDefault={() => setDefaultAddress(address?.id)}
                    />
                  ))
                ) : (
                  <View style={styles.emptyPanel}>
                    <Text style={styles.emptyPanelTitle}>No addresses yet</Text>
                    <Text style={styles.emptyPanelSubtitle}>
                      Save one address to start placing orders.
                    </Text>
                  </View>
                )}
              </View>
            </SectionCard>

            <SectionCard
              title="My orders"
              subtitle={`Search, filter, and open live tracking for any order${pendingPaymentOrdersCount ? ` · ${pendingPaymentOrdersCount} payment pending` : ''}`}>
              <View style={styles.searchWrap}>
                <Ionicons name="search-outline" size={18} color={COLORS.subtle} />
                <TextInput
                  value={search}
                  onChangeText={setSearch}
                  placeholder="Search by order id, vendor, item, payment, or status"
                  placeholderTextColor={COLORS.subtle}
                  style={styles.searchInput}
                />
              </View>

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.filtersRow}>
                {ORDER_FILTERS.map((filter) => {
                  const active = filter.key === filterKey;

                  return (
                    <TouchableOpacity
                      key={filter.key}
                      activeOpacity={0.92}
                      style={[styles.filterChip, active && styles.filterChipActive]}
                      onPress={() => setFilterKey(filter.key)}>
                      <Text style={[styles.filterChipText, active && styles.filterChipTextActive]}>
                        {filter.label}
                        {filter.key === 'payment_pending' && pendingPaymentOrdersCount
                          ? ` (${pendingPaymentOrdersCount})`
                          : ''}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>

              {ordersLoading && !filteredOrders.length ? (
                <View style={styles.loadingWrap}>
                  <ActivityIndicator color={COLORS.brand} />
                  <Text style={styles.loadingText}>Loading orders…</Text>
                </View>
              ) : filteredOrders.length ? (
                filteredOrders.map((order) => (
                  <OrderCard
                    key={String(order?.id)}
                    order={order}
                    onPress={() => loadOrderDetail(order)}
                  />
                ))
              ) : (
                <View style={styles.emptyPanel}>
                  <Text style={styles.emptyPanelTitle}>No orders match your search</Text>
                  <Text style={styles.emptyPanelSubtitle}>
                    Try a different filter or place your first order.
                  </Text>
                </View>
              )}
            </SectionCard>

            <SectionCard
              title="Support, help & refunds"
              subtitle="Customer trust grows when help, refunds, and post-order care are easy to access.">
              <View style={styles.supportActionsRow}>
                {SUPPORT_ACTIONS.map((action) => (
                  <TouchableOpacity
                    key={action.key}
                    activeOpacity={0.92}
                    style={styles.supportActionCard}
                    onPress={() => handleSupportAction(action.key)}>
                    <View style={styles.supportActionIcon}>
                      <Ionicons name={action.icon} size={17} color={COLORS.brand} />
                    </View>
                    <Text style={styles.supportActionTitle}>{action.label}</Text>
                    <Text style={styles.supportActionCopy}>{action.copy}</Text>
                  </TouchableOpacity>
                ))}
              </View>

              <View style={styles.supportQueueWrap}>
                <Text style={styles.supportQueueTitle}>Recent support-ready orders</Text>
                {supportQueue.length ? (
                  supportQueue.map((item) => (
                    <View key={String(item.id)} style={styles.supportQueueRow}>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.supportQueuePrimary}>Order #{item.id}</Text>
                        <Text style={styles.supportQueueMeta}>
                          {item.service.toUpperCase()} · {item.status} · Payment {item.payment}
                        </Text>
                      </View>
                      <Text
                        style={[
                          styles.supportQueueBadge,
                          item.isRefundEligible
                            ? styles.supportQueueBadgeSuccess
                            : styles.supportQueueBadgeNeutral,
                        ]}>
                        {item.isRefundEligible ? 'Refund eligible' : 'Needs review'}
                      </Text>
                    </View>
                  ))
                ) : (
                  <Text style={styles.supportQueueEmpty}>
                    Place your first order to activate support and refund shortcuts.
                  </Text>
                )}
              </View>
            </SectionCard>

            <SectionCard
              title="Growth, analytics & release governance"
              subtitle="P2 readiness: personalization, loyalty, instrumentation quality, and safer staged rollouts.">
              <View style={styles.governanceChipRow}>
                <View style={styles.governanceChip}>
                  <Text style={styles.governanceChipLabel}>Environment</Text>
                  <Text style={styles.governanceChipValue}>{String(APP_ENV || 'development').toUpperCase()}</Text>
                </View>
                <View style={styles.governanceChip}>
                  <Text style={styles.governanceChipLabel}>Crash monitor</Text>
                  <Text style={styles.governanceChipValue}>Sentry + PostHog</Text>
                </View>
              </View>

              <View style={styles.governanceFlagList}>
                <Text style={styles.governanceFlagTitle}>Feature flags</Text>
                <Text style={styles.governanceFlagItem}>
                  • Personalization + reorder intelligence: {FEATURE_FLAGS.reorderIntelligence ? 'Enabled' : 'Disabled'}
                </Text>
                <Text style={styles.governanceFlagItem}>
                  • Loyalty & membership experiences: {FEATURE_FLAGS.loyaltyMembership ? 'Enabled' : 'Disabled'}
                </Text>
                <Text style={styles.governanceFlagItem}>
                  • Analytics taxonomy v2: {FEATURE_FLAGS.analyticsTaxonomyV2 ? 'Enabled' : 'Disabled'}
                </Text>
                <Text style={styles.governanceFlagItem}>
                  • Staging→production rollout gates: {FEATURE_FLAGS.stagedRollout ? 'Enabled' : 'Disabled'}
                </Text>
              </View>
            </SectionCard>

            <SectionCard title="Current status" subtitle="What is done today in this app shell">
              <Text style={styles.checkText}>• Push notifications are registered globally from the root layout.</Text>
              <Text style={styles.checkText}>• Customer app can open a live tracking map for order details.</Text>
              <Text style={styles.checkText}>• Search + filters work inside the order list in this screen.</Text>
              <Text style={styles.checkText}>• Online payments can now be retried from Account for unpaid UPI/card orders.</Text>
              <Text style={styles.checkText}>• A dedicated Payment pending filter chip now isolates unpaid UPI/card orders quickly.</Text>
            </SectionCard>
          </>
        )}
      </ScrollView>

      <OrderDetailsSheet
        visible={Boolean(selectedOrder)}
        onClose={closeSheet}
        order={selectedOrder}
        detail={orderDetail}
        vendor={orderDetailVendor}
        loading={detailLoading}
        actionLoading={detailActionLoading}
        retryLoading={retryPaymentLoading}
        onRefresh={refreshSelectedOrder}
        onCancel={cancelOrder}
        onRetryPayment={retryPendingPayment}
        onOpenStore={() => openStoreFromOrder(selectedOrder)}
        realtimeEvents={realtimeEvents}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  feedbackStack: { paddingHorizontal: 18, paddingTop: 14, gap: 12 },
  heroCard: {
    marginHorizontal: 16,
    marginTop: 12,
    marginBottom: 14,
    borderRadius: 24,
    padding: 18,
    backgroundColor: COLORS.brand,
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 14,
  },
  heroEyebrow: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    color: 'rgba(255,255,255,0.78)',
    marginBottom: 6,
  },
  heroTitle: {
    fontSize: 22,
    lineHeight: 28,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  heroSubtitle: {
    marginTop: 8,
    fontSize: 13,
    lineHeight: 19,
    color: 'rgba(255,255,255,0.86)',
  },
  heroIconWrap: {
    width: 54,
    height: 54,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.16)',
  },

  sectionCard: {
    marginHorizontal: 16,
    marginBottom: 14,
    borderRadius: 22,
    padding: 16,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    shadowColor: COLORS.shadow,
    shadowOpacity: 1,
    shadowRadius: 14,
    shadowOffset: { width: 0, height: 6 },
    elevation: 3,
  },
  supportActionsRow: {
    gap: 10,
  },
  supportActionCard: {
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 12,
    paddingVertical: 11,
  },
  supportActionIcon: {
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.brandSoft,
  },
  supportActionTitle: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  supportActionCopy: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 17,
  },
  supportQueueWrap: {
    marginTop: 12,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    padding: 12,
    gap: 9,
  },
  supportQueueTitle: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },
  supportQueueRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  supportQueuePrimary: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  supportQueueMeta: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 11,
    fontWeight: '600',
  },
  supportQueueBadge: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
    fontSize: 10,
    fontWeight: '800',
    overflow: 'hidden',
  },
  supportQueueBadgeSuccess: {
    backgroundColor: COLORS.successSoft,
    color: COLORS.success,
  },
  supportQueueBadgeNeutral: {
    backgroundColor: COLORS.warningSoft,
    color: COLORS.warning,
  },
  supportQueueEmpty: {
    color: COLORS.subtle,
    fontSize: 12,
    fontWeight: '600',
  },
  governanceChipRow: {
    flexDirection: 'row',
    gap: 10,
  },
  governanceChip: {
    flex: 1,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  governanceChipLabel: {
    color: COLORS.subtle,
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  governanceChipValue: {
    marginTop: 4,
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '900',
  },
  governanceFlagList: {
    marginTop: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  governanceFlagTitle: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '900',
    marginBottom: 6,
  },
  governanceFlagItem: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 18,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 14,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: COLORS.text,
  },
  sectionSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 18,
    color: COLORS.muted,
  },

  fieldWrap: {
    marginBottom: 12,
  },
  fieldLabel: {
    marginBottom: 6,
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.text,
  },
  input: {
    minHeight: 50,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 14,
    fontSize: 15,
    color: COLORS.text,
  },
  inputMultiline: {
    minHeight: 96,
    paddingTop: 12,
    textAlignVertical: 'top',
  },

  primaryButton: {
    minHeight: 50,
    borderRadius: 16,
    backgroundColor: COLORS.brand,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
    marginTop: 4,
    paddingHorizontal: 14,
  },
  primaryButtonText: {
    fontSize: 15,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  secondaryButton: {
    minHeight: 46,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
    paddingHorizontal: 14,
    marginTop: 10,
  },
  secondaryButtonText: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text,
  },
  authChallengeWrap: {
    marginTop: 14,
    borderTopWidth: 1,
    borderTopColor: COLORS.line,
    paddingTop: 12,
    gap: 8,
  },
  inlineActionsRow: {
    flexDirection: 'row',
    gap: 8,
    flexWrap: 'wrap',
  },
  miniActionButton: {
    backgroundColor: COLORS.brandSoft,
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  miniActionButtonText: {
    color: COLORS.brand,
    fontSize: 12,
    fontWeight: '700',
  },
  linkText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.brand,
  },

  statsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  statCard: {
    flex: 1,
    borderRadius: 18,
    padding: 14,
    backgroundColor: COLORS.cardAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
  },
  statIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  statValue: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.text,
  },
  statLabel: {
    marginTop: 3,
    fontSize: 12,
    color: COLORS.muted,
  },

  accountMetaRow: {
    marginTop: 14,
    gap: 12,
  },
  accountMetaItem: {
    borderRadius: 16,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
    borderWidth: 1,
    borderColor: COLORS.line,
  },
  accountMetaLabel: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    color: COLORS.subtle,
  },
  accountMetaValue: {
    marginTop: 6,
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.text,
  },

  inlineFieldsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  addressListWrap: {
    marginTop: 14,
    gap: 10,
  },
  addressCard: {
    borderRadius: 18,
    padding: 14,
    backgroundColor: COLORS.cardAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
  },
  addressCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 8,
  },
  addressBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: COLORS.brandSoft,
  },
  addressBadgeText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.brand,
  },
  defaultChip: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: COLORS.successSoft,
  },
  defaultChipText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.success,
  },
  addressLine: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.text,
  },
  addressMeta: {
    marginTop: 6,
    fontSize: 12,
    color: COLORS.muted,
  },

  searchWrap: {
    minHeight: 50,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 14,
    marginBottom: 12,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: COLORS.text,
  },
  filtersRow: {
    paddingBottom: 4,
    gap: 8,
    marginBottom: 12,
  },
  filterChip: {
    height: 38,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
  filterChipActive: {
    backgroundColor: COLORS.brand,
    borderColor: COLORS.brand,
  },
  filterChipText: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.text,
  },
  filterChipTextActive: {
    color: '#FFFFFF',
  },

  loadingWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 28,
    gap: 10,
  },
  loadingText: {
    fontSize: 13,
    color: COLORS.muted,
  },

  orderCard: {
    borderRadius: 18,
    padding: 14,
    backgroundColor: COLORS.cardAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
    marginBottom: 10,
  },
  orderCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderAvatar: {
    width: 44,
    height: 44,
    borderRadius: 15,
    backgroundColor: COLORS.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderAvatarText: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.brand,
  },
  orderVendorName: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  orderVendorMeta: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
  },
  orderStatusBadge: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
    alignSelf: 'flex-start',
  },
  orderStatusText: {
    fontSize: 11,
    fontWeight: '800',
  },
  orderSummary: {
    marginTop: 12,
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.text,
  },
  orderMetaRow: {
    marginTop: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
  },
  orderMeta: {
    fontSize: 12,
    color: COLORS.muted,
  },
  orderFooterRow: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  orderFooterBadges: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
    paddingRight: 8,
  },
  orderHintPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
    backgroundColor: COLORS.infoSoft,
  },
  orderHintText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.info,
  },
  orderPaymentRetryPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
    backgroundColor: COLORS.brandSoft,
  },
  orderPaymentRetryPillText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.brand,
  },

  checkText: {
    fontSize: 14,
    lineHeight: 21,
    color: COLORS.text,
    marginBottom: 8,
  },
  emptyPanel: {
    borderRadius: 18,
    backgroundColor: COLORS.cardAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
    padding: 16,
  },
  emptyPanelTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  emptyPanelSubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },

  sheetOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(20, 14, 10, 0.35)',
  },
  sheetCard: {
    maxHeight: '92%',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    backgroundColor: COLORS.bg,
    paddingTop: 10,
  },
  sheetHandle: {
    width: 48,
    height: 5,
    borderRadius: 999,
    backgroundColor: '#D6C4B2',
    alignSelf: 'center',
    marginBottom: 12,
  },
  sheetHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingBottom: 12,
  },
  sheetTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.text,
  },
  sheetSubtitle: {
    marginTop: 4,
    fontSize: 13,
    color: COLORS.muted,
  },
  sheetCloseButton: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sheetLoaderWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 40,
    gap: 10,
  },
  sheetLoaderText: {
    fontSize: 13,
    color: COLORS.muted,
  },
  sheetBody: {
    paddingHorizontal: 16,
    paddingBottom: 28,
  },
  sheetHeroCard: {
    borderRadius: 22,
    padding: 16,
    backgroundColor: COLORS.black,
    marginBottom: 14,
  },
  sheetHeroAmount: {
    marginTop: 14,
    fontSize: 26,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  sheetHeroMeta: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 18,
    color: 'rgba(255,255,255,0.75)',
  },

  paymentRetryBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    padding: 14,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.brandSoft,
    marginBottom: 12,
  },
  paymentRetryIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
  paymentRetryTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  paymentRetrySubtitle: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },

  statusPill: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  statusPillText: {
    fontSize: 12,
    fontWeight: '800',
  },

  mapWrap: {
    overflow: 'hidden',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.cardAlt,
  },
  map: {
    width: '100%',
    height: 260,
  },
  mapMetaWrap: {
    padding: 12,
  },
  mapLegendRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  mapLegendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  mapLegendDot: {
    width: 10,
    height: 10,
    borderRadius: 999,
  },
  mapLegendText: {
    fontSize: 12,
    color: COLORS.muted,
  },
  markerPickup: {
    width: 30,
    height: 30,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.brand,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  markerDrop: {
    width: 30,
    height: 30,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.success,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  markerRider: {
    width: 30,
    height: 30,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.info,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },

  detailGrid: {
    gap: 10,
  },
  detailTile: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: COLORS.cardAlt,
    padding: 14,
  },
  detailLabel: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    color: COLORS.subtle,
  },
  detailValue: {
    marginTop: 8,
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  detailHint: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 18,
    color: COLORS.muted,
  },

  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.line,
  },
  itemQtyBubble: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.brandSoft,
  },
  itemQtyBubbleText: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.brand,
  },
  itemName: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text,
  },
  itemMeta: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
  },
  itemPrice: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.text,
  },

  timelineWrap: {
    marginTop: 4,
  },
  timelineRow: {
    flexDirection: 'row',
    gap: 12,
    paddingBottom: 16,
  },
  timelineRail: {
    width: 18,
    alignItems: 'center',
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: 999,
    marginTop: 4,
  },
  timelineLine: {
    width: 2,
    flex: 1,
    backgroundColor: COLORS.line,
    marginTop: 4,
  },
  timelineTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  timelineMeta: {
    marginTop: 4,
    fontSize: 12,
    color: COLORS.muted,
  },
  timelineNote: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.text,
  },

  sheetButtonRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 6,
  },
  cancelButton: {
    flex: 1,
    backgroundColor: COLORS.danger,
  },
});
