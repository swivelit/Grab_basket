import os, re, textwrap

ROOT = os.getcwd()

def read(path: str) -> str:
    with open(path, "r", encoding="utf-8") as f:
        return f.read()

def write(path: str, content: str) -> None:
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)

def append_pubspec_deps():
    path = os.path.join(ROOT, "pubspec.yaml")
    s = read(path)
    if "flutter_riverpod" in s:
        print("pubspec.yaml already patched")
        return
    s2 = re.sub(
        r"(?m)^dependencies:\s*$",
        "dependencies:\n"
        "  dio: ^5.7.0\n"
        "  flutter_secure_storage: ^9.2.2\n"
        "  go_router: ^14.6.3\n"
        "  flutter_riverpod: ^2.6.1\n"
        "  collection: ^1.18.0\n",
        s,
        count=1,
    )
    write(path, s2)
    print("pubspec.yaml updated")

def patch_build_gradle():
    """
    Flutter new templates use Kotlin DSL:
      android/app/build.gradle.kts
    Older templates use:
      android/app/build.gradle

    This function patches whichever exists.
    """
    kts_path = os.path.join(ROOT, "android", "app", "build.gradle.kts")
    groovy_path = os.path.join(ROOT, "android", "app", "build.gradle")

    if os.path.exists(kts_path):
        s = read(kts_path)
        if "productFlavors" in s and "Grabbasket" in s:
            print("android/app/build.gradle.kts already has flavors")
            return

        insert = textwrap.dedent('''
            // ✅ Grabbasket flavors (Customer/Seller/Partner)
            flavorDimensions += "app"
            productFlavors {
                create("customer") {
                    dimension = "app"
                    applicationIdSuffix = ".customer"
                    resValue("string", "app_name", "Grabbasket")
                }
                create("seller") {
                    dimension = "app"
                    applicationIdSuffix = ".seller"
                    resValue("string", "app_name", "Grabbasket Seller")
                }
                create("partner") {
                    dimension = "app"
                    applicationIdSuffix = ".partner"
                    resValue("string", "app_name", "Grabbasket Partner")
                }
            }
        ''').strip("\n")

        # Insert after defaultConfig { ... } block
        s2 = re.sub(r"(defaultConfig\s*\{[\s\S]*?\}\s*)", r"\1\n\n" + insert + "\n\n", s, count=1)
        if s2 == s:
            print("Could not patch build.gradle.kts automatically. Add flavors manually.")
            return
        write(kts_path, s2)
        print("android/app/build.gradle.kts patched with flavors")
        return

    if os.path.exists(groovy_path):
        s = read(groovy_path)
        if "productFlavors" in s and "Grabbasket" in s:
            print("android/app/build.gradle already has flavors")
            return

        insert = textwrap.dedent('''
        flavorDimensions "app"
        productFlavors {
            customer {
                dimension "app"
                applicationIdSuffix ".customer"
                resValue "string", "app_name", "Grabbasket"
            }
            seller {
                dimension "app"
                applicationIdSuffix ".seller"
                resValue "string", "app_name", "Grabbasket Seller"
            }
            partner {
                dimension "app"
                applicationIdSuffix ".partner"
                resValue "string", "app_name", "Grabbasket Partner"
            }
        }
        ''').strip("\n")

        s2 = re.sub(r"(defaultConfig\s*\{[\s\S]*?\}\s*)", r"\1\n" + insert + "\n", s, count=1)
        if s2 == s:
            print("Could not patch build.gradle automatically. Add flavors manually.")
            return
        write(groovy_path, s2)
        print("android/app/build.gradle patched with flavors")
        return

    print("No android/app/build.gradle(.kts) found; cannot patch flavors.")

