import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/core/services/location_service.dart';
import 'package:driver_finance_mobile/features/voice_memo/data/voice_memo_service.dart';
import 'package:driver_finance_mobile/features/voice_memo/presentation/controllers/voice_memo_controller.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';

class MockVoiceMemoService extends Fake implements VoiceMemoService {
  bool isStarted = false;
  bool isStopped = false;
  bool shouldThrow = false;
  String? uploadedFilePath;

  @override
  Future<void> start() async {
    isStarted = true;
  }

  @override
  Future<String?> stop() async {
    isStopped = true;
    return '/tmp/recorded_voice.m4a';
  }

  @override
  Future<Map<String, dynamic>> upload({
    required String? filePath,
    double? latitude,
    double? longitude,
  }) async {
    if (shouldThrow) throw Exception('Gagal upload audio');
    uploadedFilePath = filePath;
    return {'status': 'success'};
  }
}

class MockLocationServiceVoice extends Fake implements LocationService {
  @override
  Future<LocationData?> getCurrentLocation() async {
    return const LocationData(latitude: -7.9826, longitude: 112.6308);
  }
}

void main() {
  group('VoiceMemoController Tests', () {
    late MockVoiceMemoService mockService;
    late MockLocationServiceVoice mockLocation;
    late VoiceMemoController controller;

    setUp(() {
      mockService = MockVoiceMemoService();
      mockLocation = MockLocationServiceVoice();
      controller = VoiceMemoController(
        service: mockService,
        locationService: mockLocation,
      );
    });

    tearDown(() {
      controller.dispose();
    });

    test('startRecording starts timer and sets isRecording true', () async {
      await controller.startRecording();

      expect(mockService.isStarted, true);
      expect(controller.state.isRecording, true);
      expect(controller.state.durationSeconds, 0);
    });

    test('stopAndUpload stops recording and triggers upload', () async {
      await controller.startRecording();
      final success = await controller.stopAndUpload();

      expect(success, true);
      expect(mockService.isStopped, true);
      expect(mockService.uploadedFilePath, '/tmp/recorded_voice.m4a');
      expect(controller.state.isRecording, false);
      expect(controller.state.isUploading, false);
      expect(controller.state.successMessage, 'Voice memo berhasil disimpan dan diunggah.');
    });
  });
}
