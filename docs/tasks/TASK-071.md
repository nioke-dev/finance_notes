# TASK-071: Floating Action Button Audio Recorder Intercom Helm (`record` Package)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Floating Action Button Perekam Suara Mikrofon Helm & Upload Otomatis  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun fitur perekaman catatan suara cepat (*Voice Memo*) via mikrofon headset intercom helm menggunakan paket `record`:
1. **Antarmuka Floating Action Button (FAB):**
   - Ikon Mikrofon mencolok di sudut kanan bawah aplikasi.
   - **Tekan Sekali (Start Recording):** FAB berubah menjadi warna merah berdenyut (*pulsing red animation*) dengan counter durasi berjalan (misal: "0:05").
   - **Tekan Sekali Lagi (Stop Recording):** Menghentikan rekaman audio, otomatis mengambil koordinat GPS terkini, dan mengirim audio ke server via multipart form data (`POST /api/v1/voice-memos` sesuai `TASK-037`).
2. **Format Audio & Optimasi Ukuran:**
   - Format: AAC / M4A (kompresi ringan, rata-rata $< 500$ KB untuk 30 detik rekaman).
   - Maksimum durasi otomatis: 60 detik (auto-stop jika driver lupa menekan stop).
3. **Penyimpanan Lokal Sementara:**
   - File tersimpan sementara di direktori temporary aplikasi (`path_provider`) dan langsung dibersihkan setelah berhasil di-upload ke backend Laravel.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/voice_memo/presentation/widgets/voice_memo_fab.dart` — Floating action button perekam suara.
2. `mobile/lib/features/voice_memo/data/voice_memo_service.dart` — Service pembungkus logic rekaman audio `record`.
3. `mobile/test/features/voice_memo/presentation/voice_memo_fab_test.dart` — Widget test FAB audio recorder.

---

## 3. Spesifikasi Anatomi Kode (`voice_memo_fab.dart`)
```dart
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class VoiceMemoFab extends StatefulWidget {
  final Future<void> Function() onStartRecord;
  final Future<void> Function() onStopRecord;

  const VoiceMemoFab({
    Key? key,
    required this.onStartRecord,
    required this.onStopRecord,
  }) : super(key: key);

  @override
  State<VoiceMemoFab> createState() => _VoiceMemoFabState();
}

class _VoiceMemoFabState extends State<VoiceMemoFab> {
  bool _isRecording = false;
  int _seconds = 0;
  Timer? _timer;

  void _toggleRecord() async {
    HapticFeedback.heavyImpact();
    if (_isRecording) {
      _timer?.cancel();
      setState(() {
        _isRecording = false;
        _seconds = 0;
      });
      await widget.onStopRecord();
    } else {
      setState(() => _isRecording = true);
      _timer = Timer.periodic(const Duration(seconds: 1), (t) {
        setState(() => _seconds++);
        if (_seconds >= 60) _toggleRecord(); // Auto-stop limit
      });
      await widget.onStartRecord();
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton.extended(
      backgroundColor: _isRecording ? const Color(0xFFF43F5E) : const Color(0xFF10B981),
      onPressed: _toggleRecord,
      icon: Icon(_isRecording ? Icons.stop_rounded : Icons.mic_rounded, color: Colors.white),
      label: Text(
        _isRecording ? 'Rekam ($_seconds s)' : 'Memo Suara',
        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Menekan FAB memicu `onStartRecord` dan memulai hitungan durasi detik.
2. Menekan FAB kembali memicu `onStopRecord` dan menghentikan timer.
3. Maksimal durasi 60 detik otomatis memicu stop recording.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/voice_memo/presentation/voice_memo_fab_test.dart`
- **Metode Uji:**
  1. `testWidgets('Tapping FAB toggles recording state and triggers start/stop callbacks', (tester) async)`:
     - Pump widget `VoiceMemoFab`.
     - Tap 1x $\rightarrow$ Pastikan label berubah menjadi 'Rekam (0 s)' dan start callback terpanggil.
     - Tap 2x $\rightarrow$ Pastikan stop callback terpanggil dan label kembali ke 'Memo Suara'.
