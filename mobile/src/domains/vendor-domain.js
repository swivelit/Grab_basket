import { useCallback, useEffect, useMemo, useState } from 'react';

import { requestJson } from '../lib/api-client';
import { useCachedQuery } from '../lib/query-cache';
import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';
import {
  MAX_RECENT,
  buildVendorQuery,
  createShortcutBuckets,
  dedupeStrings,
  findVendorById,
  normalizeErrorMessage,
  sortVendors,
} from './grab-basket-utils';

const VENDORS_STALE_TIME_MS = 60 * 1000;
const VENDORS_CACHE_TIME_MS = 20 * 60 * 1000;
const HOME_DEALS_STALE_TIME_MS = 2 * 60 * 1000;
const HOME_DEALS_CACHE_TIME_MS = 20 * 60 * 1000;
const SEARCH_DEBOUNCE_MS = 220;

function parseStoredArray(rawValue) {
  if (!rawValue) return [];

  try {
    const parsed = JSON.parse(rawValue);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function buildAddressCacheKey(address) {
  if (!address || typeof address !== 'object') {
    return 'no-address';
  }

  return {
    id: address.id ?? '',
    pincode: String(address.pincode || '').trim(),
    lat: Number.isFinite(Number(address.lat)) ? Number(address.lat) : null,
    lng: Number.isFinite(Number(address.lng)) ? Number(address.lng) : null,
  };
}

function createDealsFromGroups(groups = []) {
  return groups
    .flatMap(({ vendor, products }) =>
      (Array.isArray(products) ? products : [])
        .filter((item) => item?.is_available)
        .slice(0, 3)
        .map((item) => ({
          ...item,
          vendorName: vendor?.name,
          brand: vendor?.description || vendor?.address || 'Top pick',
        }))
    )
    .slice(0, 8);
}

export function useVendorDomain({ activeService, activeShortcut, homeSearch, defaultAddress }) {
  const [recentStoreIds, setRecentStoreIds] = useState([]);
  const [recentSearches, setRecentSearches] = useState([]);
  const [productsError, setProductsError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [hiddenVendorErrorMessage, setHiddenVendorErrorMessage] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState(String(homeSearch || '').trim());

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(String(homeSearch || '').trim());
    }, SEARCH_DEBOUNCE_MS);

    return () => clearTimeout(timer);
  }, [homeSearch]);

  useEffect(() => {
    let cancelled = false;

    Promise.all([
      readStoredValue(STORAGE_KEYS.recentStores),
      readStoredValue(STORAGE_KEYS.recentSearches),
    ])
      .then(([storedRecentStores, storedRecentSearches]) => {
        if (cancelled) return;

        setRecentStoreIds(parseStoredArray(storedRecentStores));
        setRecentSearches(parseStoredArray(storedRecentSearches));
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    writeStoredValue(STORAGE_KEYS.recentStores, JSON.stringify(recentStoreIds)).catch(() => {});
  }, [recentStoreIds]);

  useEffect(() => {
    writeStoredValue(STORAGE_KEYS.recentSearches, JSON.stringify(recentSearches)).catch(() => {});
  }, [recentSearches]);

  const addressCacheKey = useMemo(() => buildAddressCacheKey(defaultAddress), [defaultAddress]);

  const vendorsQuery = useCachedQuery({
    queryKey: ['vendors', activeService || 'food', debouncedSearch || '', addressCacheKey],
    staleTime: VENDORS_STALE_TIME_MS,
    cacheTime: VENDORS_CACHE_TIME_MS,
    keepPreviousData: true,
    initialData: [],
    fetcher: useCallback(async () => {
      const data = await requestJson(
        buildVendorQuery({
          search: debouncedSearch,
          service: activeService,
          address: defaultAddress,
        })
      );

      const parsed = Array.isArray(data) ? data : [];
      return sortVendors(parsed);
    }, [activeService, debouncedSearch, defaultAddress]),
  });

  const vendors = useMemo(
    () => (Array.isArray(vendorsQuery.data) ? vendorsQuery.data : []),
    [vendorsQuery.data]
  );
  const rawVendorsError = vendorsQuery.error
    ? normalizeErrorMessage(vendorsQuery.error, 'Could not load stores.')
    : '';
  const vendorsError =
    rawVendorsError && rawVendorsError !== hiddenVendorErrorMessage ? rawVendorsError : '';
  const vendorsLoading = vendorsQuery.isLoading;

  useEffect(() => {
    if (!rawVendorsError && hiddenVendorErrorMessage) {
      setHiddenVendorErrorMessage('');
    }
  }, [hiddenVendorErrorMessage, rawVendorsError]);

  const topVendors = useMemo(() => vendors.slice(0, 4), [vendors]);
  const topVendorIds = useMemo(
    () => topVendors.map((vendor) => Number(vendor?.id || 0)),
    [topVendors]
  );

  const homeDealsQuery = useCachedQuery({
    queryKey: ['vendors', 'home-deals', activeService || 'food', topVendorIds],
    enabled: topVendorIds.length > 0,
    staleTime: HOME_DEALS_STALE_TIME_MS,
    cacheTime: HOME_DEALS_CACHE_TIME_MS,
    keepPreviousData: true,
    initialData: [],
    fetcher: useCallback(async () => {
      const groups = await Promise.all(
        topVendors.map(async (vendor) => {
          try {
            const data = await requestJson(`/vendors/${vendor.id}/products?limit=12`);
            return { vendor, products: Array.isArray(data) ? data : [] };
          } catch {
            return { vendor, products: [] };
          }
        })
      );

      return createDealsFromGroups(groups);
    }, [topVendors]),
  });

  const homeDeals = useMemo(
    () => (Array.isArray(homeDealsQuery.data) ? homeDealsQuery.data : []),
    [homeDealsQuery.data]
  );
  const homeDealsLoading = topVendorIds.length > 0 ? homeDealsQuery.isLoading : false;

  const rememberSearch = useCallback((term) => {
    const value = String(term || '').trim();
    if (!value) return;

    setRecentSearches((current) =>
      [value, ...current.filter((item) => String(item || '').trim().toLowerCase() !== value.toLowerCase())].slice(
        0,
        MAX_RECENT
      )
    );
  }, []);

  const rememberStore = useCallback((vendorId) => {
    if (vendorId == null) return;

    setRecentStoreIds((current) =>
      [vendorId, ...current.filter((item) => String(item) !== String(vendorId))].slice(0, MAX_RECENT)
    );
  }, []);

  const loadVendors = useCallback(
    async ({ pullToRefresh = false } = {}) => {
      try {
        if (pullToRefresh) {
          setRefreshing(true);
        }

        return await vendorsQuery.refresh();
      } catch {
        return Array.isArray(vendorsQuery.data) ? vendorsQuery.data : [];
      } finally {
        if (pullToRefresh) {
          setRefreshing(false);
        }
      }
    },
    [vendorsQuery]
  );

  const setVendorsError = useCallback(
    (nextValue = '') => {
      if (String(nextValue || '').trim()) {
        setHiddenVendorErrorMessage('');
        return;
      }

      setHiddenVendorErrorMessage(rawVendorsError);
    },
    [rawVendorsError]
  );

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      const vendorId = Number(vendor?.id);
      if (!Number.isFinite(vendorId) || vendorId <= 0) {
        throw new Error('We could not resolve this store.');
      }

      const params = new URLSearchParams();
      const q = String(searchValue || '').trim();

      if (q) params.set('q', q);
      params.set('limit', '200');

      const query = params.toString();
      const data = await requestJson(`/vendors/${vendorId}/products${query ? `?${query}` : ''}`);
      setProductsError('');
      return Array.isArray(data) ? data : [];
    } catch (error) {
      setProductsError(normalizeErrorMessage(error, 'Could not load products.'));
      return [];
    }
  }, []);

  const keywordMap = useMemo(() => createShortcutBuckets(vendors), [vendors]);

  const shortcutFilteredVendors = useMemo(() => {
    if (activeService !== 'warehouse' || activeShortcut === 'all') return vendors;
    const bucket = keywordMap[activeShortcut] || [];
    return bucket.length > 0 ? bucket : vendors;
  }, [activeService, activeShortcut, keywordMap, vendors]);

  const featuredVendors = useMemo(() => shortcutFilteredVendors.slice(0, 8), [shortcutFilteredVendors]);

  const recentVendors = useMemo(
    () => recentStoreIds.map((id) => findVendorById(vendors, id)).filter(Boolean),
    [recentStoreIds, vendors]
  );

  const suggestionPool = useMemo(
    () =>
      dedupeStrings([
        ...recentSearches,
        ...vendors.map((vendor) => vendor?.name),
        ...homeDeals.map((item) => item?.name),
      ]).slice(0, 12),
    [recentSearches, vendors, homeDeals]
  );

  return {
    vendors,
    setVendors: vendorsQuery.setData,
    vendorsLoading,
    refreshing,
    vendorsError,
    setVendorsError,
    homeDeals,
    homeDealsLoading,
    productsError,
    setProductsError,
    recentStoreIds,
    recentSearches,
    rememberSearch,
    rememberStore,
    loadVendors,
    loadProducts,
    featuredVendors,
    recentVendors,
    suggestionPool,
    vendorsIsFetching: vendorsQuery.isFetching,
    vendorsIsStale: vendorsQuery.isStale,
    vendorsSource: vendorsQuery.source,
    vendorsUpdatedAt: vendorsQuery.updatedAt,
  };
}
