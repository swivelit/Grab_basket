import { useCallback, useEffect, useState } from 'react';
import * as Location from 'expo-location';

import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';
import { normalizeAddress, normalizeErrorMessage } from './grab-basket-utils';

function firstNonEmpty(...values) {
  return values
    .map((value) => String(value || '').trim())
    .find(Boolean) || '';
}

function buildCurrentLocationAddress({ lat, lng, place = null } = {}) {
  const line1 = firstNonEmpty(
    [place?.name, place?.street].filter(Boolean).join(' ').trim(),
    place?.street,
    place?.district,
    place?.subregion,
    place?.city,
    place?.region
  );

  const line2 = firstNonEmpty(
    [place?.district, place?.subregion].filter(Boolean).join(', '),
    place?.region,
    place?.country
  );

  const city = firstNonEmpty(place?.city, place?.subregion, place?.district, place?.region);
  const pincode = firstNonEmpty(place?.postalCode);

  return {
    id: null,
    label: 'Current location',
    line1: line1 || 'Using your live location',
    line2,
    city,
    pincode,
    lat,
    lng,
    is_default: false,
    is_ephemeral: true,
    source: 'device-location',
  };
}

function buildAddressPayload(payload = {}) {
  return {
    label: String(payload?.label || 'Home').trim() || 'Home',
    line1: String(payload?.line1 || '').trim(),
    line2: String(payload?.line2 || '').trim(),
    city: String(payload?.city || '').trim(),
    pincode: String(payload?.pincode || '').trim(),
    lat: Number(payload?.lat),
    lng: Number(payload?.lng),
    is_default: Boolean(payload?.is_default),
  };
}

function validateAddressPayload(body) {
  if (!body.line1) throw new Error('Address line 1 is required.');
  if (!Number.isFinite(body.lat) || !Number.isFinite(body.lng)) {
    throw new Error('Latitude and longitude are required.');
  }
}

