import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../state.dart';

class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  Future<void> _logout(BuildContext context, WidgetRef ref) async {
    await ref.read(secureStoreProvider).clear();
    ref.read(cartProvider.notifier).clear();
    ref.invalidate(sessionProvider);

    if (context.mounted) {
      context.go('/');
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: scheme.primaryContainer,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: scheme.primary,
                    child: Icon(Icons.person, color: scheme.onPrimary),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Grabbasket user', style: TextStyle(fontWeight: FontWeight.w800)),
                        SizedBox(height: 4),
                        Text('Profile details coming soon'),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Common actions
            _tile(
              context,
              icon: Icons.location_on_outlined,
              title: 'Addresses',
              subtitle: 'Manage delivery locations',
              onTap: () => context.push('/addresses'),
            ),
            _tile(
              context,
              icon: Icons.receipt_long,
              title: 'Orders',
              subtitle: 'Track current & past orders',
              onTap: () => context.go('/orders'),
            ),
            _tile(
              context,
              icon: Icons.support_agent,
              title: 'Help & Support',
              subtitle: 'FAQs and contact',
              onTap: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Help & Support coming soon.')),
                );
              },
            ),

            const SizedBox(height: 10),
            const Divider(),
            const SizedBox(height: 10),

            // Swiggy-like “more” section placeholders
            Text('More', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 10),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _pill(context, 'Instamart', Icons.shopping_cart_outlined),
                _pill(context, 'Dineout', Icons.restaurant_outlined),
                _pill(context, 'Genie', Icons.local_shipping_outlined),
                _pill(context, 'Offers', Icons.local_offer_outlined),
              ],
            ),
            const SizedBox(height: 18),

            FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: scheme.error,
                foregroundColor: scheme.onError,
              ),
              onPressed: () => _logout(context, ref),
              icon: const Icon(Icons.logout),
              label: const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('Logout'),
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Note: Some Swiggy-like features are placeholders until the backend supports them.',
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _tile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor: Theme.of(context).colorScheme.surfaceVariant,
        child: Icon(icon),
      ),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
      subtitle: Text(subtitle),
      trailing: const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }

  Widget _pill(BuildContext context, String label, IconData icon) {
    return ActionChip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
      onPressed: () {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$label coming soon.')),
        );
      },
    );
  }
}
