import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../bootstrap.dart';
import '../location.dart';
import '../marketing/meta_events.dart';
import '../models.dart';
import '../state.dart';
import 'addresses.dart';
import 'login.dart';
import 'orders.dart';
import 'vendor_menu.dart';

class CustomerHome extends ConsumerStatefulWidget {
  const CustomerHome({super.key});

  @override
  ConsumerState<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends ConsumerState<CustomerHome> {
  final _search = TextEditingController();
  Timer? _debounce;

  double? _lat;
  double? _lng;

  bool _loadingLocation = false;
  bool _openOnly = false;
  bool _deliverableOnly = false;

  String _q = '';
  Future<List<Vendor>>? _future;

  static const String _currency = 'INR';

  @override
  void initState() {
    super.initState();
    _search.addListener(_onSearchChanged);

    // Kick off location + initial load.
    unawaited(_fetchLocation());
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
        _future = _buildVendorsFuture();
      });
    });
  }

  Future<List<Vendor>> _buildVendorsFuture() {
    final api = ref.read(apiProvider);
    return api.vendors(
      lat: _lat,
      lng: _lng,
      q: _q,
      openOnly: _openOnly,
      deliverableOnly: _deliverableOnly,
      limit: 50,
      offset: 0,
    );
  }

  Future<void> _reload() async {
    setState(() {
      _future = _buildVendorsFuture();
    });
    await _future;
  }

  Future<void> _fetchLocation() async {
    setState(() => _loadingLocation = true);
    try {
      final pos = await LocationService.getCurrent();
      if (!mounted) return;
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
        _future = _buildVendorsFuture();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));

      // Even without location, we can still show vendors.
      setState(() {
        _lat = null;
        _lng = null;
        _future = _buildVendorsFuture();
      });
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  Future<void> _logout() async {
    await ref.read(secureStoreProvider).clear();
    ref.read(cartProvider.notifier).clear();
    ref.invalidate(sessionProvider);

    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.customer)),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    final future = _future ?? _buildVendorsFuture();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Grabbasket'),
        actions: [
          IconButton(
            icon: const Icon(Icons.location_on_outlined),
            tooltip: 'Addresses',
            onPressed: () async {
              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AddressesScreen()));
              if (!mounted) return;
              await _reload();
            },
          ),
          IconButton(
            icon: const Icon(Icons.receipt_long),
            tooltip: 'My orders',
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OrdersScreen())),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
            onPressed: _logout,
          ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _search,
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        prefixIcon: const Icon(Icons.search),
                        hintText: 'Search restaurants / stores',
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
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    onPressed: _loadingLocation ? null : _fetchLocation,
                    icon: _loadingLocation
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.my_location),
                    tooltip: 'Refresh location',
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Align(
                alignment: Alignment.centerLeft,
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    FilterChip(
                      label: const Text('Open now'),
                      selected: _openOnly,
                      onSelected: (v) async {
                        setState(() {
                          _openOnly = v;
                          _future = _buildVendorsFuture();
                        });
                      },
                    ),
                    FilterChip(
                      label: const Text('Deliverable'),
                      selected: _deliverableOnly,
                      onSelected: (v) async {
                        setState(() {
                          _deliverableOnly = v;
                          _future = _buildVendorsFuture();
                        });
                      },
                    ),
                    if (_lat != null && _lng != null)
                      Chip(
                        avatar: const Icon(Icons.near_me, size: 18),
                        label: Text('Using location'),
                      )
                    else
                      const Chip(
                        avatar: Icon(Icons.location_off, size: 18),
                        label: Text('No location'),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _reload,
                  child: FutureBuilder<List<Vendor>>(
                    future: future,
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
                                'Could not load stores.\n${snap.error}',
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

                      final vendors = snap.data ?? [];
                      if (vendors.isEmpty) {
                        return ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: const [
                            SizedBox(height: 60),
                            Icon(Icons.storefront, size: 48),
                            SizedBox(height: 12),
                            Center(child: Text('No stores found')),
                          ],
                        );
                      }

                      return ListView.separated(
                        physics: const AlwaysScrollableScrollPhysics(),
                        itemCount: vendors.length,
                        separatorBuilder: (_, __) => const Divider(height: 1),
                        itemBuilder: (context, i) {
                          final v = vendors[i];

                          final openNow = v.openNow ?? v.isOpen;
                          final dist = v.distanceKm;
                          final canDeliver = v.canDeliver;

                          return ListTile(
                            contentPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
                            title: Text(v.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (v.description.trim().isNotEmpty)
                                  Text(v.description, maxLines: 1, overflow: TextOverflow.ellipsis),
                                const SizedBox(height: 6),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 6,
                                  children: [
                                    _chip(openNow ? 'Open' : 'Closed', openNow ? Colors.green : Colors.red),
                                    if (dist != null) _chip('${dist.toStringAsFixed(1)} km', Colors.blueGrey),
                                    if (canDeliver != null)
                                      _chip(
                                        canDeliver ? 'Delivers' : 'Out of range',
                                        canDeliver ? Colors.green : Colors.orange,
                                      ),
                                  ],
                                ),
                              ],
                            ),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // ✅ Meta App Events: Viewed vendor/menu (fire-and-forget)
                              MetaEvents.instance.logViewVendorMenu(vendor: v, currency: _currency);

                              Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => VendorMenuScreen(vendor: v)),
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
