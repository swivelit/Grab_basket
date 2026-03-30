import Constants from 'expo-constants';

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

export function normalizeAppVariant(value = '', fallback = 'consumer') {
  const normalized = String(value || '').trim().toLowerCase();

  if (!normalized) {
    return fallback;
  }

  if (normalized === 'customer') return 'consumer';
  if (normalized === 'seller' || normalized === 'merchant') return 'partner';
  if (normalized === 'delivery' || normalized === 'partner_delivery' || normalized === 'rider') {
    return 'delivery';
  }

  return APP_SHELLS[normalized] ? normalized : fallback;
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

export function getAppVariant() {
  // Prefer the embedded native/expo config first.
  // This is more stable across release APK builds than relying on Metro-inlined env values.
  const expoExtra = getExpoExtra();

  const fromExpoExtra = normalizeAppVariant(expoExtra?.appVariant, '');
  if (fromExpoExtra) {
    return fromExpoExtra;
  }

  const fromApplicationId = getVariantFromNativeApplicationId();
  if (fromApplicationId) {
    return fromApplicationId;
  }

  // Last-resort fallback only.
  const fromProcessEnv = normalizeAppVariant(process?.env?.EXPO_PUBLIC_APP_VARIANT, '');
  if (fromProcessEnv) {
    return fromProcessEnv;
  }

  return 'consumer';
}

export function getAppShellConfig(variant = getAppVariant()) {
  return APP_SHELLS[normalizeAppVariant(variant)] || APP_SHELLS.consumer;
}

export function getInitialShellHref(variant = getAppVariant()) {
  return getAppShellConfig(variant).href;
}