import React, { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import { Stack, useGlobalSearchParams, usePathname } from 'expo-router';
import * as TaskManager from 'expo-task-manager';

import { GrabBasketProvider } from '../../App';
import PushNotificationBootstrap from '../components/push-notification-bootstrap';
import { getAppVariant } from '../constants/app-shell';
import { apiPost } from '../lib/api-client';
import { STORAGE_KEYS, readStoredValue, writeStoredJsonValue } from '../lib/storage';
import {
  captureEvent,
  captureException,
  flushTelemetry,
  identifyUser,
  resetTelemetryUser,
  trackScreen,
  wrapWithSentry,
} from '../lib/telemetry';
import { evaluateReleaseGovernance } from '../lib/release-governance';
import { ANALYTICS_TAXONOMY_VERSION } from '../constants/analytics-taxonomy';

const APP_VARIANT = getAppVariant();
const IS_NATIVE_RUNTIME = Platform.OS === 'android' || Platform.OS === 'ios';
const IS_DELIVERY_APP = APP_VARIANT === 'delivery';
const DELIVERY_LOCATION_TASK_NAME = 'grab-basket-delivery-background-location';

type StoredLocationPayload = {
  lat: number;
  lng: number;
  heading?: number;
  speed?: number;
  created_at?: string;
  source?: string;
};

type BackgroundLocation = {
  coords?: {
    latitude?: number;
    longitude?: number;
    heading?: number;
    speed?: number;
  };
  timestamp?: number;
};

type DeliveryLocationTaskPayload = {
  locations?: BackgroundLocation[];
};

async function persistLastBackgroundLocation(payload: StoredLocationPayload) {
  await writeStoredJsonValue(STORAGE_KEYS.lastBackgroundLocation, payload).catch(() => {
    // Best effort only.
  });
}

function toFiniteNumber(value: unknown) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function buildLocationPayload(location: BackgroundLocation) {
  const latitude = toFiniteNumber(location?.coords?.latitude);
  const longitude = toFiniteNumber(location?.coords?.longitude);

  if (latitude === null || longitude === null) {
    return null;
  }

  const heading = toFiniteNumber(location?.coords?.heading);
  const speed = toFiniteNumber(location?.coords?.speed);

  const payload: StoredLocationPayload = {
    lat: latitude,
    lng: longitude,
  };

  if (heading !== null && heading >= 0) {
    payload.heading = heading;
  }

  if (speed !== null && speed >= 0) {
    payload.speed = speed;
  }

  return payload;
}

async function hydrateTelemetryUser() {
  try {
    const [email, role] = await Promise.all([
      readStoredValue(STORAGE_KEYS.authEmail),
      readStoredValue(STORAGE_KEYS.authRole),
    ]);

    const normalizedEmail = String(email || '').trim().toLowerCase();

    if (!normalizedEmail) {
      resetTelemetryUser();
      return;
    }

    identifyUser(normalizedEmail, {
      email: normalizedEmail,
      role: String(role || '').trim().toUpperCase(),
      app_variant: APP_VARIANT,
    });
  } catch (error) {
    captureException(error, {
      tags: {
        area: 'telemetry',
        operation: 'hydrate-user',
      },
    });
  }
}

async function postPartnerLocation(token: string, payload: StoredLocationPayload) {
  await apiPost('/partner/location', payload, {
    token,
    timeoutMs: 10000,
  });
}

function registerDeliveryLocationTask() {
  if (!IS_DELIVERY_APP || !IS_NATIVE_RUNTIME) {
    return;
  }

  try {
    if (TaskManager.isTaskDefined(DELIVERY_LOCATION_TASK_NAME)) {
      return;
    }

    TaskManager.defineTask(
      DELIVERY_LOCATION_TASK_NAME,
      async (taskBody) => {
        const data = taskBody?.data as DeliveryLocationTaskPayload | undefined;
        const error = taskBody?.error;

        if (!IS_DELIVERY_APP) {
          return;
        }

        if (error) {
          captureException(error, {
            tags: {
              area: 'background-task',
              operation: 'delivery-location',
            },
            extras: {
              taskName: DELIVERY_LOCATION_TASK_NAME,
            },
          });
          return;
        }

        const locations = Array.isArray(data?.locations) ? data.locations : [];
        const latestLocation = locations[locations.length - 1];
        const payload = latestLocation ? buildLocationPayload(latestLocation) : null;

        if (!payload) {
          return;
        }

        const token = String((await readStoredValue(STORAGE_KEYS.authToken)) || '').trim();
        if (!token) {
          return;
        }

        try {
          await postPartnerLocation(token, payload);
          await persistLastBackgroundLocation({
            ...payload,
            created_at: new Date(latestLocation?.timestamp || Date.now()).toISOString(),
            source: 'background-task',
          });
        } catch (taskError) {
          captureException(taskError, {
            tags: {
              area: 'background-task',
              operation: 'partner-location-sync',
            },
            extras: {
              payload,
              taskName: DELIVERY_LOCATION_TASK_NAME,
            },
          });
        }
      }
    );
  } catch (error) {
    captureException(error, {
      tags: {
        area: 'background-task',
        operation: 'register-delivery-location-task',
      },
      extras: {
        taskName: DELIVERY_LOCATION_TASK_NAME,
        appVariant: APP_VARIANT,
        platform: Platform.OS,
      },
    });
  }
}

registerDeliveryLocationTask();

function TelemetryBootstrap() {
  const pathname = usePathname();
  const searchParams = useGlobalSearchParams();
  const initialPathRef = useRef(pathname || '/');

  useEffect(() => {
    hydrateTelemetryUser();
    captureEvent('app_opened', {
      app_variant: APP_VARIANT,
      initial_path: initialPathRef.current,
    });

    const governance = evaluateReleaseGovernance();
    captureEvent('release_governance_evaluated', {
      taxonomy_version: ANALYTICS_TAXONOMY_VERSION,
      app_variant: APP_VARIANT,
      app_env: governance.appEnv,
      governance_ready: governance.isReady,
      issue_count: governance.issues.length,
      ...governance.flagsSnapshot,
    });

    if (!governance.isReady) {
      captureException(new Error('Release governance check failed'), {
        level: 'warning',
        tags: {
          area: 'release-governance',
          app_variant: APP_VARIANT,
        },
        extras: governance,
      });

      if (governance.enforceReleaseGates) {
        throw new Error(
          `Release governance gates failed: ${governance.issues
            .map((issue) => issue.code)
            .join(', ')}`
        );
      }
    }

    return () => {
      flushTelemetry().catch(() => {
        // Best effort only.
      });
    };
  }, []);

  useEffect(() => {
    const params = Object.entries(searchParams || {}).reduce<Record<string, string>>(
      (accumulator, [key, value]) => {
        if (value === undefined || value === null) {
          return accumulator;
        }

        accumulator[key] = Array.isArray(value) ? value.join(',') : String(value);
        return accumulator;
      },
      {}
    );

    trackScreen(pathname || '/', params);
  }, [pathname, searchParams]);

  return null;
}

function RootLayout() {
  return (
    <GrabBasketProvider>
      <TelemetryBootstrap />
      <PushNotificationBootstrap />
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="(delivery)" options={{ headerShown: false }} />
        <Stack.Screen name="(partner)" options={{ headerShown: false }} />
        <Stack.Screen name="store/[vendorId]" options={{ headerShown: false }} />
        <Stack.Screen name="cart" options={{ headerShown: false }} />
      </Stack>
    </GrabBasketProvider>
  );
}

export default wrapWithSentry(RootLayout);
