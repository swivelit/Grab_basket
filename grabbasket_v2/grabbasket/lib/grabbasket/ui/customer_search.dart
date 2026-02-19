import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../location.dart';
import '../models.dart';
import '../state.dart';

enum _LocationSource { none, defaultAddress, gps }

/// Dedicated Search tab.
///
/// Uses current backend vendor search via `/vendors?q=`.
class CustomerSearchScreen extends ConsumerStatefulWidget {
  const CustomerSearchScreen({super.key});

  @override
  ConsumerState<CustomerSearchScreen> createState() => _CustomerSearchScreenState();
}

class _CustomerSearchScreenState extends ConsumerState<CustomerSearchScreen> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  final _focus = FocusNode();
  Timer? _debounce;

  double? _lat;
  double? _lng;
  _LocationSource _locationSource = _LocationSource.none;
  bool _loadingGps = false;

  String _q = '';

  // Pagination
  static const int _pageSize = 20;
  int _offset = 0;
  bool _hasMore = true;
  bool _initialLoading = false;
  bool _loadingMore = false;
  String? _err;
  final List<Vendor> _vendors = [];

  @override
  void initState() {
    super.initState();
    _search.addListener(_onSearchChanged);
    _scroll.addListener(_onScroll);

    // Auto-focus search like Swiggy.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _focus.requestFocus();
    });

    // Prefer default address location; if absent, fall back to GPS.
    ref.listen<AsyncValue<Map<String, dynamic>?>>(defaultAddressProvider, (prev, next) {
      next.when(
        data: (addr) {
          final lat = (addr?['lat'] as num?)?.toDouble();
          final lng = (addr?['lng'] as num?)?.toDouble();

          if (lat == null || lng == null) {
            if (_locationSource == _LocationSource.none) {
              unawaited(_useGpsLocation());
            }
            return;
          }

          // If user explicitly switched to GPS, don't override.
          if (_locationSource == _LocationSource.gps) return;

          final changed = _lat != lat || _lng != lng || _locationSource != _LocationSource.defaultAddress;
          setState(() {
            _lat = lat;
            _lng = lng;
            _locationSource = _LocationSource.defaultAddress;
          });

          if (changed) {
            unawaited(_loadFirstPage());
          }
        },
        error: (_, __) {
          if (_locationSource == _LocationSource.none) {
            unawaited(_useGpsLocation());
          }
        },
        loading: () {},
      );
    });

    // Quick initial load (no location); refresh once location resolves.
    unawaited(_loadFirstPage());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.removeListener(_onSearchChanged);
    _scroll.removeListener(_onScroll);
    _search.dispose();
    _scroll.dispose();
    _focus.dispose();
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

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
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
        openOnly: false,
        deliverableOnly: false,
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

      if (changed) {
        await _loadFirstPage();
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Using current location')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
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

    if (changed) {
      unawaited(_loadFirstPage());
    }
  }

  @override
  Widget build(BuildContext context) {
    final addrAsync = ref.watch(defaultAddressProvider);
    final addr = addrAsync.valueOrNull;

    final showUsingGps = _locationSource == _LocationSource.gps;
    final label = (addr?['label'] ?? '').toString().trim();
    final line1 = (addr?['line1'] ?? '').toString().trim();

    final locationText = showUsingGps
        ? 'Using current location'
        : (label.isNotEmpty ? label : (addrAsync.isLoading ? 'Loading address…' : 'No saved address'));

    return Scaffold(
      appBar: AppBar(
        title: TextField(
          controller: _search,
          focusNode: _focus,
          textInputAction: TextInputAction.search,
          decoration: InputDecoration(
            hintText: 'Search for restaurants, stores…',
            border: InputBorder.none,
            prefixIcon: const Icon(Icons.search),
            suffixIcon: _q.isEmpty
                ? null
                : IconButton(
                    onPressed: () => _search.clear(),
                    icon: const Icon(Icons.close),
                  ),
          ),
        ),
        actions: [
          IconButton(
            tooltip: 'Use current location',
            onPressed: _loadingGps ? null : _useGpsLocation,
            icon: _loadingGps
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.my_location),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
              child: Row(
                children: [
                  const Icon(Icons.location_on_outlined, size: 18),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      showUsingGps ? locationText : (line1.isNotEmpty ? '$locationText • $line1' : locationText),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                  if (showUsingGps && addr != null)
                    TextButton(
                      onPressed: () => _useDefaultAddress(addr),
                      child: const Text('Use address'),
                    ),
                  TextButton(
                    onPressed: () => context.push('/addresses'),
                    child: const Text('Change'),
                  ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: _loadFirstPage,
                child: _buildList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildList() {
    if (_q.isEmpty && _vendors.isEmpty && !_initialLoading && _err == null) {
      return ListView(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          const SizedBox(height: 20),
          const Text('Try searching for:', style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 10),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _suggestionChip('Biryani'),
              _suggestionChip('Pizza'),
              _suggestionChip('Groceries'),
              _suggestionChip('Bakery'),
              _suggestionChip('South Indian'),
              _suggestionChip('Chinese'),
            ],
          ),
          const SizedBox(height: 24),
          const Divider(),
          const SizedBox(height: 12),
          const Text('Start typing to see results.', textAlign: TextAlign.center),
        ],
      );
    }

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
        padding: const EdgeInsets.all(16),
        children: [
          const SizedBox(height: 32),
          const Icon(Icons.wifi_off, size: 40),
          const SizedBox(height: 12),
          Text('Could not load results.\n$_err', textAlign: TextAlign.center),
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
        padding: const EdgeInsets.all(16),
        children: const [
          SizedBox(height: 60),
          Icon(Icons.search_off, size: 48),
          SizedBox(height: 12),
          Center(child: Text('No results found')),
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
          if (_loadingMore) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator()),
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

        return ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
          title: Text(v.name, style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: Text(
            v.description.trim().isNotEmpty ? v.description : v.address,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          leading: CircleAvatar(
            backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            child: Icon(openNow ? Icons.storefront : Icons.storefront_outlined),
          ),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => context.push('/vendor', extra: v),
        );
      },
    );
  }

  Widget _suggestionChip(String text) {
    return ActionChip(
      label: Text(text),
      onPressed: () {
        _search.text = text;
        _search.selection = TextSelection.fromPosition(TextPosition(offset: _search.text.length));
      },
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.06),
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                box(w: 180, h: 14),
                const SizedBox(height: 8),
                box(w: 240, h: 12),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
