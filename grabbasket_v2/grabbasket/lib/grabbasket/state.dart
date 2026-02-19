import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api.dart';
import 'cart_storage.dart';
import 'models.dart';
import 'storage.dart';

final secureStoreProvider = Provider<SessionStore>((ref) => SecureStore());

final sessionProvider = FutureProvider<({String? token, String? role})>((ref) async {
  final store = ref.read(secureStoreProvider);
  return (token: await store.token, role: await store.role);
});

final apiProvider = Provider<Api>((ref) {
  final session = ref.watch(sessionProvider).valueOrNull;
  return Api(token: session?.token);
});

/// Cart persistence.
///
/// This is separate from [SessionStore] so we can keep tokens/roles isolated.
final cartStorageProvider = Provider<CartStorage>((ref) => const CartStorage());

/// Customer addresses (raw backend shape).
///
/// The backend currently returns a list of maps. We keep it as-is to avoid
/// breaking changes, and derive helpers like the default address in the UI.
final addressesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final api = ref.read(apiProvider);
  return api.addresses();
});

/// The user's default address, if any.
///
/// If no address is marked default, we return the first address (if present).
final defaultAddressProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
  final rows = await ref.watch(addressesProvider.future);
  if (rows.isEmpty) return null;
  final idx = rows.indexWhere((a) => a['is_default'] == true);
  return idx == -1 ? rows.first : rows[idx];
});

class CartNotifier extends Notifier<CartState?> {
  bool _restoring = false;
  bool _restoredOnce = false;

  @override
  CartState? build() => null;

  /// Restore persisted cart (call once after login / app gate).
  Future<void> restore() async {
    if (_restoredOnce) return;
    _restoredOnce = true;
    _restoring = true;
    try {
      final stored = await ref.read(cartStorageProvider).load();
      state = stored;
    } finally {
      _restoring = false;
    }
  }

  void _persistSoon() {
    if (_restoring) return;
    // Fire-and-forget. If secure storage isn't available on some platform,
    // CartStorage already fails safely.
    unawaited(ref.read(cartStorageProvider).save(state));
  }

  void add(Product p, {String? vendorName}) {
    final current = state;
    if (current == null || current.vendorId != p.vendorId) {
      final name = (vendorName ?? '').trim();
      state = CartState(
        vendorId: p.vendorId,
        vendorName: name.isNotEmpty ? name : 'Store #${p.vendorId}',
        lines: [CartLine(product: p, qty: 1)],
      );
      _persistSoon();
      return;
    }
    final idx = current.lines.indexWhere((l) => l.product.id == p.id);
    if (idx == -1) {
      state = CartState(
        vendorId: current.vendorId,
        vendorName: current.vendorName,
        lines: [...current.lines, CartLine(product: p, qty: 1)],
      );
    } else {
      final updated = [...current.lines];
      final old = updated[idx];
      updated[idx] = CartLine(product: old.product, qty: old.qty + 1);
      state = CartState(vendorId: current.vendorId, vendorName: current.vendorName, lines: updated);
    }

    _persistSoon();
  }

  void remove(Product p) {
    final current = state;
    if (current == null) return;
    final idx = current.lines.indexWhere((l) => l.product.id == p.id);
    if (idx == -1) return;
    final updated = [...current.lines];
    final old = updated[idx];
    if (old.qty <= 1) {
      updated.removeAt(idx);
    } else {
      updated[idx] = CartLine(product: old.product, qty: old.qty - 1);
    }
    state = updated.isEmpty ? null : CartState(vendorId: current.vendorId, vendorName: current.vendorName, lines: updated);
    _persistSoon();
  }

  void clear() {
    state = null;
    _persistSoon();
  }
}

final cartProvider = NotifierProvider<CartNotifier, CartState?>(() => CartNotifier());
