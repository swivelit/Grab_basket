import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../state.dart';
import '../models.dart';
import '../location.dart';
import '../bootstrap.dart';
import 'login.dart';
import 'vendor_menu.dart';
import 'orders.dart';

class CustomerHome extends ConsumerStatefulWidget {
  const CustomerHome({super.key});

  @override
  ConsumerState<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends ConsumerState<CustomerHome> {
  final _search = TextEditingController();
  double? _lat;
  double? _lng;
  bool _loadingLocation = false;

  Future<void> _fetchLocation() async {
    setState(() => _loadingLocation = true);
    try {
      final pos = await LocationService.getCurrent();
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _fetchLocation();
    _search.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Grabbasket'),
        actions: [
          IconButton(
            icon: const Icon(Icons.receipt_long),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OrdersScreen())),
          ),
          IconButton(
            icon: const Icon(Icons.login),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.customer))),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _search,
                    decoration: const InputDecoration(
                      prefixIcon: Icon(Icons.search),
                      hintText: 'Search restaurants / stores',
                      border: OutlineInputBorder(),
                      isDense: true,
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
                )
              ],
            ),
            const SizedBox(height: 12),
            Expanded(
              child: FutureBuilder<List<Vendor>>(
                future: api.vendors(lat: _lat, lng: _lng, q: _search.text, openOnly: false),
                builder: (context, snap) {
                  if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                  final vendors = snap.data!;
                  if (vendors.isEmpty) return const Center(child: Text('No stores found'));

                  return ListView.separated(
                    itemCount: vendors.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final v = vendors[i];
                      final openNow = v.openNow ?? v.isOpen;
                      final dist = v.distanceKm;
                      final canDeliver = v.canDeliver;
                      return ListTile(
                        title: Text(v.name),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (v.description.isNotEmpty)
                              Text(v.description, maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            Wrap(
                              spacing: 8,
                              runSpacing: 4,
                              children: [
                                _chip(openNow ? 'Open' : 'Closed', openNow ? Colors.green : Colors.red),
                                if (dist != null) _chip('${dist.toStringAsFixed(1)} km', Colors.blueGrey),
                                if (canDeliver != null) _chip(canDeliver ? 'Delivers' : 'Out of range', canDeliver ? Colors.green : Colors.orange),
                              ],
                            )
                          ],
                        ),
                        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VendorMenuScreen(vendor: v))),
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
