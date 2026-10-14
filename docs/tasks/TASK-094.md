# TASK-094: Riverpod State Notifier Monitoring On-Beat Shopee (`ShopeeController`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** State Management Lapangan untuk Monitoring Progres Target & Evaluasi Bonus  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Riverpod State Notifier `ShopeeController` untuk mengelola state performa harian ShopeeFood saat driver di lapangan:
1. **Model State (`ShopeeState`):**
   - `isLoading`: Boolean.
   - `completedOrders`: Integer (jumlah trip hari ini).
   - `hubIncome`: Integer (pendapatan dasar).
   - `hubBonus`: Integer (estimasi bonus terhitung).
   - `customerTip`: Integer (tip pelanggan).
   - `isBonusEligible`: Boolean nullable.
   - `bonusEvaluationMessage`: String nullable.
2. **Logika Bisnis di Controller:**
   - Menyimpan snapshot progres baru via `ShopeeRepository`.
   - Mengambil evaluasi jam 20.00 otomatis saat waktu lokal mencapai cut-off.
   - Mengonfirmasi pencairan bonus jam 22.00 dan memperbarui saldo ShopeePay.
3. **Penyediaan Provider:**
   - Mengekspos `shopeeControllerProvider` ke `OnBeatScreen` dan dialog evaluasi bonus.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/shopee/presentation/controllers/shopee_controller.dart` — State Notifier Riverpod ShopeeFood.
2. `mobile/test/features/shopee/presentation/shopee_controller_test.dart` — Unit test State Notifier.

---

## 3. Spesifikasi Anatomi Kode (`shopee_controller.dart`)
```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/features/shopee/data/shopee_repository.dart';
import 'package:mobile/features/shopee/data/models/bonus_evaluation_model.dart';

class ShopeeState {
  final bool isLoading;
  final int completedOrders;
  final int hubIncome;
  final int hubBonus;
  final BonusEvaluationModel? evaluation;

  ShopeeState({
    this.isLoading = false,
    this.completedOrders = 0,
    this.hubIncome = 0,
    this.hubBonus = 0,
    this.evaluation,
  });

  ShopeeState copyWith({bool? isLoading, BonusEvaluationModel? evaluation}) {
    return ShopeeState(
      isLoading: isLoading ?? this.isLoading,
      completedOrders: completedOrders,
      hubIncome: hubIncome,
      hubBonus: hubBonus,
      evaluation: evaluation ?? this.evaluation,
    );
  }
}

class ShopeeController extends StateNotifier<ShopeeState> {
  final ShopeeRepository repository;

  ShopeeController({required this.repository}) : super(ShopeeState());

  Future<void> checkBonusCutOff() async {
    state = state.copyWith(isLoading: true);
    try {
      final eval = await repository.evaluateBonus();
      state = state.copyWith(isLoading: false, evaluation: eval);
    } catch (_) {
      state = state.copyWith(isLoading: false);
    }
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Controller mengupdate state evaluasi bonus saat `checkBonusCutOff()` dipanggil.
2. Loading state aktif saat network call dan kembali false setelah selesai.
3. Unit test memverifikasi perubahan state controller.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/shopee/presentation/shopee_controller_test.dart`
- **Metode Uji:**
  1. `test('checkBonusCutOff updates evaluation state from repository')`:
     - Panggil `controller.checkBonusCutOff()`.
     - Verifikasi `state.evaluation?.isEligible == true` dan `state.isLoading == false`.
