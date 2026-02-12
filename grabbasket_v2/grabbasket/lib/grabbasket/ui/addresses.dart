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
  bool _busy = false;

  Future<void> _addFromCurrent() async {
    final api = ref.read(apiProvider);

    setState(() => _busy = true);
    try {
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
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Address saved")));
      setState(() {});
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _setDefault(int id) async {
    final api = ref.read(apiProvider);

    setState(() => _busy = true);
    try {
      await api.setDefaultAddress(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Default address updated")));
      setState(() {});
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = ref.watch(apiProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text("Addresses"),
        actions: [
          IconButton(
            onPressed: _busy ? null : () => setState(() {}),
            icon: const Icon(Icons.refresh),
            tooltip: "Refresh",
          ),
        ],
      ),
      body: Stack(
        children: [
          FutureBuilder(
            future: api.addresses(),
            builder: (context, snap) {
              if (!snap.hasData) return const Center(child: CircularProgressIndicator());
              final rows = snap.data as List<Map<String, dynamic>>;
              if (rows.isEmpty) return const Center(child: Text("No addresses yet. Tap 'Use current' to add one."));
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
                    onTap: _busy ? null : () => _setDefault(a["id"] as int),
                  );
                },
              );
            },
          ),
          if (_busy)
            const Positioned.fill(
              child: IgnorePointer(
                child: ColoredBox(
                  color: Color(0x22000000),
                  child: Center(child: CircularProgressIndicator()),
                ),
              ),
            ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _busy ? null : _addFromCurrent,
        icon: const Icon(Icons.add_location),
        label: const Text("Use current"),
      ),
    );
  }
}
