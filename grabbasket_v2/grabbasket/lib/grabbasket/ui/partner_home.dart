import 'dart:async';

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
  String? _locErr;

  StreamSubscription? _sub;
  DateTime? _lastSent;

  @override
  void initState() {
    super.initState();
    _startLocationStream();
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  Future<void> _startLocationStream() async {
    try {
      _sub?.cancel();
      _sub = LocationService.stream(seconds: 10).listen((pos) async {
        if (!_available) return;

        final now = DateTime.now();
        if (_lastSent != null && now.difference(_lastSent!).inSeconds < 8) return;
        _lastSent = now;

        try {
          await ref.read(apiProvider).partnerSendLocation(
                pos.latitude,
                pos.longitude,
                heading: pos.heading,
                speed: pos.speed,
              );
          if (mounted) setState(() => _locErr = null);
        } catch (e) {
          if (mounted) setState(() => _locErr = e.toString());
        }
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _locErr = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Partner"),
        actions: [
          IconButton(
            icon: const Icon(Icons.login),
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.partner)),
            ),
          ),
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => setState(() {})),
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
            if (_locErr != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text("Location sync: $_locErr", style: const TextStyle(color: Colors.red)),
              ),
            const Align(
              alignment: Alignment.centerLeft,
              child: Text("Assigned orders", style: TextStyle(fontWeight: FontWeight.bold)),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<Order>>(
                future: api.partnerOrders(),
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return Center(child: Text("Failed: ${snap.error}"));
                  }
                  final orders = snap.data ?? [];
                  if (orders.isEmpty) return const Center(child: Text("No assigned orders"));

                  return ListView.separated(
                    itemCount: orders.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final o = orders[i];
                      return ListTile(
                        title: Text("Order #${o.id} • ${o.status}"),
                        subtitle: Text("Total ₹${o.totalAmount.toStringAsFixed(2)}"),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (o.status == "ASSIGNED_TO_PARTNER" || o.status == "READY_FOR_PICKUP")
                              FilledButton(
                                onPressed: () async {
                                  await api.partnerPickup(o.id);
                                  if (!mounted) return;
                                  setState(() {});
                                },
                                child: const Text("Picked up"),
                              ),
                            if (o.status == "PICKED_UP")
                              FilledButton(
                                onPressed: () async {
                                  await api.partnerDeliver(o.id);
                                  if (!mounted) return;
                                  setState(() {});
                                },
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
