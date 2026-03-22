import React, { useCallback, useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { useRouter } from 'expo-router';

import { useGrabBasket } from '../../App';
import { buildApiUrl } from '../config';
import { getAppVariant } from '../constants/app-shell';

const APP_VARIANT = getAppVariant();
const STORAGE_AUTH_TOKEN = `@grab_basket/${APP_VARIANT}/auth_token_v1`;
const STORAGE_LAST_PUSH_SIGNATURE = `@grab_basket/${APP_VARIANT}/push_registration_signature_v1`;

let secureStoreModuleCache;

function getSecureStoreModule() {
  if (secureStoreModuleCache !== undefined) return secureStoreModuleCache;

  try {
    secureStoreModuleCache = require('expo-secure-store');
  } catch {
    secureStoreModuleCache = null;
  }

  return secureStoreModuleCache;
}

function hasSecureStore() {
  const module = getSecureStoreModule();
  return Boolean(module?.getItemAsync && module?.setItemAsync);
}

async function getStoredAccessToken() {
  if (hasSecureStore()) {
    return (await getSecureStoreModule().getItemAsync(STORAGE_AUTH_TOKEN)) || '';
  }

  return (await AsyncStorage.getItem(STORAGE_AUTH_TOKEN)) || '';
}

function getExpoProjectId() {
  return (
    Constants?.expoConfig?.extra?.eas?.projectId ||
    Constants?.easConfig?.projectId ||
    process.env.EXPO_PUBLIC_EAS_PROJECT_ID ||
    ''
  );
}

function getOrdersRoute(variant = APP_VARIANT) {
  if (variant === 'delivery') return '/(delivery)/(tabs)/orders';
  if (variant === 'partner') return '/(partner)/(tabs)/orders';
  return '/(tabs)/account';
}

async function ensureAndroidChannel() {
  if (Platform.OS !== 'android') return;

  await Notifications.setNotificationChannelAsync('orders-updates', {
    name: 'Order updates',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 250, 250],
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
    bypassDnd: false,
    sound: 'default',
  });
}

async function getPushTokenAsync() {
  const projectId = getExpoProjectId();

  if (projectId) {
    const response = await Notifications.getExpoPushTokenAsync({ projectId });
    return response?.data || '';
  }

  const response = await Notifications.getExpoPushTokenAsync();
  return response?.data || '';
}

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

export default function PushNotificationBootstrap() {
  const router = useRouter();
  const { authToken, authEmail, isAuthenticated, sessionReady, loadOrders, appVariant } =
    useGrabBasket();
  const tapHandledRef = useRef('');

  const syncOrders = useCallback(() => {
    if (typeof loadOrders === 'function') {
      loadOrders().catch(() => {});
    }
  }, [loadOrders]);

  const openOrdersScreen = useCallback(
    (notificationData = {}) => {
      const notificationOrderId = String(notificationData?.order_id || '').trim();
      const dedupeKey = `${String(notificationData?.notification_id || '')}:${notificationOrderId}:${String(
        notificationData?.status || ''
      )}`;

      if (dedupeKey && tapHandledRef.current === dedupeKey) {
        return;
      }

      if (dedupeKey) {
        tapHandledRef.current = dedupeKey;
      }

      syncOrders();
      router.push(getOrdersRoute(appVariant || APP_VARIANT));
    },
    [appVariant, router, syncOrders]
  );

  const registerPushToken = useCallback(async () => {
    if (Platform.OS === 'web') return;
    if (!sessionReady || !isAuthenticated) return;
    if (!Device.isDevice) return;

    let accessToken = String(authToken || '').trim();
    if (!accessToken) {
      accessToken = String(await getStoredAccessToken()).trim();
    }
    if (!accessToken) return;

    await ensureAndroidChannel();

    const permissionStatus = await Notifications.getPermissionsAsync();
    let finalStatus = permissionStatus.status;

    if (finalStatus !== 'granted') {
      const requested = await Notifications.requestPermissionsAsync();
      finalStatus = requested.status;
    }

    if (finalStatus !== 'granted') {
      return;
    }

    const pushToken = String(await getPushTokenAsync()).trim();
    if (!pushToken) return;

    const nextSignature = `${String(authEmail || '').trim().toLowerCase()}|${pushToken}`;
    const previousSignature = (await AsyncStorage.getItem(STORAGE_LAST_PUSH_SIGNATURE)) || '';

    if (previousSignature === nextSignature) {
      return;
    }

    const response = await fetch(buildApiUrl('/auth/fcm/register'), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${accessToken}`,
      },
      body: JSON.stringify({
        token: pushToken,
        platform: Platform.OS,
      }),
    });

    if (!response.ok) {
      throw new Error(`Push registration failed with status ${response.status}`);
    }

    await AsyncStorage.setItem(STORAGE_LAST_PUSH_SIGNATURE, nextSignature);
  }, [authEmail, authToken, isAuthenticated, sessionReady]);

  useEffect(() => {
    registerPushToken().catch(() => {});
  }, [registerPushToken]);

  useEffect(() => {
    if (Platform.OS === 'web') return undefined;

    const receivedSubscription = Notifications.addNotificationReceivedListener(() => {
      syncOrders();
    });

    const responseSubscription = Notifications.addNotificationResponseReceivedListener(
      (response) => {
        const data = response?.notification?.request?.content?.data || {};
        openOrdersScreen(data);
        Notifications.clearLastNotificationResponseAsync?.().catch(() => {});
      }
    );

    Notifications.getLastNotificationResponseAsync()
      .then((response) => {
        const data = response?.notification?.request?.content?.data || {};
        if (Object.keys(data).length) {
          openOrdersScreen(data);
          Notifications.clearLastNotificationResponseAsync?.().catch(() => {});
        }
      })
      .catch(() => {});

    return () => {
      Notifications.removeNotificationSubscription(receivedSubscription);
      Notifications.removeNotificationSubscription(responseSubscription);
    };
  }, [openOrdersScreen, syncOrders]);

  return null;
}