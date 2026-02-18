import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../location.dart';
import '../state.dart';

class AddressesScreen extends ConsumerStatefulWidget {
  const AddressesScreen({super.key});

  @override
  ConsumerState<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends ConsumerState<AddressesScreen> {
  bool _busy = false;
  Future<List<Map<String, dynamic>>>? _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiProvider).addresses();
  }

  Future<void> _reload() async {
    setState(() => _future = ref.read(apiProvider).addresses());
    await _future;

    // Keep other screens (Home header, Search, etc.) in sync.
    ref.invalidate(addressesProvider);
    ref.invalidate(defaultAddressProvider);
  }

  Future<void> _addFromCurrent() async {
    final api = ref.read(apiProvider);

    setState(() => _busy = true);
    try {
      final pos = await LocationService.getCurrent();
      await api.createAddress({
        'label': 'Current',
        'line1': 'Current Location',
        'line2': '',
        'city': '',
        'pincode': '',
        'lat': pos.latitude,
        'lng': pos.longitude,
        'is_default': true,
      });

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Address saved')));

      // Refresh local list + global providers.
      await _reload();
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
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Default address updated')));

      // Refresh local list + global providers.
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final future = _future ?? ref.read(apiProvider).addresses();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Addresses'),
        actions: [
          IconButton(
            onPressed: _busy ? null : _reload,
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: _reload,
            child: FutureBuilder<List<Map<String, dynamic>>>(
              future: future,
              builder: (context, snap) {
                if (snap.connectionState == ConnectionState.waiting && !snap.hasData) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snap.hasError) {
                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      const SizedBox(height: 32),
                      const Icon(Icons.error_outline, size: 40),
                      const SizedBox(height: 12),
                      Text('Failed to load addresses.\n${snap.error}', textAlign: TextAlign.center),
                      const SizedBox(height: 12),
                      Center(
                        child: FilledButton.icon(
                          onPressed: _busy ? null : _reload,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Retry'),
                        ),
                      ),
                    ],
                  );
                }

                final rows = snap.data ?? [];
                if (rows.isEmpty) {
                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 48),
                      Icon(Icons.location_on_outlined, size: 48),
                      SizedBox(height: 12),
                      Center(child: Text("No addresses yet. Tap 'Use current' to add one.")),
                    ],
                  );
                }

                return ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  itemCount: rows.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, i) {
                    final a = rows[i];
                    final isDefault = a['is_default'] == true;
                    final label = (a['label'] ?? '').toString();
                    final line1 = (a['line1'] ?? '').toString();

                    return ListTile(
                      title: Text(label.isEmpty ? (isDefault ? 'Default' : 'Address') : '$label${isDefault ? ' • Default' : ''}'),
                      subtitle: Text(line1),
                      trailing: isDefault ? const Icon(Icons.check_circle) : null,
                      onTap: _busy ? null : () => _setDefault(a['id'] as int),
                    );
                  },
                );
              },
            ),
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
        label: const Text('Use current'),
      ),
    );
  }
}
