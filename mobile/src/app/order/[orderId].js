import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Linking,
  Platform,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
  useWindowDimensions,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import MapView, { Marker, Polyline } from 'react-native-maps';
import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { mapLegacyService } from '@/domains/grab-basket-utils';
import { buildApiUrl } from '../../config';
import InlineErrorCard from '../../components/inline-error-card';
import InlineNoticeCard from '../../components/inline-notice-card';
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
  { key: 'assigned', title: 'Partner assigned' },
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

function hasCoordinatePair(value) {
  return (
    value &&
    Number.isFinite(Number(value.latitude)) &&
    Number.isFinite(Number(value.longitude))
  );
}

function buildRegion(points = []) {
  const usable = points.filter(hasCoordinatePair);
  if (!usable.length) {
    return {
      latitude: 12.9716,
      longitude: 77.5946,
      latitudeDelta: 0.08,
      longitudeDelta: 0.08,
    };
  }

  const latitudes = usable.map((point) => Number(point.latitude));
  const longitudes = usable.map((point) => Number(point.longitude));
  const minLat = Math.min(...latitudes);
  const maxLat = Math.max(...latitudes);
  const minLng = Math.min(...longitudes);
  const maxLng = Math.max(...longitudes);

  return {
    latitude: (minLat + maxLat) / 2,
    longitude: (minLng + maxLng) / 2,
    latitudeDelta: Math.max(0.02, (maxLat - minLat) * 1.8 || 0.02),
    longitudeDelta: Math.max(0.02, (maxLng - minLng) * 1.8 || 0.02),
  };
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

function getOrderItemCount(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  const explicitCount = Number(order?.item_count || 0);
  const inferred = items.reduce((sum, item) => sum + Number(item?.qty || 1), 0);
  const total = explicitCount || inferred || items.length || 0;
  if (!total) return '0 items';
  return `${total} item${total > 1 ? 's' : ''}`;
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
    return { title: 'Seller handoff completed', subtitle: 'The delivery partner has already collected the order.', tone: 'success', icon: 'storefront-outline' };
  }
  if (status === 'ASSIGNED_TO_PARTNER') {
    return { title: 'Seller is preparing the order', subtitle: 'Dispatch has a partner while the store finishes prep.', tone: 'warning', icon: 'restaurant-outline' };
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
      title: 'Partner is on the way',
      subtitle: tracking?.has_location
        ? `Latest live ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`
        : 'The partner picked up the order and is heading to the drop.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (tracking?.assigned && tracking?.has_location) {
    return {
      title: 'Partner is live in the system',
      subtitle: `Latest live ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`,
      tone: 'success',
      icon: 'navigate-outline',
    };
  }
  if (status === 'ASSIGNED_TO_PARTNER' || tracking?.assigned) {
    return {
      title: 'Partner assigned',
      subtitle: 'Dispatch assigned a partner, waiting for pickup or the first live ping.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return { title: 'No partner handoff', subtitle: 'The order closed before delivery movement began.', tone: 'danger', icon: 'remove-circle-outline' };
  }
  return { title: 'Waiting for partner assignment', subtitle: 'Dispatch has not assigned a partner yet.', tone: 'info', icon: 'time-outline' };
}

function getPartnerLabel(order, tracking) {
  const explicit = String(order?.partner_name || tracking?.partner_name || '').trim();
  if (explicit) return explicit;

  const partnerId = Number(tracking?.partner_id || order?.partner_id || 0);
  if (partnerId > 0) return `Partner #${partnerId}`;
  if (tracking?.assigned) return 'GrabBasket partner';
  return 'Delivery partner';
}

function getPartnerPhone(order, vendor, tracking) {
  const value =
    order?.partner_phone ||
    tracking?.partner_phone ||
    vendor?.support_phone ||
    order?.support_phone ||
    order?.contact_phone ||
    '';
  return String(value || '').trim();
}

function getStageCopy(order, tracking = null) {
  const status = normalizeStatus(order?.status);
  const etaMinutes = deriveEtaMinutes(order, tracking);
  const partnerLabel = getPartnerLabel(order, tracking);

  if (status === 'DELIVERED') {
    return {
      title: 'Order delivered',
      subtitle: 'The final handoff is complete. You can still review the timeline and order details below.',
      tone: 'success',
      badge: 'Delivered',
      etaPrimary: 'Done',
      etaSecondary: '',
      helper: 'Delivery complete',
      showPartnerControls: false,
    };
  }

  if (status === 'PICKED_UP') {
    return {
      title: 'Out for delivery',
      subtitle: tracking?.has_location
        ? `${partnerLabel} is on the way to deliver your order. Latest live ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`
        : `${partnerLabel} picked up the order and is moving towards your address.`,
      tone: 'success',
      badge: 'Live trip',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Live',
      etaSecondary: etaMinutes ? 'mins' : '',
      helper: 'Partner → you',
      showPartnerControls: true,
    };
  }

  if (status === 'READY_FOR_PICKUP') {
    return {
      title: 'Order is packed!',
      subtitle: tracking?.assigned
        ? `${partnerLabel} is at the store and will pick up your order soon.`
        : 'Your basket is packed and waiting for partner pickup.',
      tone: 'success',
      badge: 'Packed',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helper: 'Store ready',
      showPartnerControls: Boolean(tracking?.assigned),
    };
  }

  if (status === 'ASSIGNED_TO_PARTNER') {
    return {
      title: 'Order is getting packed!',
      subtitle: tracking?.assigned
        ? `${partnerLabel} has been assigned while the store finishes packing your basket.`
        : 'A partner is being prepared while the store keeps packing the order.',
      tone: 'warning',
      badge: 'Partner assigned',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helper: 'Store + dispatch',
      showPartnerControls: Boolean(tracking?.assigned),
    };
  }

  if (status === 'ACCEPTED_BY_SELLER') {
    return {
      title: 'Order is getting packed!',
      subtitle: 'The store accepted your order and started assembling the basket for dispatch.',
      tone: 'warning',
      badge: 'Preparing',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helper: 'Seller preparing',
      showPartnerControls: false,
    };
  }

  if (['PAYMENT_PENDING', 'PAYMENT_VERIFIED', 'CREATED'].includes(status)) {
    return {
      title: 'Order is getting packed!',
      subtitle: 'We received the order. The store and dispatch systems are syncing the next fulfilment steps now.',
      tone: 'warning',
      badge: 'Order received',
      etaPrimary: etaMinutes ? String(etaMinutes) : 'Soon',
      etaSecondary: etaMinutes ? 'mins' : '',
      helper: 'Waiting for seller',
      showPartnerControls: false,
    };
  }

  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return {
      title: prettyStatus(status),
      subtitle: 'This order is closed, so live movement has stopped. Historical events are still available below.',
      tone: 'danger',
      badge: 'Closed',
      etaPrimary: '—',
      etaSecondary: 'closed',
      helper: 'Tracking stopped',
      showPartnerControls: false,
    };
  }

  return {
    title: 'Tracking will start soon',
    subtitle: 'We are waiting for the first fulfilment updates from the store and dispatch systems.',
    tone: 'info',
    badge: 'Starting',
    etaPrimary: etaMinutes ? String(etaMinutes) : 'Live',
    etaSecondary: etaMinutes ? 'mins' : '',
    helper: 'Waiting for updates',
    showPartnerControls: false,
  };
}

