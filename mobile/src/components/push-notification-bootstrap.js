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
import { captureEvent, captureException } from '../lib/telemetry';

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

function sanitizeTargetApp(targetApp) {
  const value = String(targetApp || '').trim().toLowerCase();
  if (['customer', 'consumer', 'user'].includes(value)) return 'consumer';
  if (['delivery', 'rider', 'partner_delivery'].includes(value)) return 'delivery';
  if (['partner', 'seller', 'merchant', 'vendor'].includes(value)) return 'partner';
  return '';
}

function sanitizeNotificationData(notificationData = {}) {
  return {
    notification_id: String(notificationData?.notification_id || '').trim(),
    order_id: String(notificationData?.order_id || '').trim(),
    status: String(notificationData?.status || '').trim(),
    type: String(notificationData?.type || '').trim(),
    target_app: sanitizeTargetApp(notificationData?.target_app),
    deep_link_path: String(notificationData?.deep_link_path || '').trim(),
  };
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
      loadOrders().catch((error) => {
        captureException(error, {
          tags: {
            area: 'push',
            operation: 'sync-orders',
          },
        });
      });
    }
  }, [loadOrders]);

  const openOrdersScreen = useCallback(
    (notificationData = {}) => {
      const normalizedData = sanitizeNotificationData(notificationData);
      const dedupeKey = `${normalizedData.notification_id}:${normalizedData.order_id}:${normalizedData.status}:${normalizedData.deep_link_path}`;

      if (dedupeKey && tapHandledRef.current === dedupeKey) {
        return;
      }

      if (dedupeKey) {
        tapHandledRef.current = dedupeKey;
      }

      captureEvent('push_notification_opened', {
        app_variant: appVariant || APP_VARIANT,
        ...normalizedData,
      });

      syncOrders();

      const fallbackPath = getOrdersRoute(appVariant || APP_VARIANT);
      const targetPath = normalizedData.deep_link_path || fallbackPath;

      if (normalizedData.order_id) {
        router.push({
          pathname: targetPath,
          params: { orderId: normalizedData.order_id },
        });
        return;
      }

      router.push(targetPath);
    },
    [appVariant, router, syncOrders]
  );

  const registerPushToken = useCallback(async () => {
    if (Platform.OS === 'web') return;
    if (!sessionReady || !isAuthenticated) return;
    if (!Device.isDevice) {
      captureEvent('push_registration_skipped', {
        app_variant: appVariant || APP_VARIANT,
        reason: 'simulator_or_emulator',
        platform: Platform.OS,
      });
      return;
    }

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
      captureEvent('push_permission_denied', {
        app_variant: appVariant || APP_VARIANT,
        platform: Platform.OS,
        status: finalStatus,
      });
      return;
    }

    const pushToken = String(await getPushTokenAsync()).trim();
    if (!pushToken) {
      throw new Error('Push registration did not return a token.');
    }

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

    captureEvent('push_token_registered', {
      app_variant: appVariant || APP_VARIANT,
      platform: Platform.OS,
      has_project_id: Boolean(getExpoProjectId()),
    });
  }, [appVariant, authEmail, authToken, isAuthenticated, sessionReady]);

  useEffect(() => {
    registerPushToken().catch((error) => {
      captureException(error, {
        tags: {
          area: 'push',
          operation: 'register-token',
        },
        extras: {
          appVariant: appVariant || APP_VARIANT,
          platform: Platform.OS,
        },
      });
    });
  }, [appVariant, registerPushToken]);

  useEffect(() => {
    if (Platform.OS === 'web') return undefined;

    const receivedSubscription = Notifications.addNotificationReceivedListener((notification) => {
      const data = sanitizeNotificationData(notification?.request?.content?.data || {});
      captureEvent('push_notification_received', {
        app_variant: appVariant || APP_VARIANT,
        ...data,
      });
      syncOrders();
    });

    const responseSubscription = Notifications.addNotificationResponseReceivedListener(
      (response) => {
        const data = response?.notification?.request?.content?.data || {};
        openOrdersScreen(data);
        Notifications.clearLastNotificationResponseAsync?.().catch((error) => {
          captureException(error, {
            tags: {
              area: 'push',
              operation: 'clear-last-response',
            },
          });
        });
      }
    );

    Notifications.getLastNotificationResponseAsync()
      .then((response) => {
        const data = response?.notification?.request?.content?.data || {};
        if (Object.keys(data).length) {
          openOrdersScreen(data);
          Notifications.clearLastNotificationResponseAsync?.().catch((error) => {
            captureException(error, {
              tags: {
                area: 'push',
                operation: 'clear-last-response-initial',
              },
            });
          });
        }
      })
      .catch((error) => {
        captureException(error, {
          tags: {
            area: 'push',
            operation: 'read-last-response',
          },
        });
      });

    return () => {
      Notifications.removeNotificationSubscription(receivedSubscription);
      Notifications.removeNotificationSubscription(responseSubscription);
    };
  }, [appVariant, openOrdersScreen, syncOrders]);

  return null;
}