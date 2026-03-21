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
  return normalizeAppVariant(process.env.EXPO_PUBLIC_APP_VARIANT || 'consumer');
}

export function getAppShellConfig(variant = getAppVariant()) {
  return APP_SHELLS[normalizeAppVariant(variant)] || APP_SHELLS.consumer;
}

export function getInitialShellHref(variant = getAppVariant()) {
  return getAppShellConfig(variant).href;
}