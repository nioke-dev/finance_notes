import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/closing/presentation/closing_screen.dart';
import 'package:mobile/features/closing/presentation/widgets/denomination_input_tile.dart';

void main() {
  group('ClosingScreen Widget Tests', () {
    testWidgets('Finalize button is disabled when physical total != book balance', (tester) async {
      bool finalized = false;

      await tester.pumpWidget(
        MaterialApp(
          home: ClosingScreen(
            bookBalance: 100000,
            onFinalize: (denoms) async {
              finalized = true;
            },
          ),
        ),
      );

      // Verify book balance displayed
      expect(find.text('Rp 100.000'), findsOneWidget);
      expect(find.textContaining('SELISIH: -Rp 100.000'), findsOneWidget);

      // Finalize button should be disabled
      final button = tester.widget<ElevatedButton>(find.byType(ElevatedButton));
      expect(button.onPressed, isNull);

      await tester.tap(find.byType(ElevatedButton));
      expect(finalized, false);
    });

    testWidgets('Finalize button becomes enabled when difference is exactly 0', (tester) async {
      Map<int, int>? finalizedDenoms;

      await tester.pumpWidget(
        MaterialApp(
          home: ClosingScreen(
            bookBalance: 100000,
            onFinalize: (denoms) async {
              finalizedDenoms = denoms;
            },
          ),
        ),
      );

      // Tap + button for 100k denomination (first tile)
      final addButtons = find.byIcon(Icons.add_circle_outline);
      await tester.tap(addButtons.first);
      await tester.pumpAndSettle();

      // Physical total is now 100.000, status is KLOP
      expect(find.text('STATUS: KLOP (Rp 0)'), findsOneWidget);

      // Finalize button should now be enabled
      final button = tester.widget<ElevatedButton>(find.byType(ElevatedButton));
      expect(button.onPressed, isNotNull);

      await tester.tap(find.byType(ElevatedButton));
      await tester.pumpAndSettle();

      expect(finalizedDenoms, isNotNull);
      expect(finalizedDenoms![100000], 1);
    });
  });
}