function shortenLocationLabel(value, fallback) {
  const source = String(value || '').trim();
  if (!source) return fallback;
  const first = source.split(',')[0]?.trim() || source;
  return first.length > 24 ? `${first.slice(0, 24)}…` : first;
}

function getMapPresentation(order, pickupPoint, dropPoint, riderPoint) {
  const status = normalizeStatus(order?.status);
  const pickup = hasCoordinatePair(pickupPoint) ? pickupPoint : null;
  const drop = hasCoordinatePair(dropPoint) ? dropPoint : null;
  const rider = hasCoordinatePair(riderPoint) ? riderPoint : null;

  if (status === 'PICKED_UP' || status === 'DELIVERED') {
    return {
      solidRoute: [pickup, rider, drop].filter(hasCoordinatePair),
      dashedRoute: [],
      emphasis: 'trip',
    };
  }

  if (status === 'READY_FOR_PICKUP') {
    return {
      solidRoute: [],
      dashedRoute: rider ? [rider, pickup].filter(hasCoordinatePair) : [pickup, drop].filter(hasCoordinatePair),
      emphasis: 'pickup',
    };
  }

  if (status === 'ASSIGNED_TO_PARTNER') {
    return {
      solidRoute: [],
      dashedRoute: rider ? [rider, pickup, drop].filter(hasCoordinatePair) : [pickup, drop].filter(hasCoordinatePair),
      emphasis: 'assignment',
    };
  }

  return {
    solidRoute: [],
    dashedRoute: [pickup, drop].filter(hasCoordinatePair),
    emphasis: 'prep',
  };
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
        <Text style={styles.emptySubtitle}>Seller, dispatch, partner, and delivery checkpoints will appear here.</Text>
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

function QuickActionButton({ icon, onPress, disabled = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      disabled={disabled}
      style={[styles.quickActionButton, disabled && styles.quickActionButtonDisabled]}
      onPress={onPress}>
      <Ionicons name={icon} size={20} color={disabled ? BrandPalette.textSubtle : BrandPalette.text} />
    </TouchableOpacity>
  );
}

function MapMarkerBubble({ icon, label }) {
  return (
    <View style={styles.markerWrap}>
      <View style={styles.markerBubble}>
        <Ionicons name={icon} size={18} color={BrandPalette.white} />
      </View>
      {label ? (
        <View style={styles.markerLabelBubble}>
          <Text numberOfLines={1} style={styles.markerLabelText}>{label}</Text>
        </View>
      ) : null}
    </View>
  );
}

function TrackingStage({
  stageHeight,
  insets,
  order,
  vendor,
  tracking,
  stageCopy,
  pickupPoint,
  dropPoint,
  riderPoint,
  dispatchSummary,
  itemLabel,
  orderTone,
  trackingLoading,
  onBack,
  onMenu,
  onViewItems,
  onCall,
  onMessage,
  callEnabled,
  messageEnabled,
}) {
  const mapRef = useRef(null);
  const tone = toneColors(stageCopy.tone);
  const mapPresentation = useMemo(
    () => getMapPresentation(order, pickupPoint, dropPoint, riderPoint),
    [dropPoint, order, pickupPoint, riderPoint]
  );

  const mapPoints = useMemo(
    () => [pickupPoint, dropPoint, riderPoint].filter(hasCoordinatePair),
    [dropPoint, pickupPoint, riderPoint]
  );

  const mapRegion = useMemo(() => buildRegion(mapPoints), [mapPoints]);

  useEffect(() => {
    if (Platform.OS === 'web' || !mapRef.current || mapPoints.length < 2) {
      return undefined;
    }

    const timer = setTimeout(() => {
      mapRef.current?.fitToCoordinates(mapPoints, {
        animated: true,
        edgePadding: {
          top: 80,
          right: 44,
          bottom: 220,
          left: 44,
        },
      });
    }, 140);

    return () => clearTimeout(timer);
  }, [mapPoints]);

  const imageUri = String(order?.vendor_image_url || '').trim();
  const partnerLabel = getPartnerLabel(order, tracking);
  const headerTitle = mapLegacyService(order?.service) === 'warehouse' ? 'GrabBasket instant order' : 'GrabBasket order';
  const showMap = Platform.OS !== 'web' && mapPoints.length > 0;

  return (
    <View style={[styles.stageShell, { minHeight: stageHeight }]}>
      {showMap ? (
        <MapView ref={mapRef} style={StyleSheet.absoluteFillObject} initialRegion={mapRegion}>
          {hasCoordinatePair(dropPoint) ? (
            <Marker coordinate={dropPoint} anchor={{ x: 0.16, y: 1 }}>
              <MapMarkerBubble icon="navigate" label={shortenLocationLabel(order?.delivery_address_label, 'Your place')} />
            </Marker>
          ) : null}

          {hasCoordinatePair(pickupPoint) ? (
            <Marker coordinate={pickupPoint} anchor={{ x: 0.16, y: 1 }}>
              <MapMarkerBubble icon="storefront-outline" label={shortenLocationLabel(vendor?.name || order?.vendor_name, 'Store')} />
            </Marker>
          ) : null}

          {hasCoordinatePair(riderPoint) && normalizeStatus(order?.status) !== 'DELIVERED' ? (
            <Marker coordinate={riderPoint} anchor={{ x: 0.16, y: 1 }}>
              <MapMarkerBubble icon="bicycle-outline" label={shortenLocationLabel(partnerLabel, 'Partner')} />
            </Marker>
          ) : null}

          {mapPresentation.dashedRoute.length >= 2 ? (
            <Polyline
              coordinates={mapPresentation.dashedRoute}
              strokeWidth={4}
              strokeColor={BrandPalette.black}
              lineDashPattern={[8, 8]}
            />
          ) : null}

          {mapPresentation.solidRoute.length >= 2 ? (
            <Polyline
              coordinates={mapPresentation.solidRoute}
              strokeWidth={5}
              strokeColor={BrandPalette.info}
            />
          ) : null}
        </MapView>
      ) : (
        <View style={styles.mapFallback}>
          <Ionicons name="map-outline" size={30} color={BrandPalette.primary} />
          <Text style={styles.mapFallbackTitle}>Live map will appear once coordinates are ready</Text>
          <Text style={styles.mapFallbackSubtitle}>
            Add store, rider, and delivery coordinates to mirror the Swiggy-style live tracking flow in GrabBasket.
          </Text>
        </View>
      )}

      <View pointerEvents="none" style={styles.stageScrim} />

      <View style={[styles.stageHeader, { paddingTop: Math.max(insets.top, 16) + 6 }]}>
        <TouchableOpacity activeOpacity={0.92} style={styles.stageIconButton} onPress={onBack}>
          <Ionicons name="arrow-back" size={20} color={BrandPalette.text} />
        </TouchableOpacity>

        <View style={styles.stageHeaderCenter}>
          <Text numberOfLines={1} style={styles.stageHeaderTitle}>{headerTitle}</Text>
          <Text numberOfLines={1} style={styles.stageHeaderMeta}>
            {formatDateTime(order?.created_at || order?.updated_at)} • {getOrderItemCount(order)}
          </Text>
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.stageIconButton} onPress={onMenu}>
          <Ionicons name="ellipsis-horizontal" size={18} color={BrandPalette.text} />
        </TouchableOpacity>
      </View>

      <View style={[styles.stageInfoStrip, { top: Math.max(insets.top, 16) + 86 }]}>
        <View style={styles.stageInfoChip}>
          <Ionicons name="radio-outline" size={14} color={tracking?.has_location ? BrandPalette.success : BrandPalette.warning} />
          <Text style={styles.stageInfoChipText}>{dispatchSummary}</Text>
        </View>
      </View>

      <View style={[styles.stageCardWrap, { paddingBottom: Math.max(insets.bottom, 14) + 10 }]}>
        <View style={styles.stageCard}>
          <View style={styles.stageCardTopRow}>
            <View style={styles.stageStoreIdentity}>
              {imageUri ? (
                <Image source={{ uri: imageUri }} style={styles.stageStoreImage} />
              ) : (
                <View style={styles.stageStoreFallback}>
                  <Text style={styles.stageStoreFallbackText}>{initials(order?.vendor_name || 'GB')}</Text>
                </View>
              )}
              <View style={{ flex: 1 }}>
                <Text numberOfLines={1} style={styles.stageStoreTitle}>{order?.vendor_name || 'Store'}</Text>
                <Text numberOfLines={1} style={styles.stageStoreSubtitle}>{itemLabel}</Text>
              </View>
            </View>

            <View style={[styles.etaBadge, { backgroundColor: tone.fg }]}>
              <Text style={styles.etaBadgePrimary}>{stageCopy.etaPrimary}</Text>
              {stageCopy.etaSecondary ? <Text style={styles.etaBadgeSecondary}>{stageCopy.etaSecondary}</Text> : null}
            </View>
          </View>

          <View style={styles.stagePillRow}>
            <StatusPill tone={{ label: orderTone.label, fg: tone.fg, bg: tone.bg }} />
            <View style={styles.liveFlowChip}>
              <Ionicons name="git-network-outline" size={14} color={BrandPalette.info} />
              <Text style={styles.liveFlowChipText}>{stageCopy.helper}</Text>
            </View>
          </View>

          <Text style={styles.stagePrimaryTitle}>{stageCopy.title}</Text>
          <Text style={styles.stagePrimarySubtitle}>{stageCopy.subtitle}</Text>

          {trackingLoading ? (
            <View style={styles.stageInlineRow}>
              <ActivityIndicator size="small" color={BrandPalette.primary} />
              <Text style={styles.stageInlineText}>Refreshing the latest live movement…</Text>
            </View>
          ) : null}

          <View style={styles.stageActionsRow}>
            <TouchableOpacity activeOpacity={0.94} style={styles.itemsButton} onPress={onViewItems}>
              <Text style={styles.itemsButtonText}>View item list</Text>
              <Ionicons name="chevron-forward" size={16} color={BrandPalette.text} />
            </TouchableOpacity>

            {stageCopy.showPartnerControls ? (
              <View style={styles.partnerActionsWrap}>
                <QuickActionButton icon="call-outline" onPress={onCall} disabled={!callEnabled} />
                <QuickActionButton icon="chatbubble-ellipses-outline" onPress={onMessage} disabled={!messageEnabled} />
              </View>
            ) : null}
          </View>
        </View>
      </View>
    </View>
  );
}