def add_entrypoints_and_lib():
    write(os.path.join(ROOT, "lib", "main_customer.dart"),
          "import 'grabbasket/bootstrap.dart' as bootstrap;\n\nvoid main() => bootstrap.mainApp(bootstrap.AppFlavor.customer);\n")
    write(os.path.join(ROOT, "lib", "main_seller.dart"),
          "import 'grabbasket/bootstrap.dart' as bootstrap;\n\nvoid main() => bootstrap.mainApp(bootstrap.AppFlavor.seller);\n")
    write(os.path.join(ROOT, "lib", "main_partner.dart"),
          "import 'grabbasket/bootstrap.dart' as bootstrap;\n\nvoid main() => bootstrap.mainApp(bootstrap.AppFlavor.partner);\n")

    write(os.path.join(ROOT, "lib", "grabbasket", "config.dart"),
          textwrap.dedent('''\
          class AppConfig {
            // Android emulator: http://10.0.2.2:8000
            // Real device (same Wi-Fi): http://<YOUR_LAPTOP_LAN_IP>:8000
            static const String apiBaseUrl = String.fromEnvironment(
              'API_BASE_URL',
              defaultValue: 'http://10.0.2.2:8000',
            );
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "bootstrap.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import 'router.dart';

          enum AppFlavor { customer, seller, partner }

          void mainApp(AppFlavor flavor) {
            WidgetsFlutterBinding.ensureInitialized();
            runApp(ProviderScope(child: GrabbasketApp(flavor: flavor)));
          }

          class GrabbasketApp extends StatelessWidget {
            final AppFlavor flavor;
            const GrabbasketApp({super.key, required this.flavor});

            @override
            Widget build(BuildContext context) {
              final title = switch (flavor) {
                AppFlavor.customer => 'Grabbasket',
                AppFlavor.seller => 'Grabbasket Seller',
                AppFlavor.partner => 'Grabbasket Partner',
              };

              return MaterialApp.router(
                debugShowCheckedModeBanner: false,
                title: title,
                theme: ThemeData(useMaterial3: true, colorSchemeSeed: Colors.deepOrange),
                routerConfig: buildRouter(flavor),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "models.dart"),
          textwrap.dedent('''\
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
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "storage.dart"),
          textwrap.dedent('''\
          import 'package:flutter_secure_storage/flutter_secure_storage.dart';

          class SecureStore {
            static const _kToken = "token";
            static const _kRole = "role";

            final FlutterSecureStorage _s = const FlutterSecureStorage();

            Future<void> saveSession({required String token, required String role}) async {
              await _s.write(key: _kToken, value: token);
              await _s.write(key: _kRole, value: role);
            }

            Future<String?> get token async => _s.read(key: _kToken);
            Future<String?> get role async => _s.read(key: _kRole);

            Future<void> clear() async {
              await _s.delete(key: _kToken);
              await _s.delete(key: _kRole);
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "api.dart"),
          textwrap.dedent('''\
          import 'package:dio/dio.dart';
          import 'config.dart';
          import 'models.dart';

          class Api {
            final Dio _dio;

            Api({String? token})
                : _dio = Dio(BaseOptions(
                    baseUrl: AppConfig.apiBaseUrl,
                    connectTimeout: const Duration(seconds: 10),
                    receiveTimeout: const Duration(seconds: 20),
                    headers: token != null ? {"Authorization": "Bearer $token"} : null,
                  ));

            Future<TokenResponse> register({required String email, required String password, required String role}) async {
              final res = await _dio.post("/auth/register", data: {"email": email, "password": password, "role": role});
              return TokenResponse.fromJson(res.data);
            }

            Future<TokenResponse> login({required String email, required String password}) async {
              final res = await _dio.post("/auth/login", data: {"email": email, "password": password});
              return TokenResponse.fromJson(res.data);
            }

            Future<List<Vendor>> vendors() async {
              final res = await _dio.get("/vendors");
              return (res.data as List).map((x) => Vendor.fromJson(x)).toList();
            }

            Future<List<Product>> products(int vendorId) async {
              final res = await _dio.get("/vendors/$vendorId/products");
              return (res.data as List).map((x) => Product.fromJson(x)).toList();
            }

            Future<Order> createOrder(int vendorId, List<Map<String, dynamic>> items) async {
              final res = await _dio.post("/orders", data: {"vendor_id": vendorId, "items": items});
              return Order.fromJson(res.data);
            }

            Future<List<Order>> myOrders() async {
              final res = await _dio.get("/orders/me");
              return (res.data as List).map((x) => Order.fromJson(x)).toList();
            }

            // Seller
            Future<void> sellerCreateVendor({required String name, String description = "", String address = ""}) async {
              await _dio.post("/seller/vendor", queryParameters: {"name": name, "description": description, "address": address});
            }

            Future<List<Order>> sellerOrders() async {
              final res = await _dio.get("/seller/orders");
              return (res.data as List).map((x) => Order.fromJson(x)).toList();
            }

            Future<Order> sellerAcceptOrder(int orderId) async {
              final res = await _dio.post("/seller/orders/$orderId/accept");
              return Order.fromJson(res.data);
            }

            // Partner
            Future<void> partnerAvailability(bool isAvailable) async {
              await _dio.post("/partner/availability", queryParameters: {"is_available": isAvailable});
            }

            Future<List<Order>> partnerOrders() async {
              final res = await _dio.get("/partner/orders");
              return (res.data as List).map((x) => Order.fromJson(x)).toList();
            }

            Future<Order> partnerPickup(int orderId) async {
              final res = await _dio.post("/partner/orders/$orderId/pickup");
              return Order.fromJson(res.data);
            }

            Future<Order> partnerDeliver(int orderId) async {
              final res = await _dio.post("/partner/orders/$orderId/deliver");
              return Order.fromJson(res.data);
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "state.dart"),
          textwrap.dedent('''\
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import 'api.dart';
          import 'models.dart';
          import 'storage.dart';

          final secureStoreProvider = Provider((ref) => SecureStore());

          final sessionProvider = FutureProvider<({String? token, String? role})>((ref) async {
            final store = ref.read(secureStoreProvider);
            return (token: await store.token, role: await store.role);
          });

          final apiProvider = Provider<Api>((ref) {
            final session = ref.watch(sessionProvider).valueOrNull;
            return Api(token: session?.token);
          });

          class CartState {
            final int vendorId;
            final List<CartLine> lines;
            const CartState({required this.vendorId, required this.lines});

            double get subtotal => lines.fold(0, (s, l) => s + l.lineTotal);
            int get count => lines.fold(0, (s, l) => s + l.qty);
          }

          class CartNotifier extends Notifier<CartState?> {
            @override
            CartState? build() => null;

            void add(Product p) {
              final current = state;
              if (current == null || current.vendorId != p.vendorId) {
                state = CartState(vendorId: p.vendorId, lines: [CartLine(product: p, qty: 1)]);
                return;
              }
              final idx = current.lines.indexWhere((l) => l.product.id == p.id);
              if (idx == -1) {
                state = CartState(vendorId: current.vendorId, lines: [...current.lines, CartLine(product: p, qty: 1)]);
              } else {
                final updated = [...current.lines];
                final old = updated[idx];
                updated[idx] = CartLine(product: old.product, qty: old.qty + 1);
                state = CartState(vendorId: current.vendorId, lines: updated);
              }
            }

            void remove(Product p) {
              final current = state;
              if (current == null) return;
              final idx = current.lines.indexWhere((l) => l.product.id == p.id);
              if (idx == -1) return;
              final updated = [...current.lines];
              final old = updated[idx];
              if (old.qty <= 1) {
                updated.removeAt(idx);
              } else {
                updated[idx] = CartLine(product: old.product, qty: old.qty - 1);
              }
              state = updated.isEmpty ? null : CartState(vendorId: current.vendorId, lines: updated);
            }

            void clear() => state = null;
          }

          final cartProvider = NotifierProvider<CartNotifier, CartState?>(() => CartNotifier());
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "router.dart"),
          textwrap.dedent('''\
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
          '''))

    # UI files
    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "login.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../bootstrap.dart';
          import '../api.dart';
          import '../storage.dart';
          import 'customer_home.dart';
          import 'seller_home.dart';
          import 'partner_home.dart';

          class LoginScreen extends ConsumerStatefulWidget {
            final AppFlavor flavor;
            const LoginScreen({super.key, required this.flavor});

            @override
            ConsumerState<LoginScreen> createState() => _LoginScreenState();
          }

          class _LoginScreenState extends ConsumerState<LoginScreen> {
            final _email = TextEditingController();
            final _password = TextEditingController();
            bool _isRegister = false;
            bool _loading = false;
            String? _error;

            String get _role => switch (widget.flavor) {
              AppFlavor.customer => "CUSTOMER",
              AppFlavor.seller => "SELLER",
              AppFlavor.partner => "PARTNER",
            };

            @override
            Widget build(BuildContext context) {
              return Scaffold(
                appBar: AppBar(title: Text(_isRegister ? "Register" : "Login")),
                body: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      TextField(controller: _email, decoration: const InputDecoration(labelText: "Email")),
                      const SizedBox(height: 12),
                      TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: "Password")),
                      const SizedBox(height: 12),
                      if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: _loading ? null : () async {
                          setState(() { _loading = true; _error = null; });
                          try {
                            final api = Api();
                            final res = _isRegister
                              ? await api.register(email: _email.text.trim(), password: _password.text, role: _role)
                              : await api.login(email: _email.text.trim(), password: _password.text);

                            final store = SecureStore();
                            await store.saveSession(token: res.accessToken, role: res.role);

                            if (!context.mounted) return;
                            final target = switch (widget.flavor) {
                              AppFlavor.customer => const CustomerHome(),
                              AppFlavor.seller => const SellerHome(),
                              AppFlavor.partner => const PartnerHome(),
                            };
                            Navigator.of(context).pushAndRemoveUntil(
                              MaterialPageRoute(builder: (_) => target),
                              (_) => false,
                            );
                          } catch (e) {
                            setState(() => _error = e.toString());
                          } finally {
                            setState(() => _loading = false);
                          }
                        },
                        child: Text(_loading ? "Please wait..." : (_isRegister ? "Create account" : "Login")),
                      ),
                      TextButton(
                        onPressed: () => setState(() { _isRegister = !_isRegister; _error = null; }),
                        child: Text(_isRegister ? "Have an account? Login" : "New here? Register"),
                      ),
                      const SizedBox(height: 12),
                      Text("Role: $_role"),
                      const SizedBox(height: 8),
                      const Text("Tip: SELLER -> create vendor. PARTNER -> set availability ON."),
                    ],
                  ),
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "customer_home.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../state.dart';
          import '../models.dart';
          import 'vendor_menu.dart';
          import 'orders.dart';
          import 'login.dart';
          import '../bootstrap.dart';

          class CustomerHome extends ConsumerWidget {
            const CustomerHome({super.key});

            @override
            Widget build(BuildContext context, WidgetRef ref) {
              final api = ref.watch(apiProvider);
              return Scaffold(
                appBar: AppBar(
                  title: const Text("Grabbasket"),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.receipt_long),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OrdersScreen())),
                    ),
                    IconButton(
                      icon: const Icon(Icons.login),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.customer))),
                    ),
                  ],
                ),
                body: FutureBuilder<List<Vendor>>(
                  future: api.vendors(),
                  builder: (context, snap) {
                    if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                    final vendors = snap.data!;
                    return ListView.separated(
                      itemCount: vendors.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, i) {
                        final v = vendors[i];
                        return ListTile(
                          title: Text(v.name),
                          subtitle: Text(v.description),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VendorMenuScreen(vendor: v))),
                        );
                      },
                    );
                  },
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "vendor_menu.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../models.dart';
          import '../state.dart';
          import 'checkout.dart';

          class VendorMenuScreen extends ConsumerWidget {
            final Vendor vendor;
            const VendorMenuScreen({super.key, required this.vendor});

            @override
            Widget build(BuildContext context, WidgetRef ref) {
              final api = ref.watch(apiProvider);
              final cart = ref.watch(cartProvider);
              return Scaffold(
                appBar: AppBar(
                  title: Text(vendor.name),
                  actions: [
                    if (cart != null)
                      TextButton.icon(
                        onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const CheckoutScreen())),
                        icon: const Icon(Icons.shopping_cart),
                        label: Text("${cart.count}"),
                      )
                  ],
                ),
                body: FutureBuilder<List<Product>>(
                  future: api.products(vendor.id),
                  builder: (context, snap) {
                    if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                    final products = snap.data!;
                    return ListView.separated(
                      itemCount: products.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, i) {
                        final p = products[i];
                        return ListTile(
                          title: Text(p.name),
                          subtitle: Text("Rs ${p.price.toStringAsFixed(2)} • ${p.description}"),
                          trailing: IconButton(
                            icon: const Icon(Icons.add_circle_outline),
                            onPressed: () => ref.read(cartProvider.notifier).add(p),
                          ),
                        );
                      },
                    );
                  },
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "checkout.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../state.dart';

          class CheckoutScreen extends ConsumerWidget {
            const CheckoutScreen({super.key});

            @override
            Widget build(BuildContext context, WidgetRef ref) {
              final cart = ref.watch(cartProvider);
              final api = ref.watch(apiProvider);

              if (cart == null) {
                return const Scaffold(body: Center(child: Text("Cart is empty")));
              }

              return Scaffold(
                appBar: AppBar(title: const Text("Checkout")),
                body: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(
                        child: ListView(
                          children: [
                            for (final line in cart.lines)
                              ListTile(
                                title: Text(line.product.name),
                                subtitle: Text("Rs ${line.product.price.toStringAsFixed(2)} x ${line.qty}"),
                                trailing: Text("Rs ${line.lineTotal.toStringAsFixed(2)}"),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text("Subtotal: Rs ${cart.subtotal.toStringAsFixed(2)}"),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: () async {
                          final items = cart.lines.map((l) => {"product_id": l.product.id, "qty": l.qty}).toList();
                          final order = await api.createOrder(cart.vendorId, items);
                          ref.read(cartProvider.notifier).clear();
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Order #${order.id} placed: ${order.status}")));
                          Navigator.of(context).pop();
                        },
                        child: const Text("Place order"),
                      ),
                    ],
                  ),
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "orders.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../state.dart';
          import '../models.dart';

          class OrdersScreen extends ConsumerWidget {
            const OrdersScreen({super.key});

            @override
            Widget build(BuildContext context, WidgetRef ref) {
              final api = ref.watch(apiProvider);
              return Scaffold(
                appBar: AppBar(title: const Text("My orders")),
                body: FutureBuilder<List<Order>>(
                  future: api.myOrders(),
                  builder: (context, snap) {
                    if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                    final orders = snap.data!;
                    if (orders.isEmpty) return const Center(child: Text("No orders yet"));
                    return ListView.separated(
                      itemCount: orders.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, i) {
                        final o = orders[i];
                        return ListTile(
                          title: Text("Order #${o.id} • ${o.status}"),
                          subtitle: Text("Total Rs ${o.totalAmount.toStringAsFixed(2)} • Items: ${o.items.length}"),
                        );
                      },
                    );
                  },
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "seller_home.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../state.dart';
          import '../models.dart';
          import '../bootstrap.dart';
          import 'login.dart';

          class SellerHome extends ConsumerStatefulWidget {
            const SellerHome({super.key});

            @override
            ConsumerState<SellerHome> createState() => _SellerHomeState();
          }

          class _SellerHomeState extends ConsumerState<SellerHome> {
            final _vendorName = TextEditingController(text: "My Store");

            @override
            Widget build(BuildContext context) {
              final api = ref.watch(apiProvider);

              return Scaffold(
                appBar: AppBar(
                  title: const Text("Seller"),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.login),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.seller))),
                    ),
                  ],
                ),
                body: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      TextField(controller: _vendorName, decoration: const InputDecoration(labelText: "Vendor name")),
                      const SizedBox(height: 8),
                      FilledButton(
                        onPressed: () async {
                          await api.sellerCreateVendor(name: _vendorName.text.trim());
                          if (!mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Vendor ready")));
                          setState(() {});
                        },
                        child: const Text("Create/Attach vendor"),
                      ),
                      const SizedBox(height: 16),
                      const Align(alignment: Alignment.centerLeft, child: Text("Incoming orders", style: TextStyle(fontWeight: FontWeight.bold))),
                      const SizedBox(height: 8),
                      Expanded(
                        child: FutureBuilder<List<Order>>(
                          future: api.sellerOrders(),
                          builder: (context, snap) {
                            if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                            final orders = snap.data!;
                            if (orders.isEmpty) return const Center(child: Text("No orders"));
                            return ListView.separated(
                              itemCount: orders.length,
                              separatorBuilder: (_, __) => const Divider(height: 1),
                              itemBuilder: (context, i) {
                                final o = orders[i];
                                return ListTile(
                                  title: Text("Order #${o.id} • ${o.status}"),
                                  subtitle: Text("Total Rs ${o.totalAmount.toStringAsFixed(2)}"),
                                  trailing: (o.status == "CREATED")
                                    ? FilledButton(
                                        onPressed: () async {
                                          await api.sellerAcceptOrder(o.id);
                                          setState(() {});
                                        },
                                        child: const Text("Accept"),
                                      )
                                    : null,
                                );
                              },
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }
          }
          '''))

    write(os.path.join(ROOT, "lib", "grabbasket", "ui", "partner_home.dart"),
          textwrap.dedent('''\
          import 'package:flutter/material.dart';
          import 'package:flutter_riverpod/flutter_riverpod.dart';
          import '../state.dart';
          import '../models.dart';
          import '../bootstrap.dart';
          import 'login.dart';

          class PartnerHome extends ConsumerStatefulWidget {
            const PartnerHome({super.key});

            @override
            ConsumerState<PartnerHome> createState() => _PartnerHomeState();
          }

          class _PartnerHomeState extends ConsumerState<PartnerHome> {
            bool _available = true;

            @override
            Widget build(BuildContext context) {
              final api = ref.watch(apiProvider);

              return Scaffold(
                appBar: AppBar(
                  title: const Text("Partner"),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.login),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen(flavor: AppFlavor.partner))),
                    ),
                  ],
                ),
                body: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      SwitchListTile(
                        title: const Text("Available for deliveries"),
                        value: _available,
                        onChanged: (v) async {
                          setState(() => _available = v);
                          await api.partnerAvailability(v);
                        },
                      ),
                      const Align(alignment: Alignment.centerLeft, child: Text("Assigned orders", style: TextStyle(fontWeight: FontWeight.bold))),
                      const SizedBox(height: 8),
                      Expanded(
                        child: FutureBuilder<List<Order>>(
                          future: api.partnerOrders(),
                          builder: (context, snap) {
                            if (!snap.hasData) return const Center(child: CircularProgressIndicator());
                            final orders = snap.data!;
                            if (orders.isEmpty) return const Center(child: Text("No assigned orders"));
                            return ListView.separated(
                              itemCount: orders.length,
                              separatorBuilder: (_, __) => const Divider(height: 1),
                              itemBuilder: (context, i) {
                                final o = orders[i];
                                return ListTile(
                                  title: Text("Order #${o.id} • ${o.status}"),
                                  subtitle: Text("Total Rs ${o.totalAmount.toStringAsFixed(2)}"),
                                  trailing: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      if (o.status == "ASSIGNED_TO_PARTNER")
                                        FilledButton(
                                          onPressed: () async { await api.partnerPickup(o.id); setState(() {}); },
                                          child: const Text("Picked up"),
                                        ),
                                      if (o.status == "PICKED_UP")
                                        FilledButton(
                                          onPressed: () async { await api.partnerDeliver(o.id); setState(() {}); },
                                          child: const Text("Delivered"),
                                        ),
                                    ],
                                  ),
                                );
                              },
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }
          }
          '''))

    print("Flutter lib/ entrypoints created.")

def main():
    append_pubspec_deps()
    patch_build_gradle()
    add_entrypoints_and_lib()

if __name__ == "__main__":
    main()
