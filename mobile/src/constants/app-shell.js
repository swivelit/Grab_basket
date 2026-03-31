import Constants from 'expo-constants';
import BUILD_CONFIG from '../generated/app-build-config';

export const APP_SHELLS = {
  consumer: {
    key: 'consumer',
    appName: 'Grab Basket',
    href: '/(tabs)',
    role: 'CUSTOMER',
    description: 'Customer ordering app shell',
  },
  delivery: {
    key: 'delivery',
    appName: 'Grab Basket Delivery App',
    href: '/(delivery)/(tabs)',
    role: 'PARTNER',
    description: 'Delivery partner app shell',
  },
  partner: {
    key: 'partner',
    appName: 'Grab Basket Partner App',
    href: '/(partner)/(tabs)',
    role: 'SELLER',
    description: 'Seller app shell',
  },
};

const VALID_HREFS = Object.values(APP_SHELLS).map((item) => item.href);

export function normalizeAppVariant(value = '', fallback = 'consumer') {
  const normalized = String(value || '').trim().toLowerCase();

  if (!normalized) {
    return fallback;
  }

  if (normalized === 'customer' || normalized === 'user') return 'consumer';
  if (normalized === 'seller' || normalized === 'merchant' || normalized === 'vendor') return 'partner';
  if (normalized === 'delivery' || normalized === 'partner_delivery' || normalized === 'rider') {
    return 'delivery';
  }

  return APP_SHELLS[normalized] ? normalized : fallback;
}

function normalizeShellHref(value = '', fallback = '') {
  const normalized = String(value || '').trim();
  return VALID_HREFS.includes(normalized) ? normalized : fallback;
}

function getExpoExtra() {
  return (
    Constants?.expoConfig?.extra ||
    Constants?.manifest2?.extra ||
    Constants?.manifest?.extra ||
    {}
  );
}

function getNativeApplicationId() {
  try {
    const Application = require('expo-application');
    return String(Application?.applicationId || '').trim().toLowerCase();
  } catch {
    return '';
  }
}

function getVariantFromNativeApplicationId() {
  const applicationId = getNativeApplicationId();

  if (!applicationId) {
    return '';
  }

  if (applicationId.endsWith('.consumer')) return 'consumer';
  if (applicationId.endsWith('.delivery')) return 'delivery';
  if (applicationId.endsWith('.partner')) return 'partner';

  return '';
}

export function getEmbeddedAppVariant() {
  return normalizeAppVariant(BUILD_CONFIG?.appVariant, '');
}

export function getEmbeddedInitialShellHref() {
  return normalizeShellHref(BUILD_CONFIG?.initialHref, '');
}

export function getAppVariant() {
  const fromEmbeddedConfig = getEmbeddedAppVariant();
  if (fromEmbeddedConfig) {
    return fromEmbeddedConfig;
  }

  const fromApplicationId = getVariantFromNativeApplicationId();
  if (fromApplicationId) {
    return fromApplicationId;
  }

  const expoExtra = getExpoExtra();

  const fromExpoExtra = normalizeAppVariant(expoExtra?.appVariant, '');
  if (fromExpoExtra) {
    return fromExpoExtra;
  }

  const fromProcessEnv = normalizeAppVariant(process?.env?.EXPO_PUBLIC_APP_VARIANT, '');
  if (fromProcessEnv) {
    return fromProcessEnv;
  }

  return 'consumer';
}

export function getAppShellConfig(variant = getAppVariant()) {
  return APP_SHELLS[normalizeAppVariant(variant)] || APP_SHELLS.consumer;
}

export function getInitialShellHref(variant = '') {
  const explicitVariant = normalizeAppVariant(variant, '');

  if (!explicitVariant) {
    const embeddedHref = getEmbeddedInitialShellHref();
    if (embeddedHref) {
      return embeddedHref;
    }
  }

  return getAppShellConfig(explicitVariant || getAppVariant()).href;
}