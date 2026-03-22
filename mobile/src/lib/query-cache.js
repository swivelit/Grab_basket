import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

const STORAGE_PREFIX = '@grab_basket/query_cache_v1/';
const memoryCache = new Map();

function serializeQueryKey(queryKey) {
  if (Array.isArray(queryKey)) {
    return queryKey
      .map((value) => {
        if (typeof value === 'string') return value;

        try {
          return JSON.stringify(value);
        } catch {
          return String(value ?? '');
        }
      })
      .join('::');
  }

  if (typeof queryKey === 'string') {
    return queryKey;
  }

  try {
    return JSON.stringify(queryKey);
  } catch {
    return String(queryKey ?? 'unknown-query');
  }
}

function storageKeyFor(queryKey) {
  return `${STORAGE_PREFIX}${serializeQueryKey(queryKey)}`;
}

function isUsableRecord(record) {
  return Boolean(record && typeof record === 'object' && Number.isFinite(Number(record.updatedAt)));
}

function isExpired(record) {
  if (!isUsableRecord(record)) return true;
  if (!Number.isFinite(Number(record.expiresAt))) return false;
  return Number(record.expiresAt) <= Date.now();
}

function isStale(record, staleTime) {
  if (!isUsableRecord(record)) return true;
  return Date.now() - Number(record.updatedAt) > Number(staleTime || 0);
}

function toError(error) {
  if (error instanceof Error) return error;
  if (typeof error === 'string' && error.trim()) return new Error(error.trim());

  try {
    return new Error(JSON.stringify(error));
  } catch {
    return new Error('Unknown query error');
  }
}

function createRecord(data, cacheTime) {
  const updatedAt = Date.now();
  return {
    data,
    updatedAt,
    expiresAt: updatedAt + Number(cacheTime || 0),
  };
}

async function readPersistedRecord(storageKey) {
  try {
    const raw = await AsyncStorage.getItem(storageKey);
    if (!raw) return null;

    const parsed = JSON.parse(raw);
    return isUsableRecord(parsed) ? parsed : null;
  } catch {
    return null;
  }
}

async function writePersistedRecord(storageKey, record) {
  try {
    await AsyncStorage.setItem(storageKey, JSON.stringify(record));
  } catch {
    // best effort only
  }
}

async function clearPersistedRecord(storageKey) {
  try {
    await AsyncStorage.removeItem(storageKey);
  } catch {
    // best effort only
  }
}

export function useCachedQuery({
  queryKey,
  enabled = true,
  staleTime = 60 * 1000,
  cacheTime = 15 * 60 * 1000,
  fetcher,
  initialData = null,
}) {
  const serializedQueryKey = useMemo(() => serializeQueryKey(queryKey), [queryKey]);
  const storageKey = useMemo(() => storageKeyFor(serializedQueryKey), [serializedQueryKey]);
  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);

  const [state, setState] = useState(() => ({
    data: initialData,
    error: null,
    isLoading: Boolean(enabled),
    isFetching: false,
    updatedAt: 0,
  }));

  const applyRecord = useCallback((record) => {
    if (!mountedRef.current || !isUsableRecord(record)) return;

    setState((current) => ({
      ...current,
      data: record.data,
      updatedAt: Number(record.updatedAt),
      isLoading: false,
      error: null,
    }));
  }, []);

  const hydrate = useCallback(async () => {
    if (!enabled) {
      setState((current) => ({
        ...current,
        isLoading: false,
        isFetching: false,
      }));
      return null;
    }

    const memoryRecord = memoryCache.get(storageKey);
    if (isUsableRecord(memoryRecord) && !isExpired(memoryRecord)) {
      applyRecord(memoryRecord);
      return memoryRecord;
    }

    if (memoryRecord && isExpired(memoryRecord)) {
      memoryCache.delete(storageKey);
    }

    const persistedRecord = await readPersistedRecord(storageKey);
    if (isUsableRecord(persistedRecord) && !isExpired(persistedRecord)) {
      memoryCache.set(storageKey, persistedRecord);
      applyRecord(persistedRecord);
      return persistedRecord;
    }

    if (persistedRecord && isExpired(persistedRecord)) {
      await clearPersistedRecord(storageKey);
    }

    return null;
  }, [applyRecord, enabled, storageKey]);

  const refresh = useCallback(
    async ({ force = false } = {}) => {
      if (!enabled) {
        return null;
      }

      const cached = memoryCache.get(storageKey);
      if (!force && isUsableRecord(cached) && !isExpired(cached) && !isStale(cached, staleTime)) {
        applyRecord(cached);
        return cached.data;
      }

      const requestId = ++requestIdRef.current;

      setState((current) => ({
        ...current,
        isLoading: current.updatedAt === 0,
        isFetching: true,
        error: null,
      }));

      try {
        const data = await fetcher();
        if (!mountedRef.current || requestId !== requestIdRef.current) {
          return data;
        }

        const record = createRecord(data, cacheTime);
        memoryCache.set(storageKey, record);
        await writePersistedRecord(storageKey, record);
        applyRecord(record);
        setState((current) => ({
          ...current,
          data,
          updatedAt: record.updatedAt,
          isLoading: false,
          isFetching: false,
          error: null,
        }));
        return data;
      } catch (error) {
        if (!mountedRef.current || requestId !== requestIdRef.current) {
          throw error;
        }

        setState((current) => ({
          ...current,
          isLoading: false,
          isFetching: false,
          error: toError(error),
        }));
        throw error;
      }
    },
    [applyRecord, cacheTime, enabled, fetcher, staleTime, storageKey]
  );

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      const record = await hydrate();
      if (cancelled) return;

      if (!record || isStale(record, staleTime)) {
        refresh({ force: true }).catch(() => {});
      }
    };

    run().catch(() => {});

    return () => {
      cancelled = true;
    };
  }, [hydrate, refresh, staleTime]);

  const invalidate = useCallback(async () => {
    memoryCache.delete(storageKey);
    await clearPersistedRecord(storageKey);
  }, [storageKey]);

  return {
    data: state.data,
    error: state.error,
    isLoading: state.isLoading,
    isFetching: state.isFetching,
    updatedAt: state.updatedAt,
    refresh: () => refresh({ force: true }),
    invalidate,
  };
}

export async function primeCachedQuery(queryKey, data, cacheTime = 15 * 60 * 1000) {
  const storageKey = storageKeyFor(queryKey);
  const record = createRecord(data, cacheTime);
  memoryCache.set(storageKey, record);
  await writePersistedRecord(storageKey, record);
}