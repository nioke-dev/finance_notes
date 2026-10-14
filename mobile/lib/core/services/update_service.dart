import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../network/api_client.dart';

final updateServiceProvider = Provider<UpdateService>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return UpdateService(apiClient);
});

class AppUpdateInfo {
  final bool hasUpdate;
  final bool forceUpdate;
  final int latestVersionCode;
  final String latestVersionName;
  final String downloadUrl;
  final String title;
  final List<String> changelog;

  AppUpdateInfo({
    required this.hasUpdate,
    required this.forceUpdate,
    required this.latestVersionCode,
    required this.latestVersionName,
    required this.downloadUrl,
    required this.title,
    required this.changelog,
  });

  factory AppUpdateInfo.fromJson(Map<String, dynamic> json) {
    return AppUpdateInfo(
      hasUpdate: json['has_update'] ?? false,
      forceUpdate: json['force_update'] ?? false,
      latestVersionCode: json['latest_version_code'] ?? 1,
      latestVersionName: json['latest_version_name'] ?? '1.0.0',
      downloadUrl: json['download_url'] ?? '',
      title: json['title'] ?? 'Pembaruan Tersedia',
      changelog: List<String>.from(json['changelog'] ?? []),
    );
  }
}

class UpdateService {
  final ApiClient apiClient;

  UpdateService(this.apiClient);

  Future<AppUpdateInfo?> checkForUpdate({int currentVersionCode = 1}) async {
    try {
      final response = await apiClient.dio.get(
        '/app/check-update',
        queryParameters: {'version_code': currentVersionCode},
      );
      if (response.statusCode == 200 && response.data != null) {
        return AppUpdateInfo.fromJson(response.data);
      }
    } catch (e) {
      debugPrint('Gagal memeriksa update: $e');
    }
    return null;
  }

  static void showUpdateDialog(BuildContext context, AppUpdateInfo info) {
    showDialog(
      context: context,
      barrierDismissible: !info.forceUpdate,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.system_update_rounded, color: Color(0xFF10B981), size: 28),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                info.title,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF10B981).withOpacity(0.15),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Versi ${info.latestVersionName} (Build #${info.latestVersionCode})',
                style: const TextStyle(
                  color: Color(0xFF10B981),
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Text(
              'Apa yang baru di versi ini:',
              style: TextStyle(
                color: Color(0xFF94A3B8),
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            ...info.changelog.map((log) => Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('• ', style: TextStyle(color: Color(0xFF10B981))),
                      Expanded(
                        child: Text(
                          log,
                          style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
        actions: [
          if (!info.forceUpdate)
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Nanti Saja', style: TextStyle(color: Color(0xFF94A3B8))),
            ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  backgroundColor: const Color(0xFF0F172A),
                  content: Text(
                    'Mengunduh APK dari: ${info.downloadUrl}',
                    style: const TextStyle(color: Color(0xFF10B981)),
                  ),
                ),
              );
            },
            icon: const Icon(Icons.download_rounded, size: 18),
            label: const Text('Unduh Sekarang'),
          ),
        ],
      ),
    );
  }
}
