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
const NOTIFICATIONS_UNAVAILABLE_MESSAGE =
  'Notifications not available in this build';

const KNOWN_APP_ROUTES = new Set([
  '/(tabs)/index',
  '/(tabs)/explore',
  '/(tabs)/reorder',
  '/(tabs)/account',
  '/delivery/(tabs)/index',
  '/delivery/(tabs)/orders',
  '/delivery/(tabs)/earnings',
  '/delivery/(tabs)/account',
  '/partner/(tabs)/index',
  '/partner/(tabs)/orders',
  '/partner/(tabs)/catalog',
  '/partner/(tabs)/account',
  '/cart',
  '/explore',
]);

let notificationHandlerConfigured = false;

function hasNotificationFunction(name) {
  switch (name) {
    case 'setNotificationHandler':
      return typeof Notifications?.setNotificationHandler === 'function';
    case 'setNotificationChannelAsync':
      return typeof Notifications?.setNotificationChannelAsync === 'function';
    case 'getPermissionsAsync':
      return typeof Notifications?.getPermissionsAsync === 'function';
    case 'requestPermissionsAsync':
      return typeof Notifications?.requestPermissionsAsync === 'function';
    case 'getExpoPushTokenAsync':
      return typeof Notifications?.getExpoPushTokenAsync === 'function';
    case 'getDevicePushTokenAsync':
      return typeof Notifications?.getDevicePushTokenAsync === 'function';
    case 'addNotificationResponseReceivedListener':
      return typeof Notifications?.addNotificationResponseReceivedListener === 'function';
    case 'addNotificationReceivedListener':
      return typeof Notifications?.addNotificationReceivedListener === 'function';
    default:
      return false;
  }
}

function warnNotificationCapability(message) {
  console.warn(`[PushNotificationBootstrap] ${message}`);
}

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
  if (variant === 'delivery') return '/delivery/(tabs)/orders';
  if (variant === 'partner') return '/partner/(tabs)/orders';
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

function configureNotificationHandler() {
  if (notificationHandlerConfigured) {
    return true;
  }

  if (!hasNotificationFunction('setNotificationHandler')) {
    warnNotificationCapability(`${NOTIFICATIONS_UNAVAILABLE_MESSAGE}. setNotificationHandler is missing.`);
    return false;
  }

  try {
    Notifications.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowAlert: true,
        shouldShowBanner: true,
        shouldShowList: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
      }),
    });
    notificationHandlerConfigured = true;
    return true;
  } catch (error) {
    console.error('[PushNotificationBootstrap] Failed to configure notification handler:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'set-handler',
      },
    });
    return false;
  }
}

async function ensureAndroidChannel() {
  if (Platform.OS !== 'android') return true;

  if (!hasNotificationFunction('setNotificationChannelAsync')) {
    warnNotificationCapability(`${NOTIFICATIONS_UNAVAILABLE_MESSAGE}. setNotificationChannelAsync is missing.`);
    return false;
  }

  try {
    await Notifications.setNotificationChannelAsync('orders-updates', {
      name: 'Order updates',
      importance: Notifications?.AndroidImportance?.MAX,
      vibrationPattern: [0, 250, 250, 250],
      lockscreenVisibility: Notifications?.AndroidNotificationVisibility?.PUBLIC,
      bypassDnd: false,
      sound: 'default',
    });
    return true;
  } catch (error) {
    console.error('[PushNotificationBootstrap] Failed to configure Android notification channel:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'android-channel',
      },
      extras: {
        platform: Platform.OS,
      },
    });
    return false;
  }
}

async function getPushTokenAsync(projectId) {
  if (!hasNotificationFunction('getExpoPushTokenAsync')) {
    warnNotificationCapability(`${NOTIFICATIONS_UNAVAILABLE_MESSAGE}. getExpoPushTokenAsync is missing.`);
    return '';
  }

  if (!projectId) {
    warnNotificationCapability(
      'EXPO_PUBLIC_EAS_PROJECT_ID is missing. Skipping push token registration.'
    );
    return '';
  }

  try {
    const response = await Notifications.getExpoPushTokenAsync({ projectId });
    return response?.data || '';
  } catch (error) {
    console.error('[PushNotificationBootstrap] Failed to fetch Expo push token:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'get-push-token',
      },
      extras: {
        hasProjectId: Boolean(projectId),
      },
    });
    return '';
  }
}

async function getNotificationPermissionStatus() {
  if (
    !hasNotificationFunction('getPermissionsAsync') ||
    !hasNotificationFunction('requestPermissionsAsync')
  ) {
    warnNotificationCapability(
      `${NOTIFICATIONS_UNAVAILABLE_MESSAGE}. Permission APIs are missing.`
    );
    return '';
  }

  try {
    const permissionStatus = await Notifications.getPermissionsAsync();
    let finalStatus = permissionStatus?.status || '';

    if (finalStatus !== 'granted') {
      const requested = await Notifications.requestPermissionsAsync();
      finalStatus = requested?.status || '';
    }

    return finalStatus;
  } catch (error) {
    console.error('[PushNotificationBootstrap] Failed to resolve notification permissions:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'permissions',
      },
      extras: {
        platform: Platform.OS,
      },
    });
    return '';
  }
}