export function useAddressDomain({ isCustomerApp, appVariantName, sessionReady, isAuthenticated, authorizedRequest }) {
  const [addresses, setAddresses] = useState([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [addressesError, setAddressesError] = useState('');
  const [selectedAddressId, setSelectedAddressId] = useState('');
  const [currentLocationAddress, setCurrentLocationAddress] = useState(null);
  const [currentLocationLoading, setCurrentLocationLoading] = useState(false);
  const [hasAttemptedCurrentLocation, setHasAttemptedCurrentLocation] = useState(false);

  useEffect(() => {
    let cancelled = false;

    readStoredValue(STORAGE_KEYS.selectedAddressId)
      .then((value) => {
        if (!cancelled) {
          setSelectedAddressId(String(value || ''));
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!sessionReady) return;
    writeStoredValue(STORAGE_KEYS.selectedAddressId, String(selectedAddressId || '')).catch(() => {});
  }, [selectedAddressId, sessionReady]);

  const loadAddresses = useCallback(
    async ({ silent = false } = {}) => {
      if (!isCustomerApp) {
        setAddresses([]);
        return [];
      }

      if (!isAuthenticated) {
        setAddresses([]);
        return [];
      }

      try {
        if (!silent) setAddressesLoading(true);

        const data = await authorizedRequest('/me/addresses');
        const parsed = Array.isArray(data) ? data.map(normalizeAddress).filter(Boolean) : [];
        setAddresses(parsed);
        setAddressesError('');
        return parsed;
      } catch (error) {
        setAddressesError(normalizeErrorMessage(error, 'Could not load addresses.'));
        return [];
      } finally {
        if (!silent) setAddressesLoading(false);
      }
    },
    [authorizedRequest, isAuthenticated, isCustomerApp]
  );

  useEffect(() => {
    if (!sessionReady) return;

    if (!isAuthenticated) {
      setAddresses([]);
      setSelectedAddressId('');
      return;
    }

    loadAddresses({ silent: true }).catch(() => {});
  }, [isAuthenticated, loadAddresses, sessionReady]);

  useEffect(() => {
    if (!addresses.length) {
      if (selectedAddressId) setSelectedAddressId('');
      return;
    }

    const stillExists = addresses.some((item) => String(item.id) === String(selectedAddressId));
    if (stillExists) return;

    const next = addresses.find((item) => item.is_default) || addresses[0];
    setSelectedAddressId(next ? String(next.id) : '');
  }, [addresses, selectedAddressId]);

  const defaultAddress =
    addresses.find((item) => String(item.id) === String(selectedAddressId)) ||
    addresses.find((item) => item.is_default) ||
    addresses[0] ||
    null;

  const resolveCurrentLocation = useCallback(
    async ({ force = false } = {}) => {
      if (!isCustomerApp) {
        setCurrentLocationAddress(null);
        setHasAttemptedCurrentLocation(true);
        return null;
      }

      if (currentLocationLoading && !force) {
        return currentLocationAddress;
      }

      try {
        setCurrentLocationLoading(true);

        const existingPermission = await Location.getForegroundPermissionsAsync();
        let status = existingPermission?.status || 'undetermined';

        if (status !== 'granted') {
          const requestedPermission = await Location.requestForegroundPermissionsAsync();
          status = requestedPermission?.status || status;
        }

        if (status !== 'granted') {
          setCurrentLocationAddress(null);
          return null;
        }

        const position = await Location.getCurrentPositionAsync({
          accuracy: Location.Accuracy.Balanced,
        });

        const lat = Number(position?.coords?.latitude);
        const lng = Number(position?.coords?.longitude);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
          setCurrentLocationAddress(null);
          return null;
        }

        let place = null;
        try {
          const places = await Location.reverseGeocodeAsync({ latitude: lat, longitude: lng });
          place = Array.isArray(places) ? places[0] || null : null;
        } catch {
          place = null;
        }

        const nextAddress = buildCurrentLocationAddress({ lat, lng, place });
        setCurrentLocationAddress(nextAddress);
        return nextAddress;
      } catch {
        setCurrentLocationAddress(null);
        return null;
      } finally {
        setCurrentLocationLoading(false);
        setHasAttemptedCurrentLocation(true);
      }
    },
    [currentLocationAddress, currentLocationLoading, isCustomerApp]
  );

  useEffect(() => {
    if (!isCustomerApp) return;
    if (defaultAddress) return;
    if (hasAttemptedCurrentLocation) return;

    resolveCurrentLocation().catch(() => {});
  }, [defaultAddress, hasAttemptedCurrentLocation, isCustomerApp, resolveCurrentLocation]);

  const createAddress = useCallback(
    async (payload) => {
      if (!isCustomerApp) {
        setAddressesError(`${appVariantName} does not support customer delivery addresses.`);
        return null;
      }

      if (!isAuthenticated) {
        setAddressesError(`Sign in to ${appVariantName} before adding a delivery address.`);
        return null;
      }

      try {
        setAddressesLoading(true);
        const body = buildAddressPayload(payload);
        validateAddressPayload(body);

        const data = await authorizedRequest('/me/addresses', {
          method: 'POST',
          body,
        });

        const next = normalizeAddress(data);
        if (!next) return null;

        setAddresses((current) => {
          const rest = body.is_default
            ? current.map((item) => ({ ...item, is_default: false }))
            : current;
          return [next, ...rest.filter((item) => String(item.id) !== String(next.id))];
        });
        setSelectedAddressId(String(next.id));
        setAddressesError('');
        return next;
      } catch (error) {
        setAddressesError(normalizeErrorMessage(error, 'Could not save address.'));
        return null;
      } finally {
        setAddressesLoading(false);
      }
    },
    [appVariantName, authorizedRequest, isAuthenticated, isCustomerApp]
  );

  const updateAddress = useCallback(
    async (addressId, payload) => {
      if (!isCustomerApp) {
        setAddressesError(`${appVariantName} does not support customer delivery addresses.`);
        return null;
      }

      if (!isAuthenticated) {
        setAddressesError(`Sign in to ${appVariantName} before updating a delivery address.`);
        return null;
      }

      try {
        setAddressesLoading(true);
        const body = buildAddressPayload(payload);
        validateAddressPayload(body);

        const data = await authorizedRequest(`/me/addresses/${addressId}`, {
          method: 'PUT',
          body,
        });

        const next = normalizeAddress(data);
        if (!next) return null;

        setAddresses((current) =>
          current.map((item) => {
            if (String(item.id) === String(addressId)) return next;
            if (next.is_default) return { ...item, is_default: false };
            return item;
          })
        );

        if (next.is_default || String(selectedAddressId) === String(addressId)) {
          setSelectedAddressId(String(next.id));
        }

        setAddressesError('');
        return next;
      } catch (error) {
        setAddressesError(normalizeErrorMessage(error, 'Could not update address.'));
        return null;
      } finally {
        setAddressesLoading(false);
      }
    },
    [appVariantName, authorizedRequest, isAuthenticated, isCustomerApp, selectedAddressId]
  );

  const deleteAddress = useCallback(
    async (addressId) => {
      if (!isCustomerApp) {
        setAddressesError(`${appVariantName} does not support customer delivery addresses.`);
        return false;
      }

      if (!isAuthenticated) {
        return false;
      }

      try {
        setAddressesLoading(true);
        await authorizedRequest(`/me/addresses/${addressId}`, {
          method: 'DELETE',
        });

        setAddresses((current) => {
          const remaining = current.filter((item) => String(item.id) !== String(addressId));
          if (remaining.length && !remaining.some((item) => item.is_default)) {
            return remaining.map((item, index) => ({
              ...item,
              is_default: index === 0,
            }));
          }
          return remaining;
        });

        setSelectedAddressId((currentSelectedId) => {
          if (String(currentSelectedId) !== String(addressId)) return currentSelectedId;
          const remaining = addresses.filter((item) => String(item.id) !== String(addressId));
          const next = remaining.find((item) => item.is_default) || remaining[0] || null;
          return next ? String(next.id) : '';
        });

        setAddressesError('');
        return true;
      } catch (error) {
        setAddressesError(normalizeErrorMessage(error, 'Could not delete address.'));
        return false;
      } finally {
        setAddressesLoading(false);
      }
    },
    [addresses, appVariantName, authorizedRequest, isAuthenticated, isCustomerApp]
  );

  const setDefaultAddress = useCallback(
    async (addressId) => {
      if (!isCustomerApp) {
        setAddressesError(`${appVariantName} does not support customer delivery addresses.`);
        return false;
      }

      if (!isAuthenticated) {
        return false;
      }

      try {
        await authorizedRequest(`/me/addresses/${addressId}/default`, {
          method: 'POST',
        });
        setAddresses((current) =>
          current.map((item) => ({
            ...item,
            is_default: String(item.id) === String(addressId),
          }))
        );
        setSelectedAddressId(String(addressId));
        setAddressesError('');
        return true;
      } catch (error) {
        setAddressesError(normalizeErrorMessage(error, 'Could not update address.'));
        return false;
      }
    },
    [appVariantName, authorizedRequest, isAuthenticated, isCustomerApp]
  );

  return {
    addresses,
    setAddresses,
    addressesLoading,
    addressesError,
    setAddressesError,
    selectedAddressId,
    setSelectedAddressId,
    defaultAddress,
    activeAddress: defaultAddress || currentLocationAddress || null,
    currentLocationAddress,
    currentLocationLoading,
    hasAttemptedCurrentLocation,
    createAddress,
    updateAddress,
    deleteAddress,
    setDefaultAddress,
    loadAddresses,
    resolveCurrentLocation,
  };
}