import Constants from 'expo-constants';
import { Platform } from 'react-native';

function firstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value ?? '').trim();
    if (text) return text;
  }
  return '';
}

function trimTrailingSlashes(value = '') {
  return String(value || '').replace(/\/+$/, '');
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

function normalizeHostLikeValue(value = '') {
  return String(value || '')
    .replace(/^\s*https?:\/\//i, '')
    .replace(/\/.*$/, '')
    .trim();
}

function parseBoolean(value, fallback = false) {
  if (typeof value === 'boolean') return value;

  const normalized = String(value || '').trim().toLowerCase();
  if (!normalized) return fallback;

  return ['1', 'true', 'yes', 'on', 'enabled'].includes(normalized);
}

function parsePositiveInt(value, fallback, { min = 1, max = Number.MAX_SAFE_INTEGER } = {}) {
  const parsed = Number.parseInt(String(value ?? '').trim(), 10);

  if (!Number.isFinite(parsed)) return fallback;
  if (parsed < min) return fallback;
  if (parsed > max) return fallback;

  return parsed;
}

function isLocalLikeHost(value = '') {
  const host = normalizeHostLikeValue(value).toLowerCase();

  return (
    host === 'localhost' ||
    host === '127.0.0.1' ||
    host === '0.0.0.0' ||
    host === '10.0.2.2' ||
    host === '10.0.3.2' ||
    host.endsWith('.local')
  );
}

function isPrivateLanHost(value = '') {
  const host = normalizeHostLikeValue(value).toLowerCase();

  return (
    /^192\.168\.\d{1,3}\.\d{1,3}$/.test(host) ||
    /^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(host) ||
    /^172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}$/.test(host)
  );
}

function safeUrl(value = '') {
  try {
    return new URL(String(value || '').trim());
  } catch {
    return null;
  }
}

function readPlacement(envKey, expoExtra = {}) {
  return firstNonEmpty(process.env[envKey], expoExtra?.meta?.placements?.[envKey], '');
}

const expoExtra = Constants?.expoConfig?.extra || {};

const APP_ENV = normalizeAppEnv(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_APP_ENV,
    expoExtra.appEnv,
    __DEV__ ? 'development' : 'production'
  )
);

const DEFAULT_PORT = parsePositiveInt(
  firstNonEmpty(process.env.EXPO_PUBLIC_API_PORT, expoExtra.apiPort, '8000'),
  8000,
  { min: 1, max: 65535 }
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
).toLowerCase();

const API_TIMEOUT_MS = parsePositiveInt(
  firstNonEmpty(process.env.EXPO_PUBLIC_API_TIMEOUT_MS, expoExtra.apiTimeoutMs, '15000'),
  15000,
  { min: 1000, max: 120000 }
);

