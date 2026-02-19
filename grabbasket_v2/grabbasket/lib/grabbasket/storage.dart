import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Abstraction for session persistence.
///
/// Why:
/// - Allows easy mocking in tests (FlutterSecureStorage needs platform channels).
/// - Lets you swap implementations later (e.g., web/desktop).
abstract class SessionStore {
  Future<void> saveSession({required String token, required String role});

  Future<String?> get token;
  Future<String?> get role;

  Future<void> clear();
}

/// Production session store (Android/iOS).
class SecureStore implements SessionStore {
  static const _kToken = "token";
  static const _kRole = "role";

  final FlutterSecureStorage _s = const FlutterSecureStorage();

  @override
  Future<void> saveSession({required String token, required String role}) async {
    await _s.write(key: _kToken, value: token);
    await _s.write(key: _kRole, value: role);
  }

  @override
  Future<String?> get token async => _s.read(key: _kToken);

  @override
  Future<String?> get role async => _s.read(key: _kRole);

  @override
  Future<void> clear() async {
    await _s.delete(key: _kToken);
    await _s.delete(key: _kRole);
  }
}

/// Simple in-memory store (useful for widget tests / desktop prototypes).
class MemorySessionStore implements SessionStore {
  String? _token;
  String? _role;

  MemorySessionStore({String? token, String? role})
      : _token = token,
        _role = role;

  @override
  Future<void> saveSession({required String token, required String role}) async {
    _token = token;
    _role = role;
  }

  @override
  Future<String?> get token async => _token;

  @override
  Future<String?> get role async => _role;

  @override
  Future<void> clear() async {
    _token = null;
    _role = null;
  }
}
