import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../bootstrap.dart';
import '../models.dart';
import '../order_status.dart';
import '../state.dart';
import 'login.dart';
import 'order_detail.dart';

class SellerHome extends ConsumerStatefulWidget {
  const SellerHome({super.key});

  @override
  ConsumerState<SellerHome> createState() => _SellerHomeState();
}

class _SellerHomeState extends ConsumerState<SellerHome> {
  final _vendorName = TextEditingController(text: "My Store");
  Future<List<Order>>? _ordersFuture;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _ordersFuture = ref.read(apiProvider).sellerOrders();
  }

  @override
  void dispose() {
    _vendorName.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() => _ordersFuture = ref.read(apiProvider).sellerOrders());
    await _ordersFuture;
  }

  Future<void> _createVendor() async {
    final api = ref.read(apiProvider);
    final name = _vendorName.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Enter a vendor name")));
      return;
    }

    setState(() => _busy = true);
    try {
      await api.sellerCreateVendor(name: name);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Vendor ready")));
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _logout() async {
    await ref.read(secureStoreProvider).clear();
    ref.read(cartProvider.notifier).clear();
    ref.invalidate(sessionProvider);

    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.seller)),
      (_) => false,
    );
  }

  Future<void> _confirmAndRun({
    required String title,
    required String message,
    required Future<void> Function() action,
  }) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text("Cancel")),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text("Confirm")),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => _busy = true);
    try {
      await action();
      if (!mounted) return;
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);
    final future = _ordersFuture ?? api.sellerOrders();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Seller"),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: "Logout",
            onPressed: _busy ? null : _logout,
          ),
        ],
      ),
      body: SafeArea(
        child: Stack(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    "Store setup",
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _vendorName,
                    enabled: !_busy,
                    decoration: const InputDecoration(
                      labelText: "Vendor name",
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 8),
                  FilledButton.icon(
                    onPressed: _busy ? null : _createVendor,
                    icon: const Icon(Icons.storefront),
                    label: const Text("Create/Attach vendor"),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      const Expanded(
                        child: Text("Incoming orders", style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                      IconButton(
                        icon: const Icon(Icons.refresh),
                        tooltip: "Refresh",
                        onPressed: _busy ? null : _reload,
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Expanded(
                    child: RefreshIndicator(
                      onRefresh: _reload,
                      child: FutureBuilder<List<Order>>(
                        future: future,
                        builder: (context, snap) {
                          if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
                            return const Center(child: CircularProgressIndicator());
                          }

                          if (snap.hasError) {
                            return ListView(
                              physics: const AlwaysScrollableScrollPhysics(),
                              padding: const EdgeInsets.only(top: 32),
                              children: [
                                const Icon(Icons.wifi_off, size: 40),
                                const SizedBox(height: 12),
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 16),
                                  child: Text(
                                    "Could not load orders.\n${snap.error}",
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Center(
                                  child: FilledButton.icon(
                                    onPressed: _busy ? null : _reload,
                                    icon: const Icon(Icons.refresh),
                                    label: const Text("Retry"),
                                  ),
                                ),
                              ],
                            );
                          }

                          final orders = snap.data ?? const <Order>[];
                          if (orders.isEmpty) {
                            return ListView(
                              physics: const AlwaysScrollableScrollPhysics(),
                              padding: const EdgeInsets.only(top: 48),
                              children: const [
                                Icon(Icons.receipt_long, size: 48),
                                SizedBox(height: 12),
                                Center(child: Text("No incoming orders yet")),
                              ],
                            );
                          }

                          return ListView.separated(
                            physics: const AlwaysScrollableScrollPhysics(),
                            itemCount: orders.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, i) {
                              final o = orders[i];

                              final canAcceptReject = o.status == "CREATED";
                              final canReady = o.status == "ACCEPTED_BY_SELLER" || o.status == "ASSIGNED_TO_PARTNER";

                              return ListTile(
                                contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                                title: Text(
                                  "Order #${o.id}",
                                  style: const TextStyle(fontWeight: FontWeight.w700),
                                ),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const SizedBox(height: 4),
                                    Text("Status: ${OrderStatus.label(o.status)}"),
                                    const SizedBox(height: 4),
                                    Text("Total ₹${o.totalAmount.toStringAsFixed(2)} • Items: ${o.items.length}"),
                                  ],
                                ),
                                trailing: Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    if (canAcceptReject)
                                      FilledButton(
                                        onPressed: _busy
                                            ? null
                                            : () => _confirmAndRun(
                                                  title: "Accept order #${o.id}?",
                                                  message: "Confirm to accept and start preparing.",
                                                  action: () async {
                                                    await api.sellerAcceptOrder(o.id);
                                                  },
                                                ),
                                        child: const Text("Accept"),
                                      ),
                                    if (canAcceptReject)
                                      OutlinedButton(
                                        onPressed: _busy
                                            ? null
                                            : () => _confirmAndRun(
                                                  title: "Reject order #${o.id}?",
                                                  message: "Customer will be notified and refunded if applicable.",
                                                  action: () async {
                                                    await api.sellerRejectOrder(o.id, reason: "Rejected by seller");
                                                  },
                                                ),
                                        child: const Text("Reject"),
                                      ),
                                    if (canReady)
                                      FilledButton(
                                        onPressed: _busy
                                            ? null
                                            : () => _confirmAndRun(
                                                  title: "Mark ready?",
                                                  message: "Partner can pick up once marked ready.",
                                                  action: () async {
                                                    await api.sellerMarkReady(o.id);
                                                  },
                                                ),
                                        child: const Text("Ready"),
                                      ),
                                  ],
                                ),
                                onTap: () {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => OrderDetailScreen(orderId: o.id, allowCancel: false),
                                    ),
                                  );
                                },
                              );
                            },
                          );
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),
            if (_busy)
              const Positioned.fill(
                child: IgnorePointer(
                  child: ColoredBox(
                    color: Color(0x22000000),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
