import 'package:flutter/material.dart';

/// App-wide global keys and helpers.
/// Keep this small and focused (snackbars, global navigation if needed later).
class AppGlobals {
  static final GlobalKey<ScaffoldMessengerState> messengerKey = GlobalKey<ScaffoldMessengerState>();

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
}
