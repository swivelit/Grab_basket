import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api.dart';
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

class CartState {
  final int vendorId;
  final List<CartLine> lines;
  const CartState({required this.vendorId, required this.lines});

  double get subtotal => lines.fold(0, (s, l) => s + l.lineTotal);
  int get count => lines.fold(0, (s, l) => s + l.qty);
}

class CartNotifier extends Notifier<CartState?> {
  @override
  CartState? build() => null;

  void add(Product p) {
    final current = state;
    if (current == null || current.vendorId != p.vendorId) {
      state = CartState(vendorId: p.vendorId, lines: [CartLine(product: p, qty: 1)]);
      return;
    }
    final idx = current.lines.indexWhere((l) => l.product.id == p.id);
    if (idx == -1) {
      state = CartState(vendorId: current.vendorId, lines: [...current.lines, CartLine(product: p, qty: 1)]);
    } else {
      final updated = [...current.lines];
      final old = updated[idx];
      updated[idx] = CartLine(product: old.product, qty: old.qty + 1);
      state = CartState(vendorId: current.vendorId, lines: updated);
    }
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
    state = updated.isEmpty ? null : CartState(vendorId: current.vendorId, lines: updated);
  }

  void clear() => state = null;
}

final cartProvider = NotifierProvider<CartNotifier, CartState?>(() => CartNotifier());
