import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app_globals.dart';
import '../config.dart';
import '../marketing/meta_events.dart';
import '../models.dart';
import '../order_status.dart';
import '../state.dart';

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  String _paymentMethod = 'COD'; // COD / UPI
  int? _selectedAddressId;
  bool _placingOrder = false;

  Future<List<Map<String, dynamic>>>? _addrFuture;

  String get _currency => AppConfig.defaultCurrency;
  bool _loggedInitiatedCheckout = false;

  @override
  void initState() {
    super.initState();
    _addrFuture = ref.read(apiProvider).addresses();
  }

  String _money(double amount) {
    if (_currency.toUpperCase() == 'INR') {
      return '₹${amount.toStringAsFixed(2)}';
    }
    return '${amount.toStringAsFixed(2)} $_currency';
  }

  Future<void> _openAddresses() async {
    await context.push('/addresses');
    if (!mounted) return;
    setState(() => _addrFuture = ref.read(apiProvider).addresses());
  }

  Future<void> _reloadAddresses() async {
    setState(() => _addrFuture = ref.read(apiProvider).addresses());
    await _addrFuture;
  }

  int _qtyForProduct(int productId) {
    final cart = ref.read(cartProvider);
    if (cart == null) return 0;
    final idx = cart.lines.indexWhere((l) => l.product.id == productId);
    return idx == -1 ? 0 : cart.lines[idx].qty;
  }

  Widget _billCard(CartState cart) {
    // Backend currently returns final delivery fee only after order creation.
    // We keep checkout honest: show item total now, and make delivery fee
    // explicit as “calculated next”.
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('Bill details', style: TextStyle(fontWeight: FontWeight.w900)),
            const SizedBox(height: 10),
            Row(
              children: [
                const Expanded(child: Text('Item total')),
                Text(_money(cart.subtotal), style: const TextStyle(fontWeight: FontWeight.w800)),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                Expanded(child: Text('Delivery fee', style: TextStyle(color: Colors.black54))),
                const Text('Calculated next', style: TextStyle(fontWeight: FontWeight.w700)),
              ],
            ),
            const Divider(height: 18),
            Row(
              children: [
                const Expanded(child: Text('To pay (estimated)', style: TextStyle(fontWeight: FontWeight.w900))),
                Text(_money(cart.subtotal), style: const TextStyle(fontWeight: FontWeight.w900)),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'Final bill (including delivery fee) will be shown on the order page.',
              style: TextStyle(color: Colors.black.withOpacity(0.55), fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final api = ref.watch(apiProvider);

    if (cart == null) {
      return const Scaffold(body: Center(child: Text('Cart is empty')));
    }

    // ✅ Meta App Events: Initiated checkout (log once per screen visit)
    if (!_loggedInitiatedCheckout) {
      _loggedInitiatedCheckout = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        MetaEvents.instance.logInitiatedCheckout(
          cart: cart,
          currency: _currency,
          paymentMethod: _paymentMethod,
        );
      });
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Checkout • ${cart.vendorName}'),
        actions: [
          IconButton(
            onPressed: _placingOrder ? null : _reloadAddresses,
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _reloadAddresses,
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _addrFuture,
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
                  const Icon(Icons.error_outline, size: 40),
                  const SizedBox(height: 12),
                  Text('Failed to load addresses.\n${snap.error}', textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  Center(
                    child: FilledButton.icon(
                      onPressed: _placingOrder ? null : _reloadAddresses,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Retry'),
                    ),
                  ),
                ],
              );
            }

            final addresses = snap.data ?? [];

            if (addresses.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                children: [
                  const SizedBox(height: 40),
                  const Icon(Icons.location_on_outlined, size: 48),
                  const SizedBox(height: 12),
                  const Center(child: Text('Add a delivery address first.')),
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: _placingOrder ? null : _openAddresses,
                    icon: const Icon(Icons.add_location_alt_outlined),
                    label: const Text('Add address'),
                  ),
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: () => context.pop(),
                    child: const Text('Go back'),
                  ),
                ],
              );
            }

            // If not selected yet, choose default (or first)
            if (_selectedAddressId == null) {
              final def = addresses.firstWhere(
                (a) => a['is_default'] == true,
                orElse: () => addresses.first,
              );
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (!mounted) return;
                setState(() => _selectedAddressId = def['id'] as int);
              });
            }

            final selectedId = _selectedAddressId ?? (addresses.first['id'] as int);

            return Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(
                    child: ListView(
                      children: [
                        const Text('Items', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        ...cart.lines.map(
                          (line) {
                            final qty = _qtyForProduct(line.product.id);
                            return ListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(line.product.name),
                              subtitle: Text(_money(line.product.price)),
                              trailing: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.remove_circle_outline),
                                    onPressed: _placingOrder
                                        ? null
                                        : () => ref.read(cartProvider.notifier).remove(line.product),
                                  ),
                                  Text(qty.toString(), style: const TextStyle(fontWeight: FontWeight.w700)),
                                  IconButton(
                                    icon: const Icon(Icons.add_circle_outline),
                                    onPressed: _placingOrder
                                        ? null
                                        : () => ref.read(cartProvider.notifier).add(line.product),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                        const Divider(height: 24),
                        Row(
                          children: [
                            const Expanded(
                              child: Text('Delivery address', style: TextStyle(fontWeight: FontWeight.bold)),
                            ),
                            TextButton.icon(
                              onPressed: _placingOrder ? null : _openAddresses,
                              icon: const Icon(Icons.edit_location_alt_outlined),
                              label: const Text('Manage'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        ...addresses.map(
                          (a) {
                            final id = a['id'] as int;
                            final label = (a['label'] ?? 'Address').toString();
                            final line1 = (a['line1'] ?? '').toString();
                            final isDefault = a['is_default'] == true;
                            return RadioListTile<int>(
                              value: id,
                              groupValue: selectedId,
                              onChanged: _placingOrder ? null : (v) => setState(() => _selectedAddressId = v),
                              title: Text(isDefault ? '$label • Default' : label),
                              subtitle: Text(line1),
                              contentPadding: EdgeInsets.zero,
                            );
                          },
                        ),
                        const Divider(height: 24),
                        const Text('Payment method', style: TextStyle(fontWeight: FontWeight.bold)),
                        RadioListTile<String>(
                          value: 'COD',
                          groupValue: _paymentMethod,
                          onChanged: _placingOrder ? null : (v) => setState(() => _paymentMethod = v ?? 'COD'),
                          title: const Text('Cash on Delivery'),
                          contentPadding: EdgeInsets.zero,
                        ),
                        RadioListTile<String>(
                          value: 'UPI',
                          groupValue: _paymentMethod,
                          onChanged: _placingOrder ? null : (v) => setState(() => _paymentMethod = v ?? 'UPI'),
                          title: const Text('UPI'),
                          contentPadding: EdgeInsets.zero,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  _billCard(cart),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: _placingOrder
                        ? null
                        : () async {
                            final items = cart.lines
                                .map((l) => <String, dynamic>{'product_id': l.product.id, 'qty': l.qty})
                                .toList();

                            setState(() => _placingOrder = true);
                            try {
                              final order = await api.createOrder(
                                vendorId: cart.vendorId,
                                items: items,
                                deliveryAddressId: selectedId,
                                paymentMethod: _paymentMethod,
                              );

                              // ✅ Meta App Events: Purchase (fire-and-forget)
                              MetaEvents.instance.logPurchase(order: order, currency: _currency);

                              ref.read(cartProvider.notifier).clear();
                              if (!mounted) return;

                              AppGlobals.showSnack('Order #${order.id} placed • ${OrderStatus.label(order.status)}');

                              // Swiggy-like flow: show order detail immediately.
                              context.pushReplacement('/order/${order.id}');
                            } catch (e) {
                              if (!mounted) return;
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Failed to place order: $e')),
                              );
                            } finally {
                              if (mounted) setState(() => _placingOrder = false);
                            }
                          },
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(_placingOrder ? 'Placing…' : 'Place order'),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
