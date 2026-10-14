import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:driver_finance_mobile/features/home/presentation/home_screen.dart';
import 'package:driver_finance_mobile/features/home/presentation/widgets/pending_reconciliation_banner.dart';

void main() {
  group('HomeScreen Tests', () {
    testWidgets('HomeScreen displays pending reconciliation banner when count > 0', (WidgetTester tester) async {
      await tester.pumpWidget(
        const ProviderScope(
          child: MaterialApp(
            home: HomeScreen(
              pendingDaysCount: 2,
              cashInWallet: 150000,
            ),
          ),
        ),
      );

      // Verifikasi banner muncul
      expect(find.text('2 Hari Kerja Perlu Rekonsiliasi'), findsOneWidget);
      expect(find.text('Audit'), findsOneWidget);
      expect(find.byType(PendingReconciliationBanner), findsOneWidget);
      expect(find.text('Rp 150.000'), findsOneWidget);
    });

    testWidgets('HomeScreen hides banner when pending count is 0', (WidgetTester tester) async {
      await tester.pumpWidget(
        const ProviderScope(
          child: MaterialApp(
            home: HomeScreen(
              pendingDaysCount: 0,
              cashInWallet: 50000,
            ),
          ),
        ),
      );

      // Verifikasi banner tidak dirender (SizedBox.shrink)
      expect(find.text('Hari Kerja Perlu Rekonsiliasi'), findsNothing);
      expect(find.text('Audit'), findsNothing);
    });

    testWidgets('HomeScreen displays open shift button when shift is closed', (WidgetTester tester) async {
      bool openShiftTriggered = false;

      await tester.pumpWidget(
        ProviderScope(
          child: MaterialApp(
            home: HomeScreen(
              isShiftOpen: false,
              onOpenShift: () {
                openShiftTriggered = true;
              },
            ),
          ),
        ),
      );

      expect(find.text('BUKA SHIFT HARI INI'), findsOneWidget);

      await tester.tap(find.text('BUKA SHIFT HARI INI'));
      expect(openShiftTriggered, true);
    });
  });
}
