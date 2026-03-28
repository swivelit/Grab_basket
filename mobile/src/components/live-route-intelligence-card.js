import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import MapView, { Marker, Polyline } from 'react-native-maps';
import { Ionicons } from '@expo/vector-icons';

import InlineNoticeCard from './inline-notice-card';
import { requestJson } from '../lib/api-client';

const ROUTE_CACHE_TTL_MS = 2 * 60 * 1000;
const ROUTE_CACHE = new Map();

const COLORS = {
  page: '#FFF9F3',
  surface: '#FFFFFF',
  surfaceAlt: '#FFF6EC',
  border: '#F0D9C3',
  text: '#2F241C',
  muted: '#7A6758',
  brand: '#D97651',
  success: '#1F8F5F',
  successSoft: '#EAF8F0',
  info: '#2C69C9',
  warning: '#C57B12',
  warningSoft: '#FFF6DE',
  black: '#241A14',
};

function hasCoordinatePair(value) {
  return (
    value &&
    Number.isFinite(Number(value.latitude)) &&
    Number.isFinite(Number(value.longitude))
  );
}

function roundPoint(point, precision = 5) {
  if (!hasCoordinatePair(point)) return null;

  return {
    latitude: Number(Number(point.latitude).toFixed(precision)),
    longitude: Number(Number(point.longitude).toFixed(precision)),
  };
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

function getCurrentNavigationStop(orderStatus, pickupPoint, dropPoint) {
  return String(orderStatus || '').toUpperCase() === 'PICKED_UP' ? dropPoint : pickupPoint || dropPoint;
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

function formatDuration(value) {
  const totalSeconds = Math.max(0, Math.round(Number(value || 0)));
  if (!totalSeconds) return '—';

  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.round((totalSeconds % 3600) / 60);

  if (hours && minutes) return `${hours}h ${minutes}m`;
  if (hours) return `${hours}h`;
  return `${Math.max(1, minutes)}m`;
}

function formatDistanceMeters(value) {
  const meters = Math.max(0, Number(value || 0));
  if (!meters) return '—';
  if (meters < 1000) return `${Math.round(meters)} m`;
  return `${(meters / 1000).toFixed(meters >= 10000 ? 0 : 1)} km`;
}

function haversineDistanceMeters(start, end) {
  if (!hasCoordinatePair(start) || !hasCoordinatePair(end)) return 0;

  const toRad = (degrees) => (degrees * Math.PI) / 180;
  const earthRadiusMeters = 6371000;
  const deltaLat = toRad(end.latitude - start.latitude);
  const deltaLng = toRad(end.longitude - start.longitude);
  const lat1 = toRad(start.latitude);
  const lat2 = toRad(end.latitude);

  const a =
    Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
    Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2) * Math.cos(lat1) * Math.cos(lat2);

  return 2 * earthRadiusMeters * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function makeRouteCacheKey(plan) {
  return JSON.stringify({
    orderStatus: String(plan?.orderStatus || '').toUpperCase(),
    origin: roundPoint(plan?.origin),
    destination: roundPoint(plan?.destination),
    waypoints: Array.isArray(plan?.waypoints) ? plan.waypoints.map((point) => roundPoint(point)) : [],
  });
}

function buildRoutePlan({ orderId, orderStatus, riderPoint, pickupPoint, dropPoint }) {
  const status = String(orderStatus || '').toUpperCase();
  const hasRider = hasCoordinatePair(riderPoint);
  const hasPickup = hasCoordinatePair(pickupPoint);
  const hasDrop = hasCoordinatePair(dropPoint);

  if (status === 'PICKED_UP') {
    if (!hasDrop) return null;

    return {
      orderId: Number(orderId || 0),
      orderStatus: status,
      origin: riderPoint || pickupPoint || dropPoint,
      destination: dropPoint,
      waypoints: [],
      legLabels: ['To customer'],
      summaryLabel: 'To customer',
      etaLabel: 'ETA to drop',
    };
  }

  if (hasRider && hasPickup && hasDrop) {
    return {
      orderId: Number(orderId || 0),
      orderStatus: status,
      origin: riderPoint,
      destination: dropPoint,
      waypoints: [pickupPoint],
      legLabels: ['To pickup', 'Pickup → drop'],
      summaryLabel: 'Rider → pickup → drop',
      etaLabel: 'Total trip ETA',
    };
  }

  if (hasPickup && hasDrop) {
    return {
      orderId: Number(orderId || 0),
      orderStatus: status,
      origin: pickupPoint,
      destination: dropPoint,
      waypoints: [],
      legLabels: ['Pickup → drop'],
      summaryLabel: 'Pickup → drop',
      etaLabel: 'Trip ETA',
    };
  }

  if (hasRider && hasPickup) {
    return {
      orderId: Number(orderId || 0),
      orderStatus: status,
      origin: riderPoint,
      destination: pickupPoint,
      waypoints: [],
      legLabels: ['To pickup'],
      summaryLabel: 'Rider → pickup',
      etaLabel: 'ETA to pickup',
    };
  }

  if (hasRider && hasDrop) {
    return {
      orderId: Number(orderId || 0),
      orderStatus: status,
      origin: riderPoint,
      destination: dropPoint,
      waypoints: [],
      legLabels: ['Active leg'],
      summaryLabel: 'Rider → drop',
      etaLabel: 'ETA',
    };
  }

  return null;
}

function buildFallbackRoute(plan, reason = '') {
  const points = [plan?.origin, ...(plan?.waypoints || []), plan?.destination].filter(hasCoordinatePair);
  const legs = [];

  for (let index = 0; index < points.length - 1; index += 1) {
    const distanceMeters = haversineDistanceMeters(points[index], points[index + 1]);
    legs.push({
      key: `fallback-${index}`,
      label: plan?.legLabels?.[index] || `Leg ${index + 1}`,
      distanceMeters,
      durationSeconds: Math.max(60, Math.round(distanceMeters / 8.33)),
    });
  }

  const distanceMeters = legs.reduce((total, leg) => total + leg.distanceMeters, 0);
  const durationSeconds = legs.reduce((total, leg) => total + leg.durationSeconds, 0);

  return {
    source: 'fallback',
    isFallback: true,
    message: reason || 'Using a straight-line preview until the live route service responds.',
    coordinates: points,
    distanceMeters,
    durationSeconds,
    legs,
  };
}

async function getRoutePreview(plan) {
  if (!plan || !hasCoordinatePair(plan.origin) || !hasCoordinatePair(plan.destination)) {
    return null;
  }

  const cacheKey = makeRouteCacheKey(plan);
  const cached = ROUTE_CACHE.get(cacheKey);
  if (cached && Date.now() - cached.updatedAt < ROUTE_CACHE_TTL_MS) {
    return cached.data;
  }

  let routeData = null;
  try {
    const payload = await requestJson('/platform/dispatch/route-intelligence', {
      method: 'POST',
      body: {
        order_id: Number(plan?.orderId || 0),
        rider_lat: Number(plan?.origin?.latitude || 0),
        rider_lng: Number(plan?.origin?.longitude || 0),
      },
    });
    if (payload?.ok && Array.isArray(payload?.polyline_points) && payload.polyline_points.length >= 2) {
      routeData = {
        source: 'backend-route-intelligence',
        isFallback: false,
        message: '',
        coordinates: payload.polyline_points,
        distanceMeters: Number(payload.distance_km || 0) * 1000,
        durationSeconds: Math.max(60, Number(payload.eta_minutes || 0) * 60),
        legs: [
          {
            key: 'backend-0',
            label: plan?.summaryLabel || 'Active leg',
            distanceMeters: Number(payload.distance_km || 0) * 1000,
            durationSeconds: Math.max(60, Number(payload.eta_minutes || 0) * 60),
          },
        ],
      };
    }
  } catch {
    routeData = null;
  }

  if (!routeData) {
    routeData = buildFallbackRoute(plan, 'Live route unavailable. Showing a straight-line preview.');
  }

  ROUTE_CACHE.set(cacheKey, {
    updatedAt: Date.now(),
    data: routeData,
  });
  return routeData;
}

function RouteStatTile({ label, value, accent = COLORS.text }) {
  return (
    <View style={styles.routeStatTile}>
      <Text style={[styles.routeStatValue, { color: accent }]} numberOfLines={1}>
        {value}
      </Text>
      <Text style={styles.routeStatLabel}>{label}</Text>
    </View>
  );
}

export default function LiveRouteIntelligenceCard({
  orderId,
  orderStatus,
  pickupPoint,
  dropPoint,
  riderPoint,
  pickupTitle = 'Pickup',
  pickupDescription = 'Pickup point',
  dropTitle = 'Drop',
  dropDescription = 'Drop point',
  riderTitle = 'Rider',
  riderDescription = '',
  emptyTitle = 'Live map unavailable',
  emptySubtitle = 'Add pickup and drop coordinates to see a live route preview.',
  webTitle = 'Map preview is only available on iOS and Android.',
  webSubtitle = 'The app will still open the active stop in the installed maps application.',
  routeUnavailableMessage = 'Pickup or drop coordinates are not available for this order yet.',
}) {
  const mapRef = useRef(null);
  const [routePreview, setRoutePreview] = useState(null);
  const [routeLoading, setRouteLoading] = useState(false);
  const [routeError, setRouteError] = useState('');
  const [navigationNotice, setNavigationNotice] = useState(null);

  const navigationStop = useMemo(
    () => getCurrentNavigationStop(orderStatus, pickupPoint, dropPoint),
    [dropPoint, orderStatus, pickupPoint]
  );

  const routePlan = useMemo(
    () =>
      buildRoutePlan({
        orderId,
        orderStatus,
        riderPoint,
        pickupPoint,
        dropPoint,
      }),
    [dropPoint, orderId, orderStatus, pickupPoint, riderPoint]
  );

  useEffect(() => {
    let isMounted = true;

    if (!routePlan) {
      setRoutePreview(null);
      setRouteError('');
      setRouteLoading(false);
      return undefined;
    }

    setRouteLoading(true);

    getRoutePreview(routePlan)
      .then((data) => {
        if (!isMounted) return;
        setRoutePreview(data);
        setRouteError(data?.isFallback ? data?.message || 'Using a fallback preview.' : '');
      })
      .catch((error) => {
        if (!isMounted) return;
        const fallback = buildFallbackRoute(routePlan, error?.message || 'Could not load route preview.');
        setRoutePreview(fallback);
        setRouteError(fallback.message);
      })
      .finally(() => {
        if (isMounted) {
          setRouteLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [routePlan]);

  const routeCoordinates = useMemo(() => {
    const points = Array.isArray(routePreview?.coordinates) ? routePreview.coordinates.filter(hasCoordinatePair) : [];
    return points.length >= 2 ? points : [pickupPoint, dropPoint].filter(hasCoordinatePair);
  }, [dropPoint, pickupPoint, routePreview?.coordinates]);

  const mapRegion = useMemo(
    () => buildRegion(routeCoordinates.length ? routeCoordinates : [pickupPoint, dropPoint, riderPoint]),
    [dropPoint, pickupPoint, riderPoint, routeCoordinates]
  );

  useEffect(() => {
    if (Platform.OS === 'web' || !mapRef.current) {
      return undefined;
    }

    const pointsToFit = (routeCoordinates.length ? routeCoordinates : [pickupPoint, dropPoint, riderPoint]).filter(
      hasCoordinatePair
    );

    if (pointsToFit.length < 2) {
      return undefined;
    }

    const timer = setTimeout(() => {
      mapRef.current?.fitToCoordinates(pointsToFit, {
        animated: true,
        edgePadding: {
          top: 56,
          right: 40,
          bottom: 56,
          left: 40,
        },
      });
    }, 120);

    return () => clearTimeout(timer);
  }, [dropPoint, pickupPoint, riderPoint, routeCoordinates]);

  const openRoute = useCallback(async () => {
    const url = buildMapsUrl(navigationStop);
    if (!url) {
      setNavigationNotice({
        tone: 'warning',
        title: 'Route unavailable',
        message: routeUnavailableMessage,
      });
      return;
    }

    const supported = await Linking.canOpenURL(url).catch(() => false);
    if (!supported) {
      setNavigationNotice({
        tone: 'warning',
        title: 'Maps unavailable',
        message: 'No maps app was found to open the route.',
      });
      return;
    }

    setNavigationNotice(null);
    await Linking.openURL(url);
  }, [navigationStop, routeUnavailableMessage]);

  const primaryLeg = routePreview?.legs?.[0] || null;
  const totalDistanceLabel = formatDistanceMeters(routePreview?.distanceMeters);
  const totalDurationLabel = formatDuration(routePreview?.durationSeconds);

  if (!hasCoordinatePair(pickupPoint) && !hasCoordinatePair(dropPoint) && !hasCoordinatePair(riderPoint)) {
    return (
      <View style={styles.emptyState}>
        <Text style={styles.emptyStateTitle}>{emptyTitle}</Text>
        <Text style={styles.emptyStateSubtitle}>{emptySubtitle}</Text>
      </View>
    );
  }

  return (
    <View style={styles.wrap}>
      <View style={styles.mapWrap}>
        {Platform.OS === 'web' ? (
          <View style={styles.webMapFallback}>
            <Ionicons name="map-outline" size={22} color={COLORS.brand} />
            <Text style={styles.webMapFallbackTitle}>{webTitle}</Text>
            <Text style={styles.webMapFallbackSubtitle}>{webSubtitle}</Text>
            <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={openRoute}>
              <Ionicons name="navigate-outline" size={16} color={COLORS.text} />
              <Text style={styles.secondaryButtonText}>Open route</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            <MapView ref={mapRef} style={styles.map} initialRegion={mapRegion}>
              {hasCoordinatePair(pickupPoint) ? (
                <Marker coordinate={pickupPoint} title={pickupTitle} description={pickupDescription} pinColor={COLORS.warning} />
              ) : null}
              {hasCoordinatePair(dropPoint) ? (
                <Marker coordinate={dropPoint} title={dropTitle} description={dropDescription} pinColor={COLORS.success} />
              ) : null}
              {hasCoordinatePair(riderPoint) ? (
                <Marker coordinate={riderPoint} title={riderTitle} description={riderDescription} pinColor={COLORS.brand} />
              ) : null}
              {routeCoordinates.length >= 2 ? (
                <Polyline coordinates={routeCoordinates} strokeWidth={5} strokeColor={COLORS.black} />
              ) : null}
            </MapView>

            {routeLoading ? (
              <View style={styles.mapOverlayBadge}>
                <ActivityIndicator size="small" color={COLORS.text} />
                <Text style={styles.mapOverlayBadgeText}>Resolving live route…</Text>
              </View>
            ) : null}
          </>
        )}
      </View>

      {routePlan ? (
        <View style={styles.routeStatsGrid}>
          <RouteStatTile
            label={primaryLeg?.label || routePlan.etaLabel}
            value={primaryLeg ? formatDuration(primaryLeg.durationSeconds) : totalDurationLabel}
            accent={COLORS.brand}
          />
          <RouteStatTile label={routePlan.etaLabel} value={totalDurationLabel} accent={COLORS.info} />
          <RouteStatTile label="Distance left" value={totalDistanceLabel} accent={COLORS.success} />
        </View>
      ) : null}

      {routePreview?.legs?.length ? (
        <View style={styles.routeLegList}>
          {routePreview.legs.map((leg) => (
            <View key={leg.key} style={styles.routeLegRow}>
              <View style={styles.routeLegDot} />
              <View style={{ flex: 1 }}>
                <Text style={styles.routeLegTitle}>{leg.label}</Text>
                <Text style={styles.routeLegMeta}>
                  {formatDuration(leg.durationSeconds)} · {formatDistanceMeters(leg.distanceMeters)}
                </Text>
              </View>
            </View>
          ))}
        </View>
      ) : null}

      {routeError ? (
        <View style={styles.routeNotice}>
          <Ionicons name="information-circle-outline" size={16} color={COLORS.warning} />
          <Text style={styles.routeNoticeText}>{routeError}</Text>
        </View>
      ) : routePreview ? (
        <View style={styles.routeNoticeSuccess}>
          <Ionicons name="checkmark-circle-outline" size={16} color={COLORS.success} />
          <Text style={styles.routeNoticeSuccessText}>
            {routePreview.source === 'google-routes'
              ? 'Google Routes API is powering the in-app preview.'
              : routePreview.source === 'google-directions'
                ? 'Google Directions fallback is powering the in-app preview.'
                : 'Fallback route preview is active.'}
          </Text>
        </View>
      ) : null}

      {navigationNotice ? (
        <InlineNoticeCard
          tone={navigationNotice.tone}
          icon="navigate-outline"
          title={navigationNotice.title}
          message={navigationNotice.message}
          onDismiss={() => setNavigationNotice(null)}
        />
      ) : null}

      <View style={styles.bottomRow}>
        <View style={styles.flowBadge}>
          <Ionicons name="git-network-outline" size={15} color={COLORS.info} />
          <Text style={styles.flowBadgeText}>{routePlan ? routePlan.summaryLabel : 'Waiting for live route data'}</Text>
        </View>

        <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={openRoute}>
          <Ionicons name="navigate-outline" size={16} color={COLORS.text} />
          <Text style={styles.secondaryButtonText}>Open route</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    gap: 12,
  },
  mapWrap: {
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
  },
  map: {
    height: 250,
    width: '100%',
  },
  emptyState: {
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 18,
    paddingVertical: 18,
    gap: 6,
  },
  emptyStateTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.text,
  },
  emptyStateSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  webMapFallback: {
    minHeight: 250,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 22,
    paddingVertical: 24,
    gap: 10,
  },
  webMapFallbackTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.text,
    textAlign: 'center',
  },
  webMapFallbackSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
    textAlign: 'center',
  },
  mapOverlayBadge: {
    position: 'absolute',
    top: 12,
    left: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    backgroundColor: 'rgba(255, 249, 243, 0.94)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  mapOverlayBadgeText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.text,
  },
  routeStatsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  routeStatTile: {
    flexBasis: '31%',
    flexGrow: 1,
    minWidth: 94,
    borderRadius: 18,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 14,
    gap: 4,
  },
  routeStatValue: {
    fontSize: 18,
    fontWeight: '800',
  },
  routeStatLabel: {
    fontSize: 12,
    lineHeight: 17,
    color: COLORS.muted,
    fontWeight: '600',
  },
  routeLegList: {
    gap: 10,
  },
  routeLegRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 16,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 11,
  },
  routeLegDot: {
    width: 10,
    height: 10,
    borderRadius: 999,
    backgroundColor: COLORS.brand,
  },
  routeLegTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.text,
  },
  routeLegMeta: {
    marginTop: 3,
    fontSize: 12,
    color: COLORS.muted,
  },
  routeNotice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    borderRadius: 14,
    backgroundColor: COLORS.warningSoft,
    paddingHorizontal: 12,
    paddingVertical: 11,
  },
  routeNoticeText: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.warning,
    fontWeight: '600',
  },
  routeNoticeSuccess: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 14,
    backgroundColor: COLORS.successSoft,
    paddingHorizontal: 12,
    paddingVertical: 11,
  },
  routeNoticeSuccessText: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.success,
    fontWeight: '600',
  },
  bottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    flexWrap: 'wrap',
  },
  flowBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flexShrink: 1,
    borderRadius: 999,
    backgroundColor: '#EBF2FF',
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  flowBadgeText: {
    flexShrink: 1,
    fontSize: 12,
    lineHeight: 17,
    color: COLORS.info,
    fontWeight: '700',
  },
  secondaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  secondaryButtonText: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.text,
  },
});
