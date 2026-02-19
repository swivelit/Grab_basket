import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../config.dart';
import '../models.dart';
import '../order_status.dart';
import '../state.dart';

class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  Future<List<Order>>? _future;

  String get _currency => AppConfig.defaultCurrency;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiProvider).myOrders();
  }

  String _money(double amount) {
    if (_currency.toUpperCase() == 'INR') {
      return '₹${amount.toStringAsFixed(2)}';
    }
    return '${amount.toStringAsFixed(2)} $_currency';
  }

  Future<void> _reload() async {
    setState(() => _future = ref.read(apiProvider).myOrders());
    await _future;
  }

  Widget _statusPill(String code) {
    final c = OrderStatus.color(code);
    final label = OrderStatus.label(code);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: c.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: c.withOpacity(0.35)),
      ),
      child: Text(label, style: TextStyle(color: c, fontWeight: FontWeight.w700, fontSize: 12)),
    );
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
                padding: const EdgeInsets.all(16),
                children: [
                  const SizedBox(height: 32),
                  const Icon(Icons.wifi_off, size: 44),
                  const SizedBox(height: 12),
                  Text("Failed to load orders.\n${snap.error}", textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  Center(
                    child: FilledButton.icon(
                      onPressed: _reload,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Retry'),
                    ),
                  ),
                ],
              );
            }

            final orders = snap.data ?? const <Order>[];
            if (orders.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.only(top: 56),
                children: const [
                  Icon(Icons.receipt_long, size: 48),
                  SizedBox(height: 12),
                  Center(child: Text("No orders yet")),
                ],
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: orders.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final o = orders[i];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  title: Text(
                    "Order #${o.id}",
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  subtitle: Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text("Total ${_money(o.totalAmount)} • Items: ${o.items.length}"),
                  ),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      _statusPill(o.status),
                      const SizedBox(height: 8),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                  onTap: () async {
                    await context.push('/order/${o.id}');
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
