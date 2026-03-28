import * as Application from 'expo-application';
import { Platform } from 'react-native';

import { API_CONFIG_ERROR, API_TIMEOUT_MS, buildApiUrl } from '../config';
import { getAppVariant } from '../constants/app-shell';
import { STORAGE_KEYS, buildScopedStorageKey, readStoredValue } from './storage';

const APP_VARIANT = getAppVariant();
const DEFAULT_TIMEOUT_MS =
  Number.isFinite(Number(API_TIMEOUT_MS)) && Number(API_TIMEOUT_MS) > 0
    ? Number(API_TIMEOUT_MS)
    : 15000;
const DEFAULT_RETRY_DELAY_MS = 350;
const MAX_RETRY_DELAY_MS = 2500;

const RETRYABLE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);
const RETRYABLE_STATUS_CODES = new Set([408, 425, 429, 500, 502, 503, 504]);

export { STORAGE_KEYS, buildScopedStorageKey };

export class ApiError extends Error {
  constructor(message, extras = {}) {
    super(message || 'API request failed');
    this.name = 'ApiError';
    this.status = extras.status || 0;
    this.code = extras.code || 'API_ERROR';
    this.requestId = extras.requestId || '';
    this.payload = extras.payload;
    this.headers = extras.headers || {};
    this.url = extras.url || '';
    this.method = extras.method || 'GET';
    this.isRetryable = Boolean(extras.isRetryable);
    this.durationMs = Number(extras.durationMs || 0);
    this.attempt = Number(extras.attempt || 0);
    this.cause = extras.cause;
  }

  toJSON() {
    return {
      name: this.name,
      message: this.message,
      status: this.status,
      code: this.code,
      requestId: this.requestId,
      payload: this.payload,
      headers: this.headers,
      url: this.url,
      method: this.method,
      isRetryable: this.isRetryable,
      durationMs: this.durationMs,
      attempt: this.attempt,
    };
  }
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function clampNumber(value, fallback, { min = 0, max = Number.MAX_SAFE_INTEGER } = {}) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) return fallback;
  if (parsed < min) return fallback;
  if (parsed > max) return fallback;
  return parsed;
}

function normalizeMethod(value = 'GET') {
  return String(value || 'GET').trim().toUpperCase();
}

function isAbsoluteUrl(value = '') {
  return /^https?:\/\//i.test(String(value || '').trim());
}

function appendQuery(urlString, query) {
  if (!query || typeof query !== 'object') {
    return urlString;
  }

  const url = new URL(urlString);

  Object.entries(query).forEach(([key, rawValue]) => {
    if (rawValue === undefined || rawValue === null || rawValue === '') {
      return;
    }

    if (Array.isArray(rawValue)) {
      rawValue.forEach((item) => {
        if (item === undefined || item === null || item === '') return;
        url.searchParams.append(key, String(item));
      });
      return;
    }

    url.searchParams.set(key, String(rawValue));
  });

  return url.toString();
}

function resolveUrl(path, query) {
  const base = isAbsoluteUrl(path) ? String(path).trim() : buildApiUrl(path);
  return appendQuery(base, query);
}

function createFallbackRequestId() {
  const seed = Math.random().toString(36).slice(2, 10);
  return `req_${Date.now().toString(36)}_${seed}`;
}

function createRequestId(explicitRequestId = '') {
  const normalized = String(explicitRequestId || '').trim();
  if (normalized) return normalized;

  const randomUuid = globalThis?.crypto?.randomUUID?.();
  if (randomUuid) return randomUuid;

  return createFallbackRequestId();
}

function safeJsonParse(raw) {
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    return raw;
  }
}

function isPlainObject(value) {
  return Boolean(value) && Object.prototype.toString.call(value) === '[object Object]';
}

function normalizeResponseHeaders(response) {
  const headers = {};

  if (!response?.headers) {
    return headers;
  }

  if (typeof response.headers.forEach === 'function') {
    response.headers.forEach((value, key) => {
      headers[key] = value;
    });
  }

  return headers;
}

function getHeaderValue(headers, key) {
  const expected = String(key || '').toLowerCase();
  const match = Object.keys(headers || {}).find((headerKey) => headerKey.toLowerCase() === expected);
  return match ? headers[match] : '';
}

function normalizeErrorMessage(error, fallback = 'Something went wrong') {
  if (error instanceof Error && error.message) return error.message;
  if (typeof error === 'string' && error.trim()) return error.trim();
  return fallback;
}

