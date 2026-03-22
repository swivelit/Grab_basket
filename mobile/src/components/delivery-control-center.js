import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Platform,
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
import MapView, { Marker, Polyline } from 'react-native-maps';
import * as Location from 'expo-location';

import { useGrabBasket } from '../../App';
import { buildApiUrl } from '../config';
import { useCachedQuery } from '../lib/query-cache';

const DELIVERY_LOCATION_TASK_NAME = 'grab-basket-delivery-background-location';
const ACTIVE_ORDER_STATUSES = ['ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP', 'PICKED_UP'];
const FILTERS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'active', label: 'Active', icon: 'bicycle-outline' },
  { key: 'pickup', label: 'Pickup', icon: 'bag-check-outline' },
  { key: 'delivered', label: 'Delivered', icon: 'checkmark-circle-outline' },
];

const COLORS = {
  page: '#FFF9F3',
  surface: '#FFFFFF',
  surfaceAlt: '#FFF6EC',
  border: '#F0D9C3',
  line: '#F3E0CD',
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
};

function safeJsonParse(raw, fallback = null) {
  try {
    return raw ? JSON.parse(raw) : fallback;
  } catch {
    return fallback;
  }
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

function formatStatus(status = '') {
  return (
    String(status || '')
      .replace(/_/g, ' ')
      .toLowerCase()
      .replace(/\b\w/g, (character) => character.toUpperCase()) || 'Unknown'
  );
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

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function buildQueryString(params = {}) {
  const pairs = Object.entries(params)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);

  return pairs.length ? `?${pairs.join('&')}` : '';
}

function createHttpError(message, extras = {}) {
  const error = new Error(message);
  Object.entries(extras).forEach(([key, value]) => {
    error[key] = value;
  });
  return error;
}

async function request(path, token = '', { method = 'GET', body, query } = {}) {
  const response = await fetch(`${buildApiUrl(path)}${buildQueryString(query)}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body,
  });

  const raw = await response.text();
  const payload = safeJsonParse(raw, {});

  if (!response.ok) {
    const message =
      (typeof payload?.detail === 'string' && payload.detail.trim()) ||
      (typeof payload?.message === 'string' && payload.message.trim()) ||
      payload?.error?.message ||
      `Request failed with status ${response.status}`;

    throw createHttpError(message, { status: response.status, payload });
  }

  return payload;
}

function getLatestEvent(order) {
  const events = Array.isArray(order?.events) ? order.events : [];
  return events.length ? events[events.length - 1] : null;
}

function getStatusTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value === 'DELIVERED') {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle-outline' };
  }

  if (value === 'PICKED_UP') {
    return { bg: COLORS.infoSoft, text: COLORS.info, icon: 'navigate-outline' };
  }

  if (value.includes('READY') || value.includes('ASSIGNED')) {
    return { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'bag-check-outline' };
  }

  if (value.includes('CANCEL') || value.includes('REJECT')) {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'close-circle-outline' };
  }

  return { bg: COLORS.brandSoft, text: COLORS.brand, icon: 'time-outline' };
}

function summarizeOrder(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  if (!items.length) return 'No items added yet';

  const first = items[0];
  const extra = Math.max(0, items.length - 1);
  return `${Number(first?.qty || 1)} x ${first?.name_snapshot || first?.name || 'Item'}${
    extra ? ` +${extra} more` : ''
  }`;
}

function hasCoordinatePair(value) {
  return (
    value &&
    Number.isFinite(Number(value.latitude)) &&
    Number.isFinite(Number(value.longitude))
  );
}

function makeCoordinate(lat, lng) {
  const latitude = Number(lat);
  const longitude = Number(lng);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null;
  }

  return { latitude, longitude };
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

  const latitudes = usable.map((point) => point.latitude);
  const longitudes = usable.map((point) => point.longitude);
  const minLat = Math.min(...latitudes);
  const maxLat = Math.max(...latitudes);
  const minLng = Math.min(...longitudes);
  const maxLng = Math.max(...longitudes);

  return {
    latitude: (minLat + maxLat) / 2,
    longitude: (minLng + maxLng) / 2,
    latitudeDelta: Math.max(0.02, (maxLat - minLat) * 1.7 || 0.02),
    longitudeDelta: Math.max(0.02, (maxLng - minLng) * 1.7 || 0.02),
  };
}

function getCurrentNavigationStop(order, pickupPoint, dropPoint) {
  if (!order) return null;
  return String(order?.status || '').toUpperCase() === 'PICKED_UP' ? dropPoint : pickupPoint || dropPoint;
}

function matchesFilter(order, filterKey) {
  const status = String(order?.status || '').toUpperCase();

  switch (filterKey) {
    case 'active':
      return ACTIVE_ORDER_STATUSES.includes(status);
    case 'pickup':
      return status === 'ASSIGNED_TO_PARTNER' || status === 'READY_FOR_PICKUP';
    case 'delivered':
      return status === 'DELIVERED';
    default:
      return true;
  }
}

function filterOrders(list = [], search, filterKey) {
  const query = normalizeText(search);

  return (list || []).filter((order) => {
    if (!matchesFilter(order, filterKey)) {
      return false;
    }

    if (!query) return true;

    const searchText = [
      `order ${order?.id ?? ''}`,
      order?.status,
      order?.payment_method,
      order?.payment_status,
      summarizeOrder(order),
      getLatestEvent(order)?.note,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return searchText.includes(query);
  });
}

function buildMapsUrl(destination) {
  if (!hasCoordinatePair(destination)) return '';

  const lat = Number(destination.latitude).toFixed(6);
  const lng = Number(destination.longitude).toFixed(6);
  const encoded = `${lat},${lng}`;

  if (Platform.OS === 'ios') {
    return `http://maps.apple.com/?daddr=${encoded}&dirflg=d`;
  }

  return `https://www.google.com/maps/dir/?api=1&destination=${encoded}&travelmode=driving`;
}

function MetaLine({ icon, label, tone = COLORS.muted }) {
  return (
    <View style={styles.metaLine}>
      <Ionicons name={icon} size={15} color={tone} />
      <Text style={[styles.metaLineText, { color: tone }]}>{label}</Text>
    </View>
  );
}

function Chip({ label, active, icon, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      style={[styles.chip, active && styles.chipActive]}>
      <Ionicons name={icon} size={15} color={active ? COLORS.brand : COLORS.muted} />
      <Text style={[styles.chipText, active && styles.chipTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function SectionCard({ title, subtitle, right, children }) {
  return (
    <View style={styles.card}>
      {(title || subtitle || right) && (
        <View style={styles.cardHeader}>
          <View style={{ flex: 1 }}>
            {title ? <Text style={styles.cardTitle}>{title}</Text> : null}
            {subtitle ? <Text style={styles.cardSubtitle}>{subtitle}</Text> : null}
          </View>
          {right ? <View>{right}</View> : null}
        </View>
      )}
      {children}
    </View>
  );
}

function PrimaryButton({ label, icon, tone = 'brand', disabled = false, onPress }) {
  const palette =
    tone === 'success'
      ? { background: COLORS.success, text: '#FFFFFF', border: COLORS.success }
      : tone === 'muted'
        ? { background: '#FFFFFF', text: COLORS.text, border: COLORS.border }
        : { background: COLORS.brand, text: '#FFFFFF', border: COLORS.brand };

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      disabled={disabled}
      style={[
        styles.primaryButton,
        {
          backgroundColor: disabled ? '#E8DED5' : palette.background,
          borderColor: disabled ? '#E8DED5' : palette.border,
        },
      ]}
      onPress={onPress}>
      {icon ? <Ionicons name={icon} size={16} color={disabled ? '#907E70' : palette.text} /> : null}
      <Text style={[styles.primaryButtonText, { color: disabled ? '#907E70' : palette.text }]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function OrderRow({ order, active, onPickup, onDeliver, onNavigate }) {
  const tone = getStatusTone(order?.status);
  const isPickedUp = String(order?.status || '').toUpperCase() === 'PICKED_UP';
  const latestEvent = getLatestEvent(order);

  return (
    <View style={[styles.orderRow, active && styles.orderRowActive]}>
      <View style={styles.orderRowTop}>
        <View style={{ flex: 1 }}>
          <Text style={styles.orderTitle}>Order #{order?.id}</Text>
          <Text style={styles.orderSubtitle}>{summarizeOrder(order)}</Text>
        </View>

        <View style={[styles.pill, { backgroundColor: tone.bg }]}>
          <Ionicons name={tone.icon} size={14} color={tone.text} />
          <Text style={[styles.pillText, { color: tone.text }]}>{formatStatus(order?.status)}</Text>
        </View>
      </View>

      <View style={styles.metaList}>
        <MetaLine
          icon="cash-outline"
          label={`${money(order?.total_amount)} · ${String(order?.payment_method || 'COD').toUpperCase()}`}
        />
        <MetaLine
          icon="time-outline"
          label={formatDateTime(latestEvent?.created_at || order?.updated_at || order?.created_at)}
        />
      </View>

      <View style={styles.buttonRow}>
        <PrimaryButton label="Open route" icon="navigate-outline" tone="muted" onPress={onNavigate} />
        {isPickedUp ? (
          <PrimaryButton
            label="Complete delivery"
            icon="checkmark-circle-outline"
            tone="success"
            onPress={onDeliver}
          />
        ) : (
          <PrimaryButton
            label="Confirm pickup"
            icon="bag-check-outline"
            tone="brand"
            onPress={onPickup}
          />
        )}
      </View>
    </View>
  );
}

export default function DeliveryControlCenter() {
  const tabBarHeight = useBottomTabBarHeight();
  const { authToken, sessionReady, isAuthenticated, logout } = useGrabBasket();

  const [filterKey, setFilterKey] = useState('active');
  const [search, setSearch] = useState('');
  const [trackingEnabled, setTrackingEnabled] = useState(false);
  const [foregroundPermission, setForegroundPermission] = useState('unknown');
  const [backgroundPermission, setBackgroundPermission] = useState('unknown');
  const [working, setWorking] = useState(false);

  const canLoad = Boolean(sessionReady && isAuthenticated && authToken);

  const statusQuery = useCachedQuery({
    queryKey: ['delivery', 'status', authToken || 'guest'],
    enabled: canLoad,
    staleTime: 20 * 1000,
    cacheTime: 10 * 60 * 1000,
    fetcher: useCallback(() => request('/partner/status', authToken), [authToken]),
    initialData: null,
  });

  const ordersQuery = useCachedQuery({
    queryKey: ['delivery', 'orders', authToken || 'guest'],
    enabled: canLoad,
    staleTime: 20 * 1000,
    cacheTime: 10 * 60 * 1000,
    fetcher: useCallback(
      async () => {
        const response = await request('/partner/orders', authToken, {
          query: { limit: 100 },
        });
        return Array.isArray(response) ? response : [];
      },
      [authToken]
    ),
    initialData: [],
  });

  const statusData = statusQuery.data || null;
  const allOrders = Array.isArray(ordersQuery.data) ? ordersQuery.data : [];
  const visibleOrders = useMemo(() => filterOrders(allOrders, search, filterKey), [allOrders, filterKey, search]);
  const activeOrder = useMemo(
    () =>
      allOrders.find((order) =>
        ACTIVE_ORDER_STATUSES.includes(String(order?.status || '').toUpperCase())
      ) || null,
    [allOrders]
  );

  const vendorQuery = useCachedQuery({
    queryKey: ['delivery', 'vendor', activeOrder?.vendor_id || 'none'],
    enabled: Boolean(activeOrder?.vendor_id),
    staleTime: 5 * 60 * 1000,
    cacheTime: 30 * 60 * 1000,
    fetcher: useCallback(async () => {
      if (!activeOrder?.vendor_id) return null;
      return request(`/vendors/${activeOrder.vendor_id}`);
    }, [activeOrder?.vendor_id]),
    initialData: null,
  });

  const latestLocation = statusData?.latest_location || null;
  const pickupPoint = useMemo(
    () => makeCoordinate(vendorQuery.data?.lat, vendorQuery.data?.lng),
    [vendorQuery.data?.lat, vendorQuery.data?.lng]
  );
  const dropPoint = useMemo(
    () => makeCoordinate(activeOrder?.delivery_lat, activeOrder?.delivery_lng),
    [activeOrder?.delivery_lat, activeOrder?.delivery_lng]
  );
  const riderPoint = useMemo(
    () => makeCoordinate(latestLocation?.lat, latestLocation?.lng),
    [latestLocation?.lat, latestLocation?.lng]
  );
  const activeStop = useMemo(
    () => getCurrentNavigationStop(activeOrder, pickupPoint, dropPoint),
    [activeOrder, pickupPoint, dropPoint]
  );
  const mapRegion = useMemo(
    () => buildRegion([pickupPoint, dropPoint, riderPoint]),
    [dropPoint, pickupPoint, riderPoint]
  );

  const refreshPermissions = useCallback(async () => {
    try {
      const [foreground, background, started] = await Promise.all([
        Location.getForegroundPermissionsAsync(),
        Location.getBackgroundPermissionsAsync(),
        Location.hasStartedLocationUpdatesAsync(DELIVERY_LOCATION_TASK_NAME),
      ]);

      setForegroundPermission(String(foreground?.status || 'unknown'));
      setBackgroundPermission(String(background?.status || 'unknown'));
      setTrackingEnabled(Boolean(started));
    } catch {
      setForegroundPermission('unknown');
      setBackgroundPermission('unknown');
      setTrackingEnabled(false);
    }
  }, []);

  useEffect(() => {
    refreshPermissions().catch(() => {});
  }, [refreshPermissions]);

  const refreshEverything = useCallback(async () => {
    await Promise.all([statusQuery.refresh(), ordersQuery.refresh()]);
    if (activeOrder?.vendor_id) {
      await vendorQuery.refresh().catch(() => {});
    }
    await refreshPermissions();
  }, [activeOrder?.vendor_id, ordersQuery, refreshPermissions, statusQuery, vendorQuery]);

  const handleAuthFailure = useCallback(
    (error) => {
      if (Number(error?.status || 0) === 401) {
        logout().catch(() => {});
      }
    },
    [logout]
  );

  const requestPermissions = useCallback(async () => {
    const foreground = await Location.requestForegroundPermissionsAsync();
    if (foreground.status !== 'granted') {
      throw new Error('Foreground location permission is required for rider tracking.');
    }

    const background = await Location.requestBackgroundPermissionsAsync();
    if (background.status !== 'granted') {
      throw new Error('Background location permission is required for live delivery tracking.');
    }

    await refreshPermissions();
  }, [refreshPermissions]);

  const syncCurrentLocation = useCallback(async () => {
    try {
      setWorking(true);
      if (!authToken) {
        throw new Error('Please sign in again.');
      }

      const position = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Highest,
      });

      await request('/partner/location', authToken, {
        method: 'POST',
        body: JSON.stringify({
          lat: Number(position.coords.latitude),
          lng: Number(position.coords.longitude),
          heading:
            Number.isFinite(Number(position.coords.heading)) && Number(position.coords.heading) >= 0
              ? Number(position.coords.heading)
              : undefined,
          speed:
            Number.isFinite(Number(position.coords.speed)) && Number(position.coords.speed) >= 0
              ? Number(position.coords.speed)
              : undefined,
        }),
      });

      await statusQuery.refresh();
      await refreshPermissions();
    } catch (error) {
      handleAuthFailure(error);
      Alert.alert('Location sync failed', error?.message || 'Could not sync current location.');
    } finally {
      setWorking(false);
    }
  }, [authToken, handleAuthFailure, refreshPermissions, statusQuery]);

  const toggleBackgroundTracking = useCallback(async () => {
    try {
      setWorking(true);

      if (trackingEnabled) {
        await Location.stopLocationUpdatesAsync(DELIVERY_LOCATION_TASK_NAME);
        await refreshPermissions();
        return;
      }

      await requestPermissions();

      await Location.startLocationUpdatesAsync(DELIVERY_LOCATION_TASK_NAME, {
        accuracy: Location.Accuracy.BestForNavigation,
        distanceInterval: 25,
        timeInterval: 15000,
        deferredUpdatesDistance: 50,
        deferredUpdatesInterval: 30000,
        showsBackgroundLocationIndicator: true,
        pausesUpdatesAutomatically: false,
        activityType:
          Platform.OS === 'ios' ? Location.ActivityType.AutomotiveNavigation : undefined,
        foregroundService: {
          notificationTitle: 'Grab Basket Delivery tracking is active',
          notificationBody: 'Live rider location is being shared for active orders.',
          killServiceOnDestroy: false,
        },
      });

      await syncCurrentLocation();
      await refreshPermissions();
    } catch (error) {
      Alert.alert('Tracking update failed', error?.message || 'Could not change tracking mode.');
    } finally {
      setWorking(false);
    }
  }, [refreshPermissions, requestPermissions, syncCurrentLocation, trackingEnabled]);

  const openRoute = useCallback(async () => {
    const url = buildMapsUrl(activeStop);
    if (!url) {
      Alert.alert('Route unavailable', 'Pickup or drop coordinates are not available for this order yet.');
      return;
    }

    const supported = await Linking.canOpenURL(url).catch(() => false);
    if (!supported) {
      Alert.alert('Maps unavailable', 'No maps app was found to open the route.');
      return;
    }

    await Linking.openURL(url);
  }, [activeStop]);

  const runOrderAction = useCallback(
    async (path, successMessage) => {
      try {
        setWorking(true);
        await request(path, authToken, { method: 'POST' });
        await Promise.all([statusQuery.refresh(), ordersQuery.refresh()]);
        Alert.alert('Done', successMessage);
      } catch (error) {
        handleAuthFailure(error);
        Alert.alert('Action failed', error?.message || 'Please try again.');
      } finally {
        setWorking(false);
      }
    },
    [authToken, handleAuthFailure, ordersQuery, statusQuery]
  );

  const pickupOrder = useCallback(
    (orderId) => runOrderAction(`/partner/orders/${orderId}/pickup`, `Order #${orderId} marked as picked up.`),
    [runOrderAction]
  );

  const deliverOrder = useCallback(
    (orderId) => runOrderAction(`/partner/orders/${orderId}/deliver`, `Order #${orderId} marked as delivered.`),
    [runOrderAction]
  );

  const loading = statusQuery.isLoading || ordersQuery.isLoading;
  const refreshing = statusQuery.isFetching || ordersQuery.isFetching;
  const availabilityLocked = Boolean(statusData?.availability_locked);
  const summary = statusData?.summary || {};

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refreshEverything} />}>
        <View style={styles.screenHeader}>
          <Text style={styles.eyebrow}>Grab Basket Delivery App</Text>
          <Text style={styles.screenTitle}>Control center</Text>
          <Text style={styles.screenSubtitle}>
            Push assignment handling, live GPS tracking, in-app route preview, filters, cached queries,
            analytics-ready delivery flows.
          </Text>
        </View>

        <SectionCard
          title="Live delivery tracking"
          subtitle="Background GPS is wired to the existing /partner/location endpoint and the route card stays available in debug builds."
          right={
            <View
              style={[
                styles.inlineBadge,
                { backgroundColor: trackingEnabled ? COLORS.successSoft : COLORS.warningSoft },
              ]}>
              <Text
                style={[
                  styles.inlineBadgeText,
                  { color: trackingEnabled ? COLORS.success : COLORS.warning },
                ]}>
                {trackingEnabled ? 'Tracking on' : 'Tracking off'}
              </Text>
            </View>
          }>
          <View style={styles.mapWrap}>
            <MapView style={styles.map} initialRegion={mapRegion} region={mapRegion}>
              {pickupPoint ? <Marker coordinate={pickupPoint} title="Pickup" pinColor={COLORS.warning} /> : null}
              {dropPoint ? <Marker coordinate={dropPoint} title="Drop" pinColor={COLORS.success} /> : null}
              {riderPoint ? <Marker coordinate={riderPoint} title="You" pinColor={COLORS.brand} /> : null}
              {pickupPoint && dropPoint ? (
                <Polyline coordinates={[pickupPoint, dropPoint]} strokeWidth={4} strokeColor={COLORS.black} />
              ) : null}
            </MapView>
          </View>

          <View style={styles.metaList}>
            <MetaLine icon="locate-outline" label={`Foreground permission: ${foregroundPermission}`} />
            <MetaLine icon="navigate-outline" label={`Background permission: ${backgroundPermission}`} />
            <MetaLine
              icon="time-outline"
              label={
                latestLocation
                  ? `Last rider ping: ${formatDateTime(latestLocation.created_at)}`
                  : 'No rider location synced yet'
              }
            />
            <MetaLine
              icon="bag-check-outline"
              label={
                activeOrder
                  ? `Current active order: #${activeOrder.id} · ${formatStatus(activeOrder.status)}`
                  : 'No active order assigned right now'
              }
            />
          </View>

          <View style={styles.buttonRow}>
            <PrimaryButton
              label={trackingEnabled ? 'Stop tracking' : 'Start tracking'}
              icon={trackingEnabled ? 'pause-circle-outline' : 'play-circle-outline'}
              tone={trackingEnabled ? 'muted' : 'brand'}
              disabled={working}
              onPress={toggleBackgroundTracking}
            />
            <PrimaryButton
              label="Sync now"
              icon="locate-outline"
              tone="muted"
              disabled={working}
              onPress={syncCurrentLocation}
            />
          </View>

          <View style={styles.buttonRow}>
            <PrimaryButton
              label="Request permissions"
              icon="shield-checkmark-outline"
              tone="muted"
              disabled={working}
              onPress={() =>
                requestPermissions().catch((error) =>
                  Alert.alert('Permissions required', error?.message || 'Please allow location access.')
                )
              }
            />
            <PrimaryButton
              label="Open route"
              icon="navigate-outline"
              tone="success"
              disabled={working || !activeStop}
              onPress={openRoute}
            />
          </View>
        </SectionCard>

        <SectionCard
          title="Assignment overview"
          subtitle="Push order assignment is already wired; this screen shows the same live assignment state from the backend.">
          <View style={styles.kpiGrid}>
            <View style={styles.kpiTile}>
              <Text style={styles.kpiValue}>{Number(summary.active_order_count || 0)}</Text>
              <Text style={styles.kpiLabel}>Active orders</Text>
            </View>
            <View style={styles.kpiTile}>
              <Text style={styles.kpiValue}>{Number(summary.assigned_order_count || 0)}</Text>
              <Text style={styles.kpiLabel}>Pickup queue</Text>
            </View>
            <View style={styles.kpiTile}>
              <Text style={styles.kpiValue}>{Number(summary.delivered_order_count || 0)}</Text>
              <Text style={styles.kpiLabel}>Delivered</Text>
            </View>
            <View style={styles.kpiTile}>
              <Text style={styles.kpiValue}>{money(summary.cod_cash_collected || 0)}</Text>
              <Text style={styles.kpiLabel}>COD collected</Text>
            </View>
          </View>

          <View
            style={[
              styles.inlineBanner,
              availabilityLocked ? styles.inlineBannerWarning : styles.inlineBannerSuccess,
            ]}>
            <Ionicons
              name={availabilityLocked ? 'alert-circle-outline' : 'checkmark-circle-outline'}
              size={16}
              color={availabilityLocked ? COLORS.warning : COLORS.success}
            />
            <Text
              style={[
                styles.inlineBannerText,
                { color: availabilityLocked ? COLORS.warning : COLORS.success },
              ]}>
              {availabilityLocked
                ? 'Partner availability is locked while an active trip is running.'
                : 'No active trip is locking availability right now.'}
            </Text>
          </View>
        </SectionCard>

        <SectionCard
          title="Search, filters, and cached queue"
          subtitle="This uses stale-time and cache-time query behavior so the list feels react-query style without a backend hammer.">
          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder="Search order id, status, payment, items..."
            placeholderTextColor={COLORS.subtle}
            style={styles.searchInput}
          />

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
            {FILTERS.map((filter) => (
              <Chip
                key={filter.key}
                label={filter.label}
                icon={filter.icon}
                active={filter.key === filterKey}
                onPress={() => setFilterKey(filter.key)}
              />
            ))}
          </ScrollView>

          {loading ? (
            <View style={styles.loaderWrap}>
              <ActivityIndicator color={COLORS.brand} />
              <Text style={styles.loaderText}>Loading delivery queue…</Text>
            </View>
          ) : visibleOrders.length ? (
            <View style={styles.orderList}>
              {visibleOrders.map((order) => (
                <OrderRow
                  key={String(order.id)}
                  order={order}
                  active={String(activeOrder?.id) === String(order.id)}
                  onPickup={() => pickupOrder(order.id)}
                  onDeliver={() => deliverOrder(order.id)}
                  onNavigate={openRoute}
                />
              ))}
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="search-outline" size={24} color={COLORS.brand} />
              <Text style={styles.emptyTitle}>No orders match this view</Text>
              <Text style={styles.emptySubtitle}>
                Try a different filter, or pull to refresh to fetch a newer queue snapshot.
              </Text>
            </View>
          )}
        </SectionCard>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.page,
  },
  screenHeader: {
    paddingHorizontal: 18,
    paddingTop: 14,
    paddingBottom: 8,
    gap: 6,
  },
  eyebrow: {
    fontSize: 12,
    color: COLORS.brand,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  screenTitle: {
    fontSize: 28,
    fontWeight: '800',
    color: COLORS.text,
  },
  screenSubtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: COLORS.muted,
  },
  card: {
    marginHorizontal: 16,
    marginTop: 14,
    borderRadius: 22,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 14,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.text,
  },
  cardSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 20,
    color: COLORS.muted,
  },
  inlineBadge: {
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 999,
  },
  inlineBadgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  mapWrap: {
    borderRadius: 18,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.line,
  },
  map: {
    height: 220,
    width: '100%',
  },
  metaList: {
    gap: 10,
  },
  metaLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  metaLineText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
  },
  buttonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  primaryButton: {
    minHeight: 46,
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  primaryButtonText: {
    fontSize: 14,
    fontWeight: '700',
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  kpiTile: {
    flexBasis: '47%',
    flexGrow: 1,
    borderRadius: 18,
    backgroundColor: COLORS.surfaceAlt,
    padding: 14,
    gap: 6,
  },
  kpiValue: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.text,
  },
  kpiLabel: {
    fontSize: 12,
    color: COLORS.muted,
    fontWeight: '600',
  },
  inlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 14,
    paddingHorizontal: 12,
    paddingVertical: 11,
  },
  inlineBannerSuccess: {
    backgroundColor: COLORS.successSoft,
  },
  inlineBannerWarning: {
    backgroundColor: COLORS.warningSoft,
  },
  inlineBannerText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '600',
  },
  searchInput: {
    minHeight: 48,
    borderRadius: 16,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    fontSize: 14,
    color: COLORS.text,
  },
  chipRow: {
    paddingVertical: 2,
    gap: 8,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  chipActive: {
    backgroundColor: COLORS.brandSoft,
    borderColor: '#F3BCA1',
  },
  chipText: {
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  chipTextActive: {
    color: COLORS.brand,
  },
  loaderWrap: {
    alignItems: 'center',
    gap: 10,
    paddingVertical: 28,
  },
  loaderText: {
    color: COLORS.muted,
    fontSize: 13,
  },
  orderList: {
    gap: 12,
  },
  orderRow: {
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
    padding: 14,
    gap: 12,
  },
  orderRowActive: {
    backgroundColor: COLORS.brandSoft,
    borderColor: '#F4B99B',
  },
  orderRowTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  orderTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.text,
  },
  orderSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  pill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 999,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 30,
    gap: 8,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.text,
  },
  emptySubtitle: {
    maxWidth: 280,
    textAlign: 'center',
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
});