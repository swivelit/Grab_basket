import 'package:dio/dio.dart';

import 'config.dart';
import 'models.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() => statusCode == null ? message : "[$statusCode] $message";
}

class Api {
  late final Dio _dio;

  Api({String? token}) {
    final options = BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 20),
      sendTimeout: const Duration(seconds: 10),
      headers: {
        if (token != null && token.isNotEmpty) "Authorization": "Bearer $token",
      },
    );

    _dio = Dio(options);
  }

  ApiException _mapErr(Object e) {
    if (e is DioException) {
      final status = e.response?.statusCode;
      final data = e.response?.data;
      if (data is Map && data["detail"] != null) {
        return ApiException(data["detail"].toString(), statusCode: status);
      }
      return ApiException(e.message ?? "Network error", statusCode: status);
    }
    return ApiException(e.toString());
  }

  // ---------------- Auth ----------------
  Future<({String token, String role})> login(String email, String password) async {
    try {
      final res = await _dio.post("/auth/login", data: {"email": email, "password": password});
      final m = (res.data as Map).cast<String, dynamic>();
      return (token: m["access_token"].toString(), role: m["role"].toString());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> register(String email, String password, String role) async {
    try {
      await _dio.post("/auth/register", data: {"email": email, "password": password, "role": role});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> registerFcmToken(String token, {String platform = "unknown"}) async {
    try {
      await _dio.post("/auth/fcm/register", data: {"token": token, "platform": platform});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Vendors / Products ----------------
  Future<List<Vendor>> vendors({double? lat, double? lng, String? q, bool openOnly = false}) async {
    try {
      final res = await _dio.get(
        "/vendors",
        queryParameters: {
          if (lat != null) "lat": lat,
          if (lng != null) "lng": lng,
          if (q != null && q.trim().isNotEmpty) "q": q.trim(),
          if (openOnly) "open_only": true,
        },
      );
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Vendor.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Product>> vendorProducts(int vendorId) async {
    try {
      final res = await _dio.get("/vendors/$vendorId/products");
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Product.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Orders ----------------
  Future<Order> createOrder({
    required int vendorId,
    required List<Map<String, dynamic>> items,
    int? deliveryAddressId,
    String paymentMethod = "COD",
  }) async {
    try {
      final res = await _dio.post("/orders", data: {
        "vendor_id": vendorId,
        "items": items,
        "delivery_address_id": deliveryAddressId,
        "payment_method": paymentMethod,
      });
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> myOrders() async {
    try {
      final res = await _dio.get("/orders/me");
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> getOrder(int orderId) async {
    try {
      final res = await _dio.get("/orders/$orderId");
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> orderTracking(int orderId) async {
    try {
      final res = await _dio.get("/orders/$orderId/tracking");
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> cancelOrder(int orderId) async {
    try {
      await _dio.post("/orders/$orderId/cancel");
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> partnerLatestForOrder(int orderId) async {
    try {
      final res = await _dio.get("/tracking/order/$orderId/partner_latest");
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Addresses (Customer) ----------------
  Future<List<Map<String, dynamic>>> addresses() async {
    try {
      final res = await _dio.get("/addresses");
      return (res.data as List).cast<Map<String, dynamic>>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> createAddress(Map<String, dynamic> payload) async {
    try {
      final res = await _dio.post("/addresses", data: payload);
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> setDefaultAddress(int addressId) async {
    try {
      await _dio.post("/addresses/$addressId/default");
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Seller ----------------
  Future<void> sellerCreateVendor({required String name}) async {
    try {
      await _dio.post("/seller/vendor", queryParameters: {"name": name});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> sellerOrders() async {
    try {
      final res = await _dio.get("/seller/orders");
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerAcceptOrder(int orderId) async {
    try {
      final res = await _dio.post("/seller/orders/$orderId/accept");
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerRejectOrder(int orderId, {String reason = ""}) async {
    try {
      final res = await _dio.post("/seller/orders/$orderId/reject", queryParameters: {"reason": reason});
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerMarkReady(int orderId) async {
    try {
      final res = await _dio.post("/seller/orders/$orderId/ready");
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Partner ----------------
  Future<Map<String, dynamic>> partnerAvailability(bool isAvailable) async {
    try {
      final res = await _dio.post(
        "/partner/availability",
        queryParameters: {"is_available": isAvailable},
      );
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> partnerSendLocation(double lat, double lng, {double? heading, double? speed}) async {
    try {
      await _dio.post("/partner/location", data: {
        "lat": lat,
        "lng": lng,
        if (heading != null) "heading": heading,
        if (speed != null) "speed": speed,
      });
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> partnerOrders() async {
    try {
      final res = await _dio.get("/partner/orders");
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> partnerPickup(int orderId) async {
    try {
      final res = await _dio.post("/partner/orders/$orderId/pickup");
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> partnerDeliver(int orderId) async {
    try {
      final res = await _dio.post("/partner/orders/$orderId/deliver");
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }
}
