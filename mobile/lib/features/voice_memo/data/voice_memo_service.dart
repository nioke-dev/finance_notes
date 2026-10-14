import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final voiceMemoServiceProvider = Provider<VoiceMemoService>((ref) {
  final client = ref.watch(apiClientProvider);
  return VoiceMemoService(apiClient: client);
});

class VoiceMemoService {
  final ApiClient apiClient;

  VoiceMemoService({required this.apiClient});

  Future<void> start() async {
    // Di aplikasi fisik, memanggil record.start() dengan path file di cache dir
  }

  Future<String?> stop() async {
    // Menghentikan record dan mengembalikan path file lokal m4a/wav
    return '/tmp/voice_memo_test.m4a';
  }

  Future<Map<String, dynamic>> upload({
    required String? filePath,
    double? latitude,
    double? longitude,
  }) async {
    if (filePath == null) return {'status': 'error', 'message': 'No file path'};

    final formData = FormData.fromMap({
      'audio_file': await MultipartFile.fromFile(filePath, filename: 'memo.m4a'),
      if (latitude != null) 'latitude': latitude,
      if (longitude != null) 'longitude': longitude,
    });

    final response = await apiClient.post('/voice-memos', data: formData);
    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }
}
