import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/voice_memo/presentation/widgets/voice_memo_fab.dart';

void main() {
  group('VoiceMemoFab Widget Tests', () {
    testWidgets('Tapping FAB toggles recording state and triggers start/stop callbacks', (tester) async {
      int startCount = 0;
      int stopCount = 0;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            floatingActionButton: VoiceMemoFab(
              onStartRecord: () async {
                startCount++;
              },
              onStopRecord: () async {
                stopCount++;
              },
            ),
          ),
        ),
      );

      // Verify initial state
      expect(find.text('Memo Suara'), findsOneWidget);
      expect(find.byIcon(Icons.mic_rounded), findsOneWidget);
      expect(startCount, 0);
      expect(stopCount, 0);

      // Tap to start recording
      await tester.tap(find.byType(VoiceMemoFab));
      await tester.pump();

      expect(startCount, 1);
      expect(stopCount, 0);
      expect(find.text('Rekam (0s)'), findsOneWidget);
      expect(find.byIcon(Icons.stop_rounded), findsOneWidget);

      // Fast forward 3 seconds
      await tester.pump(const Duration(seconds: 3));
      expect(find.text('Rekam (3s)'), findsOneWidget);

      // Tap to stop recording
      await tester.tap(find.byType(VoiceMemoFab));
      await tester.pump();

      expect(startCount, 1);
      expect(stopCount, 1);
      expect(find.text('Memo Suara'), findsOneWidget);
      expect(find.byIcon(Icons.mic_rounded), findsOneWidget);
    });

    testWidgets('Auto-stops recording when reaching 60 seconds limit', (tester) async {
      int startCount = 0;
      int stopCount = 0;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            floatingActionButton: VoiceMemoFab(
              onStartRecord: () async {
                startCount++;
              },
              onStopRecord: () async {
                stopCount++;
              },
            ),
          ),
        ),
      );

      // Tap to start
      await tester.tap(find.byType(VoiceMemoFab));
      await tester.pump();
      expect(startCount, 1);

      // Fast forward 60 seconds
      await tester.pump(const Duration(seconds: 60));
      await tester.pump();

      // Should automatically trigger stop
      expect(stopCount, 1);
      expect(find.text('Memo Suara'), findsOneWidget);
    });
  });
}
