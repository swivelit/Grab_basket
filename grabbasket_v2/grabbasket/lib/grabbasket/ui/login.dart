import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../bootstrap.dart';
import '../api.dart';
import '../state.dart';

class LoginScreen extends ConsumerStatefulWidget {
  final AppFlavor flavor;
  const LoginScreen({super.key, required this.flavor});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();

  bool _isRegister = false;
  bool _loading = false;
  bool _hidePassword = true;

  String get _role => switch (widget.flavor) {
        AppFlavor.customer => 'CUSTOMER',
        AppFlavor.seller => 'SELLER',
        AppFlavor.partner => 'PARTNER',
      };

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final ok = _formKey.currentState?.validate() ?? false;
    if (!ok) return;

    setState(() => _loading = true);
    try {
      final api = Api();
      final email = _email.text.trim();
      final password = _password.text;

      final res = _isRegister
          ? await api.register(email: email, password: password, role: _role)
          : await api.login(email: email, password: password);

      // Persist session.
      await ref.read(secureStoreProvider).saveSession(token: res.accessToken, role: res.role);

      // Refresh providers so subsequent API calls use the new token.
      ref.invalidate(sessionProvider);

      if (!mounted) return;
      // Go back to app root; AppGate will show the correct home.
      context.go('/');
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = _isRegister ? 'Create account' : 'Login';

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Role: $_role',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      autofillHints: const [AutofillHints.email],
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) {
                        final s = (v ?? '').trim();
                        if (s.isEmpty) return 'Email is required';
                        if (!s.contains('@') || !s.contains('.')) return 'Enter a valid email';
                        return null;
                      },
                      enabled: !_loading,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _password,
                      obscureText: _hidePassword,
                      autofillHints: _isRegister ? const [AutofillHints.newPassword] : const [AutofillHints.password],
                      decoration: InputDecoration(
                        labelText: 'Password',
                        border: const OutlineInputBorder(),
                        suffixIcon: IconButton(
                          onPressed: _loading ? null : () => setState(() => _hidePassword = !_hidePassword),
                          icon: Icon(_hidePassword ? Icons.visibility : Icons.visibility_off),
                        ),
                      ),
                      validator: (v) {
                        final s = (v ?? '');
                        if (s.isEmpty) return 'Password is required';
                        if (_isRegister && s.length < 6) return 'Use at least 6 characters';
                        return null;
                      },
                      enabled: !_loading,
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _loading ? null : _submit,
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        child: Text(_loading ? 'Please wait…' : (_isRegister ? 'Create account' : 'Login')),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: _loading
                          ? null
                          : () {
                              setState(() => _isRegister = !_isRegister);
                            },
                      child: Text(_isRegister ? 'Have an account? Login' : 'New here? Create an account'),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Tip: SELLER → create/attach vendor. PARTNER → set availability ON.',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
