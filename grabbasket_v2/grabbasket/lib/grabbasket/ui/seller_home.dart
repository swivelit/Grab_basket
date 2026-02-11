import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../models.dart';
import '../bootstrap.dart';
import 'login.dart';

class SellerHome extends ConsumerStatefulWidget {
  const SellerHome({super.key});

  @override
  ConsumerState<SellerHome> createState() => _SellerHomeState();
}

class _SellerHomeState extends ConsumerState<SellerHome> {
  final _vendorName = TextEditingController(text: "My Store");

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Seller"),
        actions: [
          IconButton(
            icon: const Icon(Icons.login),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.seller))),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _vendorName, decoration: const InputDecoration(labelText: "Vendor name")),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: () async {
                await api.sellerCreateVendor(name: _vendorName.text.trim());
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Vendor ready")));
                setState(() {});
              },
              child: const Text("Create/Attach vendor"),
            ),
            const SizedBox(height: 16),
            const Align(alignment: Alignment.centerLeft, child: Text("Incoming orders", style: TextStyle(fontWeight: FontWeight.bold))),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<Order>>(
                future: api.sellerOrders(),
                builder: (context, snap) {
                  if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                  final orders = snap.data!;
                  if (orders.isEmpty) return const Center(child: Text("No orders"));
                  return ListView.separated(
                    itemCount: orders.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final o = orders[i];
                      return ListTile(
                        title: Text("Order #${o.id} • ${o.status}"),
                        subtitle: Text("Total Rs ${o.totalAmount.toStringAsFixed(2)}"),
                        trailing: (o.status == "CREATED")
                          ? FilledButton(
                              onPressed: () async {
                                await api.sellerAcceptOrder(o.id);
                                setState(() {});
                              },
                              child: const Text("Accept"),
                            )
                          : null,
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
