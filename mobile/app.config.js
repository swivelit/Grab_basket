const pkg = require('./package.json');

function readEnv(key, fallback = '') {
  const value = process.env[key];

  if (value === undefined || value === null) {
    return fallback;
  }

  const text = String(value).trim();
  return text === '' ? fallback : text;
}

function readVariantEnv(key, variant, fallback = '') {
  const suffix = String(variant || '').trim().toUpperCase();

  if (!suffix) {
    return readEnv(key, fallback);
  }

  return readEnv(`${key}_${suffix}`, readEnv(key, fallback));
}

function readBool(key, fallback = false) {
  const value = readEnv(key, '');

  if (!value) {
    return fallback;
  }

  return ['1', 'true', 'yes', 'on', 'enabled'].includes(value.toLowerCase());
}

function readInt(key, fallback, { min = Number.NEGATIVE_INFINITY } = {}) {
  const raw = readEnv(key, '');

  if (!raw) {
    return fallback;
  }

  const value = Number.parseInt(raw, 10);

  if (!Number.isFinite(value)) {
    return fallback;
  }

  if (value < min) {
    return fallback;
  }

  return value;
}

function readFloat(
  key,
  fallback,
  { min = Number.NEGATIVE_INFINITY, max = Number.POSITIVE_INFINITY } = {}
) {
  const raw = readEnv(key, '');

  if (!raw) {
    return fallback;
  }

  const value = Number.parseFloat(raw);

  if (!Number.isFinite(value)) {
    return fallback;
  }

  if (value < min || value > max) {
    return fallback;
  }

  return value;
}

function normalizeAppEnv(value) {
  const normalized = String(value || '').trim().toLowerCase();

  if (!normalized) {
    return 'development';
  }

  if (['prod', 'production', 'release'].includes(normalized)) {
    return 'production';
  }

  if (['dev', 'development', 'debug'].includes(normalized)) {
    return 'development';
  }

  return normalized;
}

function normalizeAppVariant(value) {
  const normalized = String(value || '').trim().toLowerCase();

  if (normalized === 'customer') return 'consumer';
  if (normalized === 'consumer') return 'consumer';

  if (normalized === 'seller' || normalized === 'merchant') return 'partner';
  if (normalized === 'partner') return 'partner';

  if (
    normalized === 'delivery' ||
    normalized === 'rider' ||
    normalized === 'partner_delivery'
  ) {
    return 'delivery';
  }

  return ['consumer', 'delivery', 'partner'].includes(normalized)
    ? normalized
    : 'consumer';
}

function isHttpUrl(value = '') {
  return /^http:\/\/?/i.test(String(value || '').trim());
}

const APP_VARIANT = normalizeAppVariant(
  readEnv('EXPO_PUBLIC_APP_VARIANT', 'consumer')
);

const VARIANT_DEFAULTS = {
  consumer: {
    appName: 'Grab Basket',
    slug: 'grab-basket',
    scheme: 'grabbasket',
    iosBundleId: 'com.grabbasket.consumer',
    androidPackage: 'com.grabbasket.consumer',
  },
  delivery: {
    appName: 'Grab Basket Delivery App',
    slug: 'grab-basket-delivery',
    scheme: 'grabbasketdelivery',
    iosBundleId: 'com.grabbasket.delivery',
    androidPackage: 'com.grabbasket.delivery',
  },
  partner: {
    appName: 'Grab Basket Partner App',
    slug: 'grab-basket-partner',
    scheme: 'grabbasketpartner',
    iosBundleId: 'com.grabbasket.partner',
    androidPackage: 'com.grabbasket.partner',
  },
};

const VARIANT = VARIANT_DEFAULTS[APP_VARIANT] || VARIANT_DEFAULTS.consumer;
const IS_DELIVERY_APP = APP_VARIANT === 'delivery';

const APP_NAME = readEnv('EXPO_PUBLIC_APP_NAME', VARIANT.appName);
const APP_SLUG = readEnv('EXPO_PUBLIC_APP_SLUG', VARIANT.slug);
const APP_SCHEME = readEnv('EXPO_PUBLIC_APP_SCHEME', VARIANT.scheme);
const APP_VERSION = readEnv('EXPO_PUBLIC_APP_VERSION', pkg.version || '1.0.0');

const APP_ENV = normalizeAppEnv(
  readEnv(
    'EXPO_PUBLIC_APP_ENV',
    process.env.NODE_ENV === 'production' ? 'production' : 'development'
  )
);

