import 'package:dio/dio.dart';
import 'config.dart';
import 'models.dart';

class Api {
  final Dio _dio;

  Api({String? token})
      : _dio = Dio(BaseOptions(
          baseUrl: AppConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 20),
          headers: token != null ? {"Authorization": "Bearer $token"} : null,
        )) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (e, handler) {
          // Make errors readable in UI
          final msg = e.response?.data is Map && (e.response?.data as Map).containsKey("detail")
              ? (e.response?.data as Map)["detail"].toString()
              : e.message ?? "Request failed";
          handler.reject(
            DioException(
              requestOptions: e.requestOptions,
              response: e.response,
              type: e.type,
              error: msg,
            ),
          );
        },
      ),
    );
  }

  Future<TokenResponse> register({required String email, required String password, required String role}) async {
    final res = await _dio.post("/auth/register", data: {"email": email, "password": password, "role": role});
    return TokenResponse.fromJson(res.data);
  }

  Future<TokenResponse> login({required String email, required String password}) async {
    final res = await _dio.post("/auth/login", data: {"email": email, "password": password});
    return TokenResponse.fromJson(res.data);
  }

  // Vendors
  Future<List<Vendor>> vendors({double? lat, double? lng, String? q}) async {
    final res = await _dio.get("/vendors", queryParameters: {
      if (lat != null) "lat": lat,
      if (lng != null) "lng": lng,
      if (q != null && q.trim().isNotEmpty) "q": q.trim(),
      "limit": 100,
      "offset": 0,
    });
    return (res.data as List).map((x) => Vendor.fromJson(x)).toList();
  }

  Future<List<Product>> products(int vendorId, {String? q}) async {
    final res = await _dio.get("/vendors/$vendorId/products", queryParameters: {
      if (q != null && q.trim().isNotEmpty) "q": q.trim(),
      "limit": 500,
      "offset": 0,
    });
    return (res.data as List).map((x) => Product.fromJson(x)).toList();
  }

  // Addresses
  Future<List<Map<String, dynamic>>> addresses() async {
    final res = await _dio.get("/addresses");
    return (res.data as List).cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> createAddress(Map<String, dynamic> data) async {
    final res = await _dio.post("/addresses", data: data);
    return (res.data as Map).cast<String, dynamic>();
  }

  Future<void> setDefaultAddress(int id) async {
    await _dio.post("/addresses/$id/default");
  }

  // Orders
  Future<Order> createOrder({
    required int vendorId,
    required List<Map<String, dynamic>> items,
    required int deliveryAddressId,
    required String paymentMethod, // COD / UPI
  }) async {
    final res = await _dio.post("/orders", data: {
      "vendor_id": vendorId,
      "items": items,
      "delivery_address_id": deliveryAddressId,
      "payment_method": paymentMethod,
    });
    return Order.fromJson(res.data);
  }

  Future<List<Order>> myOrders() async {
    final res = await _dio.get("/orders/me");
    return (res.data as List).map((x) => Order.fromJson(x)).toList();
  }

  Future<Order> getOrder(int orderId) async {
    final res = await _dio.get("/orders/$orderId");
    return Order.fromJson(res.data);
  }

  Future<Order> cancelOrder(int orderId, {String reason = ""}) async {
    final res = await _dio.post("/orders/$orderId/cancel", queryParameters: {
      if (reason.trim().isNotEmpty) "reason": reason.trim(),
    });
    return Order.fromJson(res.data);
  }

  // Tracking
  Future<Map<String, dynamic>> partnerLatestForOrder(int orderId) async {
    final res = await _dio.get("/tracking/order/$orderId/partner_latest");
    return (res.data as Map).cast<String, dynamic>();
  }

  // Seller
  Future<void> sellerCreateVendor({required String name, String description = "", String address = ""}) async {
    await _dio.post("/seller/vendor", queryParameters: {"name": name, "description": description, "address": address});
  }

  Future<void> sellerVendorSettings({
    double? lat,
    double? lng,
    required double radiusKm,
    required bool isOpen,
    String? openTime, // "09:00"
    String? closeTime,
  }) async {
    await _dio.post("/seller/vendor/settings", data: {
      "lat": lat,
      "lng": lng,
      "delivery_radius_km": radiusKm,
      "is_open": isOpen,
      "open_time": openTime,
      "close_time": closeTime,
    });
  }

  Future<List<Product>> sellerProducts() async {
    final res = await _dio.get("/seller/products");
    return (res.data as List).map((x) => Product.fromJson(x)).toList();
  }

  Future<Product> sellerCreateProduct(ProductUpsert p) async {
    final res = await _dio.post("/seller/products", data: p.toJson());
    return Product.fromJson(res.data);
  }

  Future<Product> sellerUpdateProduct(int id, ProductUpsert p) async {
    final res = await _dio.put("/seller/products/$id", data: p.toJson());
    return Product.fromJson(res.data);
  }

  Future<List<Order>> sellerOrders() async {
    final res = await _dio.get("/seller/orders");
    return (res.data as List).map((x) => Order.fromJson(x)).toList();
  }

  Future<Order> sellerAcceptOrder(int orderId) async {
    final res = await _dio.post("/seller/orders/$orderId/accept");
    return Order.fromJson(res.data);
  }

  Future<Order> sellerRejectOrder(int orderId, {String reason = ""}) async {
    final res = await _dio.post("/seller/orders/$orderId/reject", queryParameters: {
      if (reason.trim().isNotEmpty) "reason": reason.trim(),
    });
    return Order.fromJson(res.data);
  }

  Future<Order> sellerMarkReady(int orderId) async {
    final res = await _dio.post("/seller/orders/$orderId/ready");
    return Order.fromJson(res.data);
  }

  // Partner
  Future<void> partnerAvailability(bool isAvailable) async {
    await _dio.post("/partner/availability", queryParameters: {"is_available": isAvailable});
  }

  Future<void> partnerSendLocation(double lat, double lng, {double? heading, double? speed}) async {
    await _dio.post("/partner/location", data: {"lat": lat, "lng": lng, "heading": heading, "speed": speed});
  }

  Future<List<Order>> partnerOrders() async {
    final res = await _dio.get("/partner/orders");
    return (res.data as List).map((x) => Order.fromJson(x)).toList();
  }

  Future<Order> partnerPickup(int orderId) async {
    final res = await _dio.post("/partner/orders/$orderId/pickup");
    return Order.fromJson(res.data);
  }

  Future<Order> partnerDeliver(int orderId) async {
    final res = await _dio.post("/partner/orders/$orderId/deliver");
    return Order.fromJson(res.data);
  }
}
