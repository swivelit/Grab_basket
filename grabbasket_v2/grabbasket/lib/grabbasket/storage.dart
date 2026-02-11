import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStore {
  static const _kToken = "token";
  static const _kRole = "role";

  final FlutterSecureStorage _s = const FlutterSecureStorage();

  Future<void> saveSession({required String token, required String role}) async {
    await _s.write(key: _kToken, value: token);
    await _s.write(key: _kRole, value: role);
  }

  Future<String?> get token async => _s.read(key: _kToken);
  Future<String?> get role async => _s.read(key: _kRole);

  Future<void> clear() async {
    await _s.delete(key: _kToken);
    await _s.delete(key: _kRole);
  }
}