const ALLOW_HTTP_IN_DEV = parseBoolean(
  firstNonEmpty(process.env.EXPO_PUBLIC_ALLOW_CLEARTEXT, expoExtra.allowCleartextTraffic, 'false'),
  false
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

const API_BASE_URL = buildBaseUrl();

function getApiConfigError() {
  if (!API_BASE_URL) {
    return APP_ENV === 'production'
      ? 'API base URL is not configured for production. Set EXPO_PUBLIC_API_BASE_URL to your live HTTPS API URL before building.'
      : '';
  }

  const parsed = safeUrl(API_BASE_URL);

  if (!parsed) {
    return `Invalid API base URL: ${API_BASE_URL}`;
  }

  const protocol = parsed.protocol.toLowerCase();
  const host = parsed.hostname.toLowerCase();

  if (APP_ENV === 'production') {
    if (protocol !== 'https:') {
      return `Production API must use HTTPS. Current value: ${API_BASE_URL}`;
    }

    if (isLocalLikeHost(host) || isPrivateLanHost(host)) {
      return `Production API cannot point to localhost/LAN. Current value: ${API_BASE_URL}`;
    }
  }

  if (APP_ENV !== 'production' && protocol === 'http:' && !ALLOW_HTTP_IN_DEV) {
    return `HTTP API is blocked for this build. Enable EXPO_PUBLIC_ALLOW_CLEARTEXT=true for local development, or switch to HTTPS. Current value: ${API_BASE_URL}`;
  }

  return '';
}

const API_CONFIG_ERROR = getApiConfigError();

if (APP_ENV === 'production' && API_CONFIG_ERROR) {
  throw new Error(API_CONFIG_ERROR);
}

function buildApiUrl(path = '') {
  const normalizedPath = String(path || '').startsWith('/')
    ? String(path || '')
    : `/${String(path || '')}`;

  if (API_CONFIG_ERROR) {
    throw new Error(API_CONFIG_ERROR);
  }

  if (!API_BASE_URL) {
    throw new Error('API base URL is not configured.');
  }

  return `${API_BASE_URL}${normalizedPath}`;
}

const META_APP_ID = firstNonEmpty(process.env.EXPO_PUBLIC_META_APP_ID, expoExtra?.meta?.appId);
const META_CLIENT_TOKEN = firstNonEmpty(
  process.env.EXPO_PUBLIC_META_CLIENT_TOKEN,
  expoExtra?.meta?.clientToken
);
const META_AD_ACCOUNT_ID = firstNonEmpty(
  process.env.EXPO_PUBLIC_META_AD_ACCOUNT_ID,
  expoExtra?.meta?.adAccountId
);

const META_CONFIG = {
  appId: META_APP_ID,
  clientToken: META_CLIENT_TOKEN,
  adAccountId: META_AD_ACCOUNT_ID,
  scheme: META_APP_ID ? `fb${META_APP_ID}` : '',
  isConfigured: Boolean(META_APP_ID && META_CLIENT_TOKEN),
};

const ADS_PLACEMENTS = {
  home_inline: readPlacement('EXPO_PUBLIC_META_HOME_INLINE_PLACEMENT_ID', expoExtra),
  food_inline: readPlacement('EXPO_PUBLIC_META_FOOD_INLINE_PLACEMENT_ID', expoExtra),
  dineout_inline: readPlacement('EXPO_PUBLIC_META_DINEOUT_INLINE_PLACEMENT_ID', expoExtra),
  events_top: readPlacement('EXPO_PUBLIC_META_EVENTS_TOP_PLACEMENT_ID', expoExtra),
  events_mid: readPlacement('EXPO_PUBLIC_META_EVENTS_MID_PLACEMENT_ID', expoExtra),
  explore_inline: readPlacement('EXPO_PUBLIC_META_EXPLORE_INLINE_PLACEMENT_ID', expoExtra),
  reorder_inline: readPlacement('EXPO_PUBLIC_META_REORDER_INLINE_PLACEMENT_ID', expoExtra),
  account_inline: readPlacement('EXPO_PUBLIC_META_ACCOUNT_INLINE_PLACEMENT_ID', expoExtra),
  store_inline: readPlacement('EXPO_PUBLIC_META_STORE_INLINE_PLACEMENT_ID', expoExtra),
  cart_inline: readPlacement('EXPO_PUBLIC_META_CART_INLINE_PLACEMENT_ID', expoExtra),
};

const HAS_ANY_AD_PLACEMENT = Object.values(ADS_PLACEMENTS).some(Boolean);

const ADS_CONFIG = {
  enabled:
    parseBoolean(process.env.EXPO_PUBLIC_ENABLE_ADS, false) &&
    META_CONFIG.isConfigured &&
    HAS_ANY_AD_PLACEMENT,
  placements: ADS_PLACEMENTS,
};

const APP_CONFIG = {
  env: APP_ENV,
  isDevelopment: APP_ENV === 'development',
  isProduction: APP_ENV === 'production',
  apiBaseUrl: API_BASE_URL,
  apiTimeoutMs: API_TIMEOUT_MS,
  apiConfigError: API_CONFIG_ERROR,
  allowHttpInDev: ALLOW_HTTP_IN_DEV,
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