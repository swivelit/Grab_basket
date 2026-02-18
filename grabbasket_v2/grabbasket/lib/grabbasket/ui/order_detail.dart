import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../app_globals.dart';
import '../config.dart';
import '../models.dart';
import '../state.dart';

class OrderDetailScreen extends ConsumerStatefulWidget {
  final int orderId;
  final bool allowCancel;
  const OrderDetailScreen({
    super.key,
    required this.orderId,
    this.allowCancel = true,
  });

  @override
  ConsumerState<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends ConsumerState<OrderDetailScreen> with WidgetsBindingObserver {
  Order? _order;
  Map<String, dynamic>? _partnerLatest;
  String? _err;
  bool _loading = true;
  bool _cancelling = false;

  Timer? _timer;
  bool _active = true;

  String get _currency => AppConfig.defaultCurrency;

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

  String _titleCase(String input) {
    final parts = input
        .trim()
        .replaceAll('_', ' ')
        .toLowerCase()
        .split(RegExp(r'\s+'))
        .where((p) => p.isNotEmpty)
        .toList();
    return parts.map((w) => w[0].toUpperCase() + w.substring(1)).join(' ');
  }

  String _statusLabel(String code) {
    switch (code) {
      case 'CREATED':
        return 'Placed';
      case 'ACCEPTED_BY_SELLER':
        return 'Accepted';
      case 'ASSIGNED_TO_PARTNER':
        return 'Partner assigned';
      case 'READY_FOR_PICKUP':
        return 'Ready for pickup';
      case 'PICKED_UP':
        return 'Picked up';
      case 'DELIVERED':
        return 'Delivered';
      case 'REJECTED_BY_SELLER':
        return 'Rejected';
      default:
        if (code.startsWith('CANCELLED')) return 'Cancelled';
        if (code.startsWith('REJECTED')) return 'Rejected';
        return _titleCase(code);
    }
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load(initial: true);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _active = state == AppLifecycleState.resumed;
    if (!_active) {
      _timer?.cancel();
      _timer = null;
    } else {
      if (_shouldTrack) {
        _timer?.cancel();
        _timer = Timer.periodic(const Duration(seconds: 6), (_) => _refreshTracking());
      }
    }
  }

  String _money(double amount) {
    if (_currency.toUpperCase() == 'INR') return '₹${amount.toStringAsFixed(2)}';
    return '${amount.toStringAsFixed(2)} $_currency';
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
        loc = await api.partnerLatestForOrder(widget.orderId);
      }

      if (!mounted) return;
      setState(() {
        _order = o;
        _partnerLatest = loc;
        _loading = false;
      });

      _timer?.cancel();
      if (_active && _shouldTrack) {
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
      // Ignore transient polling failures; keep last known state.
    }
  }

