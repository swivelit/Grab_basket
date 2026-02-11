import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../state.dart';
import '../location.dart';

class AddressesScreen extends ConsumerStatefulWidget {
  const AddressesScreen({super.key});

  @override
  ConsumerState<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends ConsumerState<AddressesScreen> {
  Future<void> _addFromCurrent() async {
    final api = ref.read(apiProvider);
    final pos = await LocationService.getCurrent();
    await api.createAddress({
      "label": "Current",
      "line1": "Current Location",
      "line2": "",
      "city": "",
      "pincode": "",
      "lat": pos.latitude,
      "lng": pos.longitude,
      "is_default": true,
    });
    if (!mounted) return;
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);
    return Scaffold(
      appBar: AppBar(title: const Text("Addresses")),
      body: FutureBuilder(
        future: api.addresses(),
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: CircularProgressIndicator());
          final rows = snap.data as List<Map<String, dynamic>>;
          if (rows.isEmpty) return const Center(child: Text("No addresses yet. Add one."));
          return ListView.separated(
            itemCount: rows.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final a = rows[i];
              final isDefault = a["is_default"] == true;
              return ListTile(
                title: Text("${a["label"]} ${isDefault ? "• Default" : ""}"),
                subtitle: Text("${a["line1"]}"),
                trailing: isDefault ? const Icon(Icons.check_circle) : null,
                onTap: () async {
                  await api.setDefaultAddress(a["id"] as int);
                  setState(() {});
                },
              );
            },
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addFromCurrent,
        icon: const Icon(Icons.add_location),
        label: const Text("Use current"),
      ),
    );
  }
}
