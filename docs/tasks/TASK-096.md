# TASK-096: Riverpod State Notifier Voice Memo (`VoiceMemoController`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** State Management Perekam Suara Intercom Helm, Timer Berjalan, & Background Upload  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Riverpod State Notifier `VoiceMemoController` untuk mengontrol siklus hidup perekaman suara mikrofon intercom helm:
1. **Model State (`VoiceMemoState`):**
   - `isRecording`: Boolean.
   - `durationSeconds`: Integer (0 s/d 60 detik).
   - `isUploading`: Boolean.
   - `lastUploadedMemoId`: Integer nullable.
   - `errorMessage`: String nullable.
2. **Logika Kontrol:**
   - Memulai rekaman via paket `record` dan mengaktifkan timer 1 detik.
   - Auto-stop ketika durasi mencapai 60 detik.
   - Mengambil koordinat GPS terkini secara asynchronous.
   - Mengirim file audio via multipart POST `/api/v1/voice-memos` dan membersihkan file lokal sementara di temporary directory.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/voice_memo/presentation/controllers/voice_memo_controller.dart` — State Notifier Riverpod Voice Memo.
2. `mobile/test/features/voice_memo/presentation/voice_memo_controller_test.dart` — Unit test State Notifier Voice Memo.

---

## 3. Spesifikasi Anatomi Kode (`voice_memo_controller.dart`)
```dart
import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/core/services/location_service.dart';
import 'package:mobile/features/voice_memo/data/voice_memo_service.dart';

class VoiceMemoState {
  final bool isRecording;
  final int durationSeconds;
  final bool isUploading;
  final String? errorMessage;

  VoiceMemoState({
    this.isRecording = false,
    this.durationSeconds = 0,
    this.isUploading = false,
    this.errorMessage,
  });
}

class VoiceMemoController extends StateNotifier<VoiceMemoState> {
  final VoiceMemoService service;
  final LocationService locationService;
  Timer? _timer;

  VoiceMemoController({required this.service, required this.locationService})
      : super(VoiceMemoState());

  Future<void> startRecording() async {
    await service.start();
    state = VoiceMemoState(isRecording: true, durationSeconds: 0);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (state.durationSeconds >= 60) {
        stopAndUpload();
      } else {
        state = VoiceMemoState(isRecording: true, durationSeconds: state.durationSeconds + 1);
      }
    });
  }

  Future<void> stopAndUpload() async {
    _timer?.cancel();
    final path = await service.stop();
    state = VoiceMemoState(isRecording: false, isUploading: true);
    try {
      final loc = await locationService.getCurrentLocation();
      await service.upload(filePath: path, latitude: loc?.latitude, longitude: loc?.longitude);
      state = VoiceMemoState(isRecording: false, isUploading: false);
    } catch (e) {
      state = VoiceMemoState(isRecording: false, isUploading: false, errorMessage: e.toString());
    }
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Controller mengupdate durasi detik per detik saat proses recording aktif.
2. Otomatis berhenti ketika mencapai 60 detik.
3. Unit test memverifikasi timer, start, stop, dan penanganan upload.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/voice_memo/presentation/voice_memo_controller_test.dart`
- **Metode Uji:**
  1. `test('startRecording starts timer and sets isRecording true')`:
     - Panggil `controller.startRecording()`.
     - Verifikasi `state.isRecording == true`.
