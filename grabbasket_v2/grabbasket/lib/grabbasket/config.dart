class AppConfig {
  // Android emulator: http://10.0.2.2:8000
  // Real device (same Wi-Fi): http://<YOUR_LAPTOP_LAN_IP>:8000
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000',
  );

  /// Backwards-compatible alias.
  ///
  /// Some files may reference `AppConfig.baseUrl`.
  static const String baseUrl = apiBaseUrl;
}
