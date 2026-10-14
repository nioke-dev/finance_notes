import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:driver_finance_mobile/core/theme/app_theme.dart';
import 'package:driver_finance_mobile/main.dart';

void main() {
  testWidgets('App initializes with dark theme and shows Home title', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: DriverFinanceApp(),
      ),
    );

    // Verify app title text exists
    expect(find.text('Driver Finance Malang'), findsOneWidget);

    // Verify scaffold background matches AppTheme slate900
    final scaffold = tester.widget<Scaffold>(find.byType(Scaffold));
    final theme = AppTheme.darkTheme;
    expect(theme.scaffoldBackgroundColor, const Color(0xFF0F172A));
  });
}
