# TASK-091: Riverpod State Notifier & Provider Quick Actions Mobile

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** State Management Lapangan (Loading State, Optimistic Feedback, & Error Toast)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun layer State Management menggunakan `flutter_riverpod` (`StateNotifier` / `AsyncNotifier`) untuk aksi cepat di jalan:
1. **Model State (`QuickActionState`):**
   - `isLoading`: Boolean (menghindari double tap beruntun saat motor berhenti sebentar di lampu merah).
   - `lastAction`: String nullable (misal: "Parkir Rp1.000 berhasil dicatat").
   - `errorMessage`: String nullable.
2. **Logika State Notifier (`QuickActionNotifier`):**
   - Menangani eksekusi cepat aksi parkir dan bensin.
   - Mengambil koordinat GPS otomatis dengan safety timeout 3 detik.
   - Memperbarui state secara reaktif sehingga UI dapat menampilkan toast sukses atau error tanpa reload layar.
3. **Penyediaan Provider:**
   - Mengekspos `quickActionNotifierProvider` untuk digunakan oleh tombol `QuickParkingButton` dan bottom sheet bensin.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/quick_actions/presentation/controllers/quick_action_controller.dart` — State Notifier Riverpod.
2. `mobile/test/features/quick_actions/presentation/quick_action_controller_test.dart` — Unit test State Notifier.

---

## 3. Spesifikasi Anatomi Kode (`quick_action_controller.dart`)
```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/features/quick_actions/data/quick_action_repository.dart';
import 'package:mobile/core/services/location_service.dart';

class QuickActionState {
  final bool isLoading;
  final String? successMessage;
  final String? errorMessage;

  QuickActionState({this.isLoading = false, this.successMessage, this.errorMessage});
}

class QuickActionController extends StateNotifier<QuickActionState> {
  final QuickActionRepository repository;
  final LocationService locationService;

  QuickActionController({required this.repository, required this.locationService})
      : super(QuickActionState());

  Future<bool> recordParking(int amount) async {
    state = QuickActionState(isLoading: true);
    try {
      final loc = await locationService.getCurrentLocation();
      await repository.submitParking(
        amount: amount,
        latitude: loc?.latitude,
        longitude: loc?.longitude,
      );
      state = QuickActionState(isLoading: false, successMessage: 'Parkir Rp $amount tercatat');
      return true;
    } catch (e) {
      state = QuickActionState(isLoading: false, errorMessage: e.toString());
      return false;
    }
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Controller mengelola transisi state `isLoading: true` $\rightarrow$ `isLoading: false` secara bersih.
2. Callback `recordParking` otomatis mengintegrasikan `LocationService` dan `QuickActionRepository`.
3. Unit test memverifikasi alur state sukses dan failure.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/quick_actions/presentation/quick_action_controller_test.dart`
- **Metode Uji:**
  1. `test('recordParking sets successMessage on successful API call')`:
     - Panggil `controller.recordParking(1000)`.
     - Verifikasi `state.successMessage == 'Parkir Rp 1000 tercatat'` dan `state.isLoading == false`.
