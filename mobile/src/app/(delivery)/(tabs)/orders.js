import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../../../App';
import { buildApiUrl } from '../../../config';

const TASK_NAME = 'grab-basket-delivery-background-location';
const LAST_BG_LOCATION_KEY = '@grab_basket/delivery/background_location_last_v1';
const ACTIVE_STATUSES = ['ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP', 'PICKED_UP'];
const DEFAULT_REGION = {
  latitude: 12.9716,
  longitude: 77.5946,
  latitudeDelta: 0.08,
  longitudeDelta: 0.08,
};

const COLORS = {
  page: '#FFF9F3',
  card: '#FFFFFF',
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
};

function parseJson(raw) {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function buildQuery(query = {}) {
  const pairs = Object.entries(query)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);

  return pairs.length ? `?${pairs.join('&')}` : '';
}

async function api(path, token, { method = 'GET', body, query } = {}) {
  const response = await fetch(`${buildApiUrl(path)}${buildQuery(query)}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body,
  });

  const raw = await response.text();
  const payload = parseJson(raw);

  if (!response.ok) {
    const message =
      (typeof payload?.detail === 'string' && payload.detail) ||
      payload?.error?.message ||
      `Request failed with status ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    throw error;
  }

  return payload;
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

function formatDistance(meters) {
  const value = Number(meters || 0);
  if (!Number.isFinite(value) || value <= 0) return '—';
  if (value >= 1000) return `${(value / 1000).toFixed(1)} km`;
  return `${Math.round(value)} m`;
}

function formatDuration(seconds) {
  const value = Number(seconds || 0);
  if (!Number.isFinite(value) || value <= 0) return '—';
  const hours = Math.floor(value / 3600);
  const minutes = Math.max(1, Math.round((value % 3600) / 60));
  return hours > 0 ? `${hours}h ${minutes}m` : `${minutes} min`;
}

function summarizeOrder(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  if (!items.length) return 'No items added yet';

  const first = items[0];
  const extra = Math.max(0, items.length - 1);
  return `${Number(first?.qty || 1)} x ${first?.name_snapshot || 'Item'}${extra ? ` +${extra} more` : ''}`;
}

function toCoordinate(lat, lng) {
  const latitude = Number(lat);
  const longitude = Number(lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return { latitude, longitude };
}

function decodePolyline(encoded = '') {
  const points = [];
  let index = 0;
  let lat = 0;
  let lng = 0;

  while (index < encoded.length) {
    let result = 0;
    let shift = 0;
    let byte = null;

    do {
      byte = encoded.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);

    lat += result & 1 ? ~(result >> 1) : result >> 1;

    result = 0;
    shift = 0;
    do {
      byte = encoded.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);

    lng += result & 1 ? ~(result >> 1) : result >> 1;
    points.push({ latitude: lat / 1e5, longitude: lng / 1e5 });
  }

  return points;
}

function buildRegion(points = []) {
  const valid = points.filter((item) => Number.isFinite(item?.latitude) && Number.isFinite(item?.longitude));
  if (!valid.length) return DEFAULT_REGION;
  if (valid.length === 1) {
    return {
      latitude: valid[0].latitude,
      longitude: valid[0].longitude,
      latitudeDelta: 0.02,
      longitudeDelta: 0.02,
    };
  }

  const lats = valid.map((item) => item.latitude);
  const lngs = valid.map((item) => item.longitude);
  const minLat = Math.min(...lats);
  const maxLat = Math.max(...lats);
  const minLng = Math.min(...lngs);
  const maxLng = Math.max(...lngs);

  return {
    latitude: (minLat + maxLat) / 2,
    longitude: (minLng + maxLng) / 2,
    latitudeDelta: Math.max(0.02, (maxLat - minLat) * 1.6),
    longitudeDelta: Math.max(0.02, (maxLng - minLng) * 1.6),
  };
}

async function getDirections(origin, destination, apiKey) {
  if (!origin || !destination) {
    return { coordinates: [], distanceMeters: null, durationSeconds: null, source: 'none' };
  }

  if (!apiKey) {
    return {
      coordinates: [origin, destination],
      distanceMeters: null,
      durationSeconds: null,
      source: 'fallback',
    };
  }

  try {
    const query = new URLSearchParams({
      origin: `${origin.latitude},${origin.longitude}`,
      destination: `${destination.latitude},${destination.longitude}`,
      mode: 'driving',
      key: apiKey,
    });

    const response = await fetch(`https://maps.googleapis.com/maps/api/directions/json?${query.toString()}`);
    const payload = await response.json();
    const route = Array.isArray(payload?.routes) ? payload.routes[0] : null;

    if (!route?.overview_polyline?.points) {
      return {
        coordinates: [origin, destination],
        distanceMeters: null,
        durationSeconds: null,
        source: 'fallback',
      };
    }

    const legs = Array.isArray(route.legs) ? route.legs : [];
    const totals = legs.reduce(
      (acc, leg) => ({
        distanceMeters: acc.distanceMeters + Number(leg?.distance?.value || 0),
        durationSeconds: acc.durationSeconds + Number(leg?.duration?.value || 0),
      }),
      { distanceMeters: 0, durationSeconds: 0 }
    );

    return {
      coordinates: decodePolyline(route.overview_polyline.points),
      distanceMeters: totals.distanceMeters,
      durationSeconds: totals.durationSeconds,
      source: 'directions',
    };
  } catch {
    return {
      coordinates: [origin, destination],
      distanceMeters: null,
      durationSeconds: null,
      source: 'fallback',
    };
  }
}

function Card({ title, subtitle, right, children }) {
  return (
    <View style={styles.card}>
      {(title || subtitle || right) && (
        <View style={styles.cardHeader}>
          <View style={{ flex: 1 }}>
            {title ? <Text style={styles.cardTitle}>{title}</Text> : null}
            {subtitle ? <Text style={styles.cardSubtitle}>{subtitle}</Text> : null}
          </View>
          {right || null}
        </View>
      )}
      {children}
    </View>
  );
}

function Pill({ text, icon, tone = 'brand' }) {
  const palette =
    tone === 'success'
      ? { bg: COLORS.successSoft, color: COLORS.success }
      : tone === 'info'
        ? { bg: COLORS.infoSoft, color: COLORS.info }
        : tone === 'warning'
          ? { bg: COLORS.warningSoft, color: COLORS.warning }
          : { bg: COLORS.brandSoft, color: COLORS.brand };

  return (
    <View style={[styles.pill, { backgroundColor: palette.bg }]}>
      <Ionicons name={icon} size={14} color={palette.color} />
      <Text style={[styles.pillText, { color: palette.color }]}>{text}</Text>
    </View>
  );
}

function Button({ label, icon, onPress, disabled = false, tone = 'brand' }) {
  const palette =
    tone === 'muted'
      ? { bg: '#FFFFFF', color: COLORS.text, border: COLORS.border }
      : tone === 'success'
        ? { bg: COLORS.success, color: '#FFFFFF', border: COLORS.success }
        : { bg: COLORS.brand, color: '#FFFFFF', border: COLORS.brand };

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      onPress={onPress}
      disabled={disabled}
      style={[
        styles.button,
        {
          backgroundColor: disabled ? '#E8DED5' : palette.bg,
          borderColor: disabled ? '#E8DED5' : palette.border,
        },
      ]}>
      {icon ? <Ionicons name={icon} size={16} color={disabled ? '#907E70' : palette.color} /> : null}
      <Text style={[styles.buttonText, { color: disabled ? '#907E70' : palette.color }]}>{label}</Text>
    </TouchableOpacity>
  );
}

function KeyValue({ icon, label, value }) {
  return (
    <View style={styles.keyValueRow}>
      <View style={styles.keyValueLeft}>
        <Ionicons name={icon} size={15} color={COLORS.subtle} />
        <Text style={styles.keyValueLabel}>{label}</Text>
      </View>
      <Text style={styles.keyValueValue}>{value}</Text>
    </View>
  );
}

export default function DeliveryOrdersScreen() {
  const { sessionReady, isAuthenticated, authToken, logout, appVariantName } = useGrabBasket();
  const tabBarHeight = useBottomTabBarHeight();
  const mapRef = useRef(null);
  const watchRef = useRef(null);

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [acting, setActing] = useState(false);
  const [taskAvailable, setTaskAvailable] = useState(false);
  const [trackingOn, setTrackingOn] = useState(false);
  const [partnerStatus, setPartnerStatus] = useState(null);
  const [orders, setOrders] = useState([]);
  const [vendors, setVendors] = useState({});
  const [selectedOrderId, setSelectedOrderId] = useState(null);
  const [currentLocation, setCurrentLocation] = useState(null);
  const [lastBackgroundLocation, setLastBackgroundLocation] = useState(null);
  const [route, setRoute] = useState({ coordinates: [], distanceMeters: null, durationSeconds: null, source: 'none' });

  const googleMapsApiKey =
    Constants?.expoConfig?.extra?.googleMaps?.apiKey ||
    process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY ||
    '';

  const activeOrders = useMemo(
    () => orders.filter((item) => ACTIVE_STATUSES.includes(String(item?.status || '').toUpperCase())),
    [orders]
  );

  const selectedOrder = useMemo(() => {
    if (!activeOrders.length) return null;
    return activeOrders.find((item) => item.id === selectedOrderId) || activeOrders[0] || null;
  }, [activeOrders, selectedOrderId]);

  const pickup = useMemo(() => {
    const vendor = selectedOrder ? vendors[selectedOrder.vendor_id] : null;
    return toCoordinate(vendor?.lat, vendor?.lng);
  }, [selectedOrder, vendors]);

  const drop = useMemo(
    () => toCoordinate(selectedOrder?.delivery_lat, selectedOrder?.delivery_lng),
    [selectedOrder]
  );

  const rider = useMemo(
    () => toCoordinate(currentLocation?.lat, currentLocation?.lng),
    [currentLocation]
  );

  const mapPoints = useMemo(
    () => [pickup, drop, rider, ...(Array.isArray(route.coordinates) ? route.coordinates : [])].filter(Boolean),
    [drop, pickup, rider, route.coordinates]
  );

  const fitMap = useCallback(() => {
    if (!mapRef.current || !mapPoints.length) return;
    if (mapPoints.length === 1) {
      mapRef.current.animateToRegion(buildRegion(mapPoints), 300);
      return;
    }

    mapRef.current.fitToCoordinates(mapPoints, {
      animated: true,
      edgePadding: { top: 70, right: 40, bottom: 70, left: 40 },
    });
  }, [mapPoints]);

  useEffect(() => {
    const timer = setTimeout(fitMap, 200);
    return () => clearTimeout(timer);
  }, [fitMap]);

  const syncTaskState = useCallback(async () => {
    if (Platform.OS === 'web') {
      setTaskAvailable(false);
      setTrackingOn(false);
      return;
    }

    try {
      const available = await TaskManager.isAvailableAsync();
      setTaskAvailable(Boolean(available));
      if (!available) {
        setTrackingOn(false);
        return;
      }
      setTrackingOn(await Location.hasStartedLocationUpdatesAsync(TASK_NAME));
    } catch {
      setTaskAvailable(false);
      setTrackingOn(false);
    }
  }, []);

  const loadLastBackgroundPoint = useCallback(async () => {
    try {
      const raw = await AsyncStorage.getItem(LAST_BG_LOCATION_KEY);
      const saved = parseJson(raw);
      if (saved?.lat != null && saved?.lng != null) {
        setLastBackgroundLocation(saved);
        setCurrentLocation((prev) => prev || saved);
      }
    } catch {
      // ignore storage issues
    }
  }, []);

  const startForegroundWatcher = useCallback(async () => {
    if (Platform.OS === 'web') return;

    const permission = await Location.getForegroundPermissionsAsync();
    if (permission.status !== 'granted') return;

    if (watchRef.current?.remove) {
      watchRef.current.remove();
      watchRef.current = null;
    }

    watchRef.current = await Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.BestForNavigation,
        timeInterval: 5000,
        distanceInterval: 8,
      },
      (update) => {
        if (!update?.coords) return;
        setCurrentLocation({
          lat: Number(update.coords.latitude),
          lng: Number(update.coords.longitude),
          heading:
            Number.isFinite(Number(update.coords.heading)) && Number(update.coords.heading) >= 0
              ? Number(update.coords.heading)
              : null,
          speed:
            Number.isFinite(Number(update.coords.speed)) && Number(update.coords.speed) >= 0
              ? Number(update.coords.speed)
              : null,
          created_at: new Date(update.timestamp || Date.now()).toISOString(),
        });
      }
    );
  }, []);

  const loadData = useCallback(async ({ silent = false } = {}) => {
    if (!authToken) return;
    if (!silent) setRefreshing(true);

    try {
      const [statusResponse, orderResponse] = await Promise.all([
        api('/partner/status', authToken),
        api('/partner/orders', authToken, { query: { active_only: true, limit: 50 } }),
      ]);

      const nextOrders = Array.isArray(orderResponse) ? orderResponse : [];
      setPartnerStatus(statusResponse || null);
      setOrders(nextOrders);

      if (statusResponse?.latest_location) {
        setCurrentLocation((prev) => prev || statusResponse.latest_location);
      }

      const vendorIds = Array.from(new Set(nextOrders.map((item) => item?.vendor_id).filter(Boolean)));
      const vendorRows = await Promise.all(
        vendorIds.map(async (vendorId) => {
          try {
            return [vendorId, await api(`/vendors/${vendorId}`, '')];
          } catch {
            return [vendorId, null];
          }
        })
      );
      setVendors(Object.fromEntries(vendorRows.filter(([, row]) => row)));

      setSelectedOrderId((prev) => {
        const ids = nextOrders.map((item) => item.id);
        if (prev && ids.includes(prev)) return prev;
        return ids[0] || null;
      });
    } catch (error) {
      Alert.alert(`${appVariantName} sync failed`, error?.message || 'Could not load rider operations.');
      if (error?.status === 401) logout().catch(() => {});
    } finally {
      if (!silent) {
        setRefreshing(false);
        setLoading(false);
      }
    }
  }, [appVariantName, authToken, logout]);

  const withAction = useCallback(async (work, successMessage = '') => {
    try {
      setActing(true);
      await work();
      if (successMessage) Alert.alert('Done', successMessage);
      await loadData({ silent: true });
      await syncTaskState();
    } catch (error) {
      Alert.alert('Action failed', error?.message || 'Please try again.');
      if (error?.status === 401) logout().catch(() => {});
    } finally {
      setActing(false);
    }
  }, [loadData, logout, syncTaskState]);

  const syncNow = useCallback(async () => {
    if (Platform.OS === 'web') {
      Alert.alert('Not supported', 'Live location needs a native Android or iOS build.');
      return;
    }

    const permission = await Location.requestForegroundPermissionsAsync();
    if (permission.status !== 'granted') {
      Alert.alert('Location permission needed', 'Allow location access and try again.');
      return;
    }

    const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.BestForNavigation });
    const payload = {
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
    };

    await withAction(async () => {
      await api('/partner/location', authToken, { method: 'POST', body: JSON.stringify(payload) });
      const saved = { ...payload, created_at: new Date(position.timestamp || Date.now()).toISOString() };
      setCurrentLocation(saved);
      setLastBackgroundLocation(saved);
      await AsyncStorage.setItem(LAST_BG_LOCATION_KEY, JSON.stringify(saved));
      await startForegroundWatcher();
    }, 'Rider location synced.');
  }, [authToken, startForegroundWatcher, withAction]);

  const startBackgroundTracking = useCallback(async () => {
    if (Platform.OS === 'web') {
      Alert.alert('Not supported', 'Background tracking needs a native Android or iOS build.');
      return;
    }

    const available = await TaskManager.isAvailableAsync();
    if (!available) {
      Alert.alert(
        'Development build required',
        'Background location is not available in this runtime. Use a dev build or release build instead of Expo Go.'
      );
      return;
    }

    const foreground = await Location.requestForegroundPermissionsAsync();
    if (foreground.status !== 'granted') {
      Alert.alert('Foreground location denied', 'Allow location access first.');
      return;
    }

    const background = await Location.requestBackgroundPermissionsAsync();
    if (background.status !== 'granted') {
      Alert.alert('Background location denied', 'Please allow always-on location in system settings.', [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Open settings', onPress: () => Linking.openSettings().catch(() => {}) },
      ]);
      return;
    }

    const started = await Location.hasStartedLocationUpdatesAsync(TASK_NAME);
    if (!started) {
      await Location.startLocationUpdatesAsync(TASK_NAME, {
        accuracy: Location.Accuracy.BestForNavigation,
        timeInterval: 15000,
        distanceInterval: 20,
        pausesUpdatesAutomatically: false,
        showsBackgroundLocationIndicator: true,
        activityType: Location.ActivityType.AutomotiveNavigation,
        foregroundService: {
          notificationTitle: 'Grab Basket delivery tracking is on',
          notificationBody: 'Your live rider location is being shared for active orders.',
          killServiceOnDestroy: false,
        },
      });
    }

    await startForegroundWatcher();
    await syncTaskState();
    await syncNow();
  }, [startForegroundWatcher, syncNow, syncTaskState]);

  const stopBackgroundTracking = useCallback(async () => {
    await withAction(async () => {
      const started = await Location.hasStartedLocationUpdatesAsync(TASK_NAME);
      if (started) await Location.stopLocationUpdatesAsync(TASK_NAME);
      if (watchRef.current?.remove) {
        watchRef.current.remove();
        watchRef.current = null;
      }
    }, 'Background tracking stopped.');
  }, [withAction]);

  const pickupOrder = useCallback((orderId) => {
    withAction(
      () => api(`/partner/orders/${orderId}/pickup`, authToken, { method: 'POST' }),
      `Order #${orderId} marked as picked up.`
    );
  }, [authToken, withAction]);

  const deliverOrder = useCallback((orderId) => {
    withAction(
      () => api(`/partner/orders/${orderId}/deliver`, authToken, { method: 'POST' }),
      `Order #${orderId} marked as delivered.`
    );
  }, [authToken, withAction]);

  useEffect(() => {
    loadLastBackgroundPoint().catch(() => {});
  }, [loadLastBackgroundPoint]);

  useEffect(() => {
    if (!sessionReady || !isAuthenticated || !authToken) return;
    loadData({ silent: false }).catch(() => {});
    syncTaskState().catch(() => {});
    startForegroundWatcher().catch(() => {});
  }, [authToken, isAuthenticated, loadData, sessionReady, startForegroundWatcher, syncTaskState]);

  useEffect(() => {
    let active = true;
    getDirections(pickup, drop, googleMapsApiKey).then((nextRoute) => {
      if (active) setRoute(nextRoute);
    });
    return () => {
      active = false;
    };
  }, [drop, googleMapsApiKey, pickup]);

  useEffect(() => {
    return () => {
      if (watchRef.current?.remove) {
        watchRef.current.remove();
        watchRef.current = null;
      }
    };
  }, []);

  if (!sessionReady || loading) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.center}>
          <ActivityIndicator color={COLORS.brand} />
          <Text style={styles.centerTitle}>Preparing {appVariantName}</Text>
          <Text style={styles.centerSub}>Loading live rider operations.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.center}>
          <Ionicons name="lock-closed-outline" size={28} color={COLORS.brand} />
          <Text style={styles.centerTitle}>Sign in required</Text>
          <Text style={styles.centerSub}>Use the delivery login flow before opening rider tracking.</Text>
        </View>
      </SafeAreaView>
    );
  }

  const latestPoint = partnerStatus?.latest_location || lastBackgroundLocation || currentLocation;
  const selectedVendor = selectedOrder ? vendors[selectedOrder.vendor_id] : null;
  const pickedUp = String(selectedOrder?.status || '').toUpperCase() === 'PICKED_UP';

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={[styles.content, { paddingBottom: tabBarHeight + 24 }]}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadData({ silent: false })} />}>
        <Card
          title="Live rider tracking"
          subtitle="Background GPS + pickup to drop map for the delivery app."
          right={
            trackingOn
              ? <Pill text="Tracking on" icon="radio-outline" tone="success" />
              : <Pill text="Tracking off" icon="pause-circle-outline" tone="warning" />
          }>
          {!taskAvailable ? (
            <View style={styles.warningBox}>
              <Ionicons name="construct-outline" size={16} color={COLORS.warning} />
              <Text style={styles.warningText}>
                Background location will not work in Expo Go. Use a dev build or release build.
              </Text>
            </View>
          ) : null}

          <View style={styles.buttonRow}>
            <Button
              label={trackingOn ? 'Tracking enabled' : 'Enable background tracking'}
              icon={trackingOn ? 'checkmark-circle-outline' : 'navigate-outline'}
              onPress={startBackgroundTracking}
              disabled={acting || trackingOn}
            />
            <Button
              label="Stop tracking"
              icon="pause-circle-outline"
              tone="muted"
              onPress={stopBackgroundTracking}
              disabled={acting || !trackingOn}
            />
            <Button
              label="Sync now"
              icon="locate-outline"
              tone="muted"
              onPress={syncNow}
              disabled={acting}
            />
          </View>

          <View style={styles.statRow}>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{activeOrders.length}</Text>
              <Text style={styles.statLabel}>Active trips</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{partnerStatus?.summary?.delivered_order_count ?? 0}</Text>
              <Text style={styles.statLabel}>Delivered</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{money(partnerStatus?.summary?.cod_cash_collected)}</Text>
              <Text style={styles.statLabel}>COD collected</Text>
            </View>
          </View>

          <KeyValue icon="time-outline" label="Latest sync" value={formatDateTime(latestPoint?.created_at)} />
          <KeyValue
            icon="navigate-outline"
            label="Coordinates"
            value={
              latestPoint?.lat != null && latestPoint?.lng != null
                ? `${Number(latestPoint.lat).toFixed(5)}, ${Number(latestPoint.lng).toFixed(5)}`
                : '—'
            }
          />
        </Card>

        <Card
          title="Pickup → drop map"
          subtitle={selectedOrder ? `Order #${selectedOrder.id}` : 'No active trip right now'}>
          {selectedOrder && !drop ? (
            <View style={styles.warningBox}>
              <Ionicons name="alert-circle-outline" size={16} color={COLORS.warning} />
              <Text style={styles.warningText}>
                Drop coordinates are missing in the order response. Add `delivery_lat` and `delivery_lng` to the backend schema below.
              </Text>
            </View>
          ) : null}

          <View style={styles.mapWrap}>
            <MapView
              ref={mapRef}
              style={styles.map}
              initialRegion={buildRegion(mapPoints)}
              provider={Platform.OS === 'android' ? PROVIDER_GOOGLE : undefined}>
              {pickup ? <Marker coordinate={pickup} title="Pickup" pinColor={COLORS.info} /> : null}
              {drop ? <Marker coordinate={drop} title="Drop" pinColor={COLORS.success} /> : null}
              {rider ? <Marker coordinate={rider} title="Rider" pinColor={COLORS.brand} /> : null}
              {route.coordinates.length > 1 ? (
                <Polyline coordinates={route.coordinates} strokeWidth={5} strokeColor={COLORS.brand} />
              ) : null}
            </MapView>
          </View>

          <View style={styles.pillRow}>
            <Pill
              text={route.source === 'directions' ? 'Road route' : route.source === 'fallback' ? 'Straight line fallback' : 'Waiting for route'}
              icon={route.source === 'directions' ? 'git-network-outline' : 'analytics-outline'}
              tone={route.source === 'directions' ? 'info' : 'warning'}
            />
            <Pill text={`Distance ${formatDistance(route.distanceMeters)}`} icon="resize-outline" tone="brand" />
            <Pill text={`ETA ${formatDuration(route.durationSeconds)}`} icon="time-outline" tone="success" />
          </View>

          {selectedOrder ? (
            <View style={styles.activeOrderBox}>
              <Text style={styles.activeOrderTitle}>Order #{selectedOrder.id}</Text>
              <Text style={styles.activeOrderSub}>{summarizeOrder(selectedOrder)}</Text>
              <KeyValue icon="storefront-outline" label="Pickup store" value={selectedVendor?.name || `Vendor #${selectedOrder.vendor_id}`} />
              <KeyValue icon="cash-outline" label="Order total" value={money(selectedOrder.total_amount)} />
              <KeyValue icon="location-outline" label="Pickup" value={pickup ? `${pickup.latitude.toFixed(5)}, ${pickup.longitude.toFixed(5)}` : '—'} />
              <KeyValue icon="flag-outline" label="Drop" value={drop ? `${drop.latitude.toFixed(5)}, ${drop.longitude.toFixed(5)}` : '—'} />
              <View style={styles.buttonRow}>
                <Button
                  label={pickedUp ? 'Mark delivered' : 'Mark picked up'}
                  icon={pickedUp ? 'checkmark-done-outline' : 'bag-check-outline'}
                  tone={pickedUp ? 'success' : 'brand'}
                  onPress={() => (pickedUp ? deliverOrder(selectedOrder.id) : pickupOrder(selectedOrder.id))}
                  disabled={acting}
                />
              </View>
            </View>
          ) : null}
        </Card>

        <Card title="Active order queue" subtitle="Tap any trip to switch the map above.">
          {activeOrders.length ? activeOrders.map((order) => {
            const selected = order.id === selectedOrder?.id;
            const orderPickedUp = String(order.status || '').toUpperCase() === 'PICKED_UP';
            const vendor = vendors[order.vendor_id] || null;

            return (
              <TouchableOpacity
                key={order.id}
                activeOpacity={0.92}
                onPress={() => setSelectedOrderId(order.id)}
                style={[styles.orderCard, selected && styles.orderCardSelected]}>
                <View style={styles.orderHead}>
                  <View>
                    <Text style={styles.orderTitle}>Order #{order.id}</Text>
                    <Text style={styles.orderSub}>{vendor?.name || `Vendor #${order.vendor_id}`}</Text>
                  </View>
                  <Pill
                    text={String(order.status || '').replace(/_/g, ' ')}
                    icon={orderPickedUp ? 'checkmark-circle-outline' : 'bicycle-outline'}
                    tone={orderPickedUp ? 'success' : 'info'}
                  />
                </View>
                <Text style={styles.orderSummary}>{summarizeOrder(order)}</Text>
                <View style={styles.orderMeta}>
                  <Text style={styles.orderMetaText}>Total {money(order.total_amount)}</Text>
                  <Text style={styles.orderMetaText}>
                    Drop {order.delivery_lat != null && order.delivery_lng != null ? 'available' : 'missing'}
                  </Text>
                </View>
              </TouchableOpacity>
            );
          }) : (
            <View style={styles.emptyBox}>
              <Ionicons name="map-outline" size={24} color={COLORS.brand} />
              <Text style={styles.emptyTitle}>No active deliveries</Text>
              <Text style={styles.emptySub}>Assigned delivery trips will appear here automatically.</Text>
            </View>
          )}
        </Card>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: COLORS.page },
  scroll: { flex: 1, backgroundColor: COLORS.page },
  content: { padding: 16, gap: 14 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24, gap: 10 },
  centerTitle: { fontSize: 20, fontWeight: '800', color: COLORS.text },
  centerSub: { fontSize: 14, lineHeight: 20, textAlign: 'center', color: COLORS.muted },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 12,
  },
  cardHeader: { flexDirection: 'row', gap: 12, justifyContent: 'space-between' },
  cardTitle: { fontSize: 18, fontWeight: '800', color: COLORS.text },
  cardSubtitle: { marginTop: 4, fontSize: 13, lineHeight: 18, color: COLORS.muted },
  warningBox: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'flex-start',
    backgroundColor: COLORS.warningSoft,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#F5DEB6',
    padding: 12,
  },
  warningText: { flex: 1, fontSize: 13, lineHeight: 18, color: COLORS.text },
  pill: { flexDirection: 'row', alignItems: 'center', gap: 6, borderRadius: 999, paddingHorizontal: 10, paddingVertical: 7 },
  pillText: { fontSize: 12, fontWeight: '800' },
  buttonRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  button: {
    minHeight: 44,
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  buttonText: { fontSize: 14, fontWeight: '800' },
  statRow: { flexDirection: 'row', gap: 10 },
  statCard: {
    flex: 1,
    backgroundColor: '#FFF6EC',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.line,
    paddingVertical: 12,
    paddingHorizontal: 10,
  },
  statValue: { fontSize: 20, fontWeight: '800', color: COLORS.text },
  statLabel: { marginTop: 4, fontSize: 12, color: COLORS.muted },
  keyValueRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  keyValueLeft: { flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 },
  keyValueLabel: { fontSize: 13, color: COLORS.muted },
  keyValueValue: { flex: 1, fontSize: 13, fontWeight: '700', textAlign: 'right', color: COLORS.text },
  mapWrap: {
    borderRadius: 18,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: '#F7F1EB',
  },
  map: { width: '100%', height: 320 },
  pillRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  activeOrderBox: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.line,
    backgroundColor: '#FFF6EC',
    padding: 14,
    gap: 10,
  },
  activeOrderTitle: { fontSize: 18, fontWeight: '800', color: COLORS.text },
  activeOrderSub: { fontSize: 13, color: COLORS.muted },
  orderCard: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    padding: 14,
    gap: 10,
  },
  orderCardSelected: { borderColor: COLORS.brand, backgroundColor: COLORS.brandSoft },
  orderHead: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  orderTitle: { fontSize: 16, fontWeight: '800', color: COLORS.text },
  orderSub: { marginTop: 4, fontSize: 13, color: COLORS.muted },
  orderSummary: { fontSize: 13, lineHeight: 18, color: COLORS.text },
  orderMeta: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  orderMetaText: { fontSize: 12, color: COLORS.subtle },
  emptyBox: { alignItems: 'center', justifyContent: 'center', paddingVertical: 18, gap: 8 },
  emptyTitle: { fontSize: 16, fontWeight: '800', color: COLORS.text },
  emptySub: { fontSize: 13, textAlign: 'center', lineHeight: 18, color: COLORS.muted },
});