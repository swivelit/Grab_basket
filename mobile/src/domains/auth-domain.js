import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { apiPost, requestJson } from '../lib/api-client';
import {
  STORAGE_KEYS,
  clearLegacyAuthStorage,
  migrateLegacyAuthStorage,
  multiRemoveStoredValues,
  multiSetStoredValues,
  readOrCreateDeviceId,
  readStoredValue,
} from '../lib/storage';
import {
  TOKEN_REFRESH_SKEW_MS,
  buildSessionFromAuthResponse,
  getUnsupportedRoleMessage,
  normalizeErrorMessage,
  normalizeUserRole,
} from './grab-basket-utils';

function normalizePhoneValue(value = '') {
  const raw = String(value || '').trim();
  if (!raw) return '';

  let digits = raw.replace(/\D/g, '');
  if (!digits) return '';

  if (digits.length === 10) {
    digits = `91${digits}`;
  } else if (digits.length === 11 && digits.startsWith('0')) {
    digits = `91${digits.slice(-10)}`;
  }

  if (digits.length < 10 || digits.length > 15) {
    return '';
  }

  return `+${digits}`;
}

function resolveLoginIdentifiers({ identifier = '', email = '', phone = '' } = {}) {
  const normalizedIdentifier = String(identifier || '').trim();
  const normalizedEmail = String(email || '').trim().toLowerCase();
  const normalizedPhone = normalizePhoneValue(phone);

  if (normalizedEmail || normalizedPhone) {
    return { email: normalizedEmail, phone: normalizedPhone };
  }

  if (!normalizedIdentifier) {
    return { email: '', phone: '' };
  }

  if (normalizedIdentifier.includes('@')) {
    return { email: normalizedIdentifier.toLowerCase(), phone: '' };
  }

  return { email: '', phone: normalizePhoneValue(normalizedIdentifier) };
}