const IOS_BUNDLE_IDENTIFIER = readEnv(
  'EXPO_PUBLIC_IOS_BUNDLE_IDENTIFIER',
  VARIANT.iosBundleId
);
const ANDROID_PACKAGE = readEnv(
  'EXPO_PUBLIC_ANDROID_PACKAGE',
  VARIANT.androidPackage
);
const ANDROID_VERSION_CODE = readInt('EXPO_PUBLIC_ANDROID_VERSION_CODE', 1, {
  min: 1,
});

const EAS_PROJECT_ID = readEnv('EXPO_PUBLIC_EAS_PROJECT_ID', '');
const EXPO_OWNER = readEnv('EXPO_PUBLIC_EXPO_OWNER', '');

const META_APP_ID = readEnv('EXPO_PUBLIC_META_APP_ID', '');
const META_CLIENT_TOKEN = readEnv('EXPO_PUBLIC_META_CLIENT_TOKEN', '');
const META_AD_ACCOUNT_ID = readEnv('EXPO_PUBLIC_META_AD_ACCOUNT_ID', '');
const HAS_META_CREDENTIALS = Boolean(META_APP_ID && META_CLIENT_TOKEN);

const API_BASE_URL = readEnv('EXPO_PUBLIC_API_BASE_URL', '');
const API_HOST = readEnv('EXPO_PUBLIC_API_HOST', '');
const API_PORT = readEnv('EXPO_PUBLIC_API_PORT', '8000');
const API_SCHEME = readEnv('EXPO_PUBLIC_API_SCHEME', 'http').toLowerCase();

const API_TIMEOUT_MS = readInt('EXPO_PUBLIC_API_TIMEOUT_MS', 15000, {
  min: 1000,
});

const ENABLE_ADS = readBool('EXPO_PUBLIC_ENABLE_ADS', false);
const ENABLE_NEW_ARCH = readBool('EXPO_PUBLIC_ENABLE_NEW_ARCH', true);
const GOOGLE_MAPS_API_KEY = readEnv('EXPO_PUBLIC_GOOGLE_MAPS_API_KEY', '');

const HAS_API_HINT = Boolean(API_BASE_URL || API_HOST || APP_ENV !== 'production');

const ALLOW_CLEARTEXT = readBool(
  'EXPO_PUBLIC_ALLOW_CLEARTEXT',
  isHttpUrl(API_BASE_URL) || (HAS_API_HINT && API_SCHEME === 'http')
);

const DEFAULT_SENTRY_TRACES_SAMPLE_RATE = APP_ENV === 'production' ? 0.2 : 1.0;
const DEFAULT_SENTRY_PROFILES_SAMPLE_RATE = APP_ENV === 'production' ? 0.1 : 1.0;
const DEFAULT_POSTHOG_HOST = 'https://us.i.posthog.com';

const SENTRY_DSN = readVariantEnv('EXPO_PUBLIC_SENTRY_DSN', APP_VARIANT, '');
const SENTRY_URL = readEnv('EXPO_PUBLIC_SENTRY_URL', 'https://sentry.io/');
const SENTRY_ORG = readEnv('EXPO_PUBLIC_SENTRY_ORG', '');
const SENTRY_PROJECT = readVariantEnv(
  'EXPO_PUBLIC_SENTRY_PROJECT',
  APP_VARIANT,
  ''
);
const SENTRY_ENVIRONMENT = readEnv(
  'EXPO_PUBLIC_SENTRY_ENVIRONMENT',
  APP_ENV
);
const SENTRY_TRACES_SAMPLE_RATE = readFloat(
  'EXPO_PUBLIC_SENTRY_TRACES_SAMPLE_RATE',
  DEFAULT_SENTRY_TRACES_SAMPLE_RATE,
  { min: 0, max: 1 }
);
const SENTRY_PROFILES_SAMPLE_RATE = readFloat(
  'EXPO_PUBLIC_SENTRY_PROFILES_SAMPLE_RATE',
  DEFAULT_SENTRY_PROFILES_SAMPLE_RATE,
  { min: 0, max: 1 }
);
const SENTRY_ENABLED = readBool(
  'EXPO_PUBLIC_SENTRY_ENABLED',
  Boolean(SENTRY_DSN)
);

