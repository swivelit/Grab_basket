import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'models.dart';

/// Persists the customer's cart between app launches.
///
/// We intentionally use [FlutterSecureStorage] here because it already exists in
/// the project and avoids adding extra dependencies.
///
/// Data shape (v1):
/// {
///   "v": 1,
///   "vendor_id": 12,
///   "vendor_name": "Some Store",
///   "lines": [
///     {
///       "qty": 2,
///       "product": {
///         "id": 10,
///         "vendor_id": 12,
///         "name": "Item",
///         "description": "...",
///         "price": 99.0,
///         "is_available": true
///       }
///     }
///   ]
/// }
class CartStorage {
  static const String _key = 'cart_state_v1';
  static const int _version = 1;

  final FlutterSecureStorage _s;

  const CartStorage({FlutterSecureStorage? storage}) : _s = storage ?? const FlutterSecureStorage();

  Future<void> save(CartState? cart) async {
    if (cart == null || cart.lines.isEmpty) {
      await _s.delete(key: _key);
      return;
    }

    final payload = <String, dynamic>{
      'v': _version,
      'vendor_id': cart.vendorId,
      'vendor_name': cart.vendorName,
      'lines': cart.lines
          .where((l) => l.qty > 0)
          .map((l) => <String, dynamic>{
                'qty': l.qty,
                'product': <String, dynamic>{
                  'id': l.product.id,
                  'vendor_id': l.product.vendorId,
                  'name': l.product.name,
                  'description': l.product.description,
                  'price': l.product.price,
                  'is_available': l.product.isAvailable,
                },
              })
          .toList(),
    };

    await _s.write(key: _key, value: jsonEncode(payload));
  }

  Future<CartState?> load() async {
    try {
      final raw = await _s.read(key: _key);
      if (raw == null || raw.trim().isEmpty) return null;

      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      final map = decoded.cast<String, dynamic>();
      final v = map['v'];
      if (v != _version) return null;

      final vendorId = map['vendor_id'];
      if (vendorId is! int) return null;
      final vendorName = (map['vendor_name'] ?? '').toString().trim();

      final linesRaw = map['lines'];
      if (linesRaw is! List) return null;

      final lines = <CartLine>[];
      for (final row in linesRaw) {
        if (row is! Map) continue;
        final r = row.cast<String, dynamic>();
        final qty = r['qty'];
        final prodRaw = r['product'];
        if (qty is! int || qty <= 0) continue;
        if (prodRaw is! Map) continue;
        final p = prodRaw.cast<String, dynamic>();

        final id = p['id'];
        final pVendorId = p['vendor_id'];
        final name = (p['name'] ?? '').toString();
        final desc = (p['description'] ?? '').toString();
        final price = p['price'];
        final isAvail = p['is_available'];

        if (id is! int || pVendorId is! int) continue;
        if (price is! num) continue;

        // Guard: keep cart consistent with a single vendor.
        if (pVendorId != vendorId) continue;

        final product = Product(
          id: id,
          vendorId: pVendorId,
          name: name,
          description: desc,
          price: price.toDouble(),
          isAvailable: isAvail == true,
        );
        lines.add(CartLine(product: product, qty: qty));
      }

      if (lines.isEmpty) return null;

      return CartState(
        vendorId: vendorId,
        vendorName: vendorName.isNotEmpty ? vendorName : 'Store #$vendorId',
        lines: lines,
      );
    } catch (_) {
      // Corrupt JSON / decoding errors: clear and ignore.
      await _s.delete(key: _key);
      return null;
    }
  }
}
