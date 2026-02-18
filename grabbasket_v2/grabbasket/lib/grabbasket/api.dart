import 'dart:math';

import 'package:dio/dio.dart';

import 'config.dart';
import 'models.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final String? requestId;

  ApiException(this.message, {this.statusCode, this.requestId});

  @override
  String toString() {
    final parts = <String>[];
    if (statusCode != null) parts.add('HTTP $statusCode');
    parts.add(message);
    if (requestId != null && requestId!.trim().isNotEmpty) parts.add('req: $requestId');
    return parts.join(' • ');
  }
}

class Api {
  late final Dio _dio;

  Api({String? token}) {
    final options = BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 12),
      receiveTimeout: const Duration(seconds: 20),
      sendTimeout: const Duration(seconds: 12),
      headers: {
        if (token != null && token.trim().isNotEmpty) 'Authorization': 'Bearer ${token.trim()}',
      },
    );

    _dio = Dio(options);

    // Attach a simple request id to help debug backend logs.
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final rid = _requestId();
          options.headers['x-request-id'] = rid;
          handler.next(options);
        },
      ),
    );
  }

  static String _requestId() {
    // No extra deps; good enough uniqueness for debugging.
    final now = DateTime.now().microsecondsSinceEpoch;
    final r = Random().nextInt(1 << 32);
    return 'm$now-$r';
  }

  ApiException _mapErr(Object e) {
    if (e is DioException) {
      final status = e.response?.statusCode;
      final data = e.response?.data;

      String? requestId;
      if (data is Map && data['request_id'] != null) {
        requestId = data['request_id']?.toString();
      }

      // Backend standard response: {detail, error:{code,message,details}, request_id}
      if (data is Map) {
        final detail = data['detail']?.toString();
        final err = data['error'];
        final errMsg = err is Map ? err['message']?.toString() : null;
        final msg = (detail?.trim().isNotEmpty == true)
            ? detail!
            : (errMsg?.trim().isNotEmpty == true)
                ? errMsg!
                : (e.message ?? 'Network error');
        return ApiException(msg, statusCode: status, requestId: requestId);
      }

      // Timeout / socket / cancellation
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.sendTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        return ApiException('Request timed out. Please try again.', statusCode: status, requestId: requestId);
      }

      if (e.type == DioExceptionType.connectionError) {
        return ApiException('Unable to reach the server. Check your internet / API URL.', statusCode: status, requestId: requestId);
      }

      return ApiException(e.message ?? 'Network error', statusCode: status, requestId: requestId);
    }

    return ApiException(e.toString());
  }

  // ---------------- Auth ----------------
  Future<TokenResponse> login({required String email, required String password}) async {
    try {
      final res = await _dio.post(
        '/auth/login',
        data: {'email': email, 'password': password},
      );
      return TokenResponse.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<TokenResponse> register({required String email, required String password, required String role}) async {
    try {
      final res = await _dio.post(
        '/auth/register',
        data: {'email': email, 'password': password, 'role': role},
      );
      return TokenResponse.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> registerFcmToken(String token, {String platform = 'unknown'}) async {
    try {
      await _dio.post('/auth/fcm/register', data: {'token': token, 'platform': platform});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Vendors / Products ----------------
  Future<List<Vendor>> vendors({
    double? lat,
    double? lng,
    String? q,
    bool openOnly = false,
    bool deliverableOnly = false,
    int limit = 50,
    int offset = 0,
  }) async {
    try {
      final res = await _dio.get(
        '/vendors',
        queryParameters: {
          if (lat != null) 'lat': lat,
          if (lng != null) 'lng': lng,
          if (q != null && q.trim().isNotEmpty) 'q': q.trim(),
          if (openOnly) 'open_only': true,
          if (deliverableOnly) 'deliverable_only': true,
          'limit': limit,
          'offset': offset,
        },
      );
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Vendor.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  /// Preferred name (used by UI)
  Future<List<Product>> products(
    int vendorId, {
    String? q,
    bool includeUnavailable = false,
    int limit = 200,
  }) async {
    try {
      final res = await _dio.get(
        '/vendors/$vendorId/products',
        queryParameters: {
          if (q != null && q.trim().isNotEmpty) 'q': q.trim(),
          if (includeUnavailable) 'include_unavailable': true,
          'limit': limit,
        },
      );
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Product.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  /// Backwards-compatible alias.
  Future<List<Product>> vendorProducts(int vendorId) => products(vendorId);

  // ---------------- Orders ----------------
  Future<Order> createOrder({
    required int vendorId,
    required List<Map<String, dynamic>> items,
    int? deliveryAddressId,
    String paymentMethod = 'COD',
  }) async {
    try {
      final res = await _dio.post(
        '/orders',
        data: {
          'vendor_id': vendorId,
          'items': items,
          if (deliveryAddressId != null) 'delivery_address_id': deliveryAddressId,
          'payment_method': paymentMethod,
        },
      );
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> myOrders() async {
    try {
      final res = await _dio.get('/orders/me');
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> getOrder(int orderId) async {
    try {
      final res = await _dio.get('/orders/$orderId');
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> orderTracking(int orderId) async {
    try {
      final res = await _dio.get('/orders/$orderId/tracking');
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> cancelOrder(int orderId, {String reason = ''}) async {
    try {
      final res = await _dio.post(
        '/orders/$orderId/cancel',
        queryParameters: {
          if (reason.trim().isNotEmpty) 'reason': reason.trim(),
        },
      );
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> partnerLatestForOrder(int orderId) async {
    try {
      final res = await _dio.get('/tracking/order/$orderId/partner_latest');
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Addresses (Customer) ----------------
  Future<List<Map<String, dynamic>>> addresses() async {
    try {
      final res = await _dio.get('/addresses');
      return (res.data as List).cast<Map<String, dynamic>>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Map<String, dynamic>> createAddress(Map<String, dynamic> payload) async {
    try {
      final res = await _dio.post('/addresses', data: payload);
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> setDefaultAddress(int addressId) async {
    try {
      await _dio.post('/addresses/$addressId/default');
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Seller ----------------
  Future<void> sellerCreateVendor({required String name}) async {
    try {
      await _dio.post('/seller/vendor', queryParameters: {'name': name});
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> sellerOrders() async {
    try {
      final res = await _dio.get('/seller/orders');
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerAcceptOrder(int orderId) async {
    try {
      final res = await _dio.post('/seller/orders/$orderId/accept');
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerRejectOrder(int orderId, {String reason = ''}) async {
    try {
      final res = await _dio.post('/seller/orders/$orderId/reject', queryParameters: {'reason': reason});
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> sellerMarkReady(int orderId) async {
    try {
      final res = await _dio.post('/seller/orders/$orderId/ready');
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  // ---------------- Partner ----------------
  Future<Map<String, dynamic>> partnerAvailability(bool isAvailable) async {
    try {
      final res = await _dio.post(
        '/partner/availability',
        queryParameters: {'is_available': isAvailable},
      );
      return (res.data as Map).cast<String, dynamic>();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<void> partnerSendLocation(double lat, double lng, {double? heading, double? speed}) async {
    try {
      await _dio.post(
        '/partner/location',
        data: {
          'lat': lat,
          'lng': lng,
          if (heading != null) 'heading': heading,
          if (speed != null) 'speed': speed,
        },
      );
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<List<Order>> partnerOrders() async {
    try {
      final res = await _dio.get('/partner/orders');
      final list = (res.data as List).cast<dynamic>();
      return list.map((e) => Order.fromJson((e as Map).cast<String, dynamic>())).toList();
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> partnerPickup(int orderId) async {
    try {
      final res = await _dio.post('/partner/orders/$orderId/pickup');
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }

  Future<Order> partnerDeliver(int orderId) async {
    try {
      final res = await _dio.post('/partner/orders/$orderId/deliver');
      return Order.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw _mapErr(e);
    }
  }
}
