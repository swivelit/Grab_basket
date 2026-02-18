import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../config.dart';
import '../marketing/meta_events.dart';
import '../models.dart';
import '../state.dart';

class VendorMenuScreen extends ConsumerStatefulWidget {
  final Vendor vendor;
  const VendorMenuScreen({super.key, required this.vendor});

  @override
  ConsumerState<VendorMenuScreen> createState() => _VendorMenuScreenState();
}

class _VendorMenuScreenState extends ConsumerState<VendorMenuScreen> {
  final _search = TextEditingController();
  Timer? _debounce;

  String _q = '';
  bool _includeUnavailable = false;
  Future<List<Product>>? _future;

  String get _currency => AppConfig.defaultCurrency;

  @override
  void initState() {
    super.initState();
    _search.addListener(_onSearchChanged);
    _future = _buildProductsFuture();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.removeListener(_onSearchChanged);
    _search.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      final next = _search.text.trim();
      if (next == _q) return;
      setState(() {
        _q = next;
        _future = _buildProductsFuture();
      });
    });
  }

  Future<List<Product>> _buildProductsFuture() {
    final api = ref.read(apiProvider);
    return api.products(
      widget.vendor.id,
      q: _q,
      includeUnavailable: _includeUnavailable,
      limit: 200,
    );
  }

  Future<void> _reload() async {
    setState(() => _future = _buildProductsFuture());
    await _future;
  }

  int _qtyFor(Product p, CartState? cart) {
    if (cart == null || cart.vendorId != p.vendorId) return 0;
    final idx = cart.lines.indexWhere((l) => l.product.id == p.id);
    return idx == -1 ? 0 : cart.lines[idx].qty;
  }

  String _money(double amount) {
    if (_currency.toUpperCase() == 'INR') {
      return '₹${amount.toStringAsFixed(2)}';
    }
    return '${amount.toStringAsFixed(2)} $_currency';
  }

  @override
  Widget build(BuildContext context) {
    final apiFuture = _future ?? _buildProductsFuture();

    final cart = ref.watch(cartProvider);
    final cartForVendor = (cart != null && cart.vendorId == widget.vendor.id) ? cart : null;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.vendor.name),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
              child: Column(
                children: [
                  TextField(
                    controller: _search,
                    textInputAction: TextInputAction.search,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.search),
                      hintText: 'Search menu items',
                      border: const OutlineInputBorder(),
                      isDense: true,
                      suffixIcon: _q.isEmpty
                          ? null
                          : IconButton(
                              onPressed: () {
                                _search.clear();
                                FocusScope.of(context).unfocus();
                              },
                              icon: const Icon(Icons.close),
                            ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        FilterChip(
                          label: const Text('Show unavailable'),
                          selected: _includeUnavailable,
                          onSelected: (v) {
                            setState(() {
                              _includeUnavailable = v;
                              _future = _buildProductsFuture();
                            });
                          },
                        ),
                        if (_q.isNotEmpty) Chip(label: Text('Search: \"$_q\"')),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: RefreshIndicator(
                onRefresh: _reload,
                child: FutureBuilder<List<Product>>(
                  future: apiFuture,
                  builder: (context, snap) {
                    if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
                      return const Center(child: CircularProgressIndicator());
                    }

                    if (snap.hasError) {
                      return ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          const SizedBox(height: 32),
                          const Icon(Icons.wifi_off, size: 40),
                          const SizedBox(height: 12),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            child: Text(
                              'Could not load menu.\n${snap.error}',
                              textAlign: TextAlign.center,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Center(
                            child: FilledButton.icon(
                              onPressed: () => _reload(),
                              icon: const Icon(Icons.refresh),
                              label: const Text('Retry'),
                            ),
                          ),
                        ],
                      );
                    }

                    final products = snap.data ?? [];
                    if (products.isEmpty) {
                      return ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: const [
                          SizedBox(height: 60),
                          Icon(Icons.restaurant_menu, size: 48),
                          SizedBox(height: 12),
                          Center(child: Text('No items found')),
                        ],
                      );
                    }

                    return ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      itemCount: products.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, i) {
                        final p = products[i];
                        final qty = _qtyFor(p, cartForVendor);
                        final isDisabled = !p.isAvailable;

                        return ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                          title: Text(
                            p.name,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: isDisabled ? Colors.grey : null,
                            ),
                          ),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SizedBox(height: 4),
                              Text(
                                p.description.isEmpty ? ' ' : p.description,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: isDisabled ? Colors.grey : null),
                              ),
                              const SizedBox(height: 6),
                              Wrap(
                                spacing: 8,
                                children: [
                                  Text(
                                    _money(p.price),
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: isDisabled ? Colors.grey : null,
                                    ),
                                  ),
                                  if (isDisabled) _chip('Unavailable', Colors.grey),
                                ],
                              ),
                            ],
                          ),
                          trailing: _QtyStepper(
                            qty: qty,
                            enabled: !isDisabled,
                            onAdd: () {
                              final before = _qtyFor(p, cartForVendor);
                              ref.read(cartProvider.notifier).add(p);

                              // Log only when item is first added (0 -> 1)
                              if (before == 0) {
                                MetaEvents.instance.logAddToCart(
                                  vendor: widget.vendor,
                                  product: p,
                                  currency: _currency,
                                );
                              }
                            },
                            onRemove: qty <= 0
                                ? null
                                : () {
                                    ref.read(cartProvider.notifier).remove(p);
                                  },
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ),

            // Cart summary bar (Swiggy-style)
            if (cartForVendor != null && cartForVendor.count > 0)
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: Material(
                    elevation: 2,
                    borderRadius: BorderRadius.circular(14),
                    color: Theme.of(context).colorScheme.primary,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(14),
                      onTap: () => context.push('/cart'),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        child: Row(
                          children: [
                            const Icon(Icons.shopping_basket, color: Colors.white),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                '${cartForVendor.count} item(s) • ${_money(cartForVendor.subtotal)}',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                              ),
                            ),
                            const Text(
                              'View cart',
                              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(width: 6),
                            const Icon(Icons.chevron_right, color: Colors.white),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _chip(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withOpacity(0.35)),
      ),
      child: Text(text, style: TextStyle(color: color, fontSize: 12)),
    );
  }
}

class _QtyStepper extends StatelessWidget {
  final int qty;
  final bool enabled;
  final VoidCallback onAdd;
  final VoidCallback? onRemove;

  const _QtyStepper({
    required this.qty,
    required this.enabled,
    required this.onAdd,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    if (qty <= 0) {
      return FilledButton(
        onPressed: enabled ? onAdd : null,
        child: const Text('ADD'),
      );
    }

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: const Icon(Icons.remove),
            visualDensity: VisualDensity.compact,
            onPressed: enabled ? onRemove : null,
          ),
          Text(
            qty.toString(),
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          IconButton(
            icon: const Icon(Icons.add),
            visualDensity: VisualDensity.compact,
            onPressed: enabled ? onAdd : null,
          ),
        ],
      ),
    );
  }
}
