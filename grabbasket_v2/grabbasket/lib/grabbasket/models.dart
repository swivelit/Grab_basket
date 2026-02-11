class TokenResponse {
  final String accessToken;
  final String role;
  TokenResponse({required this.accessToken, required this.role});

  factory TokenResponse.fromJson(Map<String, dynamic> j) =>
      TokenResponse(accessToken: j['access_token'], role: j['role']);
}

class Vendor {
  final int id;
  final String name;
  final String description;
  final String address;

  final bool? isOpen; // backend field
  final double? distanceKm;
  final bool? canDeliver;
  final bool? openNow;

  Vendor({
    required this.id,
    required this.name,
    required this.description,
    required this.address,
    this.isOpen,
    this.distanceKm,
    this.canDeliver,
    this.openNow,
  });

  factory Vendor.fromJson(Map<String, dynamic> j) => Vendor(
        id: j['id'],
        name: j['name'],
        description: (j['description'] ?? '').toString(),
        address: (j['address'] ?? '').toString(),
        isOpen: j.containsKey('is_open') ? (j['is_open'] as bool?) : null,
        distanceKm: j['distance_km'] == null ? null : (j['distance_km'] as num).toDouble(),
        canDeliver: j.containsKey('can_deliver') ? (j['can_deliver'] as bool?) : null,
        openNow: j.containsKey('open_now') ? (j['open_now'] as bool?) : null,
      );
}

class Product {
  final int id;
  final int vendorId;
  final String name;
  final String description;
  final double price;
  final bool? isAvailable;

  Product({
    required this.id,
    required this.vendorId,
    required this.name,
    required this.description,
    required this.price,
    this.isAvailable,
  });

  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: j['id'],
        vendorId: j['vendor_id'],
        name: j['name'],
        description: (j['description'] ?? '').toString(),
        price: (j['price'] as num).toDouble(),
        isAvailable: j.containsKey('is_available') ? (j['is_available'] as bool?) : null,
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
        productId: j['product_id'],
        name: j['name_snapshot'],
        price: (j['price_snapshot'] as num).toDouble(),
        qty: j['qty'],
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
        actorUserId: j['actor_user_id'],
        createdAt: DateTime.parse(j['created_at']),
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

  factory Order.fromJson(Map<String, dynamic> j) => Order(
        id: j['id'],
        vendorId: j['vendor_id'],
        customerId: j['customer_id'],
        partnerId: j['partner_id'],
        status: (j['status'] ?? '').toString(),
        subtotalAmount: (j['subtotal_amount'] as num?)?.toDouble() ?? 0.0,
        deliveryFee: (j['delivery_fee'] as num?)?.toDouble() ?? 0.0,
        totalAmount: (j['total_amount'] as num?)?.toDouble() ?? 0.0,
        paymentMethod: (j['payment_method'] ?? 'COD').toString(),
        paymentStatus: (j['payment_status'] ?? 'PENDING').toString(),
        paymentRef: j['payment_ref']?.toString(),
        items: (j['items'] as List? ?? const []).map((x) => OrderItem.fromJson(x)).toList(),
        events: (j['events'] as List? ?? const []).map((x) => OrderEvent.fromJson(x)).toList(),
      );

  bool get canCancel => {
        "CREATED",
        "ACCEPTED_BY_SELLER",
        "ASSIGNED_TO_PARTNER",
        "READY_FOR_PICKUP",
      }.contains(status);
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