function extractErrorMessage(payload, fallback = 'Request failed') {
  if (Array.isArray(payload)) {
    const first = payload[0];
    if (typeof first === 'string' && first.trim()) return first.trim();
    if (first && typeof first === 'object') {
      if (typeof first.msg === 'string' && first.msg.trim()) return first.msg.trim();
      if (typeof first.message === 'string' && first.message.trim()) return first.message.trim();
    }
  }

  if (payload && typeof payload === 'object') {
    if (typeof payload.detail === 'string' && payload.detail.trim()) return payload.detail.trim();
    if (typeof payload.message === 'string' && payload.message.trim()) return payload.message.trim();
    if (typeof payload.error === 'string' && payload.error.trim()) return payload.error.trim();

    if (payload.error && typeof payload.error === 'object') {
      if (typeof payload.error.message === 'string' && payload.error.message.trim()) {
        return payload.error.message.trim();
      }
    }

    if (Array.isArray(payload.detail) && payload.detail.length) {
      const firstDetail = payload.detail[0];
      if (typeof firstDetail === 'string' && firstDetail.trim()) return firstDetail.trim();
      if (firstDetail && typeof firstDetail === 'object') {
        if (typeof firstDetail.msg === 'string' && firstDetail.msg.trim()) {
          return firstDetail.msg.trim();
        }
        if (typeof firstDetail.message === 'string' && firstDetail.message.trim()) {
          return firstDetail.message.trim();
        }
      }
    }
  }

  if (typeof payload === 'string' && payload.trim()) {
    return payload.trim();
  }

  return fallback;
}

function buildStatusCode(status) {
  if (status === 400) return 'BAD_REQUEST';
  if (status === 401) return 'UNAUTHORIZED';
  if (status === 403) return 'FORBIDDEN';
  if (status === 404) return 'NOT_FOUND';
  if (status === 408) return 'TIMEOUT';
  if (status === 409) return 'CONFLICT';
  if (status === 422) return 'VALIDATION_ERROR';
  if (status === 429) return 'RATE_LIMITED';
  if (status >= 500) return 'SERVER_ERROR';
  return 'HTTP_ERROR';
}

function isRetryableStatus(status) {
  return RETRYABLE_STATUS_CODES.has(Number(status));
}

function shouldRetry({ error, method, attempt, retries, retryOnStatuses }) {
  if (attempt >= retries) return false;

  const normalizedMethod = normalizeMethod(method);
  if (!RETRYABLE_METHODS.has(normalizedMethod)) return false;

  if (!error) return false;
  if (error.code === 'API_CONFIG_ERROR') return false;
  if (error.code === 'ABORTED') return false;
  if (error.code === 'TIMEOUT' || error.code === 'NETWORK_ERROR') return true;

  const retryableStatuses =
    Array.isArray(retryOnStatuses) && retryOnStatuses.length
      ? new Set(retryOnStatuses.map((value) => Number(value)))
      : RETRYABLE_STATUS_CODES;

  return retryableStatuses.has(Number(error.status));
}

function buildRetryDelay(attempt, baseDelayMs = DEFAULT_RETRY_DELAY_MS) {
  const safeBase = clampNumber(baseDelayMs, DEFAULT_RETRY_DELAY_MS, {
    min: 50,
    max: MAX_RETRY_DELAY_MS,
  });
  const exponential = Math.min(MAX_RETRY_DELAY_MS, safeBase * Math.pow(2, attempt));
  const jitter = Math.floor(Math.random() * 120);
  return exponential + jitter;
}

function shouldTreatBodyAsJson(body) {
  if (body === null || body === undefined) return false;
  if (typeof body === 'string') return false;
  if (typeof FormData !== 'undefined' && body instanceof FormData) return false;
  if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) return false;
  if (typeof Blob !== 'undefined' && body instanceof Blob) return false;
  if (typeof ArrayBuffer !== 'undefined' && body instanceof ArrayBuffer) return false;
  return isPlainObject(body) || Array.isArray(body);
}

function prepareRequestBody(body, headers = {}) {
  if (body === null || body === undefined) {
    return { body: undefined, headers };
  }

  if (!shouldTreatBodyAsJson(body)) {
    return { body, headers };
  }

  const nextHeaders = { ...headers };
  const hasContentType = Object.keys(nextHeaders).some(
    (key) => key.toLowerCase() === 'content-type'
  );

  if (!hasContentType) {
    nextHeaders['Content-Type'] = 'application/json';
  }

  return {
    body: JSON.stringify(body),
    headers: nextHeaders,
  };
}

function parseResponseBody(raw, responseHeaders, parse) {
  if (parse === 'text') return raw;
  if (parse === 'none') return null;

  const contentType = String(getHeaderValue(responseHeaders, 'content-type') || '').toLowerCase();

  if (parse === 'json') {
    return safeJsonParse(raw);
  }

  if (contentType.includes('application/json') || contentType.includes('+json')) {
    return safeJsonParse(raw);
  }

  return raw;
}

