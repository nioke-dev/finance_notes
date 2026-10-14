import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/quick_actions/presentation/widgets/quick_fuel_bottom_sheet.dart';

void main() {
  group('QuickFuelBottomSheet Widget Tests', () {
    testWidgets('Renders default SPBU preset chips and handles selection', (tester) async {
      String? submittedFuelType;
      int? submittedAmount;
      String? submittedPaymentSource;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickFuelBottomSheet(
              onSubmit: ({required fuelType, required amount, required paymentSource}) async {
                submittedFuelType = fuelType;
                submittedAmount = amount;
                submittedPaymentSource = paymentSource;
              },
            ),
          ),
        ),
      );

      // Verify title & SPBU default
      expect(find.text('Catat Bensin Motor'), findsOneWidget);
      expect(find.text('SPBU Resmi (10k/L)'), findsOneWidget);
      expect(find.text('Madura (12k/Btl)'), findsOneWidget);

      // Verify SPBU chips (15k, 20k, 25k, 30k)
      expect(find.textContaining('Rp 20k (~2.0 L)'), findsOneWidget);
      expect(find.textContaining('Simpan Pengeluaran (Rp 20.000)'), findsOneWidget);

      // Select 25k chip
      await tester.tap(find.textContaining('Rp 25k (~2.5 L)'));
      await tester.pumpAndSettle();

      // Submit
      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(submittedFuelType, 'spbu');
      expect(submittedAmount, 25000);
      expect(submittedPaymentSource, 'cash');
    });

    testWidgets('Selecting Madura switches preset chips to 12k intervals', (tester) async {
      String? submittedFuelType;
      int? submittedAmount;
      String? submittedPaymentSource;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickFuelBottomSheet(
              onSubmit: ({required fuelType, required amount, required paymentSource}) async {
                submittedFuelType = fuelType;
                submittedAmount = amount;
                submittedPaymentSource = paymentSource;
              },
            ),
          ),
        ),
      );

      // Tap Madura toggle
      await tester.tap(find.text('Madura (12k/Btl)'));
      await tester.pumpAndSettle();

      // Verify Madura chips (1 Botol / 2 Botol)
      expect(find.textContaining('1 Botol (Rp 12k)'), findsOneWidget);
      expect(find.textContaining('2 Botol (Rp 24k)'), findsOneWidget);
      expect(find.textContaining('Simpan Pengeluaran (Rp 12.000)'), findsOneWidget);

      // Select 2 Botol (24k)
      await tester.tap(find.textContaining('2 Botol (Rp 24k)'));
      await tester.pumpAndSettle();

      expect(find.textContaining('Simpan Pengeluaran (Rp 24.000)'), findsOneWidget);

      // Submit
      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(submittedFuelType, 'madura');
      expect(submittedAmount, 24000);
      expect(submittedPaymentSource, 'cash');
    });
  });
}
