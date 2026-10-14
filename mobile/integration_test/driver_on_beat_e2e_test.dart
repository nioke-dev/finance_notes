import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/features/home/presentation/home_screen.dart';
import 'package:driver_finance_mobile/features/quick_actions/presentation/widgets/quick_parking_button.dart';
import 'package:driver_finance_mobile/features/quick_actions/presentation/widgets/quick_fuel_bottom_sheet.dart';
import 'package:driver_finance_mobile/features/closing/presentation/closing_screen.dart';

void main() {
  group('E2E On-Beat Field Flow Integration Test', () {
    testWidgets('Complete Driver On-Beat Flow: Open Shift -> Parkir -> Bensin -> Balanced Closing', (tester) async {
      int simulatedCashBalance = 50000;
      bool shiftOpened = false;
      bool parkingRecorded = false;
      bool fuelRecorded = false;
      bool closingFinalized = false;

      // Render Home Screen
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: HomeScreen(
              driverName: 'Pak Driver',
              cashBalance: simulatedCashBalance,
              pendingWorkDaysCount: 0,
              onOpenShift: () async {
                shiftOpened = true;
              },
            ),
          ),
        ),
      );

      // 1. Verify Home & Open Shift
      expect(find.text('Buka Shift Hari Ini'), findsOneWidget);
      await tester.tap(find.text('Buka Shift Hari Ini'));
      await tester.pumpAndSettle();
      expect(shiftOpened, true);

      // 2. 1-Tap Parking 1k
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickParkingButton(
              onQuickPark: ({required amount, restaurantName}) async {
                parkingRecorded = true;
                simulatedCashBalance -= amount;
              },
            ),
          ),
        ),
      );

      expect(find.text('Parkir 1k'), findsOneWidget);
      await tester.tap(find.text('Parkir 1k'));
      await tester.pumpAndSettle();
      expect(parkingRecorded, true);
      expect(simulatedCashBalance, 49000);

      // 3. Quick Bensin SPBU 20k
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuickFuelBottomSheet(
              onSubmit: ({required fuelType, required amount, required paymentSource}) async {
                fuelRecorded = true;
                simulatedCashBalance -= amount;
              },
            ),
          ),
        ),
      );

      expect(find.text('Catat Bensin Motor'), findsOneWidget);
      await tester.tap(find.textContaining('Simpan Pengeluaran (Rp 20.000)'));
      await tester.pumpAndSettle();
      expect(fuelRecorded, true);
      expect(simulatedCashBalance, 29000); // 50k - 1k - 20k = 29k

      // 4. Closing Screen Audit
      // Target balance is 29k: 1x 20k, 1x 5k, 2x 2k = 29k
      await tester.pumpWidget(
        MaterialApp(
          home: ClosingScreen(
            bookBalance: simulatedCashBalance,
            onFinalize: (denoms) async {
              closingFinalized = true;
            },
          ),
        ),
      );

      // Verify book balance displays 29.000 and difference is currently unbalanced
      expect(find.text('Rp 29.000'), findsOneWidget);
      expect(find.textContaining('SELISIH: -Rp 29.000'), findsOneWidget);

      // Finalize button should be disabled initially
      final finalizeBtnInitial = tester.widget<ElevatedButton>(find.byType(ElevatedButton));
      expect(finalizeBtnInitial.onPressed, isNull);

      // Fill denominations to match 29.000 exactly:
      // Tile 2: 20k (Index 2 in add buttons)
      final addButtons = find.byIcon(Icons.add_circle_outline);
      await tester.tap(addButtons.at(2)); // +1 of 20.000
      await tester.pumpAndSettle();

      // Tile 4: 5k (Index 4 in add buttons)
      await tester.tap(addButtons.at(4)); // +1 of 5.000
      await tester.pumpAndSettle();

      // Tile 5: 2k (Index 5 in add buttons)
      await tester.tap(addButtons.at(5)); // +1 of 2.000
      await tester.pumpAndSettle();
      await tester.tap(addButtons.at(5)); // +1 of 2.000 (Total: 20k + 5k + 4k = 29k)
      await tester.pumpAndSettle();

      // Verify status is now KLOP
      expect(find.text('STATUS: KLOP (Rp 0)'), findsOneWidget);

      // Finalize button should now be enabled
      final finalizeBtnReady = tester.widget<ElevatedButton>(find.byType(ElevatedButton));
      expect(finalizeBtnReady.onPressed, isNotNull);

      // Execute finalization
      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(closingFinalized, true);
    });
  });
}
