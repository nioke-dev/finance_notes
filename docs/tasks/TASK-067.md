# TASK-067: Setup Dio API Client & Token Interceptor Flutter (`ApiClient`)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Network Layer, Base URL Config, Auth Header, & Offline Fallback Error Handling  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun service HTTP Client terpusat menggunakan library `Dio` untuk menghubungkan aplikasi mobile Flutter dengan REST API backend Laravel:
1. **Konfigurasi Base URL & Timeout:**
   - Base URL terkonfigurasi fleksibel melalui environment variable / constant (default local dev: `http://10.0.2.2:8000/api/v1` untuk Android emulator atau IP LAN lokal).
   - `connectTimeout`: 10 detik, `receiveTimeout`: 10 detik.
2. **Interceptors:**
   - **Auth/Header Interceptor:** Menyisipkan header `Accept: application/json` dan bearer token Sanctum dari secure storage ke setiap request keluar.
   - **Logging Interceptor (Debug):** Mencetak request URI, status code, dan payload saat mode debug aktif.
   - **Error Handling Interceptor:** Memetakan status HTTP 422 (Validation Error), 400 (Bad Request), 401 (Unauthorized), dan network offline ke format exception seragam `ApiException`.
3. **Riverpod Provider:** Mengekspos `apiClientProvider` agar mudah di-inject ke semua repository dan state notifier.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/core/network/api_client.dart` — Wrapper kelas Dio dengan konfigurasi interceptor.
2. `mobile/lib/core/network/api_exceptions.dart` — Definisi class custom exception (`ApiException`, `NetworkException`).
3. `mobile/test/core/network/api_client_test.dart` — Unit test client HTTP dan pemetaan error.

---

## 3. Spesifikasi Anatomi Kode (`mobile/lib/core/network/api_client.dart`)
```dart
import 'package:dio/dio.dart';

class ApiClient {
  final Dio dio;

  ApiClient({Dio? customDio})
      : dio = customDio ??
            Dio(
              BaseOptions(
                baseUrl: 'http://10.0.2.2:8000/api/v1',
                connectTimeout: const Duration(seconds: 10),
                receiveTimeout: const Duration(seconds: 10),
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json',
                },
              ),
            ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Token injection if present
          return handler.next(options);
        },
        onError: (DioException e, handler) {
          // Wrap error to unified custom exception
          return handler.next(e);
        },
      ),
    );
  }

  Future<Response<T>> get<T>(String path, {Map<String, dynamic>? queryParameters}) {
    return dio.get<T>(path, queryParameters: queryParameters);
  }

  Future<Response<T>> post<T>(String path, {dynamic data}) {
    return dio.post<T>(path, data: data);
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `ApiClient` dapat menginisialisasi instance Dio dengan header default `Accept: application/json`.
2. Interceptor menyisipkan header Authorization jika token tersedia.
3. Automated test untuk mocking request HTTP GET dan POST berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/core/network/api_client_test.dart`
- **Metode Uji:**
  1. `test('ApiClient sets correct base headers and timeout options')`:
     - Verifikasi `dio.options.headers['Accept'] == 'application/json'`.
  2. `test('ApiClient successfully delegates GET and POST requests')`:
     - Gunakan mock adapter/dio untuk memverifikasi handling response 200 OK.
