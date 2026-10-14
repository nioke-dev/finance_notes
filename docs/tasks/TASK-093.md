# TASK-093: Repository Data Layer Closing & Rekonsiliasi Mobile (`ClosingRepository`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Data Access Object untuk Audit Denominasi Fisik, Cek Selisih, & Buka/Tutup Hari  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun layer Repository terisolasi `ClosingRepository` di mobile Flutter untuk menangani siklus audit harian:
1. **Fungsi-Fungsi Utama:**
   - `getPendingReconciliations()`: Mengambil daftar hari belum selesai dari `GET /api/v1/work-days/pending-reconciliations`.
   - `openWorkDay()`: Membuka shift kerja baru via `POST /api/v1/work-days/open`.
   - `calculateDifference({required int workDayId, required Map<int, int> denominations})`: Memanggil `POST /api/v1/reconciliation/calculate-difference`.
   - `finalizeClosing({required int workDayId, required Map<int, int> denominations, String? notes})`: Mengirim ke `POST /api/v1/reconciliation/finalize`.
2. **Pola Type-Safety:**
   - Mengembalikan model `ReconciliationResultModel` yang berisi status `is_balanced`, `difference`, dan `book_balance`.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/closing/data/closing_repository.dart` — Kelas repository closing & rekonsiliasi.
2. `mobile/lib/features/closing/data/models/reconciliation_result_model.dart` — Model Dart hasil hitung selisih.
3. `mobile/test/features/closing/data/closing_repository_test.dart` — Unit test repository closing.

---

## 3. Spesifikasi Anatomi Kode (`closing_repository.dart`)
```dart
import 'package:mobile/core/network/api_client.dart';
import 'package:mobile/features/closing/data/models/reconciliation_result_model.dart';

class ClosingRepository {
  final ApiClient apiClient;

  ClosingRepository({required this.apiClient});

  Future<int> getPendingReconciliationsCount() async {
    final response = await apiClient.get('/work-days/pending-reconciliations');
    final List list = response.data['data'] ?? [];
    return list.length;
  }

  Future<ReconciliationResultModel> calculateDifference(int workDayId, Map<int, int> denominations) async {
    final formattedDenominations = denominations.entries
        .map((e) => {'nominal': e.key, 'quantity': e.value})
        .toList();

    final response = await apiClient.post('/reconciliation/calculate-difference', data: {
      'work_day_id': workDayId,
      'denominations': formattedDenominations,
    });

    return ReconciliationResultModel.fromJson(response.data);
  }

  Future<void> finalizeClosing(int workDayId, Map<int, int> denominations, {String? notes}) async {
    final formattedDenominations = denominations.entries
        .map((e) => {'nominal': e.key, 'quantity': e.value})
        .toList();

    await apiClient.post('/reconciliation/finalize', data: {
      'work_day_id': workDayId,
      'denominations': formattedDenominations,
      'closing_notes': notes,
    });
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `ClosingRepository` berhasil mengonversi format Map pecahan (`nominal => quantity`) ke array of objects yang diminta API backend.
2. Respons kalkulasi selisih ter-mapping rapi ke `ReconciliationResultModel`.
3. Unit test mocking HTTP response berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/closing/data/closing_repository_test.dart`
- **Metode Uji:**
  1. `test('calculateDifference formats denomination array correctly and returns model')`:
     - Panggil `calculateDifference(1, {100000: 2})`.
     - Verifikasi payload yang dikirim berisi `[{"nominal": 100000, "quantity": 2}]`.
