# TASK-090: Repository Data Layer Quick Actions Mobile (`QuickActionRepository`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Data Access Object Mandiri untuk Parkir, Bensin, COD, & Pengeluaran Lapangan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun layer Repository terisolasi `QuickActionRepository` untuk memisahkan logika komunikasi HTTP dari widget UI:
1. **Fungsi-Fungsi Mandiri:**
   - `submitParking({required int amount, double? latitude, double? longitude, String? restaurantName})`: Mengirim ke `POST /api/v1/quick-actions/parking`.
   - `submitFuel({required String fuelType, required int amount, required int sourceAccountId})`: Mengirim ke `POST /api/v1/quick-actions/fuel`.
   - `submitCod({required int orderAmount, required int cashCollected, String? orderNumber})`: Mengirim ke `POST /api/v1/quick-actions/cod`.
   - `submitPersonalExpense({required int amount, required int categoryId, String? notes})`: Mengirim ke `POST /api/v1/quick-actions/personal-expense`.
2. **Pola Desain:**
   - Menerima dependensi `ApiClient` via constructor injection.
   - Mengembalikan data model ter-parse (`TransactionModel`) atau melempar `ApiException` yang rapi.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/quick_actions/data/quick_action_repository.dart` — Kelas repository quick actions.
2. `mobile/test/features/quick_actions/data/quick_action_repository_test.dart` — Unit test repository mocking Dio.

---

## 3. Spesifikasi Anatomi Kode (`quick_action_repository.dart`)
```dart
import 'package:mobile/core/network/api_client.dart';

class QuickActionRepository {
  final ApiClient apiClient;

  QuickActionRepository({required this.apiClient});

  Future<void> submitParking({
    required int amount,
    double? latitude,
    double? longitude,
    String? restaurantName,
  }) async {
    await apiClient.post('/quick-actions/parking', data: {
      'amount': amount,
      'latitude': latitude,
      'longitude': longitude,
      'restaurant_name': restaurantName,
    });
  }

  Future<void> submitFuel({
    required String fuelType,
    required int amount,
    required int sourceAccountId,
  }) async {
    await apiClient.post('/quick-actions/fuel', data: {
      'type': fuelType,
      'amount': amount,
      'source_account_id': sourceAccountId,
    });
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Seluruh fungsi repository memanggil path API dan payload JSON yang sesuai dengan kontrak `API-CONTRACTS.md`.
2. Error HTTP ditangkap dan dilemparkan kembali sebagai exception terstruktur.
3. Unit test mocking HTTP request berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/quick_actions/data/quick_action_repository_test.dart`
- **Metode Uji:**
  1. `test('submitParking sends expected payload to /quick-actions/parking')`:
     - Panggil `submitParking(amount: 1000, latitude: -7.98, longitude: 112.62)`.
     - Verifikasi `apiClient.post` dipanggil dengan path dan payload yang benar.
