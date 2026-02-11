import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';

class CheckoutScreen extends ConsumerWidget {
  const CheckoutScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cart = ref.watch(cartProvider);
    final api = ref.watch(apiProvider);

    if (cart == null) {
      return const Scaffold(body: Center(child: Text("Cart is empty")));
    }

    return Scaffold(
      appBar: AppBar(title: const Text("Checkout")),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: ListView(
                children: [
                  for (final line in cart.lines)
                    ListTile(
                      title: Text(line.product.name),
                      subtitle: Text("Rs ${line.product.price.toStringAsFixed(2)} x ${line.qty}"),
                      trailing: Text("Rs ${line.lineTotal.toStringAsFixed(2)}"),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            Text("Subtotal: Rs ${cart.subtotal.toStringAsFixed(2)}"),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () async {
                final items = cart.lines.map((l) => {"product_id": l.product.id, "qty": l.qty}).toList();
                final order = await api.createOrder(cart.vendorId, items);
                ref.read(cartProvider.notifier).clear();
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Order #${order.id} placed: ${order.status}")));
                Navigator.of(context).pop();
              },
              child: const Text("Place order"),
            ),
          ],
        ),
      ),
    );
  }
}
