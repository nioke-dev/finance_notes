# TASK-068: Layar Beranda Mobile (`HomeScreen`) & Banner Pengingat Hari Gantung

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** UI Screen Beranda Mobile, Ringkasan Saldo Cepat, & Banner Hari Belum Selesai  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun tampilan layar utama aplikasi mobile (`HomeScreen`) yang dioptimalkan untuk penggunaan 1 tangan saat di atas motor:
1. **Header & Saldo Cepat:**
   - Menampilkan total Kas Tunai Fisik di Dompet (presisi Rp1).
   - Menampilkan Saldo Dompet Driver ShopeePay & GoPay Merchant.
2. **Banner Pengingat Hari Belum Rekonsiliasi (Hari Gantung):**
   - Mengambil data dari endpoint `GET /api/v1/work-days/pending-reconciliations` (sesuai `TASK-054`).
   - Jika terdapat hari kemarin yang berstatus `needs_reconciliation` (selisih $\ne 0$), munculkan banner warna Amber/Orange mencolok:
     - *"Perhatian: Ada X hari kerja belum rekonsiliasi. Anda tetap bisa narik hari ini, selesaikan audit malam nanti."*
     - Tombol cepat untuk membuka detail hari gantung tersebut.
3. **Status Shift Hari Ini (Open / Closed):**
   - Tombol toggle besar: *"Buka Shift Hari Ini"* (memanggil `POST /api/v1/work-days/open` sesuai `TASK-053`).
   - Jika shift sudah dibuka, menampilkan counter trip on-beat saat ini.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/home/presentation/home_screen.dart` — Layar beranda utama Flutter.
2. `mobile/lib/features/home/presentation/widgets/pending_reconciliation_banner.dart` — Widget banner hari gantung.
3. `mobile/lib/features/home/presentation/widgets/quick_balance_card.dart` — Card saldo kas & dompet digital.
4. `mobile/test/features/home/presentation/home_screen_test.dart` — Widget test halaman beranda.

---

## 3. Spesifikasi Anatomi Kode (`pending_reconciliation_banner.dart`)
```dart
import 'package:flutter/material.dart';

class PendingReconciliationBanner extends StatelessWidget {
  final int pendingDaysCount;
  final VoidCallback onTap;

  const PendingReconciliationBanner({
    Key? key,
    required this.pendingDaysCount,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    if (pendingDaysCount == 0) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF78350F).withOpacity(0.35), // Dark Amber
        border: Border.all(color: const Color(0xFFF59E0B).withOpacity(0.8)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber_rounded, color: Color(0xFFF59E0B), size: 28),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$pendingDaysCount Hari Kerja Perlu Rekonsiliasi',
                  style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 13),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Selisih belum klop. Anda tetap bisa menarik hari ini.',
                  style: TextStyle(color: Color(0xFFD1D5DB), fontSize: 11),
                ),
              ],
            ),
          ),
          TextButton(
            onPressed: onTap,
            child: const Text('Audit', style: TextStyle(color: Color(0xFFF59E0B), fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `HomeScreen` menampilkan ringkasan saldo kas & status hari kerja aktif.
2. Banner hari gantung muncul jika ada hari berstatus `needs_reconciliation` dan tersembunyi (`SizedBox.shrink()`) jika 0.
3. Automated widget test lolos 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/home/presentation/home_screen_test.dart`
- **Metode Uji:**
  1. `testWidgets('HomeScreen displays pending reconciliation banner when count > 0', (tester) async)`:
     - Render `HomeScreen` dengan mock state pendingDays = 1.
     - Verifikasi teks 'Hari Kerja Perlu Rekonsiliasi' tampil di layar.
  2. `testWidgets('HomeScreen hides banner when pending count is 0', (tester) async)`:
     - Render `HomeScreen` dengan mock state pendingDays = 0.
     - Verifikasi banner tidak dirender.
