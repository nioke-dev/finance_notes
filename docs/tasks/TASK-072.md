# TASK-072: Layar On-Beat Progress Snapshot & Dialog Evaluasi Jam 20.00 Flutter

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Layar Snapshot Monitoring Trip Lapangan & Pop-up Evaluasi Bonus Jam 20.00  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun antarmuka mobile untuk memantau performa narik on-beat secara real-time dan evaluasi bonus malam:
1. **Layar On-Beat Tracker (`OnBeatScreen`):**
   - Menampilkan total jam online hari ini (`online_hours`).
   - Progress bar pencapaian target bonus ShopeeFood Malang:
     - Tier 1: 16 Order (Rp1.000/order)
     - Tier 2: 21 Order (Rp1.500/order)
     - Tier 3: 26+ Order (Rp2.000/order)
   - Tombol cepat tambah snapshot performa manual (`POST /api/v1/driver/snapshots` sesuai `TASK-026`).
2. **Dialog Pop-up Evaluasi Jam 20.00 (`BonusCutOffDialog`):**
   - Ketika waktu lokal $\ge$ 20.00 WIB dan shift masih terbuka:
     - Memanggil API `GET /api/v1/driver/evaluate-bonus` (sesuai `TASK-024`).
     - Jika lolos syarat (online $\ge$ 9 jam & order $\ge$ 16):
       - Menampilkan ucapan selamat, total estimasi bonus yang berhak didapatkan, dan pesan pengingat: *"Bonus cair ke ShopeePay jam 22.00-22.30 WIB"*.
     - Jika gagal:
       - Menampilkan alasan kegagalan transparan (misal: "Online baru 8.2 jam / Order baru 14 trip").

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/shopee/presentation/on_beat_screen.dart` — Layar monitoring target order harian.
2. `mobile/lib/features/shopee/presentation/widgets/bonus_cut_off_dialog.dart` — Dialog modal evaluasi jam 20.00.
3. `mobile/test/features/shopee/presentation/bonus_cut_off_dialog_test.dart` — Widget test modal dialog evaluasi bonus.

---

## 3. Spesifikasi Anatomi Kode (`bonus_cut_off_dialog.dart`)
```dart
import 'package:flutter/material.dart';

class BonusCutOffDialog extends StatelessWidget {
  final bool isEligible;
  final int bonusAmount;
  final String message;
  final VoidCallback onDismiss;

  const BonusCutOffDialog({
    Key? key,
    required this.isEligible,
    required this.bonusAmount,
    required this.message,
    required this.onDismiss,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: const Color(0xFF1E293B),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              isEligible ? Icons.emoji_events_rounded : Icons.info_outline_rounded,
              color: isEligible ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
              size: 48,
            ),
            const SizedBox(height: 12),
            Text(
              isEligible ? 'Target Bonus Tercapai!' : 'Evaluasi Bonus Jam 20.00',
              style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
            ),
            if (isEligible) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Estimasi Bonus: Rp $bonusAmount',
                  style: const TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Cair ke ShopeePay jam 22.00 - 22.30 WIB',
                style: TextStyle(color: Color(0xFF64748B), fontSize: 11),
              ),
            ],
            const SizedBox(height: 18),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFEE4D2D),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: onDismiss,
              child: const Text('Tutup', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `BonusCutOffDialog` menampilkan indikator reward hijau dan estimasi bonus saat `isEligible == true`.
2. Menampilkan pesan evaluasi gagal secara transparan saat `isEligible == false`.
3. Widget test memverifikasi rendering modal dialog dengan kedua kondisi.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/shopee/presentation/bonus_cut_off_dialog_test.dart`
- **Metode Uji:**
  1. `testWidgets('BonusCutOffDialog displays bonus amount and shopeepay info when eligible', (tester) async)`:
     - Render dialog dengan `isEligible: true, bonusAmount: 37500`.
     - Verifikasi teks 'Estimasi Bonus: Rp 37500' dan 'Cair ke ShopeePay jam 22.00' ada di layar.