const SENTRY_AUTH_TOKEN = readEnv('SENTRY_AUTH_TOKEN', '');
const SENTRY_UPLOAD_ENABLED = readBool('EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED', false);

const SHOULD_ENABLE_SENTRY_PLUGIN = Boolean(
  SENTRY_ENABLED &&
    SENTRY_UPLOAD_ENABLED &&
    SENTRY_ORG &&
    SENTRY_PROJECT &&
    SENTRY_AUTH_TOKEN
);

const POSTHOG_API_KEY = readVariantEnv(
  'EXPO_PUBLIC_POSTHOG_API_KEY',
  APP_VARIANT,
  ''
);
const POSTHOG_HOST = readEnv('EXPO_PUBLIC_POSTHOG_HOST', DEFAULT_POSTHOG_HOST);
const POSTHOG_ENABLED = readBool(
  'EXPO_PUBLIC_POSTHOG_ENABLED',
  Boolean(POSTHOG_API_KEY)
);

const pushPermissionLabel = `${APP_NAME} uses notifications for order updates, assignment alerts, and payment status changes.`;
const deliveryLocationWhenInUsePermission = `${APP_NAME} uses your location while the app is open so riders can see pickup points and delivery routes.`;
const deliveryLocationAlwaysPermission = `${APP_NAME} uses your location in the background so customers and sellers can track active deliveries.`;

const plugins = [
  [
    'expo-notifications',
    {
      icon: './assets/images/android-icon-monochrome.png',
      color: '#D97651',
      sounds: [],
      defaultChannel: 'orders-updates',
    },
  ],
  'expo-router',
  [
    'expo-secure-store',
    {
      configureAndroidBackup: true,
    },
  ],
  [
    'expo-build-properties',
    {
      android: {
        usesCleartextTraffic: ALLOW_CLEARTEXT,
      },
    },
  ],
  [
    'expo-tracking-transparency',
    {
      userTrackingPermission:
        'We use this data to improve measurement, attribution, and relevant sponsored content.',
    },
  ],
];

if (IS_DELIVERY_APP) {
  plugins.splice(2, 0, [
    'expo-location',
    {
      locationWhenInUsePermission: deliveryLocationWhenInUsePermission,
      locationAlwaysAndWhenInUsePermission: deliveryLocationAlwaysPermission,
      isAndroidBackgroundLocationEnabled: true,
      isAndroidForegroundServiceEnabled: true,
      isIosBackgroundLocationEnabled: true,
    },
  ]);
}

if (SHOULD_ENABLE_SENTRY_PLUGIN) {
  plugins.push([
    '@sentry/react-native/expo',
    {
      url: SENTRY_URL,
      organization: SENTRY_ORG,
      project: SENTRY_PROJECT,
      note: 'Enable EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED=true only in CI/release pipelines where you want source-map upload.',
    },
  ]);
}

if (HAS_META_CREDENTIALS) {
  plugins.push([
    'react-native-fbsdk-next',
    {
      appID: META_APP_ID,
      clientToken: META_CLIENT_TOKEN,
      displayName: APP_NAME,
      scheme: `fb${META_APP_ID}`,
      advertiserIDCollectionEnabled: true,
      autoLogAppEventsEnabled: true,
      isAutoInitEnabled: true,
      iosUserTrackingPermission:
        'We use this identifier to improve attribution and personalize relevant sponsored content.',
    },
  ]);
}

plugins.push(
  GOOGLE_MAPS_API_KEY
    ? [
        'react-native-maps',
        {
          androidGoogleMapsApiKey: GOOGLE_MAPS_API_KEY,
        },
      ]
    : 'react-native-maps'
);

const iosInfoPlist = {
  ITSAppUsesNonExemptEncryption: false,
  NSUserTrackingUsageDescription:
    'We use this identifier to improve attribution and personalize relevant sponsored content.',
  UIBackgroundModes: IS_DELIVERY_APP ? ['location'] : undefined,
};

if (IS_DELIVERY_APP) {
  iosInfoPlist.NSLocationWhenInUseUsageDescription = deliveryLocationWhenInUsePermission;
  iosInfoPlist.NSLocationAlwaysAndWhenInUseUsageDescription = deliveryLocationAlwaysPermission;
  iosInfoPlist.NSLocationAlwaysUsageDescription = deliveryLocationAlwaysPermission;
}

const androidPermissions = ['POST_NOTIFICATIONS'];

