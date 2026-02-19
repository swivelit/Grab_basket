import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../location.dart';
import '../marketing/meta_events.dart';
import '../models.dart';
import '../state.dart';

enum _LocationSource { none, defaultAddress, gps }

enum _VendorSort { relevance, distance, name, openFirst }

class CustomerHome extends ConsumerStatefulWidget {
  const CustomerHome({super.key});

  @override
  ConsumerState<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends ConsumerState<CustomerHome> {
  final _scroll = ScrollController();

  double? _lat;
  double? _lng;
  _LocationSource _locationSource = _LocationSource.none;

  bool _openOnly = false;
  bool _deliverableOnly = false;
  _VendorSort _sort = _VendorSort.relevance;

  // Pagination
  static const int _pageSize = 20;
  int _offset = 0;
  bool _hasMore = true;
  bool _initialLoading = false;
  bool _loadingMore = false;
  bool _loadingGps = false;
  String? _err;
  final List<Vendor> _vendors = [];

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);

    // Prefer default address as delivery location; fall back to GPS if no address.
    ref.listen<AsyncValue<Map<String, dynamic>?>>(defaultAddressProvider, (prev, next) {
      next.when(
        data: (addr) {
          final lat = (addr?['lat'] as num?)?.toDouble();
          final lng = (addr?['lng'] as num?)?.toDouble();

          if (lat == null || lng == null) {
            if (_locationSource == _LocationSource.none) unawaited(_useGpsLocation());
            return;
          }

          // If user manually switched to GPS, don't override.
          if (_locationSource == _LocationSource.gps) return;

          final changed = _lat != lat || _lng != lng || _locationSource != _LocationSource.defaultAddress;
          setState(() {
            _lat = lat;
            _lng = lng;
            _locationSource = _LocationSource.defaultAddress;
          });
          if (changed) unawaited(_loadFirstPage());
        },
        error: (_, __) {
          if (_locationSource == _LocationSource.none) unawaited(_useGpsLocation());
        },
        loading: () {},
      );
    });

