import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:permission_handler/permission_handler.dart';

import '../api.dart';
import '../app_globals.dart';

/// Required for Firebase background message delivery on Android.
/// This must be a top-level function.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase not configured or init failed; ignore.
  }
  // You can optionally send analytics/logs here later.
}

/// Best-effort push notifications init.
///
/// - Safe to call multiple times; only initializes once.
/// - If Firebase isn't configured (missing google-services.json / GoogleService-Info.plist),
///   this will silently no-op so the app can still run.
/// - Registers the FCM token with your backend (if your backend supports it).
class PushNotifications {
  PushNotifications._();

  static final PushNotifications instance = PushNotifications._();

  Future<void>? _initFuture;
  bool _listenersAttached = false;

  Future<void> tryInit({required Api api}) {
    _initFuture ??= _doInit(api);
    return _initFuture!;
  }

  Future<void> _doInit(Api api) async {
    // Register background handler early.
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    // 1) Firebase init
    try {
      await Firebase.initializeApp();
    } catch (_) {
      // Firebase not configured yet.
      _initFuture = null;
      return;
    }

    // Ensure FCM is active.
    try {
      await FirebaseMessaging.instance.setAutoInitEnabled(true);
    } catch (_) {}

    // 2) Permissions
    await _requestPermissionsBestEffort();

    // 3) Token registration
    await _registerCurrentToken(api);

    // 4) Token refresh
    FirebaseMessaging.instance.onTokenRefresh.listen((t) {
      api.registerFcmToken(t, platform: Platform.operatingSystem).catchError((_) {});
    });

    // 5) Foreground + tap handlers (attach once)
    if (!_listenersAttached) {
      _listenersAttached = true;

      FirebaseMessaging.onMessage.listen((RemoteMessage m) {
        final n = m.notification;
        final title = (n?.title ?? '').trim();
        final body = (n?.body ?? '').trim();

        if (title.isEmpty && body.isEmpty) return;

        // Swiggy-like behavior: surface something while user is in-app.
        AppGlobals.showSnack(
          title.isNotEmpty ? "$title${body.isNotEmpty ? " — $body" : ""}" : body,
        );
      });

      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage m) {
        final n = m.notification;
        final title = (n?.title ?? '').trim();
        final body = (n?.body ?? '').trim();
        if (title.isEmpty && body.isEmpty) return;

        // Later you can deep-link into OrderDetail using m.data (order_id).
        AppGlobals.showSnack(
          "Opened notification: ${title.isNotEmpty ? title : body}",
          duration: const Duration(seconds: 4),
        );
      });
    }
  }

  Future<void> _requestPermissionsBestEffort() async {
    // iOS/macOS permissions + foreground presentation
    try {
      if (Platform.isIOS || Platform.isMacOS) {
        await FirebaseMessaging.instance.requestPermission(
          alert: true,
          badge: true,
          sound: true,
        );
        await FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
          alert: true,
          badge: true,
          sound: true,
        );
      }
    } catch (_) {
      // Ignore permission issues.
    }

    // Android 13+ runtime notification permission (POST_NOTIFICATIONS).
    try {
      if (Platform.isAndroid) {
        final status = await Permission.notification.status;
        if (!status.isGranted) {
          await Permission.notification.request();
        }
      }
    } catch (_) {
      // Ignore; notifications are optional for app usability.
    }
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
