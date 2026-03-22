import Constants from 'expo-constants';
import { Platform } from 'react-native';

import { APP_ENV } from '../config';
import { getAppShellConfig, getAppVariant } from '../constants/app-shell';

function firstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value ?? '').trim();
    if (text) return text;
  }

  return '';
}

function parseBoolean(value, fallback = false) {
  if (typeof value === 'boolean') return value;

  const normalized = String(value ?? '').trim().toLowerCase();
  if (!normalized) return fallback;

  return ['1', 'true', 'yes', 'on', 'enabled'].includes(normalized);
}

function parseNumber(value, fallback = 0) {
  const parsed = Number.parseFloat(String(value ?? '').trim());
  return Number.isFinite(parsed) ? parsed : fallback;
}

function toPlainObject(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return {};
  }

  return Object.entries(value).reduce((accumulator, [key, entry]) => {
    if (entry === undefined) return accumulator;

    if (Array.isArray(entry)) {
      accumulator[key] = entry.map((item) => String(item ?? ''));
      return accumulator;
    }

    if (entry && typeof entry === 'object') {
      try {
        accumulator[key] = JSON.parse(JSON.stringify(entry));
      } catch {
        accumulator[key] = String(entry);
      }
      return accumulator;
    }

    accumulator[key] = entry;
    return accumulator;
  }, {});
}

function normalizeError(error) {
  if (error instanceof Error) {
    return error;
  }

  if (typeof error === 'string' && error.trim()) {
    return new Error(error.trim());
  }

  try {
    return new Error(JSON.stringify(error));
  } catch {
    return new Error('Unknown error');
  }
}

function getOptionalModule(name) {
  try {
    return require(name);
  } catch {
    return null;
  }
}

function getOptionalDefaultExport(name) {
  const module = getOptionalModule(name);
  return module?.default || module;
}

const expoExtra =
  Constants?.expoConfig?.extra || Constants?.manifest2?.extra || Constants?.manifest?.extra || {};

const APP_VARIANT = getAppVariant();
const APP_SHELL = getAppShellConfig(APP_VARIANT);

const telemetryExtra = expoExtra?.telemetry || {};
const sentryExtra = telemetryExtra?.sentry || {};
const posthogExtra = telemetryExtra?.posthog || {};

const SENTRY_DSN = firstNonEmpty(process.env.EXPO_PUBLIC_SENTRY_DSN, sentryExtra?.dsn);
const SENTRY_ENABLED = parseBoolean(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_SENTRY_ENABLED,
    sentryExtra?.enabled,
    SENTRY_DSN ? 'true' : ''
  ),
  Boolean(SENTRY_DSN)
);
const SENTRY_ENVIRONMENT = firstNonEmpty(
  process.env.EXPO_PUBLIC_SENTRY_ENVIRONMENT,
  sentryExtra?.environment,
  APP_ENV
);
const SENTRY_TRACES_SAMPLE_RATE = parseNumber(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_SENTRY_TRACES_SAMPLE_RATE,
    sentryExtra?.tracesSampleRate
  ),
  APP_ENV === 'production' ? 0.2 : 1.0
);
const SENTRY_PROFILES_SAMPLE_RATE = parseNumber(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_SENTRY_PROFILES_SAMPLE_RATE,
    sentryExtra?.profilesSampleRate
  ),
  APP_ENV === 'production' ? 0.1 : 1.0
);

const POSTHOG_API_KEY = firstNonEmpty(
  process.env.EXPO_PUBLIC_POSTHOG_API_KEY,
  posthogExtra?.apiKey
);
const POSTHOG_HOST = firstNonEmpty(
  process.env.EXPO_PUBLIC_POSTHOG_HOST,
  posthogExtra?.host,
  'https://us.i.posthog.com'
);
const POSTHOG_ENABLED = parseBoolean(
  firstNonEmpty(
    process.env.EXPO_PUBLIC_POSTHOG_ENABLED,
    posthogExtra?.enabled,
    POSTHOG_API_KEY ? 'true' : ''
  ),
  Boolean(POSTHOG_API_KEY)
);

