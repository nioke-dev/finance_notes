import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/services/location_service.dart';
import '../../data/voice_memo_service.dart';

class VoiceMemoState {
  final bool isRecording;
  final int durationSeconds;
  final bool isUploading;
  final String? successMessage;
  final String? errorMessage;

  const VoiceMemoState({
    this.isRecording = false,
    this.durationSeconds = 0,
    this.isUploading = false,
    this.successMessage,
    this.errorMessage,
  });

  VoiceMemoState copyWith({
    bool? isRecording,
    int? durationSeconds,
    bool? isUploading,
    String? successMessage,
    String? errorMessage,
  }) {
    return VoiceMemoState(
      isRecording: isRecording ?? this.isRecording,
      durationSeconds: durationSeconds ?? this.durationSeconds,
      isUploading: isUploading ?? this.isUploading,
      successMessage: successMessage,
      errorMessage: errorMessage,
    );
  }
}

final voiceMemoControllerProvider =
    StateNotifierProvider<VoiceMemoController, VoiceMemoState>((ref) {
  final service = ref.watch(voiceMemoServiceProvider);
  final locationService = ref.watch(locationServiceProvider);

  return VoiceMemoController(
    service: service,
    locationService: locationService,
  );
});

class VoiceMemoController extends StateNotifier<VoiceMemoState> {
  final VoiceMemoService service;
  final LocationService locationService;
  Timer? _timer;

  VoiceMemoController({
    required this.service,
    required this.locationService,
  }) : super(const VoiceMemoState());

  Future<void> startRecording() async {
    await service.start();
    state = const VoiceMemoState(isRecording: true, durationSeconds: 0);

    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (state.durationSeconds >= 60) {
        stopAndUpload();
      } else {
        state = state.copyWith(
          isRecording: true,
          durationSeconds: state.durationSeconds + 1,
        );
      }
    });
  }

  Future<bool> stopAndUpload() async {
    _timer?.cancel();
    _timer = null;

    final path = await service.stop();
    state = state.copyWith(isRecording: false, isUploading: true, errorMessage: null);

    try {
      final loc = await locationService.getCurrentLocation();
      await service.upload(
        filePath: path,
        latitude: loc?.latitude,
        longitude: loc?.longitude,
      );

      state = state.copyWith(
        isUploading: false,
        durationSeconds: 0,
        successMessage: 'Voice memo berhasil disimpan dan diunggah.',
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isUploading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }
}
