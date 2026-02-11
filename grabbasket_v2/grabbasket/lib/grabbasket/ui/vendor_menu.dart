import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models.dart';
import '../state.dart';
import 'checkout.dart';

class VendorMenuScreen extends ConsumerWidget {
  final Vendor vendor;
  const VendorMenuScreen({super.key, required this.vendor});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final api = ref.watch(apiProvider);
    final cart = ref.watch(cartProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(vendor.name),
        actions: [
          if (cart != null)
            TextButton.icon(
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const CheckoutScreen())),
              icon: const Icon(Icons.shopping_cart),
              label: Text("${cart.count}"),
            )
        ],
      ),
      body: FutureBuilder<List<Product>>(
        future: api.products(vendor.id),
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: CircularProgressIndicator());
          final products = snap.data!;
          return ListView.separated(
            itemCount: products.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final p = products[i];
              return ListTile(
                title: Text(p.name),
                subtitle: Text("Rs ${p.price.toStringAsFixed(2)} • ${p.description}"),
                trailing: IconButton(
                  icon: const Icon(Icons.add_circle_outline),
                  onPressed: () => ref.read(cartProvider.notifier).add(p),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