export default function CustomerLiveTrackingScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const orderId = String(params?.orderId || '').trim();
  const insets = useSafeAreaInsets();
  const { height: windowHeight } = useWindowDimensions();
  const scrollRef = useRef(null);
  const [detailsAnchorY, setDetailsAnchorY] = useState(0);
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
      const message = fetchError?.message || 'Could not load live partner state for this order.';
      setError(message);
      return null;
    } finally {
      if (!silent) setTrackingLoading(false);
    }
  };

  useEffect(() => {
    if (!order?.id || !authToken) return undefined;

    const subscription = subscribeOrderTimeline?.({ orderId: order.id, sinceId: 0, onError: () => {} });
    fetchTracking({ silent: false }).catch(() => {});

    if (!isLiveOrder(order)) {
      return () => subscription?.close?.();
    }

    const timer = setInterval(() => {
      fetchTracking({ silent: true }).catch(() => {});
      loadOrders?.({ silent: true }).catch(() => {});
    }, 12000);

    return () => {
      clearInterval(timer);
      subscription?.close?.();
    };
  }, [authToken, loadOrders, order, subscribeOrderTimeline]);

  const onRefresh = async () => {
    try {
      setRefreshing(true);
      await loadOrders?.({ silent: true });
      await fetchTracking({ silent: true });
      setNotice('Live order state refreshed.');
    } finally {
      setRefreshing(false);
    }
  };

  const scrollToDetails = () => {
    scrollRef.current?.scrollTo({
      y: Math.max(0, detailsAnchorY - 12),
      animated: true,
    });
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['bottom']}>
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
      <SafeAreaView style={styles.safeArea} edges={['bottom']}>
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
      <SafeAreaView style={styles.safeArea} edges={['bottom']}>
        <StatusBar barStyle="dark-content" />
        <View style={[styles.stageHeader, { paddingTop: Math.max(insets.top, 16) + 6 }]}>
          <TouchableOpacity activeOpacity={0.92} style={styles.stageIconButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={20} color={BrandPalette.text} />
          </TouchableOpacity>
          <View style={styles.stageHeaderCenter}>
            <Text style={styles.stageHeaderTitle}>GrabBasket order</Text>
            <Text style={styles.stageHeaderMeta}>Order #{orderId || '—'}</Text>
          </View>
          <View style={styles.stageIconButtonPlaceholder} />
        </View>
        <View style={styles.centerState}>
          <Text style={styles.emptyTitle}>Order not found</Text>
          <Text style={styles.emptySubtitle}>Pull the latest orders again or open this screen with a valid order id.</Text>
        </View>
      </SafeAreaView>
    );
  }

  const orderTone = getOrderTone(order);
  const stageCopy = getStageCopy(order, tracking);
  const steps = buildSteps(order, events, tracking);
  const seller = getSellerSummary(order);
  const rider = getRiderSummary(order, tracking);
  const itemLabel = getOrderItemsLabel(order);
  const pickupPoint = normalizeCoordinate(vendor?.lat, vendor?.lng);
  const dropPoint = normalizeCoordinate(order?.delivery_lat, order?.delivery_lng);
  const riderPoint = normalizeCoordinate(
    tracking?.has_location ? tracking?.lat : null,
    tracking?.has_location ? tracking?.lng : null
  );
  const latestEvent = events[0];
  const dispatchSummary = trackingLoading
    ? 'Checking the latest partner state…'
    : tracking?.assigned
      ? tracking?.has_location
        ? `Live ping at ${formatDateTime(tracking?.ts || tracking?.created_at)}`
        : 'Partner assigned, waiting for first live location'
      : 'No partner assigned yet';
  const detailsDistance = order?.delivery_distance_km ? `${Number(order.delivery_distance_km).toFixed(1)} km` : '';
  const partnerPhone = getPartnerPhone(order, vendor, tracking);
  const callEnabled = Boolean(partnerPhone) && stageCopy.showPartnerControls;
  const messageEnabled = stageCopy.showPartnerControls;
  const stageHeight = Math.max(windowHeight * 0.72, 580);

  const handleCall = async () => {
    if (!partnerPhone) {
      setNotice('Connect partner or support phone data to enable calling from the live tracking card.');
      return;
    }

    const url = `tel:${partnerPhone}`;
    const supported = await Linking.canOpenURL(url).catch(() => false);
    if (!supported) {
      setNotice('Calling is not available on this device right now.');
      return;
    }

    await Linking.openURL(url);
  };

  const handleMessage = () => {
    if (!stageCopy.showPartnerControls) {
      setNotice('Chat will appear once a delivery partner is assigned.');
      return;
    }

    setNotice('Use this slot for rider chat when your in-app messaging flow is ready.');
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['bottom']}>
      <StatusBar barStyle="dark-content" />

      <ScrollView
        ref={scrollRef}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={BrandPalette.primary} />}>
        <TrackingStage
          stageHeight={stageHeight}
          insets={insets}
          order={order}
          vendor={vendor}
          tracking={tracking}
          stageCopy={stageCopy}
          pickupPoint={pickupPoint}
          dropPoint={dropPoint}
          riderPoint={riderPoint}
          dispatchSummary={dispatchSummary}
          itemLabel={itemLabel}
          orderTone={orderTone}
          trackingLoading={trackingLoading}
          onBack={() => router.back()}
          onMenu={scrollToDetails}
          onViewItems={scrollToDetails}
          onCall={handleCall}
          onMessage={handleMessage}
          callEnabled={callEnabled}
          messageEnabled={messageEnabled}
        />

        <View style={styles.detailStack} onLayout={(event) => setDetailsAnchorY(event.nativeEvent.layout.y)}>
          {notice ? <InlineNoticeCard title="Updated" message={notice} onDismiss={() => setNotice('')} /> : null}
          {error ? <InlineErrorCard title="Tracking issue" message={error} /> : null}

          <Surface>
            <View style={styles.rowBetween}>
              <Text style={styles.sectionTitle}>Delivery progress</Text>
              <Text style={styles.smallMuted}>{stageCopy.badge}</Text>
            </View>
            <Text style={styles.blockSubtitle}>{latestEvent?.note || 'Keep the first viewport focused on map + status card, and push detailed breakdowns below it.'}</Text>
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
            <MetaRow icon="bicycle-outline" label="Partner" value={rider.title} />
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
                stageCopy.etaSecondary ? `${stageCopy.etaPrimary} ${stageCopy.etaSecondary}` : '',
                detailsDistance,
              ].filter(Boolean).join(' • ') || 'ETA becomes clearer as seller and rider events arrive'}
              muted={!stageCopy.etaSecondary && !detailsDistance}
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
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: BrandPalette.background },
  scrollContent: {
    paddingBottom: 28,
  },
  detailStack: {
    paddingHorizontal: 16,
    gap: 14,
    marginTop: 14,
  },
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
  stageShell: {
    backgroundColor: '#D9DDD9',
    position: 'relative',
  },
  stageScrim: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(20,18,16,0.04)',
  },
  stageHeader: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 5,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
  },
  stageHeaderCenter: { flex: 1, alignItems: 'center' },
  stageHeaderTitle: { fontSize: 22, fontWeight: '900', color: BrandPalette.text },
  stageHeaderMeta: { marginTop: 3, fontSize: 13, color: BrandPalette.textMuted },
  stageIconButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,249,243,0.96)',
  },
  stageIconButtonPlaceholder: {
    width: 42,
    height: 42,
  },
  stageInfoStrip: {
    position: 'absolute',
    left: 16,
    right: 16,
    zIndex: 4,
    alignItems: 'center',
  },
  stageInfoChip: {
    maxWidth: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    backgroundColor: 'rgba(255,249,243,0.96)',
    paddingHorizontal: 14,
    paddingVertical: 9,
  },
  stageInfoChipText: {
    flexShrink: 1,
    fontSize: 12,
    lineHeight: 17,
    color: BrandPalette.text,
    fontWeight: '700',
  },
  mapFallback: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 28,
    gap: 10,
    backgroundColor: '#E7E9E5',
  },
  mapFallbackTitle: {
    fontSize: 18,
    lineHeight: 24,
    fontWeight: '800',
    color: BrandPalette.text,
    textAlign: 'center',
  },
  mapFallbackSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  markerWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  markerBubble: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#23211F',
    borderWidth: 2,
    borderColor: BrandPalette.white,
  },
  markerLabelBubble: {
    maxWidth: 150,
    borderRadius: 12,
    backgroundColor: 'rgba(255,249,243,0.98)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  markerLabelText: {
    fontSize: 12,
    color: BrandPalette.text,
    fontWeight: '700',
  },
  stageCardWrap: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 0,
    zIndex: 5,
  },
  stageCard: {
    borderRadius: 30,
    padding: 18,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 12,
    ...createShadow(0.14, 18, 10),
  },
  stageCardTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  stageStoreIdentity: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  stageStoreImage: { width: 54, height: 54, borderRadius: 16, backgroundColor: BrandPalette.backgroundAlt },
  stageStoreFallback: {
    width: 54,
    height: 54,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
  },
  stageStoreFallbackText: { fontSize: 17, fontWeight: '900', color: BrandPalette.primary },
  stageStoreTitle: { fontSize: 17, fontWeight: '900', color: BrandPalette.text },
  stageStoreSubtitle: { marginTop: 3, fontSize: 13, color: BrandPalette.textMuted },
  etaBadge: {
    minWidth: 86,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  etaBadgePrimary: { fontSize: 28, lineHeight: 30, fontWeight: '900', color: BrandPalette.white },
  etaBadgeSecondary: { marginTop: 2, fontSize: 12, fontWeight: '800', color: BrandPalette.white, textTransform: 'lowercase' },
  stagePillRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: 8 },
  statusPill: { alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 999 },
  statusPillText: { fontSize: 12, fontWeight: '800' },
  liveFlowChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: BrandPalette.infoSoft,
  },
  liveFlowChipText: { fontSize: 12, fontWeight: '700', color: BrandPalette.info },
  stagePrimaryTitle: { fontSize: 22, lineHeight: 28, fontWeight: '900', color: BrandPalette.text },
  stagePrimarySubtitle: { fontSize: 15, lineHeight: 22, color: BrandPalette.textMuted },
  stageInlineRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  stageInlineText: { fontSize: 13, color: BrandPalette.textMuted },
  stageActionsRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  itemsButton: {
    flex: 1,
    minHeight: 48,
    borderRadius: 14,
    paddingHorizontal: 4,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  itemsButtonText: { fontSize: 17, fontWeight: '800', color: BrandPalette.text },
  partnerActionsWrap: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  quickActionButton: {
    width: 54,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  quickActionButtonDisabled: {
    opacity: 0.5,
  },
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