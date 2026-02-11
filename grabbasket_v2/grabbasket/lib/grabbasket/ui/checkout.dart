import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  String _paymentMethod = "COD"; // COD / UPI
  int? _selectedAddressId;

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final api = ref.watch(apiProvider);

    if (cart == null) {
      return const Scaffold(body: Center(child: Text("Cart is empty")));
    }

    return Scaffold(
      appBar: AppBar(title: const Text("Checkout")),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: api.addresses(),
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: CircularProgressIndicator());
          final addresses = snap.data!;

          if (addresses.isEmpty) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text("Add a delivery address first."),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => Navigator.of(context).pop(),
                    child: const Text("Go back"),
                  )
                ],
              ),
            );
          }

          // If not selected yet, choose default (or first)
          if (_selectedAddressId == null) {
            final def = addresses.cast<Map<String, dynamic>>().firstWhere(
                  (a) => a["is_default"] == true,
                  orElse: () => addresses.first,
                );
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (!mounted) return;
              setState(() => _selectedAddressId = def["id"] as int);
            });
          }

          final selectedId = _selectedAddressId ?? (addresses.first["id"] as int);

          return Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: ListView(
                    children: [
                      const Text("Items", style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      ...cart.lines.map(
                        (line) => ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(line.product.name),
                          subtitle: Text("Rs ${line.product.price.toStringAsFixed(2)} x ${line.qty}"),
                          trailing: Text("Rs ${line.lineTotal.toStringAsFixed(2)}"),
                        ),
                      ),
                      const Divider(height: 24),
                      const Text("Delivery address", style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      ...addresses.map(
                        (a) {
                          final id = a["id"] as int;
                          final label = (a["label"] ?? "Address").toString();
                          final line1 = (a["line1"] ?? "").toString();
                          final isDefault = a["is_default"] == true;
                          return RadioListTile<int>(
                            value: id,
                            groupValue: selectedId,
                            onChanged: (v) => setState(() => _selectedAddressId = v),
                            title: Text(isDefault ? "$label • Default" : label),
                            subtitle: Text(line1),
                          );
                        },
                      ),
                      const Divider(height: 24),
                      const Text("Payment method", style: TextStyle(fontWeight: FontWeight.bold)),
                      RadioListTile<String>(
                        value: "COD",
                        groupValue: _paymentMethod,
                        onChanged: (v) => setState(() => _paymentMethod = v ?? "COD"),
                        title: const Text("Cash on Delivery"),
                      ),
                      RadioListTile<String>(
                        value: "UPI",
                        groupValue: _paymentMethod,
                        onChanged: (v) => setState(() => _paymentMethod = v ?? "UPI"),
                        title: const Text("UPI"),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                Text("Subtotal: Rs ${cart.subtotal.toStringAsFixed(2)}"),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: () async {
                    final items = cart.lines
                        .map((l) => {"product_id": l.product.id, "qty": l.qty})
                        .toList();

                    try {
                      final order = await api.createOrder(
                        vendorId: cart.vendorId,
                        items: items,
                        deliveryAddressId: selectedId,
                        paymentMethod: _paymentMethod,
                      );
                      ref.read(cartProvider.notifier).clear();
                      if (!mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text("Order #${order.id} placed: ${order.status}")),
                      );
                      Navigator.of(context).pop();
                    } catch (e) {
                      if (!mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text("Failed to place order: $e")),
                      );
                    }
                  },
                  child: const Text("Place order"),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
