import { useCallback, useEffect, useRef } from 'react';
import { DeviceEventEmitter, Platform } from 'react-native';
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { useRouter } from 'expo-router';

import { useGrabBasket } from '../../App';
import { getAppVariant } from '../constants/app-shell';
import { apiPost } from '../lib/api-client';
import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';
import { captureEvent, captureException } from '../lib/telemetry';

const APP_VARIANT = getAppVariant();
const ORDER_SYNC_EVENT = 'grab_basket:orders_sync_requested';
const ORDER_OPEN_EVENT = 'grab_basket:push_order_open_requested';

const KNOWN_APP_ROUTES = new Set([
  '/(tabs)/index',
  '/(tabs)/explore',
  '/(tabs)/reorder',
  '/(tabs)/account',
  '/(delivery)/(tabs)/index',
  '/(delivery)/(tabs)/orders',
  '/(delivery)/(tabs)/earnings',
  '/(delivery)/(tabs)/account',
  '/(partner)/(tabs)/index',
  '/(partner)/(tabs)/orders',
  '/(partner)/(tabs)/catalog',
  '/(partner)/(tabs)/account',
  '/cart',
  '/explore',
]);

async function getStoredAccessToken() {
  return String((await readStoredValue(STORAGE_KEYS.authToken)) || '').trim();
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

function isSafeAppRoute(path) {
  return KNOWN_APP_ROUTES.has(String(path || '').trim());
}

function resolveNotificationRoute({ appVariant = APP_VARIANT, deepLinkPath = '' } = {}) {
  const sanitizedPath = String(deepLinkPath || '').trim();

  if (isSafeAppRoute(sanitizedPath)) {
    return sanitizedPath;
  }

  return getOrdersRoute(appVariant);
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
    DeviceEventEmitter.emit(ORDER_SYNC_EVENT, {
      app_variant: appVariant || APP_VARIANT,
      source: 'push',
      at: Date.now(),
    });

    if (typeof loadOrders === 'function') {
      loadOrders({ silent: true }).catch((error) => {
        captureException(error, {
          tags: {
            area: 'push',
            operation: 'sync-orders',
          },
        });
      });
    }
  }, [appVariant, loadOrders]);

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

      const activeVariant = appVariant || APP_VARIANT;
      const targetPath = resolveNotificationRoute({
        appVariant: activeVariant,
        deepLinkPath: normalizedData.deep_link_path,
      });

      if (normalizedData.order_id) {
        DeviceEventEmitter.emit(ORDER_OPEN_EVENT, {
          app_variant: activeVariant,
          order_id: normalizedData.order_id,
          status: normalizedData.status,
          type: normalizedData.type,
          source: 'push',
          at: Date.now(),
        });
      }

      router.push({
        pathname: targetPath,
        params: normalizedData.order_id
          ? {
              orderId: normalizedData.order_id,
              highlightOrderId: normalizedData.order_id,
              source: 'push',
            }
          : undefined,
      });
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
      accessToken = await getStoredAccessToken();
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
    const previousSignature = String(
      (await readStoredValue(STORAGE_KEYS.pushRegistrationSignature)) || ''
    ).trim();

    if (previousSignature === nextSignature) {
      return;
    }

    await apiPost(
      '/auth/fcm/register',
      {
        token: pushToken,
        platform: Platform.OS,
      },
      {
        token: accessToken,
        timeoutMs: 10000,
      }
    );

    await writeStoredValue(STORAGE_KEYS.pushRegistrationSignature, nextSignature);

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
            operation: 'get-last-response',
          },
        });
      });

    return () => {
      receivedSubscription.remove();
      responseSubscription.remove();
    };
  }, [appVariant, openOrdersScreen, syncOrders]);

  return null;
}