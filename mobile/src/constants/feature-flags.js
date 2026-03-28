import { APP_ENV } from '../config';

function fromEnv(name, fallback = false) {
  const raw = String(process.env[name] ?? '').trim().toLowerCase();
  if (!raw) return fallback;
  return ['1', 'true', 'yes', 'on', 'enabled'].includes(raw);
}

const FEATURE_FLAGS = Object.freeze({
  personalizationRanking: fromEnv('EXPO_PUBLIC_FF_PERSONALIZATION_RANKING', true),
  loyaltyMembership: fromEnv('EXPO_PUBLIC_FF_LOYALTY_MEMBERSHIP', true),
  premiumTrustCards: fromEnv('EXPO_PUBLIC_FF_PREMIUM_TRUST_CARDS', true),
  reorderIntelligence: fromEnv('EXPO_PUBLIC_FF_REORDER_INTELLIGENCE', true),
  analyticsTaxonomyV2: fromEnv('EXPO_PUBLIC_FF_ANALYTICS_TAXONOMY_V2', true),
  stagedRollout: fromEnv('EXPO_PUBLIC_FF_STAGED_ROLLOUT', APP_ENV !== 'production'),
  crashMonitoring: fromEnv('EXPO_PUBLIC_FF_CRASH_MONITORING', true),
  rolloutControl: fromEnv('EXPO_PUBLIC_FF_ROLLOUT_CONTROL', APP_ENV !== 'production'),
});

function describeFeatureFlags() {
  return {
    ...FEATURE_FLAGS,
    appEnv: APP_ENV,
  };
}

export { FEATURE_FLAGS, describeFeatureFlags };