  Future<void> _cancelOrder() async {
    final o = _order;
    if (o == null) return;

    // Backend allows cancellation up to pickup; also prevent cancelling rejected orders.
    final canCancel = widget.allowCancel && o.canCancel && !o.status.startsWith('REJECTED');

    if (!canCancel) {
      AppGlobals.showSnack("This order can’t be cancelled now.");
      return;
    }

    final controller = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel order?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Tell us why you want to cancel (optional).'),
            const SizedBox(height: 10),
            TextField(
              controller: controller,
              decoration: const InputDecoration(
                hintText: 'Reason',
                border: OutlineInputBorder(),
                isDense: true,
              ),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('No')),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Yes, cancel'),
          ),
        ],
      ),
    );

    if (ok != true) return;

    setState(() => _cancelling = true);
    try {
      final api = ref.read(apiProvider);
      final updated = await api.cancelOrder(widget.orderId, reason: controller.text.trim());
      if (!mounted) return;
      setState(() => _order = updated);
      AppGlobals.showSnack("Order cancelled.");
    } catch (e) {
      if (!mounted) return;
      AppGlobals.showSnack("Failed to cancel: $e");
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  Future<void> _copy(String text, {String doneMessage = "Copied"}) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!mounted) return;
    AppGlobals.showSnack(doneMessage);
  }

  @override
  Widget build(BuildContext context) {
    final o = _order;

    return Scaffold(
      appBar: AppBar(
        title: Text('Order #${widget.orderId}'),
        actions: [
          IconButton(
            onPressed: _loading ? null : () => _load(),
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _err != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.error_outline, size: 44),
                        const SizedBox(height: 10),
                        Text(_err!, textAlign: TextAlign.center),
                        const SizedBox(height: 10),
                        FilledButton.icon(
                          onPressed: () => _load(),
                          icon: const Icon(Icons.refresh),
                          label: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : o == null
                  ? const Center(child: Text('Order not found'))
                  : RefreshIndicator(
                      onRefresh: () => _load(),
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        children: [
                          _headerCard(o),
                          const SizedBox(height: 12),
                          _statusCard(o),
                          const SizedBox(height: 12),
                          _itemsCard(o),
                          const SizedBox(height: 12),
                          _trackingCard(o),
                          const SizedBox(height: 16),
                          if (widget.allowCancel)
                            FilledButton.tonalIcon(
                              onPressed: _cancelling ? null : _cancelOrder,
                              icon: _cancelling
                                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                  : const Icon(Icons.cancel_outlined),
                              label: Text(_cancelling ? 'Cancelling…' : 'Cancel order'),
                            ),
                        ],
                      ),
                    ),
    );
  }

  Widget _headerCard(Order o) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Status: ${_statusLabel(o.status)}',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                  ),
                ),
                _pill(
                  o.paymentStatus,
                  o.paymentStatus == "PAID" ? Colors.green : Colors.orange,
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text('Code: ${o.status}', style: TextStyle(color: Colors.black.withOpacity(0.55))),
            const SizedBox(height: 6),
            Text('Vendor ID: ${o.vendorId}'),
            const SizedBox(height: 6),
            Text('Payment: ${o.paymentMethod}'),
            const SizedBox(height: 6),
            Text('Total: ${_money(o.totalAmount)}', style: const TextStyle(fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }

  Widget _statusCard(Order o) {
    final steps = <({String code, String label})>[
      (code: "CREATED", label: "Placed"),
      (code: "ACCEPTED_BY_SELLER", label: "Accepted"),
      (code: "ASSIGNED_TO_PARTNER", label: "Partner assigned"),
      (code: "READY_FOR_PICKUP", label: "Ready for pickup"),
      (code: "PICKED_UP", label: "Picked up"),
      (code: "DELIVERED", label: "Delivered"),
    ];

    final currentIndex = steps.indexWhere((s) => s.code == o.status);
    final showAll = currentIndex != -1;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Progress', style: TextStyle(fontWeight: FontWeight.w800)),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (showAll)
                  for (var i = 0; i < steps.length; i++)
                    _pill(
                      steps[i].label,
                      i < currentIndex ? Colors.green : (i == currentIndex ? Colors.blue : Colors.grey),
                    )
                else
                  _pill(o.status, Colors.blue),
              ],
            ),
            if (o.status.startsWith('CANCELLED')) ...[
              const SizedBox(height: 10),
              const Text('This order has been cancelled.', style: TextStyle(fontWeight: FontWeight.w700)),
            ],
            if (o.status.startsWith('REJECTED')) ...[
              const SizedBox(height: 10),
              const Text('This order was rejected by the seller.', style: TextStyle(fontWeight: FontWeight.w700)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _itemsCard(Order o) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Items', style: TextStyle(fontWeight: FontWeight.w800)),
            const SizedBox(height: 10),
            ...o.items.map(
              (it) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Expanded(child: Text(it.name)),
                    Text('x${it.qty}', style: const TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(width: 12),
                    Text(_money(it.price * it.qty)),
                  ],
                ),
              ),
            ),
            const Divider(height: 18),
            Align(
              alignment: Alignment.centerRight,
              child: Text('Total: ${_money(o.totalAmount)}', style: const TextStyle(fontWeight: FontWeight.w900)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _trackingCard(Order o) {
    final loc = _partnerLatest;
    final canShow = loc != null && loc['lat'] != null && loc['lng'] != null;

    final lat = canShow ? (loc!['lat'] as num).toDouble() : null;
    final lng = canShow ? (loc!['lng'] as num).toDouble() : null;
    final updatedAt = canShow ? (loc!['ts'] ?? loc!['updated_at'] ?? '').toString() : '';

    final mapsUrl = (lat != null && lng != null)
        ? 'https://www.google.com/maps?q=${lat.toStringAsFixed(6)},${lng.toStringAsFixed(6)}'
        : '';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(child: Text('Live tracking', style: TextStyle(fontWeight: FontWeight.w800))),
                if (_shouldTrack) _pill('Live', Colors.green),
              ],
            ),
            const SizedBox(height: 10),
            if (o.partnerId == null)
              const Text('No delivery partner assigned yet.')
            else if (!canShow)
              const Text('Waiting for partner location…')
            else ...[
              Text(
                'Partner location: ${lat!.toStringAsFixed(5)}, ${lng!.toStringAsFixed(5)}',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              if (updatedAt.trim().isNotEmpty) ...[
                const SizedBox(height: 6),
                Text('Updated: $updatedAt'),
              ],
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: () => _copy('${lat.toStringAsFixed(6)},${lng.toStringAsFixed(6)}',
                        doneMessage: 'Coordinates copied'),
                    icon: const Icon(Icons.copy),
                    label: const Text('Copy coordinates'),
                  ),
                  OutlinedButton.icon(
                    onPressed: () => _copy(mapsUrl, doneMessage: 'Maps link copied'),
                    icon: const Icon(Icons.map_outlined),
                    label: const Text('Copy maps link'),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _pill(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withOpacity(0.35)),
      ),
      child: Text(text, style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 12)),
    );
  }
}
