class AppConfig {
  /// Base URL of the backend API.
  ///
  /// Override at build time:
  /// flutter run --dart-define=API_BASE_URL=https://api.example.com
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000',
  );

  /// Backwards-compatible alias.
  static const baseUrl = apiBaseUrl;

  /// Default currency for analytics / purchase events.
  static const defaultCurrency = String.fromEnvironment(
    'DEFAULT_CURRENCY',
    defaultValue: 'INR',
  );

  /// Optional: disable Meta events without removing the SDK.
  static const enableMetaEvents = bool.fromEnvironment(
    'ENABLE_META_EVENTS',
    defaultValue: true,
  );
}
