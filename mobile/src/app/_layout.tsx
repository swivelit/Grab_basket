import React, { useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Stack, useGlobalSearchParams, usePathname } from 'expo-router';
import * as TaskManager from 'expo-task-manager';

import { GrabBasketProvider } from '../../App';
import PushNotificationBootstrap from '../components/push-notification-bootstrap';
import { getAppVariant } from '../constants/app-shell';
import { buildApiUrl } from '../config';
import {
  captureEvent,
  captureException,
  flushTelemetry,
  identifyUser,
  resetTelemetryUser,
  trackScreen,
  wrapWithSentry,
} from '../lib/telemetry';

const APP_VARIANT = getAppVariant();
const DELIVERY_LOCATION_TASK_NAME = 'grab-basket-delivery-background-location';

function buildScopedStorageKey(key: string) {
  return `@grab_basket/${APP_VARIANT}/${key}`;
}

const STORAGE_AUTH_TOKEN = buildScopedStorageKey('auth_token_v1');
const STORAGE_AUTH_EMAIL = buildScopedStorageKey('auth_email_v1');
const STORAGE_AUTH_ROLE = buildScopedStorageKey('auth_role_v1');
const STORAGE_LAST_BACKGROUND_LOCATION = buildScopedStorageKey('background_location_last_v1');

let secureStoreModuleCache: any;

function getSecureStoreModule() {
  if (secureStoreModuleCache !== undefined) return secureStoreModuleCache;

  try {
    secureStoreModuleCache = require('expo-secure-store');
  } catch {
    secureStoreModuleCache = null;
  }

  return secureStoreModuleCache;
}

async function readStoredValue(key: string) {
  const secureStoreModule = getSecureStoreModule();

  if (secureStoreModule?.getItemAsync) {
    try {
      const secureValue = await secureStoreModule.getItemAsync(key);
      if (secureValue) return secureValue;
    } catch {
      // fall back to AsyncStorage
    }
  }

  try {
    return await AsyncStorage.getItem(key);
  } catch {
    return null;
  }
}

async function persistLastBackgroundLocation(payload: Record<string, any>) {
  try {
    await AsyncStorage.setItem(STORAGE_LAST_BACKGROUND_LOCATION, JSON.stringify(payload));
  } catch {
    // best effort only
  }
}

async function hydrateTelemetryUser() {
  try {
    const [email, role] = await Promise.all([
      readStoredValue(STORAGE_AUTH_EMAIL),
      readStoredValue(STORAGE_AUTH_ROLE),
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

async function postPartnerLocation(token: string, payload: Record<string, any>) {
  const response = await fetch(buildApiUrl('/partner/location'), {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw new Error(`Location sync failed with status ${response.status}`);
  }
}

if (!TaskManager.isTaskDefined(DELIVERY_LOCATION_TASK_NAME)) {
  TaskManager.defineTask(DELIVERY_LOCATION_TASK_NAME, async ({ data, error }) => {
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

    const locations = Array.isArray((data as any)?.locations) ? (data as any).locations : [];
    const latest = locations[locations.length - 1];

    if (!latest?.coords) {
      return;
    }

    const token = String((await readStoredValue(STORAGE_AUTH_TOKEN)) || '').trim();
    if (!token) {
      return;
    }

    const heading = Number(latest.coords.heading);
    const speed = Number(latest.coords.speed);

    const payload: Record<string, any> = {
      lat: Number(latest.coords.latitude),
      lng: Number(latest.coords.longitude),
    };

    if (Number.isFinite(heading) && heading >= 0) {
      payload.heading = heading;
    }

    if (Number.isFinite(speed) && speed >= 0) {
      payload.speed = speed;
    }

    try {
      await postPartnerLocation(token, payload);
      await persistLastBackgroundLocation({
        ...payload,
        created_at: new Date(latest.timestamp || Date.now()).toISOString(),
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
        },
      });
    }
  });
}

function TelemetryBootstrap() {
  const pathname = usePathname();
  const searchParams = useGlobalSearchParams();

  useEffect(() => {
    hydrateTelemetryUser();
    captureEvent('app_opened', {
      app_variant: APP_VARIANT,
      initial_path: pathname || '/',
    });

    return () => {
      flushTelemetry();
    };
  }, []);

  useEffect(() => {
    const params = Object.entries(searchParams || {}).reduce(
      (accumulator, [key, value]) => {
        if (value === undefined || value === null) {
          return accumulator;
        }

        accumulator[key] = Array.isArray(value) ? value.join(',') : String(value);
        return accumulator;
      },
      {} as Record<string, string>
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