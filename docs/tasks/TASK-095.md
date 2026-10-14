# TASK-095: Riverpod State Notifier Closing Audit Mobile (`ClosingController`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** State Management Hitung Fisik Kas, Audit Selisih Realtime, & Eksekusi Finalisasi  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Riverpod State Notifier `ClosingController` untuk mengelola interaksi audit closing di layar mobile:
1. **Model State (`ClosingState`):**
   - `bookBalance`: Integer (saldo kas buku dari backend).
   - `denominations`: Map<int, int> (jumlah lembar per nominal).
   - `physicalTotal`: Integer (hasil perkalian nominal x quantity).
   - `difference`: Integer (physicalTotal - bookBalance).
   - `isFinalizing`: Boolean.
   - `errorMessage`: String nullable.
2. **Logika Reaktif di Controller:**
   - Metode `updateQuantity(int nominal, int qty)`: Memperbarui lembar pecahan dan langsung menghitung ulang `physicalTotal` dan `difference` tanpa menunggu response server.
   - Metode `finalize(int workDayId)`: Memvalidasi `difference == 0` sebelum memanggil `ClosingRepository.finalizeClosing()`. Jika ada selisih, aksi finalisasi ditolak di client layer.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/closing/presentation/controllers/closing_controller.dart` — State Notifier Riverpod closing audit.
2. `mobile/test/features/closing/presentation/closing_controller_test.dart` — Unit test State Notifier closing.

---

## 3. Spesifikasi Anatomi Kode (`closing_controller.dart`)
```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/features/closing/data/closing_repository.dart';

class ClosingState {
  final int bookBalance;
  final Map<int, int> denominations;
  final bool isFinalizing;
  final String? errorMessage;

  ClosingState({
    required this.bookBalance,
    required this.denominations,
    this.isFinalizing = false,
    this.errorMessage,
  });

  int get physicalTotal => denominations.entries.fold(0, (sum, e) => sum + (e.key * e.value));
  int get difference => physicalTotal - bookBalance;
  bool get isBalanced => difference == 0;
}

class ClosingController extends StateNotifier<ClosingState> {
  final ClosingRepository repository;

  ClosingController({required this.repository, required int initialBookBalance})
      : super(ClosingState(bookBalance: initialBookBalance, denominations: {}));

  void updateCount(int nominal, int quantity) {
    final updated = Map<int, int>.from(state.denominations);
    updated[nominal] = quantity;
    state = ClosingState(
      bookBalance: state.bookBalance,
      denominations: updated,
    );
  }

  Future<bool> finalize(int workDayId) async {
    if (!state.isBalanced) return false;
    state = ClosingState(bookBalance: state.bookBalance, denominations: state.denominations, isFinalizing: true);
    try {
      await repository.finalizeClosing(workDayId, state.denominations);
      return true;
    } catch (e) {
      state = ClosingState(bookBalance: state.bookBalance, denominations: state.denominations, errorMessage: e.toString());
      return false;
    }
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Controller menghitung `physicalTotal` dan `difference` secara instan saat `updateCount` dipanggil.
2. Finalisasi ditolak jika `isBalanced == false`.
3. Unit test memverifikasi integritas kalkulasi selisih.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/closing/presentation/closing_controller_test.dart`
- **Metode Uji:**
  1. `test('updateCount recalculates physical total and difference reactively')`:
     - Initial bookBalance = 100000.
     - Panggil `controller.updateCount(100000, 1)`.
     - Verifikasi `state.physicalTotal == 100000` dan `state.isBalanced == true`.
