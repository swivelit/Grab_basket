import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../models.dart';

class OrderDetailScreen extends ConsumerStatefulWidget {
  final int orderId;
  const OrderDetailScreen({super.key, required this.orderId});

  @override
  ConsumerState<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends ConsumerState<OrderDetailScreen> {
  Order? _order;
  Map<String, dynamic>? _partnerLatest;
  String? _err;
  bool _loading = true;

  Timer? _timer;

  bool get _shouldTrack {
    final o = _order;
    if (o == null) return false;
    if (o.partnerId == null) return false;
    return {
      "ASSIGNED_TO_PARTNER",
      "READY_FOR_PICKUP",
      "PICKED_UP",
    }.contains(o.status);
  }

  @override
  void initState() {
    super.initState();
    _load(initial: true);
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _load({bool initial = false}) async {
    final api = ref.read(apiProvider);
    setState(() {
      if (initial) _loading = true;
      _err = null;
    });

    try {
      final o = await api.getOrder(widget.orderId);
      Map<String, dynamic>? loc;
      if (o.partnerId != null) {
        // safe even if not picked up yet
        loc = await api.partnerLatestForOrder(widget.orderId);
      }

      if (!mounted) return;
      setState(() {
        _order = o;
        _partnerLatest = loc;
        _loading = false;
      });

      _timer?.cancel();
      if (_shouldTrack) {
        _timer = Timer.periodic(const Duration(seconds: 6), (_) => _refreshTracking());
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _err = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _refreshTracking() async {
    final api = ref.read(apiProvider);
    try {
      final o = await api.getOrder(widget.orderId);
      Map<String, dynamic>? loc;
      if (o.partnerId != null) {
        loc = await api.partnerLatestForOrder(widget.orderId);
      }
      if (!mounted) return;
      setState(() {
        _order = o;
        _partnerLatest = loc;
      });

      if (!_shouldTrack) {
        _timer?.cancel();
        _timer = null;
      }
    } catch (_) {
      // ignore transient tracking failures
    }
  }

  Future<void> _cancelOrder() async {
    final api = ref.read(apiProvider);
    final o = _order;
    if (o == null) return;

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Cancel order?"),
        content: const Text("If the restaurant has started preparing, cancellation may not be allowed."),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text("No")),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text("Yes, cancel")),
        ],
      ),
    );

    if (ok != true) return;

    try {
      final updated = await api.cancelOrder(o.id);
      if (!mounted) return;
      setState(() => _order = updated);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Order cancelled")));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed: $e")));
    }
  }

  Widget _statusChip(String text, {Color? color}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: (color ?? Colors.blueGrey).withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final o = _order;

    return Scaffold(
      appBar: AppBar(
        title: Text("Order #${widget.orderId}"),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => _load()),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _err != null
              ? Center(child: Text("Failed: $_err"))
              : o == null
                  ? const Center(child: Text("Order not found"))
                  : RefreshIndicator(
                      onRefresh: () => _load(),
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        children: [
                          Row(
                            children: [
                              _statusChip(o.status),
                              const Spacer(),
                              if (o.canCancel)
                                TextButton.icon(
                                  onPressed: _cancelOrder,
                                  icon: const Icon(Icons.close),
                                  label: const Text("Cancel"),
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            "Total ₹${o.totalAmount.toStringAsFixed(2)}",
                            style: Theme.of(context).textTheme.headlineSmall,
                          ),
                          const SizedBox(height: 4),
                          Text("Payment: ${o.paymentMethod} • ${o.paymentStatus}${o.paymentRef != null ? " • ${o.paymentRef}" : ""}"),
                          const SizedBox(height: 16),

                          // Items
                          Text("Items", style: Theme.of(context).textTheme.titleMedium),
                          const SizedBox(height: 8),
                          ...o.items.map(
                            (it) => ListTile(
                              dense: true,
                              contentPadding: EdgeInsets.zero,
                              title: Text(it.name),
                              subtitle: Text("₹${it.price.toStringAsFixed(2)} × ${it.qty}"),
                              trailing: Text("₹${(it.price * it.qty).toStringAsFixed(2)}"),
                            ),
                          ),
                          const Divider(height: 24),

                          // Tracking
                          if (_partnerLatest != null) ...[
                            Text("Tracking", style: Theme.of(context).textTheme.titleMedium),
                            const SizedBox(height: 8),
                            Builder(builder: (context) {
                              // Backend can return either a flat map {lat,lng,ts}
                              // or a nested map {partner_latest_location:{...}}.
                              Map<String, dynamic>? loc;
                              if (_partnerLatest == null) loc = null;
                              else if (_partnerLatest!.containsKey("lat")) {
                                loc = _partnerLatest;
                              } else if (_partnerLatest!["partner_latest_location"] is Map) {
                                loc = (_partnerLatest!["partner_latest_location"] as Map).cast<String, dynamic>();
                              } else if (_partnerLatest!["partner_latest"] is Map) {
                                loc = (_partnerLatest!["partner_latest"] as Map).cast<String, dynamic>();
                              }

                              if (loc == null) {
                                return const Text("Partner location not available yet.");
                              }

                              final lat = loc["lat"];
                              final lng = loc["lng"];
                              final ts = (loc["created_at"] ?? loc["ts"])?.toString() ?? "";
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text("Partner last location: $lat, $lng"),
                                  if (ts.isNotEmpty) Text("Updated: $ts"),
                                ],
                              );
                            }),
                            const Divider(height: 24),
                          ],

                          // Timeline
                          Text("Status timeline", style: Theme.of(context).textTheme.titleMedium),
                          const SizedBox(height: 8),
                          if (o.events.isEmpty)
                            const Text("No events yet.")
                          else
                            ...o.events.map(
                              (e) => ListTile(
                                dense: true,
                                contentPadding: EdgeInsets.zero,
                                leading: const Icon(Icons.check_circle_outline),
                                title: Text(e.status),
                                subtitle: Text("${e.createdAt.toLocal()}${e.note.isNotEmpty ? " • ${e.note}" : ""}"),
                              ),
                            ),
                        ],
                      ),
                    ),
    );
  }
}
