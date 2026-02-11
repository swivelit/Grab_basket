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
  Vendor({required this.id, required this.name, required this.description, required this.address});
  factory Vendor.fromJson(Map<String, dynamic> j) => Vendor(
    id: j['id'],
    name: j['name'],
    description: j['description'] ?? '',
    address: j['address'] ?? '',
  );
}

class Product {
  final int id;
  final int vendorId;
  final String name;
  final String description;
  final double price;
  Product({required this.id, required this.vendorId, required this.name, required this.description, required this.price});
  factory Product.fromJson(Map<String, dynamic> j) => Product(
    id: j['id'],
    vendorId: j['vendor_id'],
    name: j['name'],
    description: j['description'] ?? '',
    price: (j['price'] as num).toDouble(),
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

class Order {
  final int id;
  final int vendorId;
  final int customerId;
  final int? partnerId;
  final String status;
  final double totalAmount;
  final double deliveryFee;
  final List<OrderItem> items;

  Order({
    required this.id,
    required this.vendorId,
    required this.customerId,
    required this.partnerId,
    required this.status,
    required this.totalAmount,
    required this.deliveryFee,
    required this.items,
  });

  factory Order.fromJson(Map<String, dynamic> j) => Order(
    id: j['id'],
    vendorId: j['vendor_id'],
    customerId: j['customer_id'],
    partnerId: j['partner_id'],
    status: j['status'],
    totalAmount: (j['total_amount'] as num).toDouble(),
    deliveryFee: (j['delivery_fee'] as num).toDouble(),
    items: (j['items'] as List).map((x) => OrderItem.fromJson(x)).toList(),
  );
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