export async function readStoredAccessToken() {
  return String((await readStoredValue(STORAGE_KEYS.authToken)) || '').trim();
}

export async function readStoredRefreshToken() {
  return String((await readStoredValue(STORAGE_KEYS.refreshToken)) || '').trim();
}

async function resolveAuthorizationHeader({ requireAuth = false, token = '', tokenProvider } = {}) {
  let resolvedToken = String(token || '').trim();

  if (!resolvedToken && typeof tokenProvider === 'function') {
    resolvedToken = String((await tokenProvider()) || '').trim();
  }

  if (!resolvedToken && requireAuth) {
    resolvedToken = await readStoredAccessToken();
  }

  if (!resolvedToken) {
    if (requireAuth) {
      throw new ApiError('Authentication required', {
        code: 'UNAUTHORIZED',
        status: 401,
        isRetryable: false,
      });
    }

    return {};
  }

  return {
    Authorization: `Bearer ${resolvedToken}`,
  };
}

function normalizeApiError(error, extras = {}) {
  if (error instanceof ApiError) {
    return error;
  }

  if (error?.name === 'AbortError') {
    return new ApiError(
      `Request timed out after ${Math.round(Number(extras.timeoutMs || DEFAULT_TIMEOUT_MS) / 1000)}s`,
      {
        code: 'TIMEOUT',
        status: 408,
        requestId: extras.requestId,
        url: extras.url,
        method: extras.method,
        isRetryable: true,
        durationMs: extras.durationMs,
        attempt: extras.attempt,
        cause: error,
      }
    );
  }

  return new ApiError(normalizeErrorMessage(error, 'Network request failed'), {
    code: error?.code || 'NETWORK_ERROR',
    status: Number(error?.status || 0),
    payload: error?.payload,
    headers: error?.headers,
    requestId: extras.requestId || error?.requestId,
    url: extras.url || error?.url,
    method: extras.method || error?.method,
    isRetryable: error?.isRetryable ?? true,
    durationMs: extras.durationMs,
    attempt: extras.attempt,
    cause: error,
  });
}

function getAppMetadataHeaders() {
  const appVersion =
    Application?.nativeApplicationVersion ||
    Application?.nativeBuildVersion ||
    '';

  return {
    'X-Request-Platform': Platform.OS,
    'X-App-Variant': APP_VARIANT,
    ...(appVersion ? { 'X-App-Version': String(appVersion) } : {}),
  };
}

export async function apiRequest(path, options = {}) {
  const method = normalizeMethod(options.method || 'GET');
  const retries = clampNumber(options.retries, RETRYABLE_METHODS.has(method) ? 1 : 0, {
    min: 0,
    max: 5,
  });
  const timeoutMs = clampNumber(options.timeoutMs, DEFAULT_TIMEOUT_MS, {
    min: 1000,
    max: 120000,
  });
  const requestId = createRequestId(options.requestId);

  if (API_CONFIG_ERROR) {
    throw new ApiError(API_CONFIG_ERROR, {
      code: 'API_CONFIG_ERROR',
      requestId,
      method,
      isRetryable: false,
    });
  }

  const url = resolveUrl(path, options.query);
  const authHeaders = await resolveAuthorizationHeader({
    requireAuth: Boolean(options.requireAuth),
    token: options.token,
    tokenProvider: options.tokenProvider,
  });

  const baseHeaders = {
    Accept: 'application/json',
    'X-Request-Id': requestId,
    ...getAppMetadataHeaders(),
    ...authHeaders,
    ...(options.headers || {}),
  };

  const prepared = prepareRequestBody(options.body, baseHeaders);
  let lastError = null;

  for (let attempt = 0; attempt <= retries; attempt += 1) {
    const startedAt = Date.now();
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    let removeAbortListener = null;

    if (options.signal) {
      if (options.signal.aborted) {
        clearTimeout(timeoutId);
        throw new ApiError('Request was cancelled', {
          code: 'ABORTED',
          requestId,
          method,
          url,
          isRetryable: false,
          attempt,
        });
      }

      const abortHandler = () => controller.abort();
      options.signal.addEventListener('abort', abortHandler);
      removeAbortListener = () => options.signal.removeEventListener('abort', abortHandler);
    }

    try {
      const response = await fetch(url, {
        method,
        headers: prepared.headers,
        body: prepared.body,
        signal: controller.signal,
      });

      const raw = await response.text();
      const responseHeaders = normalizeResponseHeaders(response);
      const payload = parseResponseBody(raw, responseHeaders, options.parse || 'auto');
      const durationMs = Date.now() - startedAt;

      if (!response.ok) {
        throw new ApiError(
          extractErrorMessage(payload, `Request failed with status ${response.status}`),
          {
            status: response.status,
            code: buildStatusCode(response.status),
            requestId,
            payload,
            headers: responseHeaders,
            url,
            method,
            isRetryable: isRetryableStatus(response.status),
            durationMs,
            attempt,
          }
        );
      }

      return {
        ok: true,
        status: response.status,
        headers: responseHeaders,
        data: payload,
        requestId,
        url,
        method,
        durationMs,
        attempt,
      };
    } catch (error) {
      const durationMs = Date.now() - startedAt;
      const normalizedError = normalizeApiError(error, {
        requestId,
        url,
        method,
        timeoutMs,
        durationMs,
        attempt,
      });

      lastError = normalizedError;

      if (
        !shouldRetry({
          error: normalizedError,
          method,
          attempt,
          retries,
          retryOnStatuses: options.retryOnStatuses,
        })
      ) {
        throw normalizedError;
      }

      const retryDelayMs = buildRetryDelay(
        attempt,
        options.retryDelayMs || DEFAULT_RETRY_DELAY_MS
      );
      await sleep(retryDelayMs);
    } finally {
      clearTimeout(timeoutId);
      if (removeAbortListener) removeAbortListener();
    }
  }

  throw (
    lastError ||
    new ApiError('Request failed', {
      code: 'API_ERROR',
      requestId,
      url,
      method,
    })
  );
}

