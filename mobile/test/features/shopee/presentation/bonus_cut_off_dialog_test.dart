import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/shopee/presentation/widgets/bonus_cut_off_dialog.dart';

void main() {
  group('BonusCutOffDialog Widget Tests', () {
    testWidgets('BonusCutOffDialog displays bonus amount and shopeepay info when eligible', (tester) async {
      bool dismissed = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: BonusCutOffDialog(
              isEligible: true,
              bonusAmount: 37500,
              message: 'Selamat! Anda memenuhi target insentif hari ini.',
              onDismiss: () {
                dismissed = true;
              },
            ),
          ),
        ),
      );

      // Verify header & message
      expect(find.text('Target Bonus Tercapai!'), findsOneWidget);
      expect(find.text('Selamat! Anda memenuhi target insentif hari ini.'), findsOneWidget);

      // Verify bonus display & disbursement info
      expect(find.textContaining('Estimasi Bonus: Rp 37.500'), findsOneWidget);
      expect(find.text('Cair ke ShopeePay jam 22.00 - 22.30 WIB'), findsOneWidget);
      expect(find.byIcon(Icons.emoji_events_rounded), findsOneWidget);

      // Verify dismiss
      await tester.tap(find.text('Tutup'));
      expect(dismissed, true);
    });

    testWidgets('BonusCutOffDialog displays transparent failure reason when not eligible', (tester) async {
      bool dismissed = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: BonusCutOffDialog(
              isEligible: false,
              bonusAmount: 0,
              message: 'Jam online baru 7.5 jam (minimal 9.0 jam) atau order baru 12 trip.',
              onDismiss: () {
                dismissed = true;
              },
            ),
          ),
        ),
      );

      // Verify failure header & transparent reason
      expect(find.text('Evaluasi Bonus Jam 20.00'), findsOneWidget);
      expect(find.text('Jam online baru 7.5 jam (minimal 9.0 jam) atau order baru 12 trip.'), findsOneWidget);
      expect(find.byIcon(Icons.info_outline_rounded), findsOneWidget);

      // Ensure eligible container is hidden
      expect(find.textContaining('Estimasi Bonus:'), findsNothing);
      expect(find.text('Cair ke ShopeePay jam 22.00 - 22.30 WIB'), findsNothing);

      // Dismiss
      await tester.tap(find.text('Tutup'));
      expect(dismissed, true);
    });
  });
}
