import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../bootstrap.dart';
import '../location.dart';
import '../models.dart';
import '../state.dart';
import 'login.dart';
import 'order_detail.dart';

class PartnerHome extends ConsumerStatefulWidget {
  const PartnerHome({super.key});

  @override
  ConsumerState<PartnerHome> createState() => _PartnerHomeState();
}

class _PartnerHomeState extends ConsumerState<PartnerHome> {
  bool _available = true;
  bool _availabilityBusy = false;
  String? _locErr;
  DateTime? _lastSync;

  StreamSubscription? _sub;
  DateTime? _lastSent;

  Future<List<Order>>? _ordersFuture;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _ordersFuture = ref.read(apiProvider).partnerOrders();
    _syncLocationStream();
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  Future<void> _logout() async {
    await ref.read(secureStoreProvider).clear();
    ref.read(cartProvider.notifier).clear();
    ref.invalidate(sessionProvider);

    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.partner)),
      (_) => false,
    );
  }

  Future<void> _reload() async {
    setState(() => _ordersFuture = ref.read(apiProvider).partnerOrders());
    await _ordersFuture;
  }

  void _syncLocationStream() {
    if (_available) {
      _startLocationStream();
    } else {
      _sub?.cancel();
      _sub = null;
    }
  }

  Future<void> _startLocationStream() async {
    try {
      await _sub?.cancel();
      _sub = LocationService.stream(seconds: 10).listen((pos) async {
        if (!_available) return;

        final now = DateTime.now();
        // Throttle uploads.
        if (_lastSent != null && now.difference(_lastSent!).inSeconds < 8) return;
        _lastSent = now;

        try {
          await ref.read(apiProvider).partnerSendLocation(
                pos.latitude,
                pos.longitude,
                heading: pos.heading,
                speed: pos.speed,
              );
          if (mounted) {
            setState(() {
              _locErr = null;
              _lastSync = DateTime.now();
            });
          }
        } catch (e) {
          if (mounted) setState(() => _locErr = e.toString());
        }
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _locErr = e.toString());
    }
  }

  Future<void> _toggleAvailability(bool v) async {
    final api = ref.read(apiProvider);
    final prev = _available;

    setState(() {
      _available = v;
      _availabilityBusy = true;
    });

    try {
      final resp = await api.partnerAvailability(v);
      final actual = resp["is_available"] == true;

      if (!mounted) return;
      setState(() {
        _available = actual;
        _availabilityBusy = false;
      });
      _syncLocationStream();

      final reason = resp["reason"];
      if (reason is String && reason.trim().isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(reason)));
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _available = prev;
        _availabilityBusy = false;
      });
      _syncLocationStream();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _runOrderAction(Future<void> Function() action) async {
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

  Future<bool> _confirm(String title, String message) async {
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
    return ok == true;
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);
    final future = _ordersFuture ?? api.partnerOrders();

    final syncText = _lastSync == null
        ? "Not synced yet"
        : "Last synced: ${_lastSync!.toLocal().toString().split('.').first}";

    return Scaffold(
      appBar: AppBar(
        title: const Text("Partner"),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: "Refresh",
            onPressed: (_busy || _availabilityBusy) ? null : _reload,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: "Logout",
            onPressed: (_busy || _availabilityBusy) ? null : _logout,
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
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text("Available for deliveries"),
                    subtitle: Text(syncText),
                    value: _available,
                    onChanged: _availabilityBusy ? null : _toggleAvailability,
                  ),
                  if (_locErr != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Text("Location sync: $_locErr", style: const TextStyle(color: Colors.red)),
                    ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Expanded(
                        child: Text("Assigned orders", style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                      IconButton(
                        icon: const Icon(Icons.refresh),
                        tooltip: "Refresh",
                        onPressed: (_busy || _availabilityBusy) ? null : _reload,
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
                                    onPressed: (_busy || _availabilityBusy) ? null : _reload,
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
                                Icon(Icons.delivery_dining, size: 48),
                                SizedBox(height: 12),
                                Center(child: Text("No assigned orders")),
                              ],
                            );
                          }

                          return ListView.separated(
                            physics: const AlwaysScrollableScrollPhysics(),
                            itemCount: orders.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, i) {
                              final o = orders[i];

                              final canPickup = o.status == "ASSIGNED_TO_PARTNER" || o.status == "READY_FOR_PICKUP";
                              final canDeliver = o.status == "PICKED_UP";

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
                                    Text("Status: ${o.status}"),
                                    const SizedBox(height: 4),
                                    Text("Total ₹${o.totalAmount.toStringAsFixed(2)}"),
                                  ],
                                ),
                                trailing: Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    if (canPickup)
                                      FilledButton(
                                        onPressed: _busy
                                            ? null
                                            : () => _runOrderAction(() async {
                                                  final ok = await _confirm(
                                                    "Picked up?",
                                                    "Confirm you have picked up order #${o.id}.",
                                                  );
                                                  if (!ok) return;
                                                  await api.partnerPickup(o.id);
                                                }),
                                        child: const Text("Picked up"),
                                      ),
                                    if (canDeliver)
                                      FilledButton(
                                        onPressed: _busy
                                            ? null
                                            : () => _runOrderAction(() async {
                                                  final ok = await _confirm(
                                                    "Delivered?",
                                                    "Confirm you delivered order #${o.id}.",
                                                  );
                                                  if (!ok) return;
                                                  await api.partnerDeliver(o.id);
                                                }),
                                        child: const Text("Delivered"),
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
