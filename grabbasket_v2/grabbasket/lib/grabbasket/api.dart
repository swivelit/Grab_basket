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
      return (res.data as List).map((x) => Vendor.fromJson((x as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Product>> products(int vendorId, {String? q}) async {
    try {
      final res = await _dio.get(
        "/vendors/$vendorId/products",
        queryParameters: {
          if (q != null && q.trim().isNotEmpty) "q": q.trim(),
        },
      );
      return (res.data as List).map((x) => Product.fromJson((x as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Map<String, dynamic>>> addresses() async {
    try {
      final res = await _dio.get("/addresses");
      return (res.data as List).cast<Map<String, dynamic>>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> createOrder({
    required int vendorId,
    required List<Map<String, dynamic>> items,
    required int deliveryAddressId,
    required String paymentMethod,
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
      final res = await _dio.post("/orders/$orderId/cancel", queryParameters: {if (reason.isNotEmpty) "reason": reason});
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

  Future<List<Order>> sellerOrders() async {
    try {
      final res = await _dio.get("/seller/orders");
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

  Future<Order> sellerRejectOrder(int orderId, {String reason = ""}) async {
    try {
      final res = await _dio.post("/seller/orders/$orderId/reject", queryParameters: {if (reason.isNotEmpty) "reason": reason});
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