    // Fast initial load (no location), then refresh when address/GPS resolves.
    unawaited(_loadFirstPage());
  }

  @override
  void dispose() {
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore || _loadingMore || _initialLoading) return;
    if (!_scroll.hasClients) return;
    final pos = _scroll.position;
    if (pos.pixels >= (pos.maxScrollExtent - 300)) {
      unawaited(_loadMore());
    }
  }

  Future<void> _loadFirstPage() => _loadPage(reset: true);
  Future<void> _loadMore() => _loadPage(reset: false);

  Future<void> _loadPage({required bool reset}) async {
    if (reset) {
      setState(() {
        _initialLoading = true;
        _err = null;
        _offset = 0;
        _hasMore = true;
        _vendors.clear();
      });
    } else {
      if (_loadingMore || _initialLoading || !_hasMore) return;
      setState(() => _loadingMore = true);
    }

    try {
      final api = ref.read(apiProvider);
      final page = await api.vendors(
        lat: _lat,
        lng: _lng,
        q: null,
        openOnly: _openOnly,
        deliverableOnly: _deliverableOnly,
        limit: _pageSize,
        offset: _offset,
      );

      if (!mounted) return;
      setState(() {
        _vendors.addAll(page);
        _offset += page.length;
        _hasMore = page.length == _pageSize;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _err = e.toString());
    } finally {
      if (!mounted) return;
      setState(() {
        if (reset) {
          _initialLoading = false;
        } else {
          _loadingMore = false;
        }
      });
    }
  }

  Future<void> _useGpsLocation() async {
    if (_loadingGps) return;
    setState(() => _loadingGps = true);

    try {
      final pos = await LocationService.getCurrent();
      if (!mounted) return;

      final changed = _lat != pos.latitude || _lng != pos.longitude || _locationSource != _LocationSource.gps;
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
        _locationSource = _LocationSource.gps;
      });

      if (changed) await _loadFirstPage();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Using current location')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _loadingGps = false);
    }
  }

  void _useDefaultAddress(Map<String, dynamic>? addr) {
    if (addr == null) return;
    final lat = (addr['lat'] as num?)?.toDouble();
    final lng = (addr['lng'] as num?)?.toDouble();
    if (lat == null || lng == null) return;

    final changed = _lat != lat || _lng != lng || _locationSource != _LocationSource.defaultAddress;
    setState(() {
      _lat = lat;
      _lng = lng;
      _locationSource = _LocationSource.defaultAddress;
    });

    if (changed) unawaited(_loadFirstPage());
  }

  List<Vendor> get _displayVendors {
    final list = [..._vendors];

    int byDistance(Vendor a, Vendor b) {
      final da = a.distanceKm;
      final db = b.distanceKm;
      if (da == null && db == null) return 0;
      if (da == null) return 1;
      if (db == null) return -1;
      return da.compareTo(db);
    }

    int byName(Vendor a, Vendor b) => a.name.toLowerCase().compareTo(b.name.toLowerCase());
    bool isOpen(Vendor v) => (v.openNow ?? v.isOpen);

    switch (_sort) {
      case _VendorSort.relevance:
        break;
      case _VendorSort.distance:
        list.sort(byDistance);
        break;
      case _VendorSort.name:
        list.sort(byName);
        break;
      case _VendorSort.openFirst:
        list.sort((a, b) {
          final ao = isOpen(a);
          final bo = isOpen(b);
          if (ao != bo) return ao ? -1 : 1;
          return byDistance(a, b);
        });
        break;
    }

    return list;
  }

  String _sortLabel(_VendorSort s) => switch (s) {
        _VendorSort.relevance => 'Relevance',
        _VendorSort.distance => 'Distance',
        _VendorSort.name => 'Name',
        _VendorSort.openFirst => 'Open first',
      };

  Future<void> _openSortSheet() async {
    final selected = await showModalBottomSheet<_VendorSort>(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Sort by', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
                const SizedBox(height: 8),
                for (final opt in _VendorSort.values)
                  RadioListTile<_VendorSort>(
                    value: opt,
                    groupValue: _sort,
                    onChanged: (v) => Navigator.of(context).pop(v),
                    title: Text(_sortLabel(opt)),
                  ),
              ],
            ),
          ),
        );
      },
    );

    if (!mounted) return;
    if (selected != null && selected != _sort) setState(() => _sort = selected);
  }

  Future<void> _openAddresses() async {
    await context.push('/addresses');
    if (!mounted) return;
    await _loadFirstPage();
  }

  void _comingSoon(String name) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$name coming soon.')));
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final cartCount = cart?.count ?? 0;

    final addrAsync = ref.watch(defaultAddressProvider);
    final addr = addrAsync.valueOrNull;

    final showUsingGps = _locationSource == _LocationSource.gps;
    final label = (addr?['label'] ?? '').toString().trim();
    final line1 = (addr?['line1'] ?? '').toString().trim();

    final headerTitle = showUsingGps
        ? 'Using current location'
        : (label.isNotEmpty ? label : (addrAsync.isLoading ? 'Loading address…' : 'Deliver to'));
    final headerSubtitle = showUsingGps
        ? 'Tap to change'
        : (line1.isNotEmpty ? line1 : (addrAsync.isLoading ? '' : 'Set your delivery location'));

    final vendors = _displayVendors;

    // Build a single ListView with sections (Swiggy-like home feed)
    const sectionCount = 4; // top header, offers, filters, footer
    final listCount = sectionCount + (vendors.isEmpty ? 0 : vendors.length);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadFirstPage,
          child: ListView.builder(
            controller: _scroll,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.zero,
            itemCount: listCount,
            itemBuilder: (context, index) {
              // 0) Top header
              if (index == 0) {
                return Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: InkWell(
                              borderRadius: BorderRadius.circular(12),
                              onTap: _openAddresses,
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Flexible(
                                          child: Text(
                                            headerTitle,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        const Icon(Icons.keyboard_arrow_down),
                                      ],
                                    ),
                                    if (headerSubtitle.isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(headerSubtitle, maxLines: 1, overflow: TextOverflow.ellipsis),
                                    ],
                                    if (showUsingGps && addr != null) ...[
                                      const SizedBox(height: 6),
                                      Align(
                                        alignment: Alignment.centerLeft,
                                        child: TextButton.icon(
                                          onPressed: () => _useDefaultAddress(addr),
                                          icon: const Icon(Icons.home_outlined),
                                          label: const Text('Use saved address'),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ),
                          ),
                          IconButton(
                            tooltip: 'Use current location',
                            onPressed: _loadingGps ? null : _useGpsLocation,
                            icon: _loadingGps
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                                : const Icon(Icons.my_location),
                          ),
                          IconButton(
                            tooltip: 'Cart',
                            onPressed: () => context.push('/cart'),
                            icon: cartCount > 0
                                ? Badge(
                                    label: Text(cartCount.toString()),
                                    child: const Icon(Icons.shopping_basket_outlined),
                                  )
                                : const Icon(Icons.shopping_basket_outlined),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      InkWell(
                        borderRadius: BorderRadius.circular(14),
                        onTap: () => context.go('/search'),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.surfaceVariant,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.search),
                              SizedBox(width: 10),
                              Expanded(child: Text('Search for restaurants, stores…')),
                              Icon(Icons.mic_none),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 86,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            _CategoryTile(icon: Icons.restaurant, label: 'Food', onTap: () {}),
                            const SizedBox(width: 12),
                            _CategoryTile(
                              icon: Icons.shopping_cart_outlined,
                              label: 'Instamart',
                              onTap: () => _comingSoon('Instamart'),
                            ),
                            const SizedBox(width: 12),
                            _CategoryTile(icon: Icons.local_shipping_outlined, label: 'Genie', onTap: () => _comingSoon('Genie')),
                            const SizedBox(width: 12),
                            _CategoryTile(icon: Icons.restaurant_outlined, label: 'Dineout', onTap: () => _comingSoon('Dineout')),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }

              // 1) Offers
              if (index == 1) {
                return SizedBox(
                  height: 116,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                    itemBuilder: (context, i) => _OfferCard(offer: _offers[i]),
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemCount: _offers.length,
                  ),
                );
              }

              // 2) Filters
              if (index == 2) {
                return Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        ActionChip(
                          avatar: const Icon(Icons.swap_vert, size: 18),
                          label: Text('Sort: ${_sortLabel(_sort)}'),
                          onPressed: _openSortSheet,
                        ),
                        const SizedBox(width: 10),
                        FilterChip(
                          label: const Text('Open now'),
                          selected: _openOnly,
                          onSelected: (v) {
                            setState(() => _openOnly = v);
                            unawaited(_loadFirstPage());
                          },
                        ),
                        const SizedBox(width: 10),
                        FilterChip(
                          label: const Text('Deliverable'),
                          selected: _deliverableOnly,
                          onSelected: (v) {
                            setState(() => _deliverableOnly = v);
                            unawaited(_loadFirstPage());
                          },
                        ),
                        if (_openOnly || _deliverableOnly || _sort != _VendorSort.relevance) ...[
                          const SizedBox(width: 10),
                          TextButton(
                            onPressed: () {
                              setState(() {
                                _openOnly = false;
                                _deliverableOnly = false;
                                _sort = _VendorSort.relevance;
                              });
                              unawaited(_loadFirstPage());
                            },
                            child: const Text('Clear'),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              }

              // Last section is the footer (pagination / error / empty)
              final footerIndex = listCount - 1;
              if (index == footerIndex) {
                // Empty/error/loading states
                if (_initialLoading && _vendors.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.fromLTRB(16, 8, 16, 24),
                    child: _VendorListSkeleton(),
                  );
                }

                if (_err != null && _vendors.isEmpty) {
                  return Padding(
                    padding: const EdgeInsets.fromLTRB(16, 24, 16, 24),
                    child: Column(
                      children: [
                        const Icon(Icons.wifi_off, size: 44),
                        const SizedBox(height: 10),
                        Text('Could not load stores.\n$_err', textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        FilledButton.icon(
                          onPressed: _loadFirstPage,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Retry'),
                        ),
                      ],
                    ),
                  );
                }

                if (!_initialLoading && vendors.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.fromLTRB(16, 32, 16, 24),
                    child: Column(
                      children: [
                        Icon(Icons.storefront, size: 52),
                        SizedBox(height: 12),
                        Text('No stores found'),
                      ],
                    ),
                  );
                }

                // Pagination footer
                if (_loadingMore) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 18),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                if (_err != null && _vendors.isNotEmpty) {
                  return Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                    child: Row(
                      children: [
                        Expanded(child: Text('Failed to load more: $_err')),
                        TextButton(onPressed: _loadMore, child: const Text('Retry')),
                      ],
                    ),
                  );
                }

                if (_hasMore) {
                  return Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                    child: Center(
                      child: TextButton(
                        onPressed: _loadMore,
                        child: const Text('Load more'),
                      ),
                    ),
                  );
                }

                return const SizedBox(height: 24);
              }

              // Vendor cards start after the 3 header sections
              final vendorIndex = index - 3;
              if (vendorIndex < 0 || vendorIndex >= vendors.length) return const SizedBox.shrink();

              final v = vendors[vendorIndex];
              return Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: _VendorCard(
                  vendor: v,
                  onTap: () {
                    MetaEvents.instance.logViewVendorMenu(vendor: v, currency: 'INR');
                    context.push('/vendor', extra: v);
                  },
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _CategoryTile({required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        width: 86,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: scheme.surfaceVariant,
          borderRadius: BorderRadius.circular(18),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 28),
            const SizedBox(height: 8),
            Text(label, style: const TextStyle(fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class _Offer {
  final IconData icon;
  final String title;
  final String subtitle;

  const _Offer({required this.icon, required this.title, required this.subtitle});
}

const List<_Offer> _offers = [
  _Offer(icon: Icons.local_offer_outlined, title: 'Up to 60% OFF', subtitle: 'On your first order'),
  _Offer(icon: Icons.payments_outlined, title: 'Cash on Delivery', subtitle: 'Pay when you receive'),
  _Offer(icon: Icons.delivery_dining_outlined, title: 'Fast delivery', subtitle: 'From nearby stores'),
];

class _OfferCard extends StatelessWidget {
  final _Offer offer;
  const _OfferCard({required this.offer});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      width: 260,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: scheme.primaryContainer,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: scheme.primary,
            child: Icon(offer.icon, color: scheme.onPrimary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(offer.title, style: const TextStyle(fontWeight: FontWeight.w900)),
                const SizedBox(height: 4),
                Text(offer.subtitle, maxLines: 2, overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VendorCard extends StatelessWidget {
  final Vendor vendor;
  final VoidCallback onTap;

  const _VendorCard({required this.vendor, required this.onTap});

  bool get _openNow => vendor.openNow ?? vendor.isOpen;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final dist = vendor.distanceKm;
    final canDeliver = vendor.canDeliver;

    final openTime = vendor.openTime?.trim();
    final closeTime = vendor.closeTime?.trim();
    final hours = (openTime?.isNotEmpty == true && closeTime?.isNotEmpty == true) ? '$openTime - $closeTime' : null;

    return Material(
      color: scheme.surface,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.35)),
          ),
          child: Row(
            children: [
              Container(
                width: 110,
                height: 110,
                decoration: BoxDecoration(
                  color: scheme.surfaceVariant,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(18),
                    bottomLeft: Radius.circular(18),
                  ),
                ),
                child: Stack(
                  children: [
                    const Center(child: Icon(Icons.storefront, size: 34)),
                    Positioned(
                      left: 10,
                      top: 10,
                      child: _tag(_openNow ? 'OPEN' : 'CLOSED', _openNow ? Colors.green : Colors.red),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              vendor.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
                            ),
                          ),
                          const Icon(Icons.chevron_right),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        vendor.description.trim().isNotEmpty ? vendor.description : vendor.address,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          if (dist != null) _pill(context, '${dist.toStringAsFixed(1)} km', Icons.near_me),
                          if (canDeliver != null)
                            _pill(
                              context,
                              canDeliver ? 'Delivers' : 'Out of range',
                              canDeliver ? Icons.check_circle_outline : Icons.do_not_disturb_on_outlined,
                            ),
                          if (hours != null) _pill(context, hours, Icons.schedule),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static Widget _tag(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.9),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withOpacity(0.35)),
      ),
      child: Text(text, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w900)),
    );
  }

  static Widget _pill(BuildContext context, String text, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceVariant,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16),
          const SizedBox(width: 6),
          Text(text, style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _VendorListSkeleton extends StatelessWidget {
  const _VendorListSkeleton();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(6, (_) => const Padding(padding: EdgeInsets.only(bottom: 12), child: _VendorCardSkeleton())),
    );
  }
}

class _VendorCardSkeleton extends StatelessWidget {
  const _VendorCardSkeleton();

  @override
  Widget build(BuildContext context) {
    final c = Colors.black.withOpacity(0.06);

    Widget box({double? w, required double h, BorderRadius? r}) {
      return Container(
        width: w,
        height: h,
        decoration: BoxDecoration(
          color: c,
          borderRadius: r ?? BorderRadius.circular(10),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          Container(
            width: 110,
            height: 110,
            decoration: BoxDecoration(
              color: c,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(18),
                bottomLeft: Radius.circular(18),
              ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  box(w: 170, h: 16),
                  const SizedBox(height: 10),
                  box(w: 220, h: 12),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      box(w: 86, h: 26, r: BorderRadius.circular(999)),
                      const SizedBox(width: 10),
                      box(w: 86, h: 26, r: BorderRadius.circular(999)),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
