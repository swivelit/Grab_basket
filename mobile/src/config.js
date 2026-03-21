import Constants from 'expo-constants';
import { Platform } from 'react-native';

const DEFAULT_PORT = process.env.EXPO_PUBLIC_API_PORT || '8000';
const EXPLICIT_API_BASE_URL = String(process.env.EXPO_PUBLIC_API_BASE_URL || '').trim();
const EXPLICIT_API_HOST = String(process.env.EXPO_PUBLIC_API_HOST || '').trim();
const EXPLICIT_API_SCHEME = String(process.env.EXPO_PUBLIC_API_SCHEME || 'http').trim();
const APP_ENV = String(process.env.EXPO_PUBLIC_APP_ENV || (__DEV__ ? 'development' : 'production')).trim().toLowerCase();

function trimTrailingSlashes(value = '') {
  return String(value || '').replace(/\/+$/, '');
}

function normalizeHostLikeValue(value = '') {
  return String(value || '')
    .replace(/^https?:\/\//i, '')
    .replace(/\/.*$/, '')
    .trim();
}

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

  const expoHost = getExpoHostCandidate();
  if (expoHost && expoHost !== 'localhost' && expoHost !== '127.0.0.1') {
    return expoHost;
  }

  if (Platform.OS === 'android') {
    return '10.0.2.2';
  }

  return '127.0.0.1';
}

function buildBaseUrl() {
  if (EXPLICIT_API_BASE_URL) {
    return trimTrailingSlashes(EXPLICIT_API_BASE_URL);
  }

  const host = resolveDefaultHost();
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
  enabled: parseBoolean(process.env.EXPO_PUBLIC_ENABLE_ADS, false) && META_CONFIG.isConfigured && HAS_ANY_AD_PLACEMENT,
  placements: ADS_PLACEMENTS,
};

const API_BASE_URL = buildBaseUrl();
const API_TIMEOUT_MS = Number(process.env.EXPO_PUBLIC_API_TIMEOUT_MS || 15000);

function buildApiUrl(path = '') {
  const normalizedPath = String(path || '').startsWith('/')
    ? String(path || '')
    : `/${String(path || '')}`;
  return `${API_BASE_URL}${normalizedPath}`;
}

const APP_CONFIG = {
  env: APP_ENV,
  isDevelopment: APP_ENV === 'development',
  isProduction: APP_ENV === 'production',
  apiBaseUrl: API_BASE_URL,
  apiTimeoutMs: API_TIMEOUT_MS,
};

export {
  ADS_CONFIG,
  API_BASE_URL,
  API_TIMEOUT_MS,
  APP_CONFIG,
  APP_ENV,
  META_CONFIG,
  buildApiUrl,
};