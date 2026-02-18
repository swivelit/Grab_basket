import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../config.dart';
import '../location.dart';
import '../marketing/meta_events.dart';
import '../models.dart';
import '../state.dart';

class CustomerHome extends ConsumerStatefulWidget {
  const CustomerHome({super.key});

  @override
  ConsumerState<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends ConsumerState<CustomerHome> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  Timer? _debounce;

  double? _lat;
  double? _lng;

  bool _loadingLocation = false;
  bool _openOnly = false;
  bool _deliverableOnly = false;

  String _q = '';

  // Pagination
  static const int _pageSize = 20;
  int _offset = 0;
  bool _hasMore = true;
  bool _initialLoading = false;
  bool _loadingMore = false;
  String? _err;
  final List<Vendor> _vendors = [];

  String get _currency => AppConfig.defaultCurrency;

  @override
  void initState() {
    super.initState();
    _search.addListener(_onSearchChanged);
    _scroll.addListener(_onScroll);

    // Load once without location (fast), then refresh once location is available.
    unawaited(_loadFirstPage());
    unawaited(_fetchLocation());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.removeListener(_onSearchChanged);
    _search.dispose();
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore || _loadingMore || _initialLoading) return;
    if (!_scroll.hasClients) return;

    // Load next page when within 300px of bottom.
    final pos = _scroll.position;
    if (pos.pixels >= (pos.maxScrollExtent - 300)) {
      unawaited(_loadMore());
    }
  }

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      final next = _search.text.trim();
      if (next == _q) return;
      setState(() => _q = next);
      unawaited(_loadFirstPage());
    });
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
        q: _q,
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

  Future<void> _fetchLocation() async {
    setState(() => _loadingLocation = true);
    try {
      final pos = await LocationService.getCurrent();
      if (!mounted) return;

      final changed = _lat != pos.latitude || _lng != pos.longitude;
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
      });

      // Refresh list with location-aware results.
      if (changed) {
        await _loadFirstPage();
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  Future<void> _logout() async {
    await ref.read(secureStoreProvider).clear();
    ref.read(cartProvider.notifier).clear();
    ref.invalidate(sessionProvider);

    if (!mounted) return;
    context.go('/');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Grabbasket'),
        actions: [
          IconButton(
            icon: const Icon(Icons.location_on_outlined),
            tooltip: 'Addresses',
            onPressed: () async {
              await context.push('/addresses');
              if (!mounted) return;
              await _loadFirstPage();
            },
          ),
          IconButton(
            icon: const Icon(Icons.receipt_long),
            tooltip: 'My orders',
            onPressed: () => context.push('/orders'),
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
                      onSelected: (v) {
                        setState(() => _openOnly = v);
                        unawaited(_loadFirstPage());
                      },
                    ),
                    FilterChip(
                      label: const Text('Deliverable'),
                      selected: _deliverableOnly,
                      onSelected: (v) {
                        setState(() => _deliverableOnly = v);
                        unawaited(_loadFirstPage());
                      },
                    ),
                    if (_lat != null && _lng != null)
                      const Chip(
                        avatar: Icon(Icons.near_me, size: 18),
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
                  onRefresh: _loadFirstPage,
                  child: _buildList(),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildList() {
    if (_initialLoading && _vendors.isEmpty) {
      return ListView.separated(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: 8,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, __) => const _VendorSkeletonTile(),
      );
    }

    if (_err != null && _vendors.isEmpty) {
      return ListView(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 32),
          const Icon(Icons.wifi_off, size: 40),
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'Could not load stores.\n$_err',
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(height: 12),
          Center(
            child: FilledButton.icon(
              onPressed: _loadFirstPage,
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ),
        ],
      );
    }

    if (_vendors.isEmpty) {
      return ListView(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 60),
          Icon(Icons.storefront, size: 48),
          SizedBox(height: 12),
          Center(child: Text('No stores found')),
        ],
      );
    }

    final itemCount = _vendors.length + (_hasMore || _loadingMore ? 1 : 0);

    return ListView.separated(
      controller: _scroll,
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: itemCount,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (context, i) {
        if (i >= _vendors.length) {
          // Pagination footer
          if (_loadingMore) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator()),
            );
          }

          if (_err != null) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
              child: Row(
                children: [
                  Expanded(child: Text('Failed to load more: $_err')),
                  TextButton(
                    onPressed: _loadMore,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Center(
              child: TextButton(
                onPressed: _loadMore,
                child: const Text('Load more'),
              ),
            ),
          );
        }

        final v = _vendors[i];
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

            context.push('/vendor', extra: v);
          },
        );
      },
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

class _VendorSkeletonTile extends StatelessWidget {
  const _VendorSkeletonTile();

  @override
  Widget build(BuildContext context) {
    Widget box({double? w, required double h}) {
      return Container(
        width: w,
        height: h,
        decoration: BoxDecoration(
          color: Colors.black.withOpacity(0.06),
          borderRadius: BorderRadius.circular(10),
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          box(w: 180, h: 16),
          const SizedBox(height: 8),
          box(w: 240, h: 12),
          const SizedBox(height: 10),
          Row(
            children: [
              box(w: 64, h: 22),
              const SizedBox(width: 8),
              box(w: 72, h: 22),
              const SizedBox(width: 8),
              box(w: 80, h: 22),
            ],
          ),
        ],
      ),
    );
  }
}
