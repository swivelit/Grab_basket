import 'package:flutter/widgets.dart';

import 'grabbasket/bootstrap.dart' as bootstrap;
import 'grabbasket/storage.dart';

Future<void> main() async {
  // ✅ Production entrypoint (was the default Flutter counter demo + had invalid syntax).
  // We auto-select the app flavor based on the last logged-in role.
  WidgetsFlutterBinding.ensureInitialized();

  final store = SecureStore();
  final role = (await store.role)?.toUpperCase();

  final flavor = switch (role) {
    "SELLER" => bootstrap.AppFlavor.seller,
    "PARTNER" => bootstrap.AppFlavor.partner,
    _ => bootstrap.AppFlavor.customer,
  };

  bootstrap.mainApp(flavor);
}
