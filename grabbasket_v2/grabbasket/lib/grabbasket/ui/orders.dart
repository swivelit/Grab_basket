import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../models.dart';
import 'order_detail.dart';

class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  Future<List<Order>>? _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiProvider).myOrders();
  }

  Future<void> _reload() async {
    setState(() => _future = ref.read(apiProvider).myOrders());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);
    return Scaffold(
      appBar: AppBar(title: const Text("My orders")),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<List<Order>>(
          future: _future ?? api.myOrders(),
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [Padding(padding: const EdgeInsets.all(16), child: Text("Failed: ${snap.error}"))],
              );
            }
            final orders = snap.data ?? [];
            if (orders.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [Padding(padding: EdgeInsets.all(16), child: Text("No orders yet"))],
              );
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: orders.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final o = orders[i];
                return ListTile(
                  title: Text("Order #${o.id} • ${o.status}"),
                  subtitle: Text("Total ₹${o.totalAmount.toStringAsFixed(2)} • Items: ${o.items.length}"),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () async {
                    await Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: o.id)),
                    );
                    await _reload();
                  },
                );
              },
            );
          },
        ),
      ),
    );
  }
}
