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

  @override
  void initState() {
    super.initState();
    _loadLocation();
  }

  Future<void> _loadLocation() async {
    try {
      final pos = await LocationService.getCurrent();
      setState(() { _lat = pos.latitude; _lng = pos.longitude; _err = null; });
    } catch (e) {
      setState(() { _err = e.toString(); });
    }
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
          Expanded(
            child: FutureBuilder<List<Vendor>>(
              future: api.vendors(lat: _lat, lng: _lng),
              builder: (context, snap) {
                if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                final vendors = snap.data!;
                if (vendors.isEmpty) {
                  return const Center(child: Text("No vendors deliver to your current location."));
                }
                return ListView.separated(
                  itemCount: vendors.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, i) {
                    final v = vendors[i];
                    return ListTile(
                      title: Text(v.name),
                      subtitle: Text(v.description),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VendorMenuScreen(vendor: v))),
                    );
                  },
                );
              },
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
