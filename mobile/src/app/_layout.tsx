import React from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Stack } from 'expo-router';
import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import { GrabBasketProvider } from '../../App';
import PushNotificationBootstrap from '../components/push-notification-bootstrap';
import { buildApiUrl } from '../config';

const DELIVERY_LOCATION_TASK_NAME = 'grab-basket-delivery-background-location';
const STORAGE_AUTH_TOKEN = '@grab_basket/delivery/auth_token_v1';
const STORAGE_LAST_BACKGROUND_LOCATION = '@grab_basket/delivery/background_location_last_v1';

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
    } catch {
      // Ignore background sync failures. The UI surfaces fresh state when the rider reopens the app.
    }
  });
}

export default function RootLayout() {
  return (
    <GrabBasketProvider>
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