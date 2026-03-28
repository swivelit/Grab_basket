import { API_CONFIG_ERROR, APP_ENV } from '../config';
import { FEATURE_FLAGS } from '../constants/feature-flags';

function boolLabel(value) {
  return value ? 'enabled' : 'disabled';
}

function evaluateReleaseGovernance({
  appEnv = APP_ENV,
  apiConfigError = API_CONFIG_ERROR,
  flags = FEATURE_FLAGS,
} = {}) {
  const issues = [];
  const normalizedEnv = String(appEnv || '').toLowerCase();
  const isProduction = normalizedEnv === 'production';
  const enforceReleaseGatesRaw =
    process.env.EXPO_PUBLIC_ENFORCE_RELEASE_GATES ?? (isProduction ? 'true' : 'false');
  const enforceReleaseGates =
    String(enforceReleaseGatesRaw).trim().toLowerCase() !== 'false';

  if (apiConfigError) {
    issues.push({
      severity: 'critical',
      code: 'api_config_invalid',
      message: String(apiConfigError),
    });
  }

  if (isProduction && !flags.crashMonitoring) {
    issues.push({
      severity: 'critical',
      code: 'crash_monitoring_disabled',
      message: 'Crash monitoring is disabled for production builds.',
    });
  }

  if (isProduction && !flags.rolloutControl) {
    issues.push({
      severity: 'critical',
      code: 'rollout_control_disabled',
      message: 'Rollout control is disabled for production builds.',
    });
  }

  if (isProduction && !flags.stagedRollout) {
    issues.push({
      severity: 'high',
      code: 'staged_rollout_disabled',
      message: 'Staged rollout is disabled; production pushes are riskier.',
    });
  }

  return {
    appEnv: normalizedEnv || 'development',
    flagsSnapshot: {
      crashMonitoring: boolLabel(Boolean(flags.crashMonitoring)),
      rolloutControl: boolLabel(Boolean(flags.rolloutControl)),
      stagedRollout: boolLabel(Boolean(flags.stagedRollout)),
      analyticsTaxonomyV2: boolLabel(Boolean(flags.analyticsTaxonomyV2)),
      personalizationRanking: boolLabel(Boolean(flags.personalizationRanking)),
      loyaltyMembership: boolLabel(Boolean(flags.loyaltyMembership)),
    },
    issues,
    isReady: issues.every((issue) => issue.severity !== 'critical'),
    enforceReleaseGates,
  };
}

export { evaluateReleaseGovernance };
