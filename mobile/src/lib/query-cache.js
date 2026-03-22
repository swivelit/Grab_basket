import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { AppState } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const STORAGE_PREFIX = '@grab_basket/query_cache_v2/';
const DEFAULT_STALE_TIME = 60 * 1000;
const DEFAULT_CACHE_TIME = 15 * 60 * 1000;
const DEFAULT_RETRY_DELAY = 750;
const GC_INTERVAL_MS = 5 * 60 * 1000;

const memoryCache = new Map();
const inflightRequests = new Map();
const hydrationPromises = new Map();
const listenersByKey = new Map();
const runtimeByKey = new Map();

let lastGarbageCollectionAt = 0;
let garbageCollectionPromise = null;

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function stableStringify(value) {
  if (value === null) return 'null';
  if (value === undefined) return 'undefined';

  const valueType = typeof value;
  if (valueType === 'string') return JSON.stringify(value);
  if (valueType === 'number' || valueType === 'boolean' || valueType === 'bigint') return String(value);

  if (valueType === 'function') {
    return `[function:${value.name || 'anonymous'}]`;
  }

  if (value instanceof Date) {
    return `[date:${value.toISOString()}]`;
  }

  if (Array.isArray(value)) {
    return `[${value.map((item) => stableStringify(item)).join(',')}]`;
  }

  if (valueType === 'object') {
    const keys = Object.keys(value).sort();
    return `{${keys.map((key) => `${JSON.stringify(key)}:${stableStringify(value[key])}`).join(',')}}`;
  }

  try {
    return JSON.stringify(value);
  } catch {
    return String(value);
  }
}

function serializeQueryKey(queryKey) {
  if (typeof queryKey === 'string') return queryKey;
  return stableStringify(queryKey);
}

function storageKeyFor(queryHash) {
  return `${STORAGE_PREFIX}${queryHash}`;
}

function isUsableRecord(record) {
  return Boolean(record && typeof record === 'object' && Number.isFinite(Number(record.updatedAt)));
}

function isExpired(record) {
  if (!isUsableRecord(record)) return true;
  if (!Number.isFinite(Number(record.expiresAt))) return false;
  return Number(record.expiresAt) <= Date.now();
}

