import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { mapLegacyService } from '@/domains/grab-basket-utils';
import { buildApiUrl } from '../../config';
import InlineErrorCard from '../../components/inline-error-card';
import InlineNoticeCard from '../../components/inline-notice-card';
import LiveRouteIntelligenceCard from '../../components/live-route-intelligence-card';
import { useGrabBasket } from '../../../App';

const TERMINAL_STATUSES = new Set(['DELIVERED', 'CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED']);
const LIVE_ORDER_STATUSES = new Set([
  'PAYMENT_PENDING',
  'CREATED',
  'PAYMENT_VERIFIED',
  'ACCEPTED_BY_SELLER',
  'ASSIGNED_TO_PARTNER',
  'READY_FOR_PICKUP',
  'PICKED_UP',
]);

const HANDOFF_STEPS = [
  { key: 'placed', title: 'Order placed' },
  { key: 'accepted', title: 'Seller accepted' },
  { key: 'assigned', title: 'Rider assigned' },
  { key: 'ready', title: 'Packed & ready' },
  { key: 'picked_up', title: 'Picked up' },
  { key: 'delivered', title: 'Delivered' },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function normalizeStatus(value) {
  return String(value || '').trim().toUpperCase();
}

function prettyStatus(value) {
  return (
    String(value || '')
      .replace(/_/g, ' ')
      .trim()
      .toLowerCase()
      .replace(/\b\w/g, (character) => character.toUpperCase()) || 'Processing'
  );
}

function formatDateTime(value) {
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

function normalizeCoordinate(lat, lng) {
  const latitude = Number(lat);
  const longitude = Number(lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return { latitude, longitude };
}

function eventTime(event) {
  return Date.parse(event?.created_at || 0) || 0;
}

function mergeEvents(base = [], live = []) {
  const byKey = new Map();
  [...base, ...live].forEach((event, index) => {
    if (!event || typeof event !== 'object') return;
    const key = Number(event?.id) > 0 ? `id:${event.id}` : `${event?.status || 'status'}:${event?.created_at || index}`;
    byKey.set(key, event);
  });
  return [...byKey.values()].sort((left, right) => eventTime(right) - eventTime(left));
}

function isLiveOrder(order) {
  return LIVE_ORDER_STATUSES.has(normalizeStatus(order?.status));
}

function getOrderItemsLabel(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  const names = items.map((item) => String(item?.name || item?.name_snapshot || '').trim()).filter(Boolean);
  if (names.length > 1) return `${names[0]} +${names.length - 1} more`;
  if (names.length === 1) return names[0];
  const totalItems = Number(order?.item_count || items.length || 0);
  if (totalItems > 0) return `${totalItems} item${totalItems > 1 ? 's' : ''}`;
  return 'Order details available in history';
}

function toneColors(tone) {
  if (tone === 'success') return { fg: BrandPalette.success, bg: BrandPalette.successSoft };
  if (tone === 'warning') return { fg: BrandPalette.warning, bg: BrandPalette.warningSoft };
  if (tone === 'danger') return { fg: BrandPalette.danger, bg: BrandPalette.dangerSoft };
  return { fg: BrandPalette.info, bg: BrandPalette.infoSoft };
}

function getOrderTone(order) {
  const status = normalizeStatus(order?.status || order?.payment_status);
  if (status === 'DELIVERED') return { label: 'Delivered', fg: BrandPalette.success, bg: BrandPalette.successSoft };
  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return { label: prettyStatus(status), fg: BrandPalette.danger, bg: BrandPalette.dangerSoft };
  }
  return { label: prettyStatus(status || 'processing'), fg: BrandPalette.warning, bg: BrandPalette.warningSoft };
}

function deriveEtaMinutes(order, tracking = null) {
  const explicitEta = Number(order?.delivery_eta_minutes || 0);
  if (Number.isFinite(explicitEta) && explicitEta > 0) return explicitEta;

  const status = normalizeStatus(order?.status);
  if (status === 'DELIVERED') return 0;
  if (status === 'PICKED_UP') return tracking?.has_location ? 4 : 6;
  if (status === 'READY_FOR_PICKUP') return 4;
  if (status === 'ASSIGNED_TO_PARTNER') return tracking?.has_location ? 6 : 8;
  if (status === 'ACCEPTED_BY_SELLER') return 10;
  if (['PAYMENT_PENDING', 'PAYMENT_VERIFIED', 'CREATED'].includes(status)) return 12;
  return null;
}

function getTrackingHero(order, tracking = null) {
  const status = normalizeStatus(order?.status);
  const etaMinutes = deriveEtaMinutes(order, tracking);

  if (status === 'DELIVERED') {
    return {
      title: 'Delivered',
      subtitle: 'The order handoff is complete. You can still review the seller, rider and event timeline below.',
      tone: 'success',
      progressLabel: 'Completed',
      etaPrimary: 'Done',
      etaSecondary: '',
      helperText: 'Final delivery checkpoints remain visible for post-order support.',
    };
  }

  if (status === 'PICKED_UP') {
    return {
      title: 'Out for delivery',
      subtitle: tracking?.has_location
        ? `Your rider is on the move. Latest live ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`
        : 'Your rider picked up the order and is moving towards your address.',
      tone: 'success',
      progressLabel: 'Rider en route',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Live',
      etaSecondary: etaMinutes ? 'mins' : '',
      helperText: 'This mirrors the Swiggy-style final-mile pattern: map first, status card second, timeline below.',
    };
  }

  if (status === 'READY_FOR_PICKUP') {
    return {
      title: 'Order is packed!',
      subtitle: tracking?.assigned
        ? 'The basket is ready at the store and your rider will pick it up shortly.'
        : 'The store finished packing the basket and dispatch is getting pickup ready.',
      tone: 'success',
      progressLabel: 'Packed and ready',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helperText: 'Packed orders stay in a ready-to-pickup state until the rider confirms handoff.',
    };
  }

  if (status === 'ASSIGNED_TO_PARTNER') {
    return {
      title: 'Order is getting packed!',
      subtitle: tracking?.assigned
        ? 'A delivery partner is already assigned while the store finishes preparing your basket.'
        : 'Dispatch is working on partner assignment while the store keeps packing the order.',
      tone: 'warning',
      progressLabel: 'Partner assigned',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helperText: 'This is the same transition state customers expect in quick-commerce apps before pickup starts.',
    };
  }

  if (status === 'ACCEPTED_BY_SELLER') {
    return {
      title: 'Order is getting packed!',
      subtitle: 'The store accepted your order and started assembling the basket for dispatch.',
      tone: 'warning',
      progressLabel: 'Seller preparing',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helperText: 'Seller updates are surfaced before rider movement so the customer always knows the current stage.',
    };
  }

  if (['PAYMENT_PENDING', 'PAYMENT_VERIFIED', 'CREATED'].includes(status)) {
    return {
      title: 'Order is getting packed!',
      subtitle: 'We received the order. The store and dispatch systems are syncing the next fulfilment steps now.',
      tone: 'warning',
      progressLabel: 'Order received',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helperText: 'Early-stage orders keep the customer informed even before seller acceptance and partner assignment finish.',
    };
  }

  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return {
      title: prettyStatus(status),
      subtitle: 'This order is closed, so the live movement feed has stopped. Historical events remain available below.',
      tone: 'danger',
      progressLabel: 'Closed',
      etaPrimary: '—',
      etaSecondary: 'closed',
      helperText: 'Keep the closed-state messaging explicit so the customer is never confused about why tracking stopped.',
    };
  }

  return {
    title: 'Tracking will start soon',
    subtitle: 'We are waiting for the first fulfilment updates from the store and dispatch systems.',
    tone: 'info',
    progressLabel: 'Starting',
    etaPrimary: etaMinutes ? String(etaMinutes) : 'Live',
    etaSecondary: etaMinutes ? 'mins' : '',
    helperText: 'The screen is ready for seller acceptance, rider assignment and live route events.',
  };
}

function getStepIndex(order, events = [], tracking = null) {
  const status = normalizeStatus(order?.status);
  const statuses = new Set(events.map((event) => normalizeStatus(event?.status)));
  if (status === 'DELIVERED' || statuses.has('DELIVERED')) return 5;
  if (status === 'PICKED_UP' || statuses.has('PICKED_UP')) return 4;
  if (status === 'READY_FOR_PICKUP' || statuses.has('READY_FOR_PICKUP')) return 3;
  if (status === 'ASSIGNED_TO_PARTNER' || statuses.has('ASSIGNED_TO_PARTNER') || tracking?.assigned) return 2;
  if (status === 'ACCEPTED_BY_SELLER' || statuses.has('ACCEPTED_BY_SELLER')) return 1;
  return 0;
}

function buildSteps(order, events = [], tracking = null) {
  const current = getStepIndex(order, events, tracking);
  const closed = TERMINAL_STATUSES.has(normalizeStatus(order?.status)) && normalizeStatus(order?.status) !== 'DELIVERED';
  return HANDOFF_STEPS.map((step, index) => ({
    ...step,
    done: !closed && index <= current,
    current: !closed && index === current,
  }));
}

function getSellerSummary(order) {
  const status = normalizeStatus(order?.status);
  if (status === 'REJECTED_BY_SELLER') {
    return { title: 'Seller rejected the order', subtitle: 'The order closed before merchant handoff.', tone: 'danger', icon: 'close-circle-outline' };
  }
  if (status === 'READY_FOR_PICKUP') {
    return { title: 'Seller finished prep', subtitle: 'Packed and waiting at pickup.', tone: 'success', icon: 'bag-handle-outline' };
  }
  if (status === 'PICKED_UP' || status === 'DELIVERED') {
    return { title: 'Seller handoff completed', subtitle: 'The rider has already collected the order.', tone: 'success', icon: 'storefront-outline' };
  }
  if (status === 'ASSIGNED_TO_PARTNER') {
    return { title: 'Seller is preparing the order', subtitle: 'Dispatch has a rider while the store finishes prep.', tone: 'warning', icon: 'restaurant-outline' };
  }
  if (status === 'ACCEPTED_BY_SELLER') {
    return { title: 'Seller accepted the order', subtitle: 'The store is actively preparing your basket.', tone: 'warning', icon: 'checkmark-done-outline' };
  }
  return { title: 'Waiting for seller confirmation', subtitle: 'The store has not accepted the order yet.', tone: 'info', icon: 'time-outline' };
}

function getRiderSummary(order, tracking = null) {
  const status = normalizeStatus(order?.status);
  if (status === 'DELIVERED') {
    return { title: 'Delivery completed', subtitle: 'The final handoff is complete.', tone: 'success', icon: 'checkmark-circle-outline' };
  }
  if (status === 'PICKED_UP') {
    return {
      title: 'Rider is on the way',
      subtitle: tracking?.has_location
        ? `Latest rider ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`
        : 'The rider picked up the order and is heading to the drop.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (tracking?.assigned && tracking?.has_location) {
    return {
      title: 'Rider is live in the system',
      subtitle: `Latest rider ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`,
      tone: 'success',
      icon: 'navigate-outline',
    };
  }
  if (status === 'ASSIGNED_TO_PARTNER' || tracking?.assigned) {
    return {
      title: 'Rider assigned',
      subtitle: 'Dispatch assigned a rider, waiting for pickup or first live ping.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return { title: 'No rider handoff', subtitle: 'The order closed before delivery movement began.', tone: 'danger', icon: 'remove-circle-outline' };
  }
  return { title: 'Waiting for rider assignment', subtitle: 'Dispatch has not assigned a rider yet.', tone: 'info', icon: 'time-outline' };
}

function Surface({ children, style }) {
  return <View style={[styles.surface, style]}>{children}</View>;
}

function StatusPill({ tone }) {
  return (
    <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
      <Text style={[styles.statusPillText, { color: tone.fg }]}>{tone.label}</Text>
    </View>
  );
}

function StepRail({ steps }) {
  return (
    <View style={{ gap: 10 }}>
      {steps.map((step, index) => {
        const dotColor = step.done ? BrandPalette.success : step.current ? BrandPalette.warning : BrandPalette.border;
        const textColor = step.done ? BrandPalette.success : step.current ? BrandPalette.warning : BrandPalette.textMuted;

        return (
          <View key={step.key} style={{ gap: 6 }}>
            <View style={styles.stepTop}>
              <View style={[styles.stepDot, { backgroundColor: dotColor }]} />
              {index < steps.length - 1 ? (
                <View style={[styles.stepLine, { backgroundColor: step.done ? BrandPalette.success : BrandPalette.border }]} />
              ) : null}
            </View>
            <Text style={[styles.stepTitle, { color: textColor }]}>{step.title}</Text>
          </View>
        );
      })}
    </View>
  );
}

function StateCard({ title, subtitle, tone, icon }) {
  const colors = toneColors(tone);
  return (
    <View style={styles.stateCard}>
      <View style={[styles.stateIcon, { backgroundColor: colors.bg }]}>
        <Ionicons name={icon} size={20} color={colors.fg} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={styles.stateTitle}>{title}</Text>
        <Text style={styles.stateSubtitle}>{subtitle}</Text>
      </View>
    </View>
  );
}

function MetaRow({ icon, label, value, muted = false }) {
  return (
    <View style={styles.metaRow}>
      <Ionicons name={icon} size={16} color={BrandPalette.primary} style={{ marginTop: 2 }} />
      <View style={{ flex: 1 }}>
        <Text style={styles.metaLabel}>{label}</Text>
        <Text style={[styles.metaValue, muted && styles.metaValueMuted]}>{value}</Text>
      </View>
    </View>
  );
}

function Timeline({ events }) {
  if (!events.length) {
    return (
      <View style={styles.emptyTimeline}>
        <Text style={styles.emptyTitle}>No timeline yet</Text>
        <Text style={styles.emptySubtitle}>Seller, dispatch, rider, and delivery checkpoints will appear here.</Text>
      </View>
    );
  }

  return (
    <View style={{ marginTop: 4 }}>
      {events.map((event, index) => {
        const status = normalizeStatus(event?.status);
        const tone = toneColors(
          status === 'DELIVERED' || status === 'READY_FOR_PICKUP'
            ? 'success'
            : TERMINAL_STATUSES.has(status)
              ? 'danger'
              : 'warning'
        );

        return (
          <View key={`${event?.id || event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.fg }]} />
              {index < events.length - 1 ? <View style={styles.timelineLine} /> : null}
            </View>
            <View style={{ flex: 1, paddingBottom: 14 }}>
              <Text style={styles.timelineTitle}>{prettyStatus(event?.status)}</Text>
              <Text style={styles.timelineMeta}>{formatDateTime(event?.created_at)}</Text>
              {event?.note ? <Text style={styles.timelineNote}>{event.note}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}

export default function CustomerLiveTrackingScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const orderId = String(params?.orderId || '').trim();
  const {
    sessionReady,
    isAuthenticated,
    authToken,
    orderHistory,
    vendors,
    loadOrders,
    loadAddresses,
    timelineEventsByOrder,
    subscribeOrderTimeline,
  } = useGrabBasket();

  const [tracking, setTracking] = useState(null);
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [notice, setNotice] = useState('');
  const [error, setError] = useState('');
  const hydratedRef = useRef(false);

  useEffect(() => {
    if (!sessionReady || !isAuthenticated || hydratedRef.current) return;
    hydratedRef.current = true;
    loadAddresses?.().catch(() => {});
    loadOrders?.({ silent: true }).catch(() => {});
  }, [isAuthenticated, loadAddresses, loadOrders, sessionReady]);

  const order = useMemo(
    () => (Array.isArray(orderHistory) ? orderHistory.find((item) => String(item?.id) === orderId) || null : null),
    [orderHistory, orderId]
  );

  const vendor = useMemo(
    () => (Array.isArray(vendors) ? vendors.find((item) => String(item?.id) === String(order?.vendor_id)) || null : null),
    [order?.vendor_id, vendors]
  );

  const events = useMemo(
    () => mergeEvents(order?.events || [], timelineEventsByOrder?.[order?.id] || []),
    [order, timelineEventsByOrder]
  );

  const fetchTracking = async ({ silent = false } = {}) => {
    const numericOrderId = Number(orderId || 0);
    if (!numericOrderId || !authToken) return null;

    if (!silent) setTrackingLoading(true);
    setError('');

    try {
      const response = await fetch(buildApiUrl(`/tracking/order/${numericOrderId}/partner_latest`), {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${String(authToken || '').trim()}`,
        },
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(String(payload?.detail || `Tracking request failed (${response.status})`));

      setTracking(payload);
      return payload;
    } catch (fetchError) {
      const message = fetchError?.message || 'Could not load live rider state for this order.';
      setError(message);
      return null;
    } finally {
      if (!silent) setTrackingLoading(false);
    }
  };

  useEffect(() => {
    if (!order?.id || !authToken) return undefined;

    const subscription = subscribeOrderTimeline({ orderId: order.id, sinceId: 0, onError: () => {} });
    fetchTracking({ silent: false }).catch(() => {});

    if (!isLiveOrder(order)) {
      return () => subscription?.close?.();
    }

    const timer = setInterval(() => {
      fetchTracking({ silent: true }).catch(() => {});
      loadOrders({ silent: true }).catch(() => {});
    }, 12000);

    return () => {
      clearInterval(timer);
      subscription?.close?.();
    };
  }, [authToken, loadOrders, order, subscribeOrderTimeline]);

  const onRefresh = async () => {
    try {
      setRefreshing(true);
      await loadOrders({ silent: true });
      await fetchTracking({ silent: true });
      setNotice('Live order state refreshed.');
    } finally {
      setRefreshing(false);
    }
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.centerState}>
          <ActivityIndicator color={BrandPalette.primary} />
          <Text style={styles.centerStateText}>Preparing live order tracking…</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.centerState}>
          <Text style={styles.emptyTitle}>Sign in to track your order</Text>
          <Text style={styles.emptySubtitle}>The live tracking feed is available only for the authenticated customer who placed the order.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!order) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.trackingHeader}>
          <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={20} color={BrandPalette.text} />
          </TouchableOpacity>
          <View style={styles.trackingHeaderCopy}>
            <Text style={styles.trackingHeaderTitle}>GrabBasket live order</Text>
            <Text style={styles.trackingHeaderMeta}>Order #{orderId || '—'}</Text>
          </View>
        </View>
        <View style={styles.centerState}>
          <Text style={styles.emptyTitle}>Order not found</Text>
          <Text style={styles.emptySubtitle}>Pull the latest orders again or open this screen with a valid order id.</Text>
        </View>
      </SafeAreaView>
    );
  }

  const tone = getOrderTone(order);
  const hero = getTrackingHero(order, tracking);
  const heroColors = toneColors(hero.tone);
  const steps = buildSteps(order, events, tracking);
  const seller = getSellerSummary(order);
  const rider = getRiderSummary(order, tracking);
  const itemLabel = getOrderItemsLabel(order);
  const imageUri = String(order?.vendor_image_url || '').trim();
  const pickupPoint = normalizeCoordinate(vendor?.lat, vendor?.lng);
  const dropPoint = normalizeCoordinate(order?.delivery_lat, order?.delivery_lng);
  const riderPoint = normalizeCoordinate(
    tracking?.has_location ? tracking?.lat : null,
    tracking?.has_location ? tracking?.lng : null
  );
  const latestEvent = events[0];
  const dispatchSummary = trackingLoading
    ? 'Checking the latest rider state…'
    : tracking?.assigned
      ? tracking?.has_location
        ? `Live rider ping at ${formatDateTime(tracking?.ts || tracking?.created_at)}`
        : 'Rider assigned, waiting for first live location'
      : 'No rider assigned yet';
  const headerTitle = mapLegacyService(order?.service) === 'warehouse' ? 'GrabBasket instant order' : 'GrabBasket live order';

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
      <StatusBar barStyle="dark-content" />

      <View style={styles.trackingHeader}>
        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={20} color={BrandPalette.text} />
        </TouchableOpacity>

        <View style={styles.trackingHeaderCopy}>
          <Text style={styles.trackingHeaderTitle}>{headerTitle}</Text>
          <Text numberOfLines={1} style={styles.trackingHeaderMeta}>
            {formatDateTime(order?.created_at || order?.updated_at)} • {itemLabel}
          </Text>
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={onRefresh}>
          <Ionicons name="refresh-outline" size={18} color={BrandPalette.text} />
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 28, gap: 14 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={BrandPalette.primary} />}>
        {notice ? <InlineNoticeCard title="Updated" message={notice} onDismiss={() => setNotice('')} /> : null}
        {error ? <InlineErrorCard title="Tracking issue" message={error} /> : null}

        <View style={styles.trackingMapSection}>
          <LiveRouteIntelligenceCard
            orderId={order?.id}
            orderStatus={order?.status}
            pickupPoint={pickupPoint}
            dropPoint={dropPoint}
            riderPoint={riderPoint}
            pickupTitle="Store"
            pickupDescription={vendor?.name || order?.vendor_name || 'Store'}
            dropTitle="Delivery address"
            dropDescription={order?.delivery_address_label || 'Customer drop'}
            riderTitle="Delivery partner"
            riderDescription={
              tracking?.has_location
                ? `Updated ${formatDateTime(tracking?.ts || tracking?.created_at)}`
                : tracking?.assigned
                  ? 'Partner assigned, waiting for first live location'
                  : 'Partner not assigned yet'
            }
            emptyTitle="Map will appear once fulfilment coordinates are ready"
            emptySubtitle="Add store coordinates and delivery coordinates to mirror the Swiggy-style live tracking experience in GrabBasket."
            webTitle="Map preview is only available on iOS and Android."
            webSubtitle="The customer app can still open the active stop in the installed maps application."
            routeUnavailableMessage="Pickup or delivery coordinates are not available for this order yet."
          />
        </View>

        <View style={styles.trackingHeroCard}>
          <View style={styles.trackingHeroTopRow}>
            <View style={styles.trackingVendorIdentity}>
              {imageUri ? (
                <Image source={{ uri: imageUri }} style={styles.trackingVendorImage} />
              ) : (
                <View style={styles.trackingVendorFallback}>
                  <Text style={styles.trackingVendorFallbackText}>{initials(order?.vendor_name || 'GB')}</Text>
                </View>
              )}
              <View style={{ flex: 1 }}>
                <Text numberOfLines={1} style={styles.trackingVendorTitle}>{order?.vendor_name || 'Store'}</Text>
                <Text numberOfLines={1} style={styles.trackingVendorSubtitle}>{itemLabel}</Text>
              </View>
            </View>

            <View style={[styles.trackingEtaCard, { backgroundColor: heroColors.fg }]}>
              <Text style={styles.trackingEtaPrimary}>{hero.etaPrimary}</Text>
              {hero.etaSecondary ? <Text style={styles.trackingEtaSecondary}>{hero.etaSecondary}</Text> : null}
            </View>
          </View>

          <View style={styles.trackingPillRow}>
            <StatusPill tone={{ label: tone.label, fg: heroColors.fg, bg: heroColors.bg }} />
            {tracking?.assigned ? (
              <View style={styles.trackingLiveBadge}>
                <Ionicons name="radio-outline" size={13} color={BrandPalette.success} />
                <Text style={styles.trackingLiveBadgeText}>{tracking?.has_location ? 'Live rider ping' : 'Rider assigned'}</Text>
              </View>
            ) : null}
          </View>

          <Text style={styles.trackingHeroTitle}>{hero.title}</Text>
          <Text style={styles.trackingHeroSubtitle}>{hero.subtitle}</Text>

          {trackingLoading ? (
            <View style={styles.inlineRow}>
              <ActivityIndicator size="small" color={BrandPalette.primary} />
              <Text style={styles.inlineText}>Refreshing the latest rider movement…</Text>
            </View>
          ) : null}
        </View>

        <Surface>
          <View style={styles.rowBetween}>
            <Text style={styles.sectionTitle}>Delivery progress</Text>
            <Text style={styles.smallMuted}>{hero.progressLabel}</Text>
          </View>
          <Text style={styles.blockSubtitle}>{latestEvent?.note || hero.helperText}</Text>
          <StepRail steps={steps} />
        </Surface>

        <Surface>
          <Text style={styles.sectionTitle}>Live fulfilment state</Text>
          <StateCard {...seller} />
          <View style={{ height: 10 }} />
          <StateCard {...rider} />
        </Surface>

        <Surface>
          <Text style={styles.sectionTitle}>Dispatch visibility</Text>
          <MetaRow icon="storefront-outline" label="Seller" value={seller.title} />
          <MetaRow icon="bicycle-outline" label="Rider" value={rider.title} />
          <MetaRow icon="radio-outline" label="Dispatch feed" value={dispatchSummary} muted={!tracking?.assigned} />
          <MetaRow icon="card-outline" label="Payment" value={`${order?.payment_method || 'COD'} • ${prettyStatus(order?.payment_status || 'pending')}`} />
          <MetaRow
            icon="location-outline"
            label="Delivery address"
            value={order?.delivery_address_label || 'Saved address not attached yet'}
            muted={!order?.delivery_address_label}
          />
          <MetaRow
            icon="time-outline"
            label="ETA / distance"
            value={[
              hero.etaSecondary ? `${hero.etaPrimary} ${hero.etaSecondary}` : '',
              order?.delivery_distance_km ? `${Number(order.delivery_distance_km).toFixed(1)} km` : '',
            ].filter(Boolean).join(' • ') || 'ETA becomes clearer as seller and rider events arrive'}
            muted={!hero.etaSecondary && !order?.delivery_distance_km}
          />
        </Surface>

        <Surface>
          <Text style={styles.sectionTitle}>Order summary</Text>
          <MetaRow icon="receipt-outline" label="Items" value={itemLabel} />
          <MetaRow icon="cash-outline" label="Bill total" value={money(order?.total_amount || 0)} />
          <MetaRow icon="calendar-outline" label="Placed" value={formatDateTime(order?.created_at || order?.updated_at)} />
        </Surface>

        <Surface>
          <View style={styles.rowBetween}>
            <Text style={styles.sectionTitle}>Timeline</Text>
            <Text style={styles.smallMuted}>Newest first</Text>
          </View>
          <Timeline events={events} />
        </Surface>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: BrandPalette.background },
  surface: {
    borderRadius: 24,
    padding: 16,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 12,
    ...createShadow(0.08, 12, 6),
  },
  centerState: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24, gap: 10 },
  centerStateText: { fontSize: 14, color: BrandPalette.textMuted, textAlign: 'center' },
  trackingHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingTop: 6,
    paddingBottom: 10,
  },
  trackingHeaderCopy: { flex: 1 },
  trackingHeaderTitle: { fontSize: 22, fontWeight: '800', color: BrandPalette.text },
  trackingHeaderMeta: { marginTop: 4, fontSize: 13, color: BrandPalette.textMuted },
  iconButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  trackingMapSection: { gap: 12 },
  trackingHeroCard: {
    borderRadius: 26,
    padding: 16,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 12,
    ...createShadow(0.1, 16, 8),
  },
  trackingHeroTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  trackingVendorIdentity: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  trackingVendorImage: { width: 52, height: 52, borderRadius: 16, backgroundColor: BrandPalette.backgroundAlt },
  trackingVendorFallback: {
    width: 52,
    height: 52,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
  },
  trackingVendorFallbackText: { fontSize: 17, fontWeight: '800', color: BrandPalette.primary },
  trackingVendorTitle: { fontSize: 17, fontWeight: '800', color: BrandPalette.text },
  trackingVendorSubtitle: { marginTop: 3, fontSize: 13, color: BrandPalette.textMuted },
  trackingEtaCard: {
    minWidth: 84,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  trackingEtaPrimary: { fontSize: 28, lineHeight: 30, fontWeight: '900', color: BrandPalette.white },
  trackingEtaSecondary: { marginTop: 2, fontSize: 12, fontWeight: '800', color: BrandPalette.white, textTransform: 'lowercase' },
  trackingPillRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: 8 },
  trackingLiveBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 999,
    backgroundColor: BrandPalette.successSoft,
  },
  trackingLiveBadgeText: { fontSize: 12, fontWeight: '700', color: BrandPalette.success },
  trackingHeroTitle: { fontSize: 22, lineHeight: 28, fontWeight: '900', color: BrandPalette.text },
  trackingHeroSubtitle: { fontSize: 15, lineHeight: 22, color: BrandPalette.textMuted },
  statusPill: { alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 999 },
  statusPillText: { fontSize: 12, fontWeight: '800' },
  rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  smallMuted: { fontSize: 12, lineHeight: 18, color: BrandPalette.textSubtle },
  blockSubtitle: { fontSize: 13, lineHeight: 19, color: BrandPalette.textMuted },
  stepTop: { flexDirection: 'row', alignItems: 'center' },
  stepDot: { width: 12, height: 12, borderRadius: 6 },
  stepLine: { flex: 1, height: 2, marginLeft: 8, borderRadius: 999 },
  stepTitle: { fontSize: 13, fontWeight: '800' },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: BrandPalette.text },
  stateCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    borderRadius: 18,
    padding: 14,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  stateIcon: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
  stateTitle: { fontSize: 14, fontWeight: '800', color: BrandPalette.text },
  stateSubtitle: { marginTop: 4, fontSize: 13, lineHeight: 19, color: BrandPalette.textMuted },
  inlineRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  inlineText: { fontSize: 13, color: BrandPalette.textMuted },
  metaRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, paddingVertical: 8 },
  metaLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: BrandPalette.textSubtle,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  metaValue: { marginTop: 3, fontSize: 14, lineHeight: 20, color: BrandPalette.text },
  metaValueMuted: { color: BrandPalette.textMuted },
  emptyTimeline: {
    borderRadius: 16,
    padding: 14,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  emptyTitle: { fontSize: 16, fontWeight: '800', color: BrandPalette.text, textAlign: 'center' },
  emptySubtitle: { marginTop: 4, fontSize: 12, lineHeight: 18, color: BrandPalette.textMuted, textAlign: 'center' },
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, minHeight: 62 },
  timelineRail: { width: 18, alignItems: 'center' },
  timelineDot: { width: 10, height: 10, borderRadius: 5, marginTop: 5 },
  timelineLine: { width: 2, flex: 1, marginTop: 6, backgroundColor: BrandPalette.border },
  timelineTitle: { fontSize: 13, fontWeight: '800', color: BrandPalette.text },
  timelineMeta: { marginTop: 2, fontSize: 12, color: BrandPalette.textMuted },
  timelineNote: { marginTop: 4, fontSize: 12, lineHeight: 18, color: BrandPalette.text },
});