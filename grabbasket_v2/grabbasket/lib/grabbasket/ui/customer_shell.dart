import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../state.dart';

/// Customer bottom navigation (Swiggy-style).
///
/// The shell keeps state for each tab via [StatefulShellRoute.indexedStack].
class CustomerShellScaffold extends ConsumerWidget {
  final StatefulNavigationShell navigationShell;

  const CustomerShellScaffold({super.key, required this.navigationShell});

  void _onTap(int index) {
    navigationShell.goBranch(
      index,
      // Re-tapping the active tab should pop to its root.
      initialLocation: index == navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cart = ref.watch(cartProvider);
    final cartCount = cart?.count ?? 0;

    // Minimal “floating” cart hint — Swiggy often shows a persistent cart bar.
    // We keep it subtle and only show when cart has items.
    final cartHint = cartCount > 0
        ? SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
              child: Material(
                elevation: 2,
                borderRadius: BorderRadius.circular(14),
                color: Theme.of(context).colorScheme.primary,
                child: InkWell(
                  borderRadius: BorderRadius.circular(14),
                  onTap: () => context.push('/cart'),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    child: Row(
                      children: [
                        const Icon(Icons.shopping_basket, color: Colors.white),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            '$cartCount item(s) in cart',
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                          ),
                        ),
                        const Text(
                          'View cart',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(width: 6),
                        const Icon(Icons.chevron_right, color: Colors.white),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          )
        : null;

    return Scaffold(
      body: Stack(
        children: [
          navigationShell,
          if (cartHint != null)
            Align(
              alignment: Alignment.bottomCenter,
              child: cartHint,
            ),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: navigationShell.currentIndex,
        onDestinationSelected: _onTap,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.restaurant_menu),
            label: 'Food',
          ),
          NavigationDestination(
            icon: Icon(Icons.search),
            label: 'Search',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_long),
            label: 'Orders',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            label: 'Account',
          ),
        ],
      ),
    );
  }
}
