import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../models.dart';
import '../bootstrap.dart';
import 'login.dart';
import '../location.dart';

class PartnerHome extends ConsumerStatefulWidget {
  const PartnerHome({super.key});

  @override
  ConsumerState<PartnerHome> createState() => _PartnerHomeState();
}

class _PartnerHomeState extends ConsumerState<PartnerHome> {
  bool _available = true;

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Partner"),
        actions: [
          IconButton(
            icon: const Icon(Icons.login),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.partner))),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            SwitchListTile(
              title: const Text("Available for deliveries"),
              value: _available,
              onChanged: (v) async {
                setState(() => _available = v);
                await api.partnerAvailability(v);
              },
            ),
            const Align(alignment: Alignment.centerLeft, child: Text("Assigned orders", style: TextStyle(fontWeight: FontWeight.bold))),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<Order>>(
                future: api.partnerOrders(),
                builder: (context, snap) {
                  if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                  final orders = snap.data!;
                  if (orders.isEmpty) return const Center(child: Text("No assigned orders"));
                  return ListView.separated(
                    itemCount: orders.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final o = orders[i];
                      return ListTile(
                        title: Text("Order #${o.id} • ${o.status}"),
                        subtitle: Text("Total Rs ${o.totalAmount.toStringAsFixed(2)}"),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (o.status == "ASSIGNED_TO_PARTNER")
                              FilledButton(
                                onPressed: () async { await api.partnerPickup(o.id); setState(() {}); },
                                child: const Text("Picked up"),
                              ),
                            if (o.status == "PICKED_UP")
                              FilledButton(
                                onPressed: () async { await api.partnerDeliver(o.id); setState(() {}); },
                                child: const Text("Delivered"),
                              ),
                          ],
                        ),
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
