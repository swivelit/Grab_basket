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

export function getEmbeddedAppVariant() {
  return normalizeAppVariant(BUILD_CONFIG?.appVariant, 'consumer');
}

export function getEmbeddedInitialShellHref() {
  const href = String(BUILD_CONFIG?.initialHref || '').trim();
  return href || '/(tabs)';
}

export function getAppVariant() {
  return 'consumer';
}

export function getAppShellConfig() {
  return APP_SHELLS.consumer;
}

export function getInitialShellHref() {
  return '/(tabs)';
}