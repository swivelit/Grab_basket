import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'app_globals.dart';
import 'bootstrap.dart';
import 'models.dart';
import 'ui/account.dart';
import 'ui/addresses.dart';
import 'ui/app_gate.dart';
import 'ui/cart.dart';
import 'ui/checkout.dart';
import 'ui/customer_home.dart';
import 'ui/customer_search.dart';
import 'ui/customer_shell.dart';
import 'ui/login.dart';
import 'ui/order_detail.dart';
import 'ui/orders.dart';
import 'ui/partner_home.dart';
import 'ui/seller_home.dart';
import 'ui/vendor_menu.dart';

GoRouter buildRouter(AppFlavor flavor) {
  // ✅ Use a global navigator key so push notifications can deep-link safely.
  final rootNavigatorKey = AppGlobals.navigatorKey;

  Widget gated(Widget child) => AppGate(flavor: flavor, child: child);

  // ---------------- Customer: Swiggy-style bottom navigation ----------------
  if (flavor == AppFlavor.customer) {
    return GoRouter(
      navigatorKey: rootNavigatorKey,
      routes: [
        GoRoute(
          path: "/login",
          builder: (c, s) => LoginScreen(flavor: flavor),
        ),

        /// Bottom-nav shell: Food / Search / Orders / Account
        StatefulShellRoute.indexedStack(
          builder: (context, state, navigationShell) {
            return gated(CustomerShellScaffold(navigationShell: navigationShell));
          },
          branches: [
            StatefulShellBranch(
              routes: [
                GoRoute(
                  path: "/",
                  builder: (c, s) => const CustomerHome(),
                ),
              ],
            ),
            StatefulShellBranch(
              routes: [
                GoRoute(
                  path: "/search",
                  builder: (c, s) => const CustomerSearchScreen(),
                ),
              ],
            ),
            StatefulShellBranch(
              routes: [
                GoRoute(
                  path: "/orders",
                  builder: (c, s) => const OrdersScreen(),
                ),
              ],
            ),
            StatefulShellBranch(
              routes: [
                GoRoute(
                  path: "/account",
                  builder: (c, s) => const AccountScreen(),
                ),
              ],
            ),
          ],
        ),

        // Full-screen routes above the bottom-nav shell.
        GoRoute(
          path: "/vendor",
          parentNavigatorKey: rootNavigatorKey,
          builder: (context, state) {
            final extra = state.extra;
            if (extra is Vendor) return gated(VendorMenuScreen(vendor: extra));
            return const _RouteErrorScreen(message: "Missing vendor data");
          },
        ),
        GoRoute(
          path: "/cart",
          parentNavigatorKey: rootNavigatorKey,
          builder: (context, state) => gated(const CartScreen()),
        ),
        GoRoute(
          path: "/checkout",
          parentNavigatorKey: rootNavigatorKey,
          builder: (context, state) => gated(const CheckoutScreen()),
        ),
        GoRoute(
          path: "/addresses",
          parentNavigatorKey: rootNavigatorKey,
          builder: (context, state) => gated(const AddressesScreen()),
        ),
        GoRoute(
          path: "/order/:id",
          parentNavigatorKey: rootNavigatorKey,
          builder: (context, state) {
            final id = int.tryParse(state.pathParameters["id"] ?? "");
            if (id == null) return const _RouteErrorScreen(message: "Invalid order id");
            final extra = state.extra;
            final allowCancel =
                (extra is Map && extra["allowCancel"] is bool) ? (extra["allowCancel"] as bool) : true;
            return gated(OrderDetailScreen(orderId: id, allowCancel: allowCancel));
          },
        ),
      ],
    );
  }

  // ---------------- Seller / Partner: simple single-home routing ----------------
  final home = switch (flavor) {
    AppFlavor.seller => const SellerHome(),
    AppFlavor.partner => const PartnerHome(),
    AppFlavor.customer => const CustomerHome(),
  };

  return GoRouter(
    navigatorKey: rootNavigatorKey,
    routes: [
      GoRoute(
        path: "/",
        builder: (c, s) => gated(home),
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
