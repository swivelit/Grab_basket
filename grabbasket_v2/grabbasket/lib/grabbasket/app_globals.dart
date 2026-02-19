import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// App-wide global keys and helpers.
///
/// Keep this small and focused:
/// - snackbars
/// - safe navigation from background services (push notifications)
class AppGlobals {
  static final GlobalKey<ScaffoldMessengerState> messengerKey = GlobalKey<ScaffoldMessengerState>();

  /// Used by GoRouter as the root navigator key so we can navigate from anywhere.
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  static BuildContext? get _navContext => navigatorKey.currentContext;

  static void showSnack(
    String message, {
    SnackBarAction? action,
    Duration duration = const Duration(seconds: 3),
  }) {
    final messenger = messengerKey.currentState;
    if (messenger == null) return;

    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        content: Text(message),
        action: action,
        duration: duration,
      ),
    );
  }

  /// Navigate using GoRouter, waiting briefly until the UI is ready.
  static Future<void> goWhenReady(
    String location, {
    int retries = 12,
    Duration retryDelay = const Duration(milliseconds: 150),
  }) async {
    for (var i = 0; i < retries; i++) {
      final ctx = _navContext;
      if (ctx != null) {
        try {
          GoRouter.of(ctx).go(location);
        } catch (_) {}
        return;
      }
      await Future.delayed(retryDelay);
    }
  }

  /// Push using GoRouter, waiting briefly until the UI is ready.
  static Future<void> pushWhenReady(
    String location, {
    int retries = 12,
    Duration retryDelay = const Duration(milliseconds: 150),
  }) async {
    for (var i = 0; i < retries; i++) {
      final ctx = _navContext;
      if (ctx != null) {
        try {
          GoRouter.of(ctx).push(location);
        } catch (_) {}
        return;
      }
      await Future.delayed(retryDelay);
    }
  }
}
