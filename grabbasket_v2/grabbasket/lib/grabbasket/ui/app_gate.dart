import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../bootstrap.dart';
import '../state.dart';
import 'login.dart';

class AppGate extends ConsumerWidget {
  final AppFlavor flavor;
  final Widget child;

  const AppGate({super.key, required this.flavor, required this.child});

  String get _expectedRole => switch (flavor) {
        AppFlavor.customer => "CUSTOMER",
        AppFlavor.seller => "SELLER",
        AppFlavor.partner => "PARTNER",
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);

    return session.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Text("Failed to load session: $e"),
          ),
        ),
      ),
      data: (s) {
        final token = (s.token ?? "").trim();
        final role = (s.role ?? "").toUpperCase().trim();

        // No token -> login.
        if (token.isEmpty) {
          return LoginScreen(flavor: flavor);
        }

        // Token exists but role mismatch -> force re-login for this flavor.
        if (role.isNotEmpty && role != _expectedRole) {
          return LoginScreen(flavor: flavor);
        }

        return child;
      },
    );
  }
}
