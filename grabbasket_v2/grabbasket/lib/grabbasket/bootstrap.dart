import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app_globals.dart';
import 'marketing/meta_events.dart';
import 'router.dart';

enum AppFlavor { customer, seller, partner }

/// Shared entrypoint for all flavors.
///
/// Swiggy-grade apps typically add:
/// - Crash reporting (Crashlytics/Sentry)
/// - Structured logging
/// - Feature flags / remote config
///
/// This file keeps hooks ready for that, while staying dependency-light.
void mainApp(AppFlavor flavor) {
  WidgetsFlutterBinding.ensureInitialized();

  // Global Flutter errors (framework layer).
  FlutterError.onError = (details) {
    FlutterError.presentError(details);
    if (kDebugMode) {
      debugPrint('FlutterError: ${details.exceptionAsString()}');
    }
  };

  // Red screen -> a user-friendly fallback in release.
  ErrorWidget.builder = (details) {
    return Material(
      color: Colors.white,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, size: 46),
              const SizedBox(height: 12),
              Text(
                'Something went wrong',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              if (kDebugMode)
                Text(
                  details.exceptionAsString(),
                  textAlign: TextAlign.center,
                )
              else
                const Text(
                  'Please reopen the app.',
                  textAlign: TextAlign.center,
                ),
            ],
          ),
        ),
      ),
    );
  };

  // Catch uncaught async errors (zone layer).
  runZonedGuarded(() {
    // Fire-and-forget: Meta SDK init should not block app startup.
    unawaited(MetaEvents.instance.init());

    runApp(ProviderScope(child: GrabbasketApp(flavor: flavor)));
  }, (error, stack) {
    if (kDebugMode) {
      debugPrint('Uncaught zone error: $error');
      debugPrint('$stack');
    }
  });
}

class GrabbasketApp extends StatelessWidget {
  final AppFlavor flavor;
  const GrabbasketApp({super.key, required this.flavor});

  @override
  Widget build(BuildContext context) {
    final title = switch (flavor) {
      AppFlavor.customer => 'Grabbasket',
      AppFlavor.seller => 'Grabbasket Seller',
      AppFlavor.partner => 'Grabbasket Partner',
    };

    return MaterialApp.router(
      debugShowCheckedModeBanner: false,
      title: title,
      scaffoldMessengerKey: AppGlobals.messengerKey,
      theme: ThemeData(useMaterial3: true, colorSchemeSeed: Colors.deepOrange),
      routerConfig: buildRouter(flavor),
    );
  }
}
