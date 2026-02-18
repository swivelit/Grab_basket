import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../config.dart';
import '../state.dart';

class CartScreen extends ConsumerWidget {
  const CartScreen({super.key});

  String get _currency => AppConfig.defaultCurrency;

  String _money(double amount) {
    if (_currency.toUpperCase() == 'INR') {
      return '₹${amount.toStringAsFixed(2)}';
    }
    return '${amount.toStringAsFixed(2)} $_currency';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cart = ref.watch(cartProvider);

    if (cart == null || cart.count == 0) {
      return Scaffold(
        appBar: AppBar(title: const Text('Cart')),
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.shopping_basket_outlined, size: 52),
                  const SizedBox(height: 12),
                  const Text('Your cart is empty', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
                  const SizedBox(height: 6),
                  const Text('Add items from a store to get started.'),
                  const SizedBox(height: 14),
                  FilledButton.icon(
                    onPressed: () => context.pop(),
                    icon: const Icon(Icons.storefront),
                    label: const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Text('Browse stores'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cart'),
        actions: [
          TextButton(
            onPressed: () => ref.read(cartProvider.notifier).clear(),
            child: const Text('Clear'),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: cart.lines.length,
                separatorBuilder: (_, __) => const Divider(height: 24),
                itemBuilder: (context, i) {
                  final line = cart.lines[i];
                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(line.product.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                            const SizedBox(height: 4),
                            Text(_money(line.product.price)),
                            const SizedBox(height: 8),
                            Text('Total: ${_money(line.lineTotal)}'),
                          ],
                        ),
                      ),
                      _QtyStepper(
                        qty: line.qty,
                        onAdd: () => ref.read(cartProvider.notifier).add(line.product),
                        onRemove: () => ref.read(cartProvider.notifier).remove(line.product),
                      ),
                    ],
                  );
                },
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      children: [
                        const Expanded(
                          child: Text('Subtotal', style: TextStyle(fontWeight: FontWeight.w700)),
                        ),
                        Text(_money(cart.subtotal), style: const TextStyle(fontWeight: FontWeight.w800)),
                      ],
                    ),
                    const SizedBox(height: 10),
                    FilledButton(
                      onPressed: () => context.push('/checkout'),
                      child: const Padding(
                        padding: EdgeInsets.symmetric(vertical: 12),
                        child: Text('Proceed to checkout'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QtyStepper extends StatelessWidget {
  final int qty;
  final VoidCallback onAdd;
  final VoidCallback onRemove;

  const _QtyStepper({required this.qty, required this.onAdd, required this.onRemove});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: const Icon(Icons.remove),
            visualDensity: VisualDensity.compact,
            onPressed: qty <= 0 ? null : onRemove,
          ),
          Text(qty.toString(), style: const TextStyle(fontWeight: FontWeight.w800)),
          IconButton(
            icon: const Icon(Icons.add),
            visualDensity: VisualDensity.compact,
            onPressed: onAdd,
          ),
        ],
      ),
    );
  }
}