let sentryModuleCache;
let sentryInitialized = false;
let posthogClientCache;

function getApplicationModule() {
  return getOptionalModule('expo-application');
}

function getDeviceModule() {
  return getOptionalModule('expo-device');
}

function getLocalizationModule() {
  return getOptionalModule('expo-localization');
}

function getSentry() {
  if (sentryModuleCache !== undefined) {
    return sentryModuleCache;
  }

  sentryModuleCache = getOptionalModule('@sentry/react-native');
  return sentryModuleCache;
}

function isNativeRuntime() {
  return Platform.OS === 'android' || Platform.OS === 'ios';
}

function ensureSentryInitialized() {
  if (sentryInitialized) {
    return getSentry();
  }

  sentryInitialized = true;

  const Sentry = getSentry();
  if (!Sentry || !isNativeRuntime() || !SENTRY_ENABLED || !SENTRY_DSN) {
    return Sentry;
  }

  const Application = getApplicationModule();
  const Device = getDeviceModule();
  const Localization = getLocalizationModule();

  const appVersion =
    Application?.nativeApplicationVersion || Constants?.expoConfig?.version || '1.0.0';
  const buildVersion = Application?.nativeBuildVersion || '1';

  Sentry.init({
    dsn: SENTRY_DSN,
    enabled: true,
    environment: SENTRY_ENVIRONMENT,
    sendDefaultPii: true,
    tracesSampleRate: SENTRY_TRACES_SAMPLE_RATE,
    profilesSampleRate: SENTRY_PROFILES_SAMPLE_RATE,
    enableNativeFramesTracking: true,
    release: `${APP_VARIANT}@${appVersion}`,
    dist: String(buildVersion)
  });

  if (typeof Sentry.setTags === 'function') {
    Sentry.setTags({
      app_name: APP_SHELL.appName || 'Grab Basket',
      app_variant: APP_VARIANT,
      app_env: APP_ENV,
      platform: Platform.OS
    });
  }

  if (typeof Sentry.setContext === 'function') {
    Sentry.setContext('device', {
      brand: Device?.brand || '',
      manufacturer: Device?.manufacturer || '',
      modelName: Device?.modelName || '',
      osName: Device?.osName || Platform.OS,
      osVersion: Device?.osVersion || '',
      locale:
        Localization?.getLocales?.()?.[0]?.languageTag ||
        Localization?.locale ||
        ''
    });
  }

  return Sentry;
}

function getPostHogClient() {
  if (posthogClientCache !== undefined) {
    return posthogClientCache;
  }

  if (!isNativeRuntime() || !POSTHOG_ENABLED || !POSTHOG_API_KEY) {
    posthogClientCache = null;
    return posthogClientCache;
  }

  const PostHog = getOptionalDefaultExport('posthog-react-native');

  if (!PostHog) {
    posthogClientCache = null;
    return posthogClientCache;
  }

  posthogClientCache = new PostHog(POSTHOG_API_KEY, {
    host: POSTHOG_HOST,
    disable: false
  });

  if (typeof posthogClientCache.register === 'function') {
    posthogClientCache.register({
      app_env: APP_ENV,
      app_name: APP_SHELL.appName || 'Grab Basket',
      app_variant: APP_VARIANT,
      platform: Platform.OS
    });
  }

  return posthogClientCache;
}

function addNavigationBreadcrumb(screenName, params = {}) {
  const Sentry = ensureSentryInitialized();

  if (!Sentry || typeof Sentry.addBreadcrumb !== 'function') {
    return;
  }

  Sentry.addBreadcrumb({
    category: 'navigation',
    type: 'navigation',
    level: 'info',
    message: screenName,
    data: toPlainObject(params)
  });
}

