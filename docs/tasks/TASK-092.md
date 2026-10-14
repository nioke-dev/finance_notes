# TASK-092: Repository Data Layer ShopeeFood Lapangan Mobile (`ShopeeRepository`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Data Access Object untuk Snapshot On-Beat, Evaluasi Bonus Jam 20.00, & Settlement  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun layer Repository terisolasi `ShopeeRepository` di aplikasi mobile Flutter untuk mengelola data operasional ShopeeFood:
1. **Fungsi-Fungsi Utama:**
   - `saveSnapshot({required int completedOrders, required int hubIncome, required int hubBonus, required int customerTip, required int shopeepayBalance, required int gopayBalance, required int gopayMerchantBalance})`: Mengirim ke `POST /api/v1/driver/snapshots`.
   - `evaluateBonus()`: Mengambil hasil evaluasi jam 20.00 dari `GET /api/v1/driver/evaluate-bonus`.
   - `confirmShopeeBonusDisbursement()`: Mengirim konfirmasi pencairan bonus jam 22.00 ke `POST /api/v1/driver/confirm-bonus`.
2. **Parsing Model Kuat:**
   - Memetakan response JSON ke objek model Dart yang *type-safe* (`BonusEvaluationModel`, `SnapshotModel`).

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/shopee/data/shopee_repository.dart` — Kelas repository ShopeeFood.
2. `mobile/lib/features/shopee/data/models/bonus_evaluation_model.dart` — Model Dart hasil evaluasi bonus.
3. `mobile/test/features/shopee/data/shopee_repository_test.dart` — Unit test repository ShopeeFood.

---

## 3. Spesifikasi Anatomi Kode (`shopee_repository.dart`)
```dart
import 'package:mobile/core/network/api_client.dart';
import 'package:mobile/features/shopee/data/models/bonus_evaluation_model.dart';

class ShopeeRepository {
  final ApiClient apiClient;

  ShopeeRepository({required this.apiClient});

  Future<BonusEvaluationModel> evaluateBonus() async {
    final response = await apiClient.get('/driver/evaluate-bonus');
    return BonusEvaluationModel.fromJson(response.data);
  }

  Future<void> saveSnapshot(Map<String, dynamic> payload) async {
    await apiClient.post('/driver/snapshots', data: payload);
  }

  Future<void> confirmShopeeBonusDisbursement() async {
    await apiClient.post('/driver/confirm-bonus');
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `ShopeeRepository` memanggil endpoint `/driver/evaluate-bonus` dan mem-parsing model tanpa error runtime.
2. Seluruh payload snapshot terkirim sesuai dengan skema kontrak API.
3. Unit test mocking HTTP response berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/shopee/data/shopee_repository_test.dart`
- **Metode Uji:**
  1. `test('evaluateBonus returns parsed BonusEvaluationModel')`:
     - Mock response API: `{"is_eligible": true, "bonus_amount": 37500}`.
     - Panggil `evaluateBonus()` $\rightarrow$ Verifikasi model memiliki `isEligible == true` dan `bonusAmount == 37500`.
