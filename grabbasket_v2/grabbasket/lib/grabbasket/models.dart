class TokenResponse {
  final String accessToken;
  final String role;

  TokenResponse({required this.accessToken, required this.role});

  factory TokenResponse.fromJson(Map<String, dynamic> j) {
    return TokenResponse(
      accessToken: (j['access_token'] ?? '').toString(),
      role: (j['role'] ?? '').toString(),
    );
  }
}

class Vendor {
  final int id;
  final String name;
  final String description;
  final String address;
  final double? lat;
  final double? lng;
  final double deliveryRadiusKm;
  final bool isOpen;
  final String? openTime;
  final String? closeTime;

  final double? distanceKm;
  final bool? canDeliver;
  final bool? openNow;

  Vendor({
    required this.id,
    required this.name,
    required this.description,
    required this.address,
    required this.lat,
    required this.lng,
    required this.deliveryRadiusKm,
    required this.isOpen,
    required this.openTime,
    required this.closeTime,
    required this.distanceKm,
    required this.canDeliver,
    required this.openNow,
  });

  factory Vendor.fromJson(Map<String, dynamic> j) {
    double? _d(dynamic v) => v == null ? null : (v as num).toDouble();
    return Vendor(
      id: j['id'] as int,
      name: (j['name'] ?? '').toString(),
      description: (j['description'] ?? '').toString(),
      address: (j['address'] ?? '').toString(),
      lat: _d(j['lat']),
      lng: _d(j['lng']),
      deliveryRadiusKm: (j['delivery_radius_km'] as num?)?.toDouble() ?? 0,
      isOpen: j['is_open'] == true,
      openTime: j['open_time']?.toString(),
      closeTime: j['close_time']?.toString(),
      distanceKm: _d(j['distance_km']),
      canDeliver: j['can_deliver'] as bool?,
      openNow: j['open_now'] as bool?,
    );
  }
}

class Product {
  final int id;
  final int vendorId;
  final String name;
  final String description;
  final double price;
  final bool isAvailable;

  Product({
    required this.id,
    required this.vendorId,
    required this.name,
    required this.description,
    required this.price,
    required this.isAvailable,
  });

  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: j['id'] as int,
        vendorId: j['vendor_id'] as int,
        name: (j['name'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        price: (j['price'] as num).toDouble(),
        isAvailable: j['is_available'] == true,
      );
}

class CartLine {
  final Product product;
  final int qty;
  CartLine({required this.product, required this.qty});
  double get lineTotal => product.price * qty;
}

class OrderItem {
  final int productId;
  final String name;
  final double price;
  final int qty;
  OrderItem({required this.productId, required this.name, required this.price, required this.qty});

  factory OrderItem.fromJson(Map<String, dynamic> j) => OrderItem(
        productId: j['product_id'] as int,
        name: (j['name_snapshot'] ?? '').toString(),
        price: (j['price_snapshot'] as num).toDouble(),
        qty: j['qty'] as int,
      );
}

class OrderEvent {
  final String status;
  final String note;
  final int? actorUserId;
  final DateTime createdAt;

  OrderEvent({required this.status, required this.note, required this.actorUserId, required this.createdAt});

  factory OrderEvent.fromJson(Map<String, dynamic> j) => OrderEvent(
        status: (j['status'] ?? '').toString(),
        note: (j['note'] ?? '').toString(),
        actorUserId: j['actor_user_id'] as int?,
        createdAt: DateTime.tryParse((j['created_at'] ?? '').toString()) ?? DateTime.fromMillisecondsSinceEpoch(0),
      );
}

class Order {
  final int id;
  final int vendorId;
  final int customerId;
  final int? partnerId;
  final String status;
  final double subtotalAmount;
  final double deliveryFee;
  final double totalAmount;
  final String paymentMethod;
  final String paymentStatus;
  final String? paymentRef;
  final List<OrderItem> items;
  final List<OrderEvent> events;

  Order({
    required this.id,
    required this.vendorId,
    required this.customerId,
    required this.partnerId,
    required this.status,
    required this.subtotalAmount,
    required this.deliveryFee,
    required this.totalAmount,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.paymentRef,
    required this.items,
    required this.events,
  });

  bool get canCancel => !(status == 'PICKED_UP' || status == 'DELIVERED' || status.startsWith('CANCELLED'));

  factory Order.fromJson(Map<String, dynamic> j) {
    return Order(
      id: j['id'] as int,
      vendorId: j['vendor_id'] as int,
      customerId: j['customer_id'] as int,
      partnerId: j['partner_id'] as int?,
      status: (j['status'] ?? '').toString(),
      subtotalAmount: (j['subtotal_amount'] as num?)?.toDouble() ?? 0,
      deliveryFee: (j['delivery_fee'] as num?)?.toDouble() ?? 0,
      totalAmount: (j['total_amount'] as num?)?.toDouble() ?? 0,
      paymentMethod: (j['payment_method'] ?? 'COD').toString(),
      paymentStatus: (j['payment_status'] ?? 'PENDING').toString(),
      paymentRef: j['payment_ref']?.toString(),
      items: ((j['items'] as List?) ?? const []).map((x) => OrderItem.fromJson(x as Map<String, dynamic>)).toList(),
      events: ((j['events'] as List?) ?? const []).map((x) => OrderEvent.fromJson(x as Map<String, dynamic>)).toList(),
    );
  }
}

class ProductUpsert {
  final String name;
  final String description;
  final double price;
  final bool isAvailable;

  ProductUpsert({
    required this.name,
    this.description = "",
    required this.price,
    this.isAvailable = true,
  });

  Map<String, dynamic> toJson() => {
        "name": name,
        "description": description,
        "price": price,
        "is_available": isAvailable,
      };
}
