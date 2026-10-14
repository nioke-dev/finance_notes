import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/features/quick_actions/presentation/widgets/quick_parking_button.dart';

void main() {
  group('QuickParkingButton Widget Tests', () {
    testWidgets('Tapping QuickParkingButton triggers onQuickTap with correct amount', (WidgetTester tester) async {
      int? tappedAmount;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickParkingButton(
              amount: 1000,
              onQuickTap: (amount) async {
                tappedAmount = amount;
              },
            ),
          ),
        ),
      );

      // Verifikasi label tombol
      expect(find.text('Parkir 1k'), findsOneWidget);
      expect(find.text('Rp 1.000'), findsOneWidget);

      // Tap widget
      await tester.tap(find.byType(QuickParkingButton));
      await tester.pump();

      expect(tappedAmount, 1000);
    });

    testWidgets('Tapping 2k variant triggers with 2000', (WidgetTester tester) async {
      int? tappedAmount;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickParkingButton(
              amount: 2000,
              onQuickTap: (amount) async {
                tappedAmount = amount;
              },
            ),
          ),
        ),
      );

      expect(find.text('Parkir 2k'), findsOneWidget);
      expect(find.text('Rp 2.000'), findsOneWidget);

      await tester.tap(find.byType(QuickParkingButton));
      await tester.pump();

      expect(tappedAmount, 2000);
    });
  });
}
