export const DEFAULT_ACCESS_TOKEN_TTL_MS = 55 * 60 * 1000;
export const DEFAULT_REFRESH_TOKEN_TTL_MS = 30 * 24 * 60 * 60 * 1000;
export const TOKEN_REFRESH_SKEW_MS = 60 * 1000;
export const FREE_DELIVERY_THRESHOLD = 199;
export const PLATFORM_FEE = 0;
export const MAX_RECENT = 8;
export const MAX_ORDERS = 50;

export function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

export function normalizeErrorMessage(error, fallback = 'Something went wrong') {
  if (error instanceof Error && error.message) return error.message;
  if (typeof error === 'string' && error.trim()) return error.trim();
  return fallback;
}

export function normalizeUserRole(value = '') {
  return String(value || '').trim().toUpperCase();
}

export function getRoleLabel(role = '') {
  const normalized = normalizeUserRole(role);
  if (normalized === 'CUSTOMER') return 'customer';
  if (normalized === 'PARTNER') return 'delivery partner';
  if (normalized === 'SELLER') return 'seller';
  if (normalized === 'ADMIN') return 'admin';
  return normalized ? normalized.toLowerCase() : 'account';
}

export function getRoleDestinationAppName(role = '') {
  const normalized = normalizeUserRole(role);
  if (normalized === 'CUSTOMER') return 'Grab Basket';
  if (normalized === 'PARTNER') return 'Grab Basket Delivery App';
  if (normalized === 'SELLER') return 'Grab Basket Partner App';
  return '';
}

export function formatSupportedRoleList(roles = []) {
  return roles.map((role) => getRoleLabel(role)).filter(Boolean).join(' or ');
}

export function isRoleSupportedByApp(role = '', allowedRoles = []) {
  return allowedRoles.includes(normalizeUserRole(role));
}

export function getUnsupportedRoleMessage(role = '', { allowedRoles = [], appVariantName = 'Grab Basket' } = {}) {
  const normalized = normalizeUserRole(role);
  const supported = formatSupportedRoleList(allowedRoles) || 'supported';
  const destinationApp = getRoleDestinationAppName(normalized);

  if (!normalized) {
    return `This ${appVariantName} build supports ${supported} accounts only.`;
  }

  if (isRoleSupportedByApp(normalized, allowedRoles)) {
    return '';
  }

  if (destinationApp && destinationApp !== appVariantName) {
    return `This ${appVariantName} build only supports ${supported} accounts. Please use ${destinationApp} for ${getRoleLabel(normalized)} accounts.`;
  }

  return `This ${appVariantName} build only supports ${supported} accounts. ${getRoleLabel(normalized)} accounts are not allowed here.`;
}

export function resolveExpiryAt(expiresInSeconds, fallbackMs) {
  const seconds = Number(expiresInSeconds);
  if (Number.isFinite(seconds) && seconds > 0) {
    return Date.now() + seconds * 1000;
  }
  return Date.now() + fallbackMs;
}

export function buildSessionFromAuthResponse(data, { email = '', fallbackRole = '' } = {}) {
  const accessToken = String(data?.access_token || '');
  const refreshToken = String(data?.refresh_token || '');

  return {
    accessToken,
    refreshToken,
    email: String(email || '').trim().toLowerCase(),
    role: normalizeUserRole(data?.role || fallbackRole),
    accessTokenExpiresAt: accessToken
      ? resolveExpiryAt(data?.access_token_expires_in, DEFAULT_ACCESS_TOKEN_TTL_MS)
      : 0,
    refreshTokenExpiresAt: refreshToken
      ? resolveExpiryAt(data?.refresh_token_expires_in, DEFAULT_REFRESH_TOKEN_TTL_MS)
      : 0,
  };
}

export function mapLegacyService(value) {
  const service = String(value || '').trim().toLowerCase();
  if (service === 'instamart') return 'warehouse';
  if (service === 'dineout') return 'eatout';
  return service || 'food';
}

export function findVendorById(vendors = [], vendorId) {
  return (vendors || []).find((item) => String(item?.id) === String(vendorId)) || null;
}

export function getDeliveryFeeAmount(vendor) {
  const value = Number(vendor?.delivery_fee ?? vendor?.packaging_fee ?? 0);
  return Number.isFinite(value) && value > 0 ? value : 0;
}

export function buildVendorQuery({ search = '', service = 'food', address = null } = {}) {
  const params = new URLSearchParams();
  const q = String(search || '').trim();
  const normalizedService = mapLegacyService(service);

  if (q) params.set('q', q);
  params.set('limit', '50');

  if (normalizedService === 'food' || normalizedService === 'warehouse') {
    params.set('delivery_only', 'true');
  }

  if (address?.lat != null && address?.lng != null) {
    params.set('lat', String(address.lat));
    params.set('lng', String(address.lng));
  }

  const query = params.toString();
  return `/vendors${query ? `?${query}` : ''}`;
}

export function sortVendors(vendors = []) {
  return [...vendors].sort((left, right) => {
    const leftOpen = left?.open_now === false ? 1 : 0;
    const rightOpen = right?.open_now === false ? 1 : 0;

    if (leftOpen !== rightOpen) {
      return leftOpen - rightOpen;
    }

    const leftDeliverable = left?.can_deliver === false ? 1 : 0;
    const rightDeliverable = right?.can_deliver === false ? 1 : 0;

    if (leftDeliverable !== rightDeliverable) {
      return leftDeliverable - rightDeliverable;
    }

    const leftRating = Number(left?.avg_rating || 0);
    const rightRating = Number(right?.avg_rating || 0);

    if (leftRating !== rightRating) {
      return rightRating - leftRating;
    }

    return String(left?.name || '').localeCompare(String(right?.name || ''));
  });
}

