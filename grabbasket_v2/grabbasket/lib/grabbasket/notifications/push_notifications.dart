import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import '../api.dart';

/// Best-effort push notifications init.
///
/// Notes:
/// - Safe to call multiple times; only initializes once.
/// - If Firebase is not configured (missing google-services.json / GoogleService-Info.plist),
///   this will silently no-op so the app can still run.
/// - Registers the FCM token with your backend (if your backend supports it).
class PushNotifications {
  PushNotifications._();

  static final PushNotifications instance = PushNotifications._();

  Future<void>? _initFuture;

  Future<void> tryInit({required Api api}) {
    _initFuture ??= _doInit(api);
    return _initFuture!;
  }

  Future<void> _doInit(Api api) async {
    // 1) Firebase init
    try {
      await Firebase.initializeApp();
    } catch (_) {
      // Firebase not configured yet.
      _initFuture = null;
      return;
    }

    // 2) Permissions (iOS/macOS only; Android handled by manifest/runtime)
    try {
      if (Platform.isIOS || Platform.isMacOS) {
        await FirebaseMessaging.instance.requestPermission(
          alert: true,
          badge: true,
          sound: true,
        );
      }
    } catch (_) {
      // Ignore permission issues.
    }

    // 3) Token registration
    await _registerCurrentToken(api);

    // 4) Token refresh
    FirebaseMessaging.instance.onTokenRefresh.listen((t) {
      // Fire-and-forget; don't crash if backend unreachable.
      api.registerFcmToken(t, platform: Platform.operatingSystem).catchError((_) {});
    });
  }

  Future<void> _registerCurrentToken(Api api) async {
    try {
      final t = await FirebaseMessaging.instance.getToken();
      if (t == null || t.trim().isEmpty) return;
      await api.registerFcmToken(t, platform: Platform.operatingSystem);
    } catch (_) {
      // Ignore; notifications are optional for app usability.
    }
  }
}