function isStale(record, staleTime = DEFAULT_STALE_TIME) {
  if (!isUsableRecord(record)) return true;
  return Date.now() - Number(record.updatedAt || 0) > Number(staleTime || 0);
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

function createRecord(data, cacheTime = DEFAULT_CACHE_TIME) {
  const updatedAt = Date.now();
  return {
    data,
    updatedAt,
    expiresAt: updatedAt + Math.max(0, Number(cacheTime || 0)),
  };
}

function resolveValue(value, previousValue) {
  return typeof value === 'function' ? value(previousValue) : value;
}

function getListeners(queryHash) {
  return listenersByKey.get(queryHash) || new Set();
}

function subscribe(queryHash, callback) {
  const listeners = getListeners(queryHash);
  listeners.add(callback);
  listenersByKey.set(queryHash, listeners);

  return () => {
    const nextListeners = listenersByKey.get(queryHash);
    if (!nextListeners) return;
    nextListeners.delete(callback);
    if (!nextListeners.size) {
      listenersByKey.delete(queryHash);
    }
  };
}

function notify(queryHash) {
  const listeners = listenersByKey.get(queryHash);
  if (!listeners?.size) return;
  listeners.forEach((listener) => {
    try {
      listener();
    } catch {
      // listener errors should never break cache propagation
    }
  });
}

function getRuntime(queryHash) {
  return runtimeByKey.get(queryHash) || { isFetching: false, error: null, lastSource: 'none' };
}

function setRuntime(queryHash, partialState = {}, { notifyListeners = true } = {}) {
  const current = getRuntime(queryHash);
  const next = {
    ...current,
    ...partialState,
    error: partialState.error === undefined ? current.error : partialState.error,
  };

  runtimeByKey.set(queryHash, next);
  if (notifyListeners) notify(queryHash);
  return next;
}

function getMemoryRecord(queryHash) {
  const record = memoryCache.get(queryHash);
  if (!isUsableRecord(record)) return null;

  if (isExpired(record)) {
    memoryCache.delete(queryHash);
    return null;
  }

  return record;
}

function setMemoryRecord(queryHash, record, { notifyListeners = true } = {}) {
  if (!isUsableRecord(record)) return null;
  memoryCache.set(queryHash, record);
  if (notifyListeners) notify(queryHash);
  return record;
}

function removeMemoryRecord(queryHash, { notifyListeners = true } = {}) {
  memoryCache.delete(queryHash);
  runtimeByKey.set(queryHash, {
    ...getRuntime(queryHash),
    isFetching: false,
    error: null,
    lastSource: 'none',
  });

  if (notifyListeners) notify(queryHash);
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

async function maybeGarbageCollectPersistedCache({ force = false } = {}) {
  const now = Date.now();
  if (!force && now - lastGarbageCollectionAt < GC_INTERVAL_MS) {
    return 0;
  }

  if (garbageCollectionPromise) {
    return garbageCollectionPromise;
  }

  lastGarbageCollectionAt = now;
  garbageCollectionPromise = (async () => {
    try {
      const keys = await AsyncStorage.getAllKeys();
      const scopedKeys = keys.filter((key) => key.startsWith(STORAGE_PREFIX));
      if (!scopedKeys.length) return 0;

      const rows = await AsyncStorage.multiGet(scopedKeys);
      const expiredKeys = rows
        .filter(([, raw]) => {
          try {
            const parsed = raw ? JSON.parse(raw) : null;
            return !isUsableRecord(parsed) || isExpired(parsed);
          } catch {
            return true;
          }
        })
        .map(([key]) => key);

      if (expiredKeys.length) {
        await AsyncStorage.multiRemove(expiredKeys);
      }

      return expiredKeys.length;
    } catch {
      return 0;
    } finally {
      garbageCollectionPromise = null;
    }
  })();

  return garbageCollectionPromise;
}

async function hydratePersistedRecord(queryHash, storageKey) {
  const cached = getMemoryRecord(queryHash);
  if (cached) {
    setRuntime(queryHash, { lastSource: 'memory' }, { notifyListeners: false });
    return cached;
  }

  if (hydrationPromises.has(queryHash)) {
    return hydrationPromises.get(queryHash);
  }

  const promise = (async () => {
    const persisted = await readPersistedRecord(storageKey);

    if (isUsableRecord(persisted) && !isExpired(persisted)) {
      setMemoryRecord(queryHash, persisted, { notifyListeners: false });
      setRuntime(queryHash, { lastSource: 'storage', error: null }, { notifyListeners: false });
      return persisted;
    }

    if (persisted && isExpired(persisted)) {
      await clearPersistedRecord(storageKey);
    }

    return null;
  })().finally(() => {
    hydrationPromises.delete(queryHash);
  });

  hydrationPromises.set(queryHash, promise);
  return promise;
}

async function runWithRetry(fetcher, retry = 0, retryDelay = DEFAULT_RETRY_DELAY) {
  let attempt = 0;

  while (true) {
    try {
      return await fetcher();
    } catch (error) {
      if (attempt >= Number(retry || 0)) {
        throw error;
      }

      let delay = Number(retryDelay || DEFAULT_RETRY_DELAY) * Math.max(1, attempt + 1);
      if (typeof retryDelay === 'function') {
        delay = Number(retryDelay(attempt, error) || 0);
      }

      attempt += 1;
      await sleep(Math.max(0, delay));
    }
  }
}

function getDataFromRecord(record, select) {
  if (!isUsableRecord(record) || isExpired(record)) {
    return { data: undefined, error: null };
  }

  if (typeof select !== 'function') {
    return { data: record.data, error: null };
  }

  try {
    return { data: select(record.data), error: null };
  } catch (error) {
    return { data: undefined, error: toError(error) };
  }
}

function buildViewState({
  record,
  staleTime = DEFAULT_STALE_TIME,
  select,
  runtime,
  initialData,
  placeholderData,
  previousData,
  previousUpdatedAt = 0,
  preferPreviousData = false,
  sourceOverride,
}) {
  const safeRuntime = runtime || { isFetching: false, error: null, lastSource: 'none' };
  const hasRecord = isUsableRecord(record) && !isExpired(record);

  let data;
  let updatedAt = 0;
  let source = sourceOverride || safeRuntime.lastSource || 'none';
  let selectionError = null;
  let isPlaceholderData = false;

  if (hasRecord) {
    const selected = getDataFromRecord(record, select);
    data = selected.data;
    selectionError = selected.error;
    updatedAt = Number(record.updatedAt || 0);
    source = sourceOverride || safeRuntime.lastSource || 'memory';
  } else if (preferPreviousData && previousData !== undefined) {
    data = previousData;
    updatedAt = Number(previousUpdatedAt || 0);
    source = 'previous';
  } else {
    const placeholder = resolveValue(placeholderData, previousData);
    const initial = resolveValue(initialData, previousData);

    if (placeholder !== undefined) {
      data = placeholder;
      isPlaceholderData = true;
      source = 'placeholder';
    } else if (initial !== undefined) {
      data = initial;
      source = 'initial';
    }
  }

  const error = selectionError || safeRuntime.error || null;
  const hasData = data !== undefined;
  const dataIsStale = hasRecord ? isStale(record, staleTime) : true;
  const isFetching = Boolean(safeRuntime.isFetching);
  const isLoading = !hasData && isFetching;
  const status = error && !hasData ? 'error' : hasData ? 'success' : isFetching ? 'pending' : 'idle';

  return {
    data,
    error,
    isLoading,
    isFetching,
    isSuccess: status === 'success',
    isError: status === 'error',
    isIdle: status === 'idle',
    isPending: status === 'pending',
    isStale: dataIsStale,
    isPlaceholderData,
    isFromCache: source === 'memory' || source === 'storage',
    status,
    fetchStatus: isFetching ? 'fetching' : 'idle',
    updatedAt,
    source,
  };
}

function areStatesEqual(previous, next) {
  return (
    previous?.data === next?.data &&
    previous?.error === next?.error &&
    previous?.isLoading === next?.isLoading &&
    previous?.isFetching === next?.isFetching &&
    previous?.isSuccess === next?.isSuccess &&
    previous?.isError === next?.isError &&
    previous?.isIdle === next?.isIdle &&
    previous?.isPending === next?.isPending &&
    previous?.isStale === next?.isStale &&
    previous?.isPlaceholderData === next?.isPlaceholderData &&
    previous?.isFromCache === next?.isFromCache &&
    previous?.status === next?.status &&
    previous?.fetchStatus === next?.fetchStatus &&
    previous?.updatedAt === next?.updatedAt &&
    previous?.source === next?.source
  );
}

function shouldRefetchOnMount(record, refetchOnMount, staleTime) {
  if (refetchOnMount === false) return false;
  if (refetchOnMount === 'always') return true;
  return !record || isStale(record, staleTime);
}

function getCurrentRecordSnapshot(queryHash) {
  return getMemoryRecord(queryHash);
}

export function getCachedQueryData(queryKey) {
  const queryHash = serializeQueryKey(queryKey);
  const record = getCurrentRecordSnapshot(queryHash);
  return record ? record.data : undefined;
}

export async function setCachedQueryData(queryKey, updater, cacheTime = DEFAULT_CACHE_TIME) {
  const queryHash = serializeQueryKey(queryKey);
  const storageKey = storageKeyFor(queryHash);
  const current = getCurrentRecordSnapshot(queryHash)?.data;
  const nextData = typeof updater === 'function' ? updater(current) : updater;
  const record = createRecord(nextData, cacheTime);

  setMemoryRecord(queryHash, record, { notifyListeners: false });
  setRuntime(queryHash, { error: null, isFetching: false, lastSource: 'memory' }, { notifyListeners: false });
  await writePersistedRecord(storageKey, record);
  notify(queryHash);
  return nextData;
}

export async function primeCachedQuery(queryKey, data, cacheTime = DEFAULT_CACHE_TIME) {
  return setCachedQueryData(queryKey, data, cacheTime);
}

export async function invalidateCachedQuery(queryKey) {
  const queryHash = serializeQueryKey(queryKey);
  const storageKey = storageKeyFor(queryHash);
  removeMemoryRecord(queryHash, { notifyListeners: false });
  await clearPersistedRecord(storageKey);
  notify(queryHash);
}

export async function invalidateCachedQueries(matcher) {
  const allKeys = Array.from(new Set([...memoryCache.keys(), ...runtimeByKey.keys()]));
  const matches = allKeys.filter((queryHash) => {
    if (typeof matcher === 'function') return Boolean(matcher(queryHash));
    if (matcher instanceof RegExp) return matcher.test(queryHash);
    return String(queryHash).includes(String(matcher || ''));
  });

  await Promise.all(matches.map((queryHash) => invalidateCachedQuery(queryHash)));
  return matches.length;
}

export async function clearAllCachedQueries() {
  const keys = await AsyncStorage.getAllKeys().catch(() => []);
  const scopedKeys = keys.filter((key) => key.startsWith(STORAGE_PREFIX));

  memoryCache.clear();
  inflightRequests.clear();
  hydrationPromises.clear();
  runtimeByKey.clear();

  if (scopedKeys.length) {
    await AsyncStorage.multiRemove(scopedKeys).catch(() => {});
  }

  Array.from(listenersByKey.keys()).forEach((queryHash) => notify(queryHash));
}

export function useCachedQuery({
  queryKey,
  enabled = true,
  staleTime = DEFAULT_STALE_TIME,
  cacheTime = DEFAULT_CACHE_TIME,
  fetcher,
  initialData,
  placeholderData,
  keepPreviousData = true,
  retry = 0,
  retryDelay = DEFAULT_RETRY_DELAY,
  refetchOnMount = 'stale',
  refetchOnAppFocus = false,
  select,
  onSuccess,
  onError,
}) {
  const queryHash = useMemo(() => serializeQueryKey(queryKey), [queryKey]);
  const storageKey = useMemo(() => storageKeyFor(queryHash), [queryHash]);
  const mountedRef = useRef(true);
  const previousQueryHashRef = useRef(queryHash);

  const [state, setState] = useState(() => {
    const record = getCurrentRecordSnapshot(queryHash);
    const runtime = getRuntime(queryHash);

    return buildViewState({
      record,
      staleTime,
      select,
      runtime,
      initialData,
      placeholderData,
      previousData: undefined,
      previousUpdatedAt: 0,
      sourceOverride: record ? 'memory' : runtime.lastSource,
    });
  });

  const latestStateRef = useRef(state);
  latestStateRef.current = state;

  const syncState = useCallback(
    (options = {}) => {
      const preferPreviousData = Boolean(options.preferPreviousData);
      const sourceOverride = options.sourceOverride;
      if (!mountedRef.current) return;

      const nextState = buildViewState({
        record: getCurrentRecordSnapshot(queryHash),
        staleTime,
        select,
        runtime: getRuntime(queryHash),
        initialData,
        placeholderData,
        previousData: preferPreviousData ? latestStateRef.current.data : undefined,
        previousUpdatedAt: preferPreviousData ? latestStateRef.current.updatedAt : 0,
        preferPreviousData,
        sourceOverride,
      });

      setState((current) => (areStatesEqual(current, nextState) ? current : nextState));
    },
    [initialData, placeholderData, queryHash, select, staleTime]
  );

  const refresh = useCallback(
    async ({ force = false, reason = 'manual', throwOnError = true } = {}) => {
      if (!enabled || typeof fetcher !== 'function') {
        syncState({ preferPreviousData: keepPreviousData });
        return undefined;
      }

      const cachedRecord = getCurrentRecordSnapshot(queryHash);
      if (!force && cachedRecord && !isStale(cachedRecord, staleTime)) {
        setRuntime(queryHash, { error: null, lastSource: 'memory' });
        return cachedRecord.data;
      }

      if (inflightRequests.has(queryHash)) {
        setRuntime(queryHash, { isFetching: true }, { notifyListeners: true });

        try {
          return await inflightRequests.get(queryHash);
        } catch (error) {
          if (throwOnError) throw error;
          return undefined;
        }
      }

      setRuntime(queryHash, { isFetching: true, error: null, lastSource: reason === 'mount' ? 'storage' : 'memory' });

      const request = runWithRetry(() => Promise.resolve(fetcher()), retry, retryDelay)
        .then(async (data) => {
          const record = createRecord(data, cacheTime);
          setMemoryRecord(queryHash, record, { notifyListeners: false });
          setRuntime(
            queryHash,
            {
              isFetching: false,
              error: null,
              lastSource: 'network',
            },
            { notifyListeners: false }
          );
          await writePersistedRecord(storageKey, record);
          notify(queryHash);

          if (typeof onSuccess === 'function') {
            try {
              onSuccess(data);
            } catch {
              // consumer callback errors should not break the query
            }
          }

          return data;
        })
        .catch((error) => {
          const safeError = toError(error);
          setRuntime(
            queryHash,
            {
              isFetching: false,
              error: safeError,
              lastSource: getCurrentRecordSnapshot(queryHash) ? 'memory' : 'network',
            },
            { notifyListeners: false }
          );
          notify(queryHash);

          if (typeof onError === 'function') {
            try {
              onError(safeError);
            } catch {
              // consumer callback errors should not break the query
            }
          }

          throw safeError;
        })
        .finally(() => {
          inflightRequests.delete(queryHash);
        });

      inflightRequests.set(queryHash, request);

      try {
        return await request;
      } catch (error) {
        if (throwOnError) throw error;
        return undefined;
      }
    },
    [cacheTime, enabled, fetcher, keepPreviousData, onError, onSuccess, queryHash, retry, retryDelay, staleTime, storageKey, syncState]
  );

  const invalidate = useCallback(
    async ({ refetch = false } = {}) => {
      await invalidateCachedQuery(queryHash);
      if (refetch && enabled) {
        return refresh({ force: true, reason: 'invalidate', throwOnError: false });
      }
      return undefined;
    },
    [enabled, queryHash, refresh]
  );

  const setData = useCallback(
    async (updater, nextCacheTime = cacheTime) => setCachedQueryData(queryHash, updater, nextCacheTime),
    [cacheTime, queryHash]
  );

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    const previousQueryHash = previousQueryHashRef.current;
    if (previousQueryHash === queryHash) return;

    previousQueryHashRef.current = queryHash;
    syncState({ preferPreviousData: keepPreviousData, sourceOverride: keepPreviousData ? 'previous' : 'none' });
  }, [keepPreviousData, queryHash, syncState]);

  useEffect(() => {
    const unsubscribe = subscribe(queryHash, () => {
      syncState({ preferPreviousData: false });
    });

    syncState({ preferPreviousData: keepPreviousData });
    return unsubscribe;
  }, [keepPreviousData, queryHash, syncState]);

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      if (!enabled) {
        setRuntime(queryHash, { isFetching: false }, { notifyListeners: false });
        syncState({ preferPreviousData: keepPreviousData });
        return;
      }

      maybeGarbageCollectPersistedCache().catch(() => {});

      const record = await hydratePersistedRecord(queryHash, storageKey);
      if (cancelled) return;

      if (record) {
        syncState({ preferPreviousData: false, sourceOverride: 'storage' });
      } else {
        syncState({ preferPreviousData: keepPreviousData });
      }

      if (shouldRefetchOnMount(record, refetchOnMount, staleTime)) {
        refresh({ force: true, reason: 'mount', throwOnError: false }).catch(() => {});
      }
    };

    run().catch(() => {
      if (!cancelled) {
        syncState({ preferPreviousData: keepPreviousData });
      }
    });

    return () => {
      cancelled = true;
    };
  }, [enabled, keepPreviousData, queryHash, refetchOnMount, refresh, staleTime, storageKey, syncState]);

  useEffect(() => {
    if (!enabled || !refetchOnAppFocus) return undefined;

    const subscription = AppState.addEventListener('change', (nextState) => {
      if (nextState !== 'active') return;

      const record = getCurrentRecordSnapshot(queryHash);
      if (!record || isStale(record, staleTime)) {
        refresh({ force: true, reason: 'app-focus', throwOnError: false }).catch(() => {});
      }
    });

    return () => {
      subscription?.remove?.();
    };
  }, [enabled, queryHash, refetchOnAppFocus, refresh, staleTime]);

  return {
    data: state.data,
    error: state.error,
    isLoading: state.isLoading,
    isFetching: state.isFetching,
    isSuccess: state.isSuccess,
    isError: state.isError,
    isIdle: state.isIdle,
    isPending: state.isPending,
    isStale: state.isStale,
    isPlaceholderData: state.isPlaceholderData,
    isFromCache: state.isFromCache,
    status: state.status,
    fetchStatus: state.fetchStatus,
    updatedAt: state.updatedAt,
    source: state.source,
    refresh: () => refresh({ force: true, reason: 'manual' }),
    invalidate,
    setData,
  };
}