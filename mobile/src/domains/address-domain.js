import { useCallback, useEffect, useState } from 'react';

import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';
import { normalizeAddress, normalizeErrorMessage } from './grab-basket-utils';

export function useAddressDomain({ isCustomerApp, appVariantName, sessionReady, isAuthenticated, authorizedRequest }) {
  const [addresses, setAddresses] = useState([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [addressesError, setAddressesError] = useState('');
  const [selectedAddressId, setSelectedAddressId] = useState('');

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

  const defaultAddress = addresses.find((item) => String(item.id) === String(selectedAddressId)) ||
    addresses.find((item) => item.is_default) ||
    addresses[0] ||
    null;

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
        const body = {
          label: String(payload?.label || 'Home').trim() || 'Home',
          line1: String(payload?.line1 || '').trim(),
          line2: String(payload?.line2 || '').trim(),
          city: String(payload?.city || '').trim(),
          pincode: String(payload?.pincode || '').trim(),
          lat: Number(payload?.lat),
          lng: Number(payload?.lng),
          is_default: Boolean(payload?.is_default),
        };

        if (!body.line1) throw new Error('Address line 1 is required.');
        if (!Number.isFinite(body.lat) || !Number.isFinite(body.lng)) {
          throw new Error('Latitude and longitude are required.');
        }

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
    createAddress,
    setDefaultAddress,
    loadAddresses,
  };
}
