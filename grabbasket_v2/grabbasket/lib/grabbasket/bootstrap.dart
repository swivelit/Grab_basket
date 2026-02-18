import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app_globals.dart';
import 'marketing/meta_events.dart';
import 'router.dart';

enum AppFlavor { customer, seller, partner }

void mainApp(AppFlavor flavor) {
  WidgetsFlutterBinding.ensureInitialized();

  // Fire-and-forget: Meta SDK init should not block app startup.
  unawaited(MetaEvents.instance.init());

  runApp(ProviderScope(child: GrabbasketApp(flavor: flavor)));
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
