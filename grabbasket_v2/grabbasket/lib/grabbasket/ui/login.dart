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
