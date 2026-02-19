import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../bootstrap.dart';
import '../notifications/push_notifications.dart';
import '../state.dart';
import 'login.dart';

class AppGate extends ConsumerStatefulWidget {
  final AppFlavor flavor;
  final Widget child;

  const AppGate({super.key, required this.flavor, required this.child});

  @override
  ConsumerState<AppGate> createState() => _AppGateState();
}

class _AppGateState extends ConsumerState<AppGate> {
  bool _pushInitAttempted = false;
  bool _cartRestoreAttempted = false;

  String get _expectedRole => switch (widget.flavor) {
        AppFlavor.customer => "CUSTOMER",
        AppFlavor.seller => "SELLER",
        AppFlavor.partner => "PARTNER",
      };

  void _maybeInitPush() {
    if (_pushInitAttempted) return;
    _pushInitAttempted = true;

    // Fire-and-forget: if Firebase isn't configured yet, init will no-op.
    unawaited(PushNotifications.instance.tryInit(api: ref.read(apiProvider)));
  }

  void _maybeRestoreCart() {
    if (_cartRestoreAttempted) return;
    _cartRestoreAttempted = true;

    if (widget.flavor != AppFlavor.customer) return;

    // Fire-and-forget; restores persisted cart after login.
    unawaited(ref.read(cartProvider.notifier).restore());
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(sessionProvider);

    return session.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.error_outline, size: 40),
                const SizedBox(height: 12),
                Text("Failed to load session:\n$e", textAlign: TextAlign.center),
              ],
            ),
          ),
        ),
      ),
      data: (s) {
        final token = (s.token ?? "").trim();
        final role = (s.role ?? "").toUpperCase().trim();

        // No token -> login.
        if (token.isEmpty) {
          return LoginScreen(flavor: widget.flavor);
        }

        // Token exists but role mismatch -> force re-login for this flavor.
        if (role.isNotEmpty && role != _expectedRole) {
          return LoginScreen(flavor: widget.flavor);
        }

        // Token is present and role matches: initialize push notifications.
        _maybeInitPush();

        // Customer-only: restore persisted cart.
        _maybeRestoreCart();

        return widget.child;
      },
    );
  }
}
