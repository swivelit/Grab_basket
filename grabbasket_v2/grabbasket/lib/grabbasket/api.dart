import 'package:dio/dio.dart';

import 'config.dart';
import 'models.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() {
    if (statusCode != null) return 'ApiError($statusCode): $message';
    return 'ApiError: $message';
  }
}

class Api {
  final Dio _dio;

  Api({String? token})
      : _dio = Dio(
          BaseOptions(
            baseUrl: AppConfig.apiBaseUrl,
            connectTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 25),
            headers: token != null ? {"Authorization": "Bearer $token"} : null,
          ),
        ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (err, handler) {
          final status = err.response?.statusCode;
          final data = err.response?.data;
          String msg;

          if (data is Map && data['detail'] != null) {
            msg = data['detail'].toString();
          } else if (data is Map && data['error'] is Map) {
            msg = (data['error']['message'] ?? data['error']['code'] ?? 'Request failed').toString();
          } else if (err.message != null) {
            msg = err.message!;
          } else {
            msg = 'Request failed';
          }

          handler.reject(
            DioException(
              requestOptions: err.requestOptions,
              response: err.response,
              type: err.type,
              error: ApiException(msg, statusCode: status),
            ),
          );
        },
      ),
    );
  }

  Exception _mapErr(Object e) {
    if (e is DioException && e.error is ApiException) return e.error as ApiException;
    return ApiException(e.toString());
  }

  // ---------- Auth ----------
  Future<TokenResponse> register({required String email, required String password, required String role}) async {
    try {
      final res = await _dio.post("/auth/register", data: {"email": email, "password": password, "role": role});
      return TokenResponse.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<TokenResponse> login({required String email, required String password}) async {
    try {
      final res = await _dio.post("/auth/login", data: {"email": email, "password": password});
      return TokenResponse.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------- Customer ----------
  Future<List<Vendor>> vendors({double? lat, double? lng}) async {
    try {
      final res = await _dio.get("/vendors", queryParameters: {"lat": lat, "lng": lng});
      return (res.data as List).map((x) => Vendor.fromJson((x as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Product>> products(int vendorId) async {
    try {
      final res = await _dio.get("/vendors/$vendorId/products");
      return (res.data as List).map((x) => Product.fromJson((x as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Map<String, dynamic>>> addresses() async {
    try {
      final res = await _dio.get("/addresses");
      return (res.data as List).map((x) => (x as Map).cast<String, dynamic>()).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> createAddress(Map<String, dynamic> data) async {
    try {
      final res = await _dio.post("/addresses", data: data);
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

  Future<Order> createOrder({
    required int vendorId,
    required List<CartLine> items,
    int? deliveryAddressId,
    String paymentMethod = "COD",
  }) async {
    try {
      final res = await _dio.post(
        "/orders",
        data: {
          "vendor_id": vendorId,
          "delivery_address_id": deliveryAddressId,
          "payment_method": paymentMethod,
          "items": items.map((l) => {"product_id": l.product.id, "qty": l.qty}).toList(),
        },
      );
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> myOrders() async {
    try {
      final res = await _dio.get("/orders/me");
      return (res.data as List).map((x) => Order.fromJson((x as Map).cast<String, dynamic>())).toList();
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

  Future<Order> cancelOrder(int orderId, {String reason = ""}) async {
    try {
      final res = await _dio.post("/orders/$orderId/cancel", queryParameters: {"reason": reason});
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

  /// Used by the customer order detail screen for live-ish tracking.
  /// Endpoint returns a flat map (lat/lng/ts) and some flags when not available yet.
  Future<Map<String, dynamic>> partnerLatestForOrder(int orderId) async {
    try {
      final res = await _dio.get("/tracking/order/$orderId/partner_latest");
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------- Seller ----------
  Future<Map<String, dynamic>> sellerCreateVendor({required String name, String description = "", String address = ""}) async {
    try {
      final res = await _dio.post(
        "/seller/vendor",
        queryParameters: {"name": name, "description": description, "address": address},
      );
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> sellerOrders() async {
    try {
      final res = await _dio.get("/orders/me");
      return (res.data as List).map((x) => Order.fromJson((x as Map).cast<String, dynamic>())).toList();
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

  Future<Order> sellerRejectOrder(int orderId) async {
    try {
      final res = await _dio.post("/seller/orders/$orderId/reject");
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

  // ---------- Partner ----------
  Future<void> partnerAvailability(bool isAvailable) async {
    try {
      await _dio.post("/partner/availability", queryParameters: {"is_available": isAvailable});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> partnerSendLocation(double lat, double lng, {double? heading, double? speed}) async {
    try {
      await _dio.post("/partner/location", data: {"lat": lat, "lng": lng, "heading": heading, "speed": speed});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> partnerOrders() async {
    try {
      final res = await _dio.get("/partner/orders");
      return (res.data as List).map((x) => Order.fromJson((x as Map).cast<String, dynamic>())).toList();
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