export async function requestJson(path, options = {}) {
  const response = await apiRequest(path, options);
  return response.data;
}

export function apiGet(path, options = {}) {
  return requestJson(path, {
    ...options,
    method: 'GET',
  });
}

export function apiPost(path, body, options = {}) {
  return requestJson(path, {
    ...options,
    method: 'POST',
    body,
  });
}

export function apiPut(path, body, options = {}) {
  return requestJson(path, {
    ...options,
    method: 'PUT',
    body,
  });
}

export function apiPatch(path, body, options = {}) {
  return requestJson(path, {
    ...options,
    method: 'PATCH',
    body,
  });
}

export function apiDelete(path, options = {}) {
  return requestJson(path, {
    ...options,
    method: 'DELETE',
  });
}

export function getErrorMessage(error, fallback = 'Something went wrong') {
  if (error instanceof ApiError && error.message) return error.message;
  return normalizeErrorMessage(error, fallback);
}

export function subscribeToOrderTimelineStream({
  orderId,
  token,
  sinceId = 0,
  onEvent,
  onError,
  onOpen,
  reconnect = true,
  maxBackoffMs = 15000,
}) {
  let disposed = false;
  let cursor = Number(sinceId || 0);
  let backoffMs = 1000;
  let controller = null;

  async function connect() {
    if (disposed) return;
    controller = new AbortController();
    const response = await fetch(
      buildApiUrl(`/platform/orders/${orderId}/timeline/stream?since_id=${cursor}`),
      {
        method: 'GET',
        headers: {
          Accept: 'text/event-stream',
          Authorization: `Bearer ${String(token || '').trim()}`,
        },
        signal: controller.signal,
      }
    );
    if (!response.ok || !response.body) {
      throw new Error(`Timeline stream unavailable (${response.status})`);
    }
    backoffMs = 1000;
    if (typeof onOpen === 'function') onOpen();

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';
    while (!disposed) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      const chunks = buffer.split('\n\n');
      buffer = chunks.pop() || '';
      chunks.forEach((chunk) => {
        const lines = chunk.split('\n');
        const dataLine = lines.find((line) => line.startsWith('data: '));
        const idLine = lines.find((line) => line.startsWith('id: '));
        if (!dataLine) return;
        try {
          const payload = JSON.parse(dataLine.slice(6));
          if (idLine) cursor = Math.max(cursor, Number(idLine.slice(4) || 0));
          if (typeof payload?.id === 'number') cursor = Math.max(cursor, payload.id);
          if (typeof onEvent === 'function') onEvent(payload);
        } catch {
          // ignore invalid event payload
        }
      });
    }
  }

  async function run() {
    while (!disposed) {
      try {
        await connect();
      } catch (error) {
        if (typeof onError === 'function') onError(error);
      }
      if (!reconnect || disposed) break;
      await sleep(backoffMs);
      backoffMs = Math.min(maxBackoffMs, Math.round(backoffMs * 1.8));
    }
  }

  run().catch((error) => {
    if (typeof onError === 'function') onError(error);
  });

  return {
    close: () => {
      disposed = true;
      controller?.abort();
    },
  };
}