export async function initPushNotifications({
  appVariant = APP_VARIANT,
  authEmail = '',
  authToken = '',
  isAuthenticated = false,
  sessionReady = false,
} = {}) {
  try {
    if (Platform.OS === 'web') return false;

    configureNotificationHandler();

    if (!sessionReady || !isAuthenticated) {
      return false;
    }

    if (!Notifications || !hasNotificationFunction('getExpoPushTokenAsync')) {
      warnNotificationCapability(NOTIFICATIONS_UNAVAILABLE_MESSAGE);
      return false;
    }

    if (!Device?.isDevice) {
      captureEvent('push_registration_skipped', {
        app_variant: appVariant || APP_VARIANT,
        reason: 'simulator_or_emulator',
        platform: Platform.OS,
      });
      return false;
    }

    const projectId = String(getExpoProjectId() || '').trim();
    if (!projectId) {
      warnNotificationCapability(
        'EXPO_PUBLIC_EAS_PROJECT_ID is missing. Skipping push token registration.'
      );
      return false;
    }

    let accessToken = String(authToken || '').trim();
    if (!accessToken) {
      accessToken = await getStoredAccessToken();
    }
    if (!accessToken) return false;

    await ensureAndroidChannel();

    const finalStatus = await getNotificationPermissionStatus();
    if (finalStatus !== 'granted') {
      captureEvent('push_permission_denied', {
        app_variant: appVariant || APP_VARIANT,
        platform: Platform.OS,
        status: finalStatus || 'unknown',
      });
      return false;
    }

    const pushToken = String(await getPushTokenAsync(projectId)).trim();
    if (!pushToken) {
      return false;
    }

    const nextSignature = `${String(authEmail || '').trim().toLowerCase()}|${pushToken}`;
    const previousSignature = String(
      (await readStoredValue(STORAGE_KEYS.pushRegistrationSignature)) || ''
    ).trim();

    if (previousSignature === nextSignature) {
      return true;
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
      has_project_id: true,
    });

    return true;
  } catch (error) {
    console.error('[PushNotificationBootstrap] Push init failed:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'init',
      },
      extras: {
        appVariant: appVariant || APP_VARIANT,
        platform: Platform.OS,
      },
    });
    return false;
  }
}

function removeSubscription(subscription) {
  try {
    if (subscription && typeof subscription.remove === 'function') {
      subscription.remove();
    }
  } catch (error) {
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'remove-listener',
      },
    });
  }
}

function attachPushListeners({ appVariant, openOrdersScreen, syncOrders }) {
  if (Platform.OS === 'web') {
    return () => {};
  }

  try {
    if (
      !hasNotificationFunction('addNotificationReceivedListener') ||
      !hasNotificationFunction('addNotificationResponseReceivedListener')
    ) {
      warnNotificationCapability(
        `${NOTIFICATIONS_UNAVAILABLE_MESSAGE}. Listener APIs are missing.`
      );
      return () => {};
    }

    const receivedSubscription = Notifications.addNotificationReceivedListener(
      (notification) => {
        try {
          const data = sanitizeNotificationData(notification?.request?.content?.data || {});
          captureEvent('push_notification_received', {
            app_variant: appVariant || APP_VARIANT,
            ...data,
          });
          syncOrders();
        } catch (error) {
          captureException(error, {
            tags: {
              area: 'push',
              operation: 'received-listener',
            },
          });
        }
      }
    );

    const responseSubscription = Notifications.addNotificationResponseReceivedListener(
      (response) => {
        try {
          const data = response?.notification?.request?.content?.data || {};
          openOrdersScreen(data);

          const clearLastResponse = Notifications?.clearLastNotificationResponseAsync;
          if (typeof clearLastResponse === 'function') {
            clearLastResponse.call(Notifications).catch((error) => {
              captureException(error, {
                tags: {
                  area: 'push',
                  operation: 'clear-last-response',
                },
              });
            });
          }
        } catch (error) {
          captureException(error, {
            tags: {
              area: 'push',
              operation: 'response-listener',
            },
          });
        }
      }
    );

    const getLastResponse = Notifications?.getLastNotificationResponseAsync;
    if (typeof getLastResponse === 'function') {
      getLastResponse
        .call(Notifications)
        .then((response) => {
          const data = response?.notification?.request?.content?.data || {};
          if (!Object.keys(data).length) {
            return;
          }

          openOrdersScreen(data);

          const clearLastResponse = Notifications?.clearLastNotificationResponseAsync;
          if (typeof clearLastResponse === 'function') {
            clearLastResponse.call(Notifications).catch((error) => {
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
    }

    return () => {
      removeSubscription(receivedSubscription);
      removeSubscription(responseSubscription);
    };
  } catch (error) {
    console.error('[PushNotificationBootstrap] Failed to attach push listeners:', error);
    captureException(error, {
      tags: {
        area: 'push',
        operation: 'attach-listeners',
      },
      extras: {
        platform: Platform.OS,
      },
    });
    return () => {};
  }
}

export default function PushNotificationBootstrap() {
  const router = useRouter();
  const { authToken, authEmail, isAuthenticated, sessionReady, loadOrders, appVariant } =
    useGrabBasket();
  const tapHandledRef = useRef('');

  const syncOrders = useCallback(() => {
    try {
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
    } catch (error) {
      captureException(error, {
        tags: {
          area: 'push',
          operation: 'emit-sync-orders',
        },
      });
    }
  }, [appVariant, loadOrders]);

  const openOrdersScreen = useCallback(
    (notificationData = {}) => {
      try {
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
      } catch (error) {
        captureException(error, {
          tags: {
            area: 'push',
            operation: 'open-orders-screen',
          },
        });
      }
    },
    [appVariant, router, syncOrders]
  );

  useEffect(() => {
    initPushNotifications({
      appVariant,
      authEmail,
      authToken,
      isAuthenticated,
      sessionReady,
    }).catch(() => {});
  }, [appVariant, authEmail, authToken, isAuthenticated, sessionReady]);

  useEffect(() => attachPushListeners({ appVariant, openOrdersScreen, syncOrders }), [
    appVariant,
    openOrdersScreen,
    syncOrders,
  ]);

  return null;
}
