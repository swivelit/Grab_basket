import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:grabbasket/grabbasket/bootstrap.dart';
import 'package:grabbasket/grabbasket/state.dart';
import 'package:grabbasket/grabbasket/storage.dart';

void main() {
  testWidgets('Shows login screen when no session is stored', (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          secureStoreProvider.overrideWithValue(MemorySessionStore()),
        ],
        child: const GrabbasketApp(flavor: AppFlavor.customer),
      ),
    );

    // Wait for sessionProvider to resolve.
    await tester.pumpAndSettle();

    expect(find.text('Login'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
  });
}
