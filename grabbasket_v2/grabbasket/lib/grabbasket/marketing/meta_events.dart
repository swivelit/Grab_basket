import 'package:facebook_app_events/facebook_app_events.dart';

import '../models.dart';
import '../state.dart';

/// Minimal Meta (Facebook) App Events wrapper.
///
/// Why a wrapper?
/// - Keeps Meta-specific code out of UI widgets
/// - Gives you one place to add/rename events later
/// - Lets the marketing team request events without touching business logic
class MetaEvents {
  MetaEvents._();

  static final MetaEvents instance = MetaEvents._();

  final FacebookAppEvents _fb = FacebookAppEvents();

  /// Call once on app startup.
  ///
  /// Notes:
  /// - Auto logging helps with install/launch signals.
  /// - Advertiser tracking is disabled by default here so you can enable it only
  ///   after showing consent (ATT / GDPR). If you want it ON by default, change
  ///   [enableAdvertiserTracking] to true.
  Future<void> init({
    bool enableAutoLogAppEvents = true,
    bool enableAdvertiserTracking = false,
    bool collectAdvertiserId = false,
  }) async {
    await _fb.setAutoLogAppEventsEnabled(enableAutoLogAppEvents);
    await _fb.setAdvertiserTracking(
      enabled: enableAdvertiserTracking,
      collectId: collectAdvertiserId,
    );

    // Explicitly mark app activation (harmless if auto-logging is on).
    await _fb.activateApp();
  }

  /// Enable advertiser tracking after user consent.
  Future<void> setAdvertiserTrackingEnabled(bool enabled) {
    return _fb.setAdvertiserTracking(enabled: enabled, collectId: enabled);
  }

  Future<void> logAddToCart({
    required Vendor vendor,
    required Product product,
    required String currency,
  }) {
    return _fb.logAddToCart(
      id: product.id.toString(),
      type: 'product',
      currency: currency,
      price: product.price,
      content: {
        'vendor_id': vendor.id.toString(),
        'vendor_name': vendor.name,
        'product_name': product.name,
        'product_vendor_id': product.vendorId.toString(),
      },
    );
  }

  Future<void> logInitiatedCheckout({
    required CartState cart,
    required String currency,
    String? paymentMethod,
  }) {
    return _fb.logInitiatedCheckout(
      totalPrice: cart.subtotal,
      currency: currency,
      numItems: cart.count,
      contentType: 'product',
      contentId: cart.vendorId.toString(),
      paymentInfoAvailable: (paymentMethod != null && paymentMethod.isNotEmpty),
    );
  }

  Future<void> logPurchase({
    required Order order,
    required String currency,
  }) {
    return _fb.logPurchase(
      amount: order.totalAmount,
      currency: currency,
      parameters: {
        'order_id': order.id.toString(),
        'vendor_id': order.vendorId.toString(),
        'payment_method': order.paymentMethod,
        'payment_status': order.paymentStatus,
        'items_count': order.items.length,
      },
    );
  }

  Future<void> logViewVendorMenu({
    required Vendor vendor,
    required String currency,
  }) {
    // Optional: helps build audiences based on vendor/menu views.
    return _fb.logViewContent(
      id: vendor.id.toString(),
      type: 'vendor_menu',
      currency: currency,
      content: {
        'vendor_id': vendor.id.toString(),
        'vendor_name': vendor.name,
      },
    );
  }
}
