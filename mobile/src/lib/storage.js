import AsyncStorage from '@react-native-async-storage/async-storage';

import { getAppVariant } from '../constants/app-shell';

const STORAGE_PREFIX = '@grab_basket';
const APP_VARIANT = getAppVariant();

let secureStoreModuleCache;

export function buildScopedStorageKey(key, variant = APP_VARIANT) {
  return `${STORAGE_PREFIX}/${variant}/${String(key || '').replace(/^\/+/, '')}`;
}

export const STORAGE_KEYS = {
  authToken: buildScopedStorageKey('auth_token_v1'),
  refreshToken: buildScopedStorageKey('auth_refresh_token_v1'),
  authEmail: buildScopedStorageKey('auth_email_v1'),
  authRole: buildScopedStorageKey('auth_role_v1'),
  authTokenExpiresAt: buildScopedStorageKey('auth_token_expires_at_v1'),
  refreshTokenExpiresAt: buildScopedStorageKey('auth_refresh_expires_at_v1'),
  selectedAddressId: buildScopedStorageKey('selected_address_id_v1'),
  cart: buildScopedStorageKey('cart_v12'),
  favorites: buildScopedStorageKey('favorites_v9'),
  recentStores: buildScopedStorageKey('recent_stores_v10'),
  recentSearches: buildScopedStorageKey('recent_searches_v9'),
  orderHistory: buildScopedStorageKey('order_history_v7'),
  pendingCheckoutAttempt: buildScopedStorageKey('pending_checkout_attempt_v2'),
};

export const LEGACY_STORAGE_KEYS = {
  authToken: '@grab_basket/auth_token_v1',
  refreshToken: '@grab_basket/auth_refresh_token_v1',
  authEmail: '@grab_basket/auth_email_v1',
  authRole: '@grab_basket/auth_role_v1',
  authTokenExpiresAt: '@grab_basket/auth_token_expires_at_v1',
  refreshTokenExpiresAt: '@grab_basket/auth_refresh_expires_at_v1',
};

function getSecureStoreModule() {
  if (secureStoreModuleCache !== undefined) {
    return secureStoreModuleCache;
  }

  try {
    secureStoreModuleCache = require('expo-secure-store');
  } catch {
    secureStoreModuleCache = null;
  }

  return secureStoreModuleCache;
}

function getSecureStoreOptions() {
  const secureStore = getSecureStoreModule();
  if (!secureStore || !secureStore.AFTER_FIRST_UNLOCK) return undefined;

  return {
    keychainAccessible: secureStore.AFTER_FIRST_UNLOCK,
  };
}

export function hasSecureStore() {
  const secureStore = getSecureStoreModule();
  return Boolean(secureStore?.getItemAsync && secureStore?.setItemAsync && secureStore?.deleteItemAsync);
}

export async function readStoredValue(key) {
  const secureStore = getSecureStoreModule();

  if (secureStore?.getItemAsync) {
    try {
      const secureValue = await secureStore.getItemAsync(key);
      if (secureValue) return secureValue;
    } catch {
      // Fall back to AsyncStorage.
    }
  }

  try {
    return await AsyncStorage.getItem(key);
  } catch {
    return null;
  }
}

export async function writeStoredValue(key, value) {
  const normalized = value === undefined || value === null ? '' : String(value);
  const secureStore = getSecureStoreModule();

  if (secureStore?.setItemAsync) {
    try {
      await secureStore.setItemAsync(key, normalized, getSecureStoreOptions());
      return true;
    } catch {
      // Fall through.
    }
  }

  await AsyncStorage.setItem(key, normalized);
  return true;
}

export async function removeStoredValue(key) {
  const secureStore = getSecureStoreModule();

  if (secureStore?.deleteItemAsync) {
    try {
      await secureStore.deleteItemAsync(key, getSecureStoreOptions());
    } catch {
      // Fall through.
    }
  }

  await AsyncStorage.removeItem(key).catch(() => {});
}

export async function multiSetStoredValues(entries = []) {
  const secureStore = getSecureStoreModule();

  if (secureStore?.setItemAsync) {
    await Promise.all(
      entries.map(([key, value]) =>
        secureStore.setItemAsync(key, String(value ?? ''), getSecureStoreOptions()).catch(() => {})
      )
    );
  }

  await AsyncStorage.multiSet(entries.map(([key, value]) => [key, String(value ?? '')])).catch(() => {});
}

export async function multiRemoveStoredValues(keys = []) {
  const secureStore = getSecureStoreModule();

  if (secureStore?.deleteItemAsync) {
    await Promise.all(keys.map((key) => secureStore.deleteItemAsync(key, getSecureStoreOptions()).catch(() => {})));
  }

  await AsyncStorage.multiRemove(keys).catch(() => {});
}

export async function clearLegacyAuthStorage() {
  await AsyncStorage.multiRemove(Object.values(LEGACY_STORAGE_KEYS)).catch(() => {});
}

export async function migrateLegacyAuthStorage() {
  const rows = await AsyncStorage.multiGet(Object.values(LEGACY_STORAGE_KEYS)).catch(() => []);
  if (!rows.length) return false;

  const map = Object.fromEntries(rows || []);
  const hasLegacySession = Object.values(map).some((value) => String(value || '').trim());

  if (!hasLegacySession) {
    await clearLegacyAuthStorage();
    return false;
  }

  await multiSetStoredValues([
    [STORAGE_KEYS.authToken, String(map[LEGACY_STORAGE_KEYS.authToken] || '')],
    [STORAGE_KEYS.refreshToken, String(map[LEGACY_STORAGE_KEYS.refreshToken] || '')],
    [STORAGE_KEYS.authEmail, String(map[LEGACY_STORAGE_KEYS.authEmail] || '')],
    [STORAGE_KEYS.authRole, String(map[LEGACY_STORAGE_KEYS.authRole] || '')],
    [STORAGE_KEYS.authTokenExpiresAt, String(map[LEGACY_STORAGE_KEYS.authTokenExpiresAt] || '0')],
    [STORAGE_KEYS.refreshTokenExpiresAt, String(map[LEGACY_STORAGE_KEYS.refreshTokenExpiresAt] || '0')],
  ]);

  await clearLegacyAuthStorage();
  return true;
}