export function dedupeStrings(values = []) {
  const seen = new Set();
  const result = [];

  values.forEach((value) => {
    const normalized = normalizeText(value);
    if (!normalized || seen.has(normalized)) return;
    seen.add(normalized);
    result.push(String(value).trim());
  });

  return result;
}

export function createShortcutBuckets(vendors = []) {
  const buckets = {
    all: vendors,
    fresh: [],
    snacks: [],
    value: [],
    essentials: [],
  };

  vendors.forEach((vendor) => {
    const haystack = normalizeText(
      [vendor?.name, vendor?.description, vendor?.address, vendor?.cuisine_tags].join(' ')
    );

    if (/fresh|fruit|veg|vegetable|dairy|farm/.test(haystack)) {
      buckets.fresh.push(vendor);
    }
    if (/snack|chips|biscuit|drink|beverage/.test(haystack)) {
      buckets.snacks.push(vendor);
    }
    if (/value|deal|offer|save|budget/.test(haystack)) {
      buckets.value.push(vendor);
    }
    if (/essential|home|daily|grocery|kitchen/.test(haystack)) {
      buckets.essentials.push(vendor);
    }
  });

  return buckets;
}

export function isValidCart(value) {
  return Boolean(
    value &&
      typeof value === 'object' &&
      value.items &&
      typeof value.items === 'object'
  );
}

export function prettifyStatus(status = '') {
  return String(status || '')
    .replace(/_/g, ' ')
    .trim()
    .toLowerCase()
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function inferServiceFromContent(vendor, items = []) {
  const haystack = normalizeText([
    vendor?.name,
    vendor?.description,
    vendor?.cuisine_tags,
    ...items.map((item) => item?.name),
  ].join(' '));

  if (/table|reserve|booking|cafe|restaurant/.test(haystack)) return 'eatout';
  if (/scene|event|workshop|experience|ticket/.test(haystack)) return 'scenes';
  if (/grocery|basket|mart|fruit|snack|daily/.test(haystack)) return 'warehouse';
  return 'food';
}

export function formatAddressShort(address) {
  if (!address) return '';
  return [address.label, address.line1, address.city].filter(Boolean).join(' · ');
}

export function normalizeAddress(item) {
  if (!item || typeof item !== 'object') return null;

  return {
    id: item.id,
    label: String(item.label || 'Address').trim(),
    line1: String(item.line1 || '').trim(),
    line2: String(item.line2 || '').trim(),
    city: String(item.city || '').trim(),
    pincode: String(item.pincode || '').trim(),
    lat: Number(item.lat),
    lng: Number(item.lng),
    is_default: Boolean(item.is_default),
  };
}

export function normalizeOrderRecord(order, { vendors = [], addresses = [], serviceHint = '' } = {}) {
  if (!order || typeof order !== 'object') return null;

  const vendor = findVendorById(vendors, order.vendor_id);
  const address = (addresses || []).find((item) => String(item?.id) === String(order.delivery_address_id)) || null;
  const items = Array.isArray(order.items) ? order.items : [];
  const service = serviceHint || inferServiceFromContent(vendor, items);

  return {
    ...order,
    id: order.id,
    service,
    vendor_id: order.vendor_id,
    vendor_name: vendor?.name || order.vendor_name || 'Store',
    vendor_image_url: vendor?.cover_image_url || vendor?.logo_image_url || '',
    delivery_address_label: formatAddressShort(address),
    item_count: items.reduce((sum, item) => sum + Number(item?.qty || 0), 0),
    status_label: prettifyStatus(order.status),
  };
}

export function mergeOrderCollections(primary = [], secondary = []) {
  const byId = new Map();

  [...primary, ...secondary].forEach((order) => {
    if (!order?.id) return;

    const key = String(order.id);
    const existing = byId.get(key);

    if (!existing) {
      byId.set(key, order);
      return;
    }

    const existingTs = Date.parse(existing?.updated_at || existing?.created_at || 0) || 0;
    const nextTs = Date.parse(order?.updated_at || order?.created_at || 0) || 0;

    byId.set(key, nextTs >= existingTs ? order : existing);
  });

  return [...byId.values()].sort((left, right) => {
    const leftTs = Date.parse(left?.updated_at || left?.created_at || 0) || 0;
    const rightTs = Date.parse(right?.updated_at || right?.created_at || 0) || 0;
    return rightTs - leftTs;
  });
}

export function normalizePaymentMethod(value = '') {
  const normalized = String(value || 'COD').trim().toUpperCase();
  if (normalized === 'CASH') return 'COD';
  return normalized || 'COD';
}

export function normalizeGatewayStatus(value = '') {
  return String(value || '').trim().toLowerCase();
}

export function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function pollGatewayStatus(requestPaymentStatus, orderId, { attempts = 4, delayMs = 1500 } = {}) {
  let last = null;

  for (let attempt = 0; attempt < attempts; attempt += 1) {
    await sleep(delayMs);
    last = await requestPaymentStatus(orderId);

    const paymentStatus = normalizeUserRole(last?.payment_status || '');
    const checkoutStatus = normalizeGatewayStatus(last?.checkout_status);

    if (['PAID', 'FAILED'].includes(paymentStatus) || ['cancelled', 'expired'].includes(checkoutStatus)) {
      return last;
    }
  }

  return last;
}