function captureException(error, context = {}) {
  const normalizedError = normalizeError(error);
  const { tags = {}, extras = {}, level = 'error' } = context || {};

  const Sentry = ensureSentryInitialized();

  if (
    Sentry &&
    typeof Sentry.withScope === 'function' &&
    typeof Sentry.captureException === 'function'
  ) {
    Sentry.withScope((scope) => {
      if (typeof scope.setLevel === 'function') {
        scope.setLevel(level);
      }

      Object.entries(toPlainObject(tags)).forEach(([key, value]) => {
        if (typeof scope.setTag === 'function') {
          scope.setTag(key, String(value));
        }
      });

      if (typeof scope.setContext === 'function') {
        scope.setContext('debug_context', toPlainObject(extras));
      }

      Sentry.captureException(normalizedError);
    });
  }

  const posthog = getPostHogClient();
  if (posthog && typeof posthog.capture === 'function') {
    posthog.capture('exception_captured', {
      app_variant: APP_VARIANT,
      message: normalizedError.message,
      name: normalizedError.name,
      ...toPlainObject(tags),
      ...toPlainObject(extras)
    });
  }
}

function captureEvent(name, properties = {}) {
  const posthog = getPostHogClient();
  if (!posthog || typeof posthog.capture !== 'function') {
    return;
  }

  posthog.capture(String(name || 'app_event'), {
    app_env: APP_ENV,
    app_name: APP_SHELL.appName || 'Grab Basket',
    app_variant: APP_VARIANT,
    platform: Platform.OS,
    ...toPlainObject(properties)
  });
}

function trackScreen(pathname, params = {}) {
  const screenName = String(pathname || '/').trim() || '/';
  const cleanedParams = toPlainObject(params);

  addNavigationBreadcrumb(screenName, cleanedParams);

  const posthog = getPostHogClient();
  if (!posthog || typeof posthog.screen !== 'function') {
    return;
  }

  posthog.screen(screenName, {
    app_env: APP_ENV,
    app_name: APP_SHELL.appName || 'Grab Basket',
    app_variant: APP_VARIANT,
    platform: Platform.OS,
    ...cleanedParams
  });
}

function identifyUser(userId, traits = {}) {
  const normalizedUserId = String(userId || '').trim();
  const normalizedTraits = toPlainObject(traits);

  if (!normalizedUserId) {
    return;
  }

  const Sentry = ensureSentryInitialized();
  if (Sentry && typeof Sentry.setUser === 'function') {
    Sentry.setUser({
      id: normalizedUserId,
      email: String(normalizedTraits.email || normalizedUserId || ''),
      app_variant: APP_VARIANT,
      ...normalizedTraits
    });
  }

  const posthog = getPostHogClient();
  if (posthog && typeof posthog.identify === 'function') {
    posthog.identify(normalizedUserId, {
      app_env: APP_ENV,
      app_name: APP_SHELL.appName || 'Grab Basket',
      app_variant: APP_VARIANT,
      ...normalizedTraits
    });
  }
}

function resetTelemetryUser() {
  const Sentry = ensureSentryInitialized();
  if (Sentry && typeof Sentry.setUser === 'function') {
    Sentry.setUser(null);
  }

  const posthog = getPostHogClient();
  if (posthog && typeof posthog.reset === 'function') {
    posthog.reset();
  }
}

async function flushTelemetry() {
  const posthog = getPostHogClient();

  if (posthog && typeof posthog.flush === 'function') {
    try {
      await posthog.flush();
    } catch {
      // best effort only
    }
  }
}

function wrapWithSentry(Component) {
  const Sentry = ensureSentryInitialized();

  if (Sentry && typeof Sentry.wrap === 'function') {
    return Sentry.wrap(Component);
  }

  return Component;
}

ensureSentryInitialized();
getPostHogClient();

export {
  APP_VARIANT,
  captureEvent,
  captureException,
  flushTelemetry,
  identifyUser,
  resetTelemetryUser,
  trackScreen,
  wrapWithSentry
};