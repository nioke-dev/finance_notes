# TASK-069: Widget Quick Parkir 1-Tap & Integrasi Geolocation GPS (`geolocator`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** 1-Tap Quick Action Potong Kas Parkir Rp1.000 / Rp2.000 dengan Auto-GPS  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun widget aksi cepat (*Quick Action*) pengeluaran parkir 1-tap di aplikasi mobile:
1. **Mekanisme 1-Tap:**
   - Tombol besar di beranda: Tombol **"Parkir 1k"** dan **"Parkir 2k"**.
   - Sekali tekan langsung:
     - Mendapatkan koordinat GPS secara instan di latar belakang via paket `geolocator` (latitude & longitude) tanpa memblokir UI.
     - Memanggil API `POST /api/v1/quick-actions/parking` (sesuai `TASK-032`).
     - Menampilkan getaran haptic feedback singkat & toast feedback halus *"Parkir Rp1.000 tercatat (GPS)"*.
2. **Opsi Nama Resto Opsional:**
   - Long-press pada tombol parkir membuka bottom sheet mini untuk mengisi nama resto/mall (opsional jika driver ingin mengingat lokasi). Jika hanya single tap, langsung terekam tanpa nama resto.
3. **Optimasi Lapangan (Toleransi Offline / Low Latency):**
   - Menggunakan timeout GPS 3 detik; jika sinyal GPS lambat (misal di basement mall), koordinat dikosongkan tanpa menggagalkan pencatatan pengeluaran.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/quick_actions/presentation/widgets/quick_parking_button.dart` — Tombol aksi cepat parkir 1-tap.
2. `mobile/lib/core/services/location_service.dart` — Service pembungkus `geolocator` dengan timeout aman.
3. `mobile/lib/features/quick_actions/data/quick_action_repository.dart` — Repository pemanggil API quick actions.
4. `mobile/test/features/quick_actions/presentation/quick_parking_button_test.dart` — Widget test tombol quick parking.

---

## 3. Spesifikasi Anatomi Kode (`quick_parking_button.dart`)
```dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class QuickParkingButton extends StatelessWidget {
  final int amount; // 1000 atau 2000
  final Future<void> Function(int amount) onQuickTap;

  const QuickParkingButton({
    Key? key,
    required this.amount,
    required this.onQuickTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final label = amount == 1000 ? '1k' : '2k';

    return ElevatedButton(
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF1E293B),
        foregroundColor: Colors.white,
        side: const BorderSide(color: Color(0xFF334155)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
      ),
      onPressed: () async {
        HapticFeedback.lightImpact();
        await onQuickTap(amount);
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.local_parking_rounded, color: Color(0xFF38BDF8), size: 24),
          const SizedBox(height: 4),
          Text('Parkir $label', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        ],
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Single tap pada `QuickParkingButton` memicu callback pengiriman nominal Rp1.000 atau Rp2.000 dengan haptic feedback.
2. `LocationService` mengembalikan koordinat latitude & longitude dengan batasan timeout 3 detik.
3. Widget test memverifikasi trigger callback saat tombol ditekan.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/quick_actions/presentation/quick_parking_button_test.dart`
- **Metode Uji:**
  1. `testWidgets('Tapping QuickParkingButton triggers onQuickTap with correct amount', (tester) async)`:
     - Pump widget `QuickParkingButton(amount: 1000, onQuickTap: mockCallback)`.
     - Tap tombol $\rightarrow$ Verifikasi mockCallback dipanggil dengan argumen `1000`.
