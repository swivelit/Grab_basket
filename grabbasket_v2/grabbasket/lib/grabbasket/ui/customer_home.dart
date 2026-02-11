import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../models.dart';
import '../location.dart';
import 'vendor_menu.dart';
import 'orders.dart';
import 'addresses.dart';

class CustomerHome extends ConsumerStatefulWidget {
  const CustomerHome({super.key});

  @override
  ConsumerState<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends ConsumerState<CustomerHome> {
  double? _lat;
  double? _lng;
  String? _err;

  final _search = TextEditingController();
  String _q = "";

  @override
  void initState() {
    super.initState();
    _loadLocation();
    _search.addListener(() {
      final next = _search.text.trim();
      if (next == _q) return;
      setState(() => _q = next);
    });
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _loadLocation() async {
    try {
      final pos = await LocationService.getCurrent();
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
        _err = null;
      });
    } catch (e) {
      setState(() {
        _err = e.toString();
      });
    }
  }

  String _subtitle(Vendor v) {
    final parts = <String>[];
    if (v.openNow == true) {
      parts.add("Open now");
    } else if (v.openNow == false) {
      parts.add("Closed");
    }

    if (v.distanceKm != null) {
      parts.add("${v.distanceKm!.toStringAsFixed(1)} km");
    }

    if (v.canDeliver == false) {
      parts.add("Out of delivery area");
    }

    final base = (v.description.isNotEmpty ? v.description : v.address).trim();
    if (base.isNotEmpty) parts.add(base);

    return parts.join(" • ");
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Grabbasket"),
        actions: [
          IconButton(
            icon: const Icon(Icons.location_on),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AddressesScreen())),
          ),
          IconButton(
            icon: const Icon(Icons.receipt_long),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OrdersScreen())),
          ),
        ],
      ),
      body: Column(
        children: [
          if (_err != null)
            Padding(
              padding: const EdgeInsets.all(12),
              child: Text("Location: $_err", style: const TextStyle(color: Colors.red)),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
            child: TextField(
              controller: _search,
              decoration: InputDecoration(
                hintText: "Search restaurants/stores",
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _q.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _search.clear();
                          FocusScope.of(context).unfocus();
                        },
                      ),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _loadLocation,
              child: FutureBuilder<List<Vendor>>(
                future: api.vendors(lat: _lat, lng: _lng, q: _q),
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return Center(child: Text("Failed: ${snap.error}"));
                  }
                  final vendors = snap.data ?? [];
                  if (vendors.isEmpty) {
                    return const Center(child: Text("No vendors found for your location/search."));
                  }
                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    itemCount: vendors.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final v = vendors[i];
                      final disabled = v.canDeliver == false;
                      return ListTile(
                        enabled: !disabled,
                        title: Text(v.name),
                        subtitle: Text(_subtitle(v)),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: disabled
                            ? null
                            : () => Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => VendorMenuScreen(vendor: v)),
                                ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _loadLocation,
        child: const Icon(Icons.my_location),
      ),
    );
  }
}
