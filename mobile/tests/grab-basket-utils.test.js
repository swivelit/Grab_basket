import test from 'node:test';
import assert from 'node:assert/strict';

import {
  buildSessionFromAuthResponse,
  createShortcutBuckets,
  mergeOrderCollections,
  normalizeOrderRecord,
  normalizePaymentMethod,
  sortVendors,
} from '../src/domains/grab-basket-utils.js';

test('buildSessionFromAuthResponse normalizes tokens and expiry', () => {
  const session = buildSessionFromAuthResponse(
    {
      access_token: 'access-123',
      refresh_token: 'refresh-456',
      role: 'customer',
      access_token_expires_in: 120,
      refresh_token_expires_in: 240,
    },
    { email: 'USER@EXAMPLE.COM', fallbackRole: 'CUSTOMER' }
  );

  assert.equal(session.accessToken, 'access-123');
  assert.equal(session.refreshToken, 'refresh-456');
  assert.equal(session.email, 'user@example.com');
  assert.equal(session.role, 'CUSTOMER');
  assert.ok(session.accessTokenExpiresAt > Date.now());
  assert.ok(session.refreshTokenExpiresAt > Date.now());
});

test('sortVendors prefers open and deliverable stores', () => {
  const result = sortVendors([
    { id: 1, name: 'Closed', open_now: false, can_deliver: true, avg_rating: 5 },
    { id: 2, name: 'Live', open_now: true, can_deliver: true, avg_rating: 4.1 },
    { id: 3, name: 'Far', open_now: true, can_deliver: false, avg_rating: 5 },
  ]);

  assert.deepEqual(result.map((item) => item.id), [2, 3, 1]);
});

test('createShortcutBuckets groups warehouse vendors by keywords', () => {
  const buckets = createShortcutBuckets([
    { id: 1, name: 'Fresh Farm', description: 'fruit and vegetable delivery' },
    { id: 2, name: 'Snack Box', description: 'chips and beverages' },
  ]);

  assert.equal(buckets.fresh[0].id, 1);
  assert.equal(buckets.snacks[0].id, 2);
});

test('normalizePaymentMethod converts cash to COD', () => {
  assert.equal(normalizePaymentMethod('cash'), 'COD');
  assert.equal(normalizePaymentMethod('upi'), 'UPI');
});

test('normalizeOrderRecord enriches raw order with vendor and address', () => {
  const order = normalizeOrderRecord(
    {
      id: 101,
      vendor_id: 9,
      delivery_address_id: 88,
      status: 'PAYMENT_PENDING',
      items: [{ qty: 2, name: 'Burger' }],
    },
    {
      vendors: [{ id: 9, name: 'Burger Hub' }],
      addresses: [{ id: 88, label: 'Home', line1: 'Street 1', city: 'Kochi' }],
    }
  );

  assert.equal(order.vendor_name, 'Burger Hub');
  assert.equal(order.delivery_address_label, 'Home · Street 1 · Kochi');
  assert.equal(order.item_count, 2);
  assert.equal(order.status_label, 'Payment Pending');
});

test('mergeOrderCollections keeps the newest version of an order', () => {
  const merged = mergeOrderCollections(
    [{ id: 1, status: 'DELIVERED', updated_at: '2026-03-28T10:00:00Z' }],
    [{ id: 1, status: 'CREATED', updated_at: '2026-03-28T09:00:00Z' }, { id: 2, status: 'CREATED' }]
  );

  assert.equal(merged.length, 2);
  assert.equal(merged.find((item) => item.id === 1).status, 'DELIVERED');
});
