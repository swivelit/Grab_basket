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
    appName: 'Grab Basket Delivery',
    href: '/(delivery)/(tabs)',
    role: 'PARTNER',
    description: 'Delivery partner app shell',
  },
  partner: {
    key: 'partner',
    appName: 'Grab Basket Partner',
    href: '/(partner)/(tabs)',
    role: 'SELLER',
    description: 'Seller app shell',
  },
};

export function normalizeAppVariant(value = '') {
  const normalized = String(value || '').trim().toLowerCase();

  if (normalized === 'customer') return 'consumer';
  if (normalized === 'seller' || normalized === 'merchant') return 'partner';
  if (normalized === 'delivery' || normalized === 'partner_delivery' || normalized === 'rider') {
    return 'delivery';
  }

  return APP_SHELLS[normalized] ? normalized : 'consumer';
}

export function getAppVariant() {
  // Prefer EXPO_PUBLIC_* (works in dev + production), but fall back to app.config extra.
  let value = process.env.EXPO_PUBLIC_APP_VARIANT;

  if (!value) {
    try {
      // Lazy require to avoid issues in non-Expo runtimes.
      // eslint-disable-next-line global-require
      const Constants = require('expo-constants').default;
      value = Constants?.expoConfig?.extra?.appVariant || Constants?.manifest2?.extra?.appVariant;
    } catch {
      // ignore
    }
  }

  return normalizeAppVariant(value || 'consumer');
}

export function getAppShellConfig(variant = getAppVariant()) {
  return APP_SHELLS[normalizeAppVariant(variant)] || APP_SHELLS.consumer;
}

export function getInitialShellHref(variant = getAppVariant()) {
  return getAppShellConfig(variant).href;
}