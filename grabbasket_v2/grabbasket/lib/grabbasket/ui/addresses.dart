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

  Future<void> _addManually() async {
    final result = await showModalBottomSheet<_AddAddressResult>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => const _AddAddressSheet(),
    );

    if (result == null) return;

    final api = ref.read(apiProvider);

    setState(() => _busy = true);
    try {
      await api.createAddress(result.payload);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Address saved')));
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _openAddMenu() async {
    final choice = await showModalBottomSheet<_AddMenuChoice>(
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
                Text(
                  'Add address',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 12),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(child: Icon(Icons.my_location)),
                  title: const Text('Use current location'),
                  subtitle: const Text('Quickly save your current GPS as an address'),
                  onTap: () => Navigator.of(context).pop(_AddMenuChoice.current),
                ),
                const Divider(height: 1),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(child: Icon(Icons.edit_location_alt_outlined)),
                  title: const Text('Add details manually'),
                  subtitle: const Text('Enter address lines; we’ll use a GPS pin'),
                  onTap: () => Navigator.of(context).pop(_AddMenuChoice.manual),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (!mounted || choice == null) return;
    if (choice == _AddMenuChoice.current) {
      await _addFromCurrent();
    } else {
      await _addManually();
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
                      Center(child: Text("No addresses yet. Tap 'Add address' to add one.")),
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
        onPressed: _busy ? null : _openAddMenu,
        icon: const Icon(Icons.add_location_alt_outlined),
        label: const Text('Add address'),
      ),
    );
  }
}

enum _AddMenuChoice { current, manual }

class _AddAddressResult {
  final Map<String, dynamic> payload;
  const _AddAddressResult(this.payload);
}

class _AddAddressSheet extends StatefulWidget {
  const _AddAddressSheet();

  @override
  State<_AddAddressSheet> createState() => _AddAddressSheetState();
}

class _AddAddressSheetState extends State<_AddAddressSheet> {
  final _formKey = GlobalKey<FormState>();
  final _label = TextEditingController(text: 'Home');
  final _line1 = TextEditingController();
  final _line2 = TextEditingController();
  final _city = TextEditingController();
  final _pincode = TextEditingController();

  bool _makeDefault = true;

  bool _locating = false;
  String? _locErr;
  double? _lat;
  double? _lng;

  @override
  void initState() {
    super.initState();
    _fetchLocation();
  }

  @override
  void dispose() {
    _label.dispose();
    _line1.dispose();
    _line2.dispose();
    _city.dispose();
    _pincode.dispose();
    super.dispose();
  }

  Future<void> _fetchLocation() async {
    if (_locating) return;
    setState(() {
      _locating = true;
      _locErr = null;
    });

    try {
      final pos = await LocationService.getCurrent();
      if (!mounted) return;
      setState(() {
        _lat = pos.latitude;
        _lng = pos.longitude;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _locErr = e.toString();
        _lat = null;
        _lng = null;
      });
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  void _submit() {
    final ok = _formKey.currentState?.validate() ?? false;
    if (!ok) return;

    if (_lat == null || _lng == null) {
      setState(() => _locErr = 'We need a GPS pin to save this address. Tap “Use current location”.');
      return;
    }

    final payload = <String, dynamic>{
      'label': _label.text.trim().isEmpty ? 'Home' : _label.text.trim(),
      'line1': _line1.text.trim(),
      'line2': _line2.text.trim(),
      'city': _city.text.trim(),
      'pincode': _pincode.text.trim(),
      'lat': _lat,
      'lng': _lng,
      'is_default': _makeDefault,
    };

    Navigator.of(context).pop(_AddAddressResult(payload));
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.only(bottom: bottomInset),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Add address',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 6),
              Text(
                'Enter address details. We’ll attach a GPS pin (required by the backend).',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: 12),
              _locationCard(context),
              const SizedBox(height: 12),
              Form(
                key: _formKey,
                child: Column(
                  children: [
                    TextFormField(
                      controller: _label,
                      textCapitalization: TextCapitalization.words,
                      decoration: const InputDecoration(
                        labelText: 'Label (Home / Work / Other)',
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) {
                        final s = (v ?? '').trim();
                        if (s.isEmpty) return 'Label is required';
                        if (s.length > 32) return 'Keep label under 32 chars';
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _line1,
                      textCapitalization: TextCapitalization.sentences,
                      decoration: const InputDecoration(
                        labelText: 'Address line 1',
                        hintText: 'House / Flat, Street, Area',
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) {
                        final s = (v ?? '').trim();
                        if (s.length < 3) return 'Enter a valid address';
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _line2,
                      textCapitalization: TextCapitalization.sentences,
                      decoration: const InputDecoration(
                        labelText: 'Address line 2 (optional)',
                        hintText: 'Landmark, etc.',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _city,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(
                              labelText: 'City (optional)',
                              border: OutlineInputBorder(),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _pincode,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Pincode (optional)',
                              border: OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Make this my default'),
                      value: _makeDefault,
                      onChanged: (v) => setState(() => _makeDefault = v),
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: _submit,
                        child: const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Text('Save address'),
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Tip: For Swiggy-like precision you’ll eventually want a “Pick on map” flow. This is a clean interim UX until we add map support.',
                      style: Theme.of(context).textTheme.bodySmall,
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _locationCard(BuildContext context) {
    final hasPin = _lat != null && _lng != null;
    final pinText = hasPin ? '${_lat!.toStringAsFixed(5)}, ${_lng!.toStringAsFixed(5)}' : 'No pin yet';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceVariant,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.my_location, size: 18),
              const SizedBox(width: 8),
              const Expanded(
                child: Text('GPS pin', style: TextStyle(fontWeight: FontWeight.w800)),
              ),
              TextButton.icon(
                onPressed: _locating ? null : _fetchLocation,
                icon: _locating
                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.refresh, size: 18),
                label: const Text('Use current location'),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(pinText, style: const TextStyle(fontWeight: FontWeight.w700)),
          if (_locErr != null) ...[
            const SizedBox(height: 8),
            Text(
              _locErr!,
              style: TextStyle(color: Theme.of(context).colorScheme.error, fontWeight: FontWeight.w600),
            ),
          ],
        ],
      ),
    );
  }
}
