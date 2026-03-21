import Constants from 'expo-constants';
import { Platform } from 'react-native';

function firstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value ?? '').trim();
    if (text) return text;
  }
  return '';
}

function normalizeAppEnv(value) {
  const normalized = String(value || '').trim().toLowerCase();

  if (!normalized) {
    return __DEV__ ? 'development' : 'production';
  }

  if (['prod', 'production', 'release'].includes(normalized)) {
    return 'production';
  }

  if (['dev', 'development', 'debug'].includes(normalized)) {
    return 'development';
  }

  return normalized;
}

function trimTrailingSlashes(value = '') {
  return String(value || '').replace(/\/+$/, '');
}

function normalizeHostLikeValue(value = '') {
  return String(value || '')
    .replace(/^https?:\/\//i, '')
    .replace(/\/.*$/, '')
    .trim();
}

function isLocalLikeHost(value = '') {
  const host = normalizeHostLikeValue(value).toLowerCase();
  return ['localhost', '127.0.0.1', '10.0.2.2', '10.0.3.2'].includes(host);
}

const expoExtra = Constants?.expoConfig?.extra || {};

const DEFAULT_PORT = firstNonEmpty(
  process.env.EXPO_PUBLIC_API_PORT,
  expoExtra.apiPort,
  '8000'
);

const EXPLICIT_API_BASE_URL = firstNonEmpty(
  process.env.EXPO_PUBLIC_API_BASE_URL,
  expoExtra.apiBaseUrl
);

const EXPLICIT_API_HOST = firstNonEmpty(
  process.env.EXPO_PUBLIC_API_HOST,
  expoExtra.apiHost
);

const EXPLICIT_API_SCHEME = firstNonEmpty(
  process.env.EXPO_PUBLIC_API_SCHEME,
  expoExtra.apiScheme,
  'http'
);

const APP_ENV = normalizeAppEnv(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_APP_ENV,
    expoExtra.appEnv,
    __DEV__ ? 'development' : 'production'
  )
);

function getExpoHostCandidate() {
  const candidates = [
    Constants?.expoConfig?.hostUri,
    Constants?.manifest2?.extra?.expoGo?.debuggerHost,
    Constants?.manifest?.debuggerHost,
  ];

  for (const candidate of candidates) {
    if (!candidate) continue;

    const raw = String(candidate).trim();
    const host = raw.includes(':') ? raw.split(':')[0] : raw;

    if (host) return host;
  }

  return '';
}

function resolveDefaultHost() {
  if (EXPLICIT_API_HOST) {
    return normalizeHostLikeValue(EXPLICIT_API_HOST);
  }

  if (APP_ENV === 'development') {
    const expoHost = getExpoHostCandidate();

    if (expoHost && !isLocalLikeHost(expoHost)) {
      return expoHost;
    }

    if (Platform.OS === 'android') {
      return '10.0.2.2';
    }

    return '127.0.0.1';
  }

  return '';
}

function buildBaseUrl() {
  if (EXPLICIT_API_BASE_URL) {
    return trimTrailingSlashes(EXPLICIT_API_BASE_URL);
  }

  const host = resolveDefaultHost();
  if (!host) return '';

  const scheme = EXPLICIT_API_SCHEME || 'http';
  return trimTrailingSlashes(`${scheme}://${host}:${DEFAULT_PORT}`);
}

function parseBoolean(value, fallback = false) {
  if (typeof value === 'boolean') return value;

  const normalized = String(value || '').trim().toLowerCase();
  if (!normalized) return fallback;

  return ['1', 'true', 'yes', 'on', 'enabled'].includes(normalized);
}

function readPlacement(envKey) {
  return String(process.env[envKey] || '').trim();
}

const META_APP_ID = String(process.env.EXPO_PUBLIC_META_APP_ID || '').trim();
const META_CLIENT_TOKEN = String(process.env.EXPO_PUBLIC_META_CLIENT_TOKEN || '').trim();
const META_AD_ACCOUNT_ID = String(process.env.EXPO_PUBLIC_META_AD_ACCOUNT_ID || '').trim();

const META_CONFIG = {
  appId: META_APP_ID,
  clientToken: META_CLIENT_TOKEN,
  adAccountId: META_AD_ACCOUNT_ID,
  scheme: META_APP_ID ? `fb${META_APP_ID}` : '',
  isConfigured: Boolean(META_APP_ID && META_CLIENT_TOKEN),
};

const ADS_PLACEMENTS = {
  home_inline: readPlacement('EXPO_PUBLIC_META_HOME_INLINE_PLACEMENT_ID'),
  food_inline: readPlacement('EXPO_PUBLIC_META_FOOD_INLINE_PLACEMENT_ID'),
  dineout_inline: readPlacement('EXPO_PUBLIC_META_DINEOUT_INLINE_PLACEMENT_ID'),
  events_top: readPlacement('EXPO_PUBLIC_META_EVENTS_TOP_PLACEMENT_ID'),
  events_mid: readPlacement('EXPO_PUBLIC_META_EVENTS_MID_PLACEMENT_ID'),
  explore_inline: readPlacement('EXPO_PUBLIC_META_EXPLORE_INLINE_PLACEMENT_ID'),
  reorder_inline: readPlacement('EXPO_PUBLIC_META_REORDER_INLINE_PLACEMENT_ID'),
  account_inline: readPlacement('EXPO_PUBLIC_META_ACCOUNT_INLINE_PLACEMENT_ID'),
  store_inline: readPlacement('EXPO_PUBLIC_META_STORE_INLINE_PLACEMENT_ID'),
  cart_inline: readPlacement('EXPO_PUBLIC_META_CART_INLINE_PLACEMENT_ID'),
};

const HAS_ANY_AD_PLACEMENT = Object.values(ADS_PLACEMENTS).some(Boolean);

const ADS_CONFIG = {
  enabled:
    parseBoolean(process.env.EXPO_PUBLIC_ENABLE_ADS, false) &&
    META_CONFIG.isConfigured &&
    HAS_ANY_AD_PLACEMENT,
  placements: ADS_PLACEMENTS,
};

const API_BASE_URL = buildBaseUrl();

const API_TIMEOUT_MS = Number(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_API_TIMEOUT_MS,
    expoExtra.apiTimeoutMs,
    '15000'
  )
);

const API_CONFIG_ERROR =
  !API_BASE_URL && APP_ENV === 'production'
    ? 'API base URL is not configured for this release build. Rebuild the app with EXPO_PUBLIC_API_BASE_URL set to your live API URL.'
    : '';

function buildApiUrl(path = '') {
  const normalizedPath = String(path || '').startsWith('/')
    ? String(path || '')
    : `/${String(path || '')}`;

  if (!API_BASE_URL) {
    throw new Error(API_CONFIG_ERROR || 'API base URL is not configured.');
  }

  return `${API_BASE_URL}${normalizedPath}`;
}

const APP_CONFIG = {
  env: APP_ENV,
  isDevelopment: APP_ENV === 'development',
  isProduction: APP_ENV === 'production',
  apiBaseUrl: API_BASE_URL,
  apiTimeoutMs: API_TIMEOUT_MS,
  apiConfigError: API_CONFIG_ERROR,
};

export {
  ADS_CONFIG,
  API_BASE_URL,
  API_TIMEOUT_MS,
  API_CONFIG_ERROR,
  APP_CONFIG,
  APP_ENV,
  META_CONFIG,
  buildApiUrl,
};