import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { requestJson } from '../lib/api-client';
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

export function useVendorDomain({ activeService, activeShortcut, homeSearch, defaultAddress }) {
  const [vendors, setVendors] = useState([]);
  const [vendorsLoading, setVendorsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [vendorsError, setVendorsError] = useState('');
  const [homeDeals, setHomeDeals] = useState([]);
  const [homeDealsLoading, setHomeDealsLoading] = useState(false);
  const [productsError, setProductsError] = useState('');
  const [recentStoreIds, setRecentStoreIds] = useState([]);
  const [recentSearches, setRecentSearches] = useState([]);

  const vendorRequestIdRef = useRef(0);
  const dealsRequestIdRef = useRef(0);


  useEffect(() => {
    let cancelled = false;

    Promise.all([
      readStoredValue(STORAGE_KEYS.recentStores),
      readStoredValue(STORAGE_KEYS.recentSearches),
    ])
      .then(([storedRecentStores, storedRecentSearches]) => {
        if (cancelled) return;

        try {
          const parsedRecentStores = storedRecentStores ? JSON.parse(storedRecentStores) : [];
          if (Array.isArray(parsedRecentStores)) {
            setRecentStoreIds(parsedRecentStores);
          }
        } catch {}

        try {
          const parsedRecentSearches = storedRecentSearches ? JSON.parse(storedRecentSearches) : [];
          if (Array.isArray(parsedRecentSearches)) {
            setRecentSearches(parsedRecentSearches);
          }
        } catch {}
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

  const rememberSearch = useCallback((term) => {
    const value = String(term || '').trim();
    if (!value) return;

    setRecentSearches((current) =>
      [value, ...current.filter((item) => item.trim().toLowerCase() !== value.toLowerCase())].slice(0, MAX_RECENT)
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
      const requestId = ++vendorRequestIdRef.current;

      try {
        if (pullToRefresh) setRefreshing(true);
        else setVendorsLoading(true);

        const data = await requestJson(
          buildVendorQuery({
            search: homeSearch,
            service: activeService,
            address: defaultAddress,
          })
        );

        if (requestId !== vendorRequestIdRef.current) return;

        const parsed = Array.isArray(data) ? data : [];
        setVendors(sortVendors(parsed));
        setVendorsError('');
      } catch (error) {
        if (requestId !== vendorRequestIdRef.current) return;
        setVendorsError(normalizeErrorMessage(error, 'Could not load stores.'));
      } finally {
        if (requestId === vendorRequestIdRef.current) {
          setVendorsLoading(false);
          setRefreshing(false);
        }
      }
    },
    [activeService, defaultAddress, homeSearch]
  );

  useEffect(() => {
    const timer = setTimeout(() => {
      loadVendors().catch(() => {});
    }, 220);

    return () => clearTimeout(timer);
  }, [loadVendors]);

  const loadHomeDeals = useCallback(async (vendorList) => {
    const requestId = ++dealsRequestIdRef.current;
    const topVendors = vendorList.slice(0, 4);

    if (topVendors.length === 0) {
      setHomeDeals([]);
      setHomeDealsLoading(false);
      return;
    }

    try {
      setHomeDealsLoading(true);

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

      if (requestId !== dealsRequestIdRef.current) return;

      const curated = groups
        .flatMap(({ vendor, products }) =>
          products
            .filter((item) => item?.is_available)
            .slice(0, 3)
            .map((item) => ({
              ...item,
              vendorName: vendor?.name,
              brand: vendor?.description || vendor?.address || 'Top pick',
            }))
        )
        .slice(0, 8);

      setHomeDeals(curated);
    } finally {
      if (requestId === dealsRequestIdRef.current) {
        setHomeDealsLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    loadHomeDeals(vendors).catch(() => {});
  }, [loadHomeDeals, vendors]);

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      const params = new URLSearchParams();
      const q = String(searchValue || '').trim();

      if (q) params.set('q', q);
      params.set('limit', '200');

      const query = params.toString();
      const data = await requestJson(`/vendors/${vendor.id}/products${query ? `?${query}` : ''}`);
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
    setVendors,
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
  };
}
