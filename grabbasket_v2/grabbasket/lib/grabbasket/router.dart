import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'bootstrap.dart';
import 'models.dart';
import 'ui/addresses.dart';
import 'ui/app_gate.dart';
import 'ui/checkout.dart';
import 'ui/customer_home.dart';
import 'ui/login.dart';
import 'ui/order_detail.dart';
import 'ui/orders.dart';
import 'ui/partner_home.dart';
import 'ui/seller_home.dart';
import 'ui/vendor_menu.dart';

GoRouter buildRouter(AppFlavor flavor) {
  final home = switch (flavor) {
    AppFlavor.customer => const CustomerHome(),
    AppFlavor.seller => const SellerHome(),
    AppFlavor.partner => const PartnerHome(),
  };

  return GoRouter(
    routes: [
      GoRoute(
        path: "/",
        builder: (c, s) => AppGate(flavor: flavor, child: home),
        routes: [
          GoRoute(
            path: "vendor",
            builder: (context, state) {
              final extra = state.extra;
              if (extra is Vendor) return VendorMenuScreen(vendor: extra);
              return const _RouteErrorScreen(message: "Missing vendor data");
            },
          ),
          GoRoute(
            path: "checkout",
            builder: (context, state) => const CheckoutScreen(),
          ),
          GoRoute(
            path: "addresses",
            builder: (context, state) => const AddressesScreen(),
          ),
          GoRoute(
            path: "orders",
            builder: (context, state) => const OrdersScreen(),
          ),
          GoRoute(
            path: "order/:id",
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters["id"] ?? "");
              if (id == null) return const _RouteErrorScreen(message: "Invalid order id");
              final extra = state.extra;
              final allowCancel = (extra is Map && extra["allowCancel"] is bool) ? (extra["allowCancel"] as bool) : true;
              return OrderDetailScreen(orderId: id, allowCancel: allowCancel);
            },
          ),
        ],
      ),
      GoRoute(
        path: "/login",
        builder: (c, s) => LoginScreen(flavor: flavor),
      ),
    ],
  );
}

class _RouteErrorScreen extends StatelessWidget {
  final String message;
  const _RouteErrorScreen({required this.message});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Oops")),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Text(message, textAlign: TextAlign.center),
        ),
      ),
    );
  }
}
