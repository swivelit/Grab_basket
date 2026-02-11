import 'package:go_router/go_router.dart';
import 'bootstrap.dart';
import 'ui/login.dart';
import 'ui/customer_home.dart';
import 'ui/seller_home.dart';
import 'ui/partner_home.dart';

GoRouter buildRouter(AppFlavor flavor) {
  final home = switch (flavor) {
    AppFlavor.customer => const CustomerHome(),
    AppFlavor.seller => const SellerHome(),
    AppFlavor.partner => const PartnerHome(),
  };

  return GoRouter(
    routes: [
      GoRoute(path: "/", builder: (c, s) => home),
      GoRoute(path: "/login", builder: (c, s) => LoginScreen(flavor: flavor)),
    ],
  );
}