export function useAuthDomain({ appVariantName, appAllowedRoles, appPrimaryRole }) {
  const [sessionReady, setSessionReady] = useState(false);
  const [authLoading, setAuthLoading] = useState(false);
  const [authError, setAuthError] = useState('');
  const [authToken, setAuthToken] = useState('');
  const [refreshToken, setRefreshToken] = useState('');
  const [authEmail, setAuthEmail] = useState('');
  const [authRole, setAuthRole] = useState('');
  const [authTokenExpiresAt, setAuthTokenExpiresAt] = useState(0);
  const [refreshTokenExpiresAt, setRefreshTokenExpiresAt] = useState(0);
  const [profile, setProfile] = useState(null);
  const [deviceId, setDeviceId] = useState('');

  const authTokenRef = useRef('');
  const refreshTokenRef = useRef('');
  const authTokenExpiresAtRef = useRef(0);
  const refreshSessionPromiseRef = useRef(null);
  const deviceIdRef = useRef('');

  const isRoleSupportedByApp = useCallback(
    (role = '') => appAllowedRoles.includes(normalizeUserRole(role)),
    [appAllowedRoles]
  );

  const applySession = useCallback((nextSession = {}) => {
    const nextAccessToken = String(nextSession.accessToken || '');
    const nextRefreshToken = String(nextSession.refreshToken || '');
    const nextEmail = String(nextSession.email || '').trim().toLowerCase();
    const nextRole = normalizeUserRole(nextSession.role || appPrimaryRole);
    const nextAccessExpiresAt = Number(nextSession.accessTokenExpiresAt || 0);
    const nextRefreshExpiresAt = Number(nextSession.refreshTokenExpiresAt || 0);

    authTokenRef.current = nextAccessToken;
    refreshTokenRef.current = nextRefreshToken;
    authTokenExpiresAtRef.current = nextAccessExpiresAt;

    setAuthToken(nextAccessToken);
    setRefreshToken(nextRefreshToken);
    setAuthEmail(nextEmail);
    setAuthRole(nextRole);
    setAuthTokenExpiresAt(nextAccessExpiresAt);
    setRefreshTokenExpiresAt(nextRefreshExpiresAt);
  }, [appPrimaryRole]);

  const persistSession = useCallback(async (nextSession = {}) => {
    await multiSetStoredValues([
      [STORAGE_KEYS.authToken, String(nextSession.accessToken || '')],
      [STORAGE_KEYS.refreshToken, String(nextSession.refreshToken || '')],
      [STORAGE_KEYS.authEmail, String(nextSession.email || '').trim().toLowerCase()],
      [STORAGE_KEYS.authRole, normalizeUserRole(nextSession.role || appPrimaryRole)],
      [STORAGE_KEYS.authTokenExpiresAt, String(Number(nextSession.accessTokenExpiresAt || 0))],
      [STORAGE_KEYS.refreshTokenExpiresAt, String(Number(nextSession.refreshTokenExpiresAt || 0))],
    ]);
    await clearLegacyAuthStorage().catch(() => {});
  }, [appPrimaryRole]);

  const clearSession = useCallback(async ({ notifyServer = false, refreshTokenOverride = '' } = {}) => {
    const tokenForLogout = String(refreshTokenOverride || refreshTokenRef.current || '').trim();

    if (notifyServer && tokenForLogout) {
      try {
        await apiPost('/auth/logout', { refresh_token: tokenForLogout, device_id: deviceIdRef.current || '' });
      } catch {
        // best effort only
      }
    }

    refreshSessionPromiseRef.current = null;
    authTokenRef.current = '';
    refreshTokenRef.current = '';
    authTokenExpiresAtRef.current = 0;

    setAuthToken('');
    setRefreshToken('');
    setAuthEmail('');
    setAuthRole('');
    setAuthTokenExpiresAt(0);
    setRefreshTokenExpiresAt(0);
    setProfile(null);
    setAuthError('');

    await multiRemoveStoredValues([
      STORAGE_KEYS.authToken,
      STORAGE_KEYS.refreshToken,
      STORAGE_KEYS.authEmail,
      STORAGE_KEYS.authRole,
      STORAGE_KEYS.authTokenExpiresAt,
      STORAGE_KEYS.refreshTokenExpiresAt,
    ]);
  }, []);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      await migrateLegacyAuthStorage().catch(() => {});
      const stableDeviceId = await readOrCreateDeviceId().catch(() => '');
      deviceIdRef.current = String(stableDeviceId || '');
      if (!cancelled) setDeviceId(deviceIdRef.current);

      const [
        storedAccessToken,
        storedRefreshToken,
        storedEmail,
        storedRole,
        storedAccessExpiry,
        storedRefreshExpiry,
      ] = await Promise.all([
        readStoredValue(STORAGE_KEYS.authToken),
        readStoredValue(STORAGE_KEYS.refreshToken),
        readStoredValue(STORAGE_KEYS.authEmail),
        readStoredValue(STORAGE_KEYS.authRole),
        readStoredValue(STORAGE_KEYS.authTokenExpiresAt),
        readStoredValue(STORAGE_KEYS.refreshTokenExpiresAt),
      ]);

      if (cancelled) return;

      const restoredRole = normalizeUserRole(storedRole || appPrimaryRole);

      if (restoredRole && !isRoleSupportedByApp(restoredRole)) {
        setAuthError(getUnsupportedRoleMessage(restoredRole, { allowedRoles: appAllowedRoles, appVariantName }));
        await clearSession();
        setSessionReady(true);
        return;
      }

      applySession({
        accessToken: String(storedAccessToken || ''),
        refreshToken: String(storedRefreshToken || ''),
        email: String(storedEmail || '').trim().toLowerCase(),
        role: restoredRole || appPrimaryRole,
        accessTokenExpiresAt: Number(storedAccessExpiry || 0),
        refreshTokenExpiresAt: Number(storedRefreshExpiry || 0),
      });

      setSessionReady(true);
    })();

    return () => {
      cancelled = true;
    };
  }, [applySession, appAllowedRoles, appPrimaryRole, appVariantName, clearSession, isRoleSupportedByApp]);

  useEffect(() => {
    if (!sessionReady) return;
    persistSession({
      accessToken: authToken,
      refreshToken,
      email: authEmail,
      role: authRole,
      accessTokenExpiresAt: authTokenExpiresAt,
      refreshTokenExpiresAt,
    }).catch(() => {});
  }, [
    authEmail,
    authRole,
    authToken,
    authTokenExpiresAt,
    persistSession,
    refreshToken,
    refreshTokenExpiresAt,
    sessionReady,
  ]);

  const refreshSession = useCallback(async () => {
    if (refreshSessionPromiseRef.current) {
      return refreshSessionPromiseRef.current;
    }

    const currentRefreshToken = String(refreshTokenRef.current || '').trim();
    if (!currentRefreshToken) {
      throw new Error('Your session has ended. Please sign in again.');
    }

    refreshSessionPromiseRef.current = (async () => {
      try {
        const data = await apiPost('/auth/refresh', {
          refresh_token: currentRefreshToken,
          device_id: deviceIdRef.current || '',
        });

        const nextSession = buildSessionFromAuthResponse(data, {
          email: authEmail,
          fallbackRole: authRole || appPrimaryRole,
        });

        if (!nextSession.accessToken) {
          throw new Error('Refresh succeeded, but no access token was returned.');
        }

        if (!isRoleSupportedByApp(nextSession.role)) {
          throw new Error(
            getUnsupportedRoleMessage(nextSession.role, {
              allowedRoles: appAllowedRoles,
              appVariantName,
            })
          );
        }

        applySession(nextSession);
        await persistSession(nextSession);
        setAuthError('');
        return nextSession.accessToken;
      } catch (error) {
        await clearSession({ notifyServer: true, refreshTokenOverride: currentRefreshToken });
        const message = normalizeErrorMessage(error, 'Your session expired. Please sign in again.');
        setAuthError(message);
        throw new Error(message);
      } finally {
        refreshSessionPromiseRef.current = null;
      }
    })();

    return refreshSessionPromiseRef.current;
  }, [
    appAllowedRoles,
    appPrimaryRole,
    appVariantName,
    applySession,
    authEmail,
    authRole,
    clearSession,
    isRoleSupportedByApp,
    persistSession,
  ]);

  const ensureValidAccessToken = useCallback(async () => {
    const currentAccessToken = String(authTokenRef.current || '');
    const currentRefreshToken = String(refreshTokenRef.current || '');
    const accessExpiresAt = Number(authTokenExpiresAtRef.current || 0);

    if (currentAccessToken) {
      if (
        currentRefreshToken &&
        accessExpiresAt &&
        accessExpiresAt - Date.now() <= TOKEN_REFRESH_SKEW_MS
      ) {
        return refreshSession();
      }
      return currentAccessToken;
    }

    if (currentRefreshToken) {
      return refreshSession();
    }

    throw new Error('Sign in is required for this action.');
  }, [refreshSession]);

  const authorizedRequest = useCallback(
    async (path, options = {}) => {
      let token = await ensureValidAccessToken();

      try {
        return await requestJson(path, {
          ...options,
          token,
          headers: {
            ...(options.headers || {}),
            Authorization: `Bearer ${token}`,
          },
        });
      } catch (error) {
        if (Number(error?.status || 0) !== 401 || !refreshTokenRef.current) {
          throw error;
        }

        token = await refreshSession();

        return requestJson(path, {
          ...options,
          token,
          headers: {
            ...(options.headers || {}),
            Authorization: `Bearer ${token}`,
          },
        });
      }
    },
    [ensureValidAccessToken, refreshSession]
  );

  const refreshProfile = useCallback(async () => {
    if (!authTokenRef.current && !refreshTokenRef.current) {
      setProfile(null);
      return null;
    }

    try {
      const data = await authorizedRequest('/me/profile');
      setProfile(data || null);
      return data || null;
    } catch (error) {
      setAuthError(normalizeErrorMessage(error, 'Could not load profile.'));
      return null;
    }
  }, [authorizedRequest]);

  const login = useCallback(
    async ({ identifier, email, phone, password }) => {
      setAuthLoading(true);
      setAuthError('');

      try {
        const resolved = resolveLoginIdentifiers({ identifier, email, phone });

        if (!resolved.email && !resolved.phone) {
          throw new Error('Enter a valid email or phone number.');
        }

        const payload = {
          password: String(password || ''),
          device_id: deviceIdRef.current || (await readOrCreateDeviceId().catch(() => '')) || '',
        };

        if (resolved.email) payload.email = resolved.email;
        if (resolved.phone) payload.phone = resolved.phone;

        const data = await apiPost('/auth/login', payload);
        const nextSession = buildSessionFromAuthResponse(data, {
          email: resolved.email || resolved.phone,
          fallbackRole: appPrimaryRole,
        });

        if (!isRoleSupportedByApp(nextSession.role)) {
          throw new Error(
            getUnsupportedRoleMessage(nextSession.role, {
              allowedRoles: appAllowedRoles,
              appVariantName,
            })
          );
        }

        if (!nextSession.accessToken) {
          throw new Error('Sign-in succeeded, but no access token was returned.');
        }

        applySession(nextSession);
        await persistSession(nextSession);
        setAuthError('');
        return true;
      } catch (error) {
        setAuthError(normalizeErrorMessage(error, `Could not sign in to ${appVariantName}.`));
        return false;
      } finally {
        setAuthLoading(false);
      }
    },
    [appAllowedRoles, appPrimaryRole, appVariantName, applySession, isRoleSupportedByApp, persistSession]
  );

  const register = useCallback(
    async ({ email, phone, password }) => {
      setAuthLoading(true);
      setAuthError('');

      try {
        const normalizedEmail = String(email || '').trim().toLowerCase();
        const normalizedPhone = normalizePhoneValue(phone);

        if (!normalizedEmail) {
          throw new Error('Enter your email address.');
        }

        if (!normalizedPhone) {
          throw new Error('Enter a valid phone number.');
        }

        const payload = {
          email: normalizedEmail,
          phone: normalizedPhone,
          password: String(password || ''),
          role: appPrimaryRole,
          device_id: deviceIdRef.current || (await readOrCreateDeviceId().catch(() => '')) || '',
        };

        const data = await apiPost('/auth/register', payload);
        const nextSession = buildSessionFromAuthResponse(data, {
          email: payload.email || payload.phone,
          fallbackRole: appPrimaryRole,
        });

        if (!isRoleSupportedByApp(nextSession.role)) {
          throw new Error(
            getUnsupportedRoleMessage(nextSession.role, {
              allowedRoles: appAllowedRoles,
              appVariantName,
            })
          );
        }

        if (!nextSession.accessToken) {
          throw new Error('Account created, but no access token was returned.');
        }

        applySession(nextSession);
        await persistSession(nextSession);
        setAuthError('');
        return true;
      } catch (error) {
        setAuthError(normalizeErrorMessage(error, `Could not create account in ${appVariantName}.`));
        return false;
      } finally {
        setAuthLoading(false);
      }
    },
    [appAllowedRoles, appPrimaryRole, appVariantName, applySession, isRoleSupportedByApp, persistSession]
  );

  const startAuthChallenge = useCallback(async ({ challengeType, target }) => {
    const payload = {
      challenge_type: String(challengeType || '').trim().toUpperCase(),
      target: String(target || '').trim().toLowerCase(),
    };
    return apiPost('/auth/challenge/start', payload);
  }, []);

  const verifyAuthChallenge = useCallback(async ({ challengeId, code }) => {
    return apiPost('/auth/challenge/verify', {
      challenge_id: Number(challengeId),
      code: String(code || '').trim(),
    });
  }, []);

  const logout = useCallback(async () => {
    await clearSession({ notifyServer: true });
  }, [clearSession]);

  const isAuthenticated = useMemo(
    () => Boolean(authToken || refreshToken) && isRoleSupportedByApp(authRole || appPrimaryRole),
    [authRole, authToken, appPrimaryRole, isRoleSupportedByApp, refreshToken]
  );

  return {
    sessionReady,
    authLoading,
    authError,
    setAuthError,
    authToken,
    refreshToken,
    authEmail,
    authRole,
    authTokenExpiresAt,
    refreshTokenExpiresAt,
    profile,
    deviceId,
    setProfile,
    isAuthenticated,
    applySession,
    clearSession,
    refreshSession,
    ensureValidAccessToken,
    authorizedRequest,
    refreshProfile,
    login,
    register,
    startAuthChallenge,
    verifyAuthChallenge,
    logout,
  };
}