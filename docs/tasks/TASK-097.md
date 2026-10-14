# TASK-097: Integration Test End-to-End On-Beat Flow Mobile Flutter

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Automated End-to-End Flow Test Skenario Narik Lapangan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun automated integration test komprehensif pada aplikasi mobile Flutter yang mensimulasikan alur operasional driver secara nyata:
1. **Skenario Pengujian Alur Lengkap:**
   - **Langkah 1 (Buka Shift):** Driver menekan tombol Buka Shift $\rightarrow$ shift hari ini aktif.
   - **Langkah 2 (Parkir 1-Tap):** Driver menekan tombol `QuickParkingButton` nominal Rp1.000 $\rightarrow$ saldo kas berkurang Rp1.000, koordinat GPS terlampir.
   - **Langkah 3 (Isi Bensin):** Driver membuka bottom sheet bensin, memilih SPBU Rp20.000 $\rightarrow$ saldo kas berkurang Rp20.000.
   - **Langkah 4 (COD Pelanggan):** Driver mencatat pesanan COD tagihan Rp45.000, terima Rp50.000 $\rightarrow$ saldo kas bertambah Rp50.000, tip surplus Rp5.000 terakui.
   - **Langkah 5 (Closing Fisik):** Driver membuka layar closing, mengisi pecahan fisik sesuai sisa uang $\rightarrow$ selisih Rp0 $\rightarrow$ tombol Finalisasi aktif dan sukses menutup hari kerja.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/integration_test/driver_on_beat_e2e_test.dart` — Integration test E2E driver lapangan.

---

## 3. Spesifikasi Anatomi Kode (`driver_on_beat_e2e_test.dart`)
```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:mobile/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('Complete Driver On-Beat Flow: Open Day -> Parkir -> Bensin -> COD -> Balanced Closing', (tester) async {
    app.main();
    await tester.pumpAndSettle();

    // 1. Verify Home & Open Shift
    expect(find.text('Buka Shift Hari Ini'), findsOneWidget);
    await tester.tap(find.text('Buka Shift Hari Ini'));
    await tester.pumpAndSettle();

    // 2. 1-Tap Parking 1k
    expect(find.text('Parkir 1k'), findsOneWidget);
    await tester.tap(find.text('Parkir 1k'));
    await tester.pumpAndSettle();

    // 3. Quick Bensin SPBU
    // ... test flow bensin ...

    // 4. Closing Denominations
    // ... test balanced closing ...
  });
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Seluruh rantai interaksi user dari buka shift sampai closing tereksekusi tanpa crash UI.
2. Perhitungan matematis saldo di setiap langkah sesuai dengan ledger backend.
3. Test suite integrasi lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/integration_test/driver_on_beat_e2e_test.dart`
- **Metode Uji:**
  1. `testWidgets('Complete Driver On-Beat Flow')` menjalankan seluruh skenario terpadu.