if (IS_DELIVERY_APP) {
  androidPermissions.push(
    'ACCESS_COARSE_LOCATION',
    'ACCESS_FINE_LOCATION',
    'ACCESS_BACKGROUND_LOCATION',
    'FOREGROUND_SERVICE',
    'FOREGROUND_SERVICE_LOCATION',
    'RECEIVE_BOOT_COMPLETED',
    'WAKE_LOCK'
  );
}

const expoConfig = {
  name: APP_NAME,
  slug: APP_SLUG,
  scheme: APP_SCHEME,
  version: APP_VERSION,
  orientation: 'portrait',
  userInterfaceStyle: 'light',
  newArchEnabled: ENABLE_NEW_ARCH,
  experiments: {
    typedRoutes: true,
  },
  assetBundlePatterns: ['**/*'],
  icon: './assets/images/icon.png',
  splash: {
    image: './assets/images/splash-icon.png',
    resizeMode: 'contain',
    backgroundColor: '#ffffff',
  },
  updates: {
    fallbackToCacheTimeout: 0,
  },
  runtimeVersion: {
    policy: 'appVersion',
  },
  notification: {
    icon: './assets/images/android-icon-monochrome.png',
    color: '#D97651',
    androidMode: 'default',
    androidCollapsedTitle: APP_NAME,
  },
  ios: {
    bundleIdentifier: IOS_BUNDLE_IDENTIFIER,
    supportsTablet: true,
    infoPlist: iosInfoPlist,
  },
  android: {
    package: ANDROID_PACKAGE,
    permissions: androidPermissions,
    versionCode: ANDROID_VERSION_CODE,
    adaptiveIcon: {
      foregroundImage: './assets/images/android-icon-foreground.png',
      backgroundImage: './assets/images/android-icon-background.png',
      monochromeImage: './assets/images/android-icon-monochrome.png',
    },
    config: {
      googleMaps: GOOGLE_MAPS_API_KEY ? { apiKey: GOOGLE_MAPS_API_KEY } : undefined,
    },
    notification: {
      icon: './assets/images/android-icon-monochrome.png',
      color: '#D97651',
      defaultChannel: 'orders-updates',
    },
  },
  web: {
    bundler: 'metro',
    favicon: './assets/images/favicon.png',
  },
  plugins,
  extra: {
    appEnv: APP_ENV,
    appVariant: APP_VARIANT,
    apiBaseUrl: API_BASE_URL,
    apiHost: API_HOST,
    apiPort: API_PORT,
    apiScheme: API_SCHEME,
    apiTimeoutMs: API_TIMEOUT_MS,
    allowCleartextTraffic: ALLOW_CLEARTEXT,
    permissions: {
      notifications: pushPermissionLabel,
      locationWhenInUse: IS_DELIVERY_APP ? deliveryLocationWhenInUsePermission : '',
      backgroundLocation: IS_DELIVERY_APP ? deliveryLocationAlwaysPermission : '',
    },
    meta: {
      appId: META_APP_ID,
      adAccountId: META_AD_ACCOUNT_ID,
      hasCredentials: HAS_META_CREDENTIALS,
      adsEnabled: ENABLE_ADS && HAS_META_CREDENTIALS,
    },
    telemetry: {
      sentry: {
        dsn: SENTRY_DSN,
        enabled: SENTRY_ENABLED,
        environment: SENTRY_ENVIRONMENT,
        tracesSampleRate: SENTRY_TRACES_SAMPLE_RATE,
        profilesSampleRate: SENTRY_PROFILES_SAMPLE_RATE,
        url: SENTRY_URL,
        organization: SENTRY_ORG,
        project: SENTRY_PROJECT,
        uploadEnabled: SENTRY_UPLOAD_ENABLED,
      },
      posthog: {
        apiKey: POSTHOG_API_KEY,
        host: POSTHOG_HOST,
        enabled: POSTHOG_ENABLED,
      },
    },
    eas: {
      projectId: EAS_PROJECT_ID || undefined,
    },
    googleMaps: {
      apiKey: GOOGLE_MAPS_API_KEY,
    },
  },
};

if (EXPO_OWNER) {
  expoConfig.owner = EXPO_OWNER;
}

if (EAS_PROJECT_ID) {
  expoConfig.updates.url = `https://u.expo.dev/${EAS_PROJECT_ID}`;
}

module.exports = () => ({
  expo: expoConfig,
});