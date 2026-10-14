# TASK-055: API Endpoint Banner Pengingat Hari Gantung (`GET /api/v1/reconciliation/unfinalized-dates`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** HTTP Endpoint Banner Pengingat Rekonsiliasi di Beranda Mobile & Web  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun endpoint API yang dipanggil secara otomatis setiap kali aplikasi Mobile atau Web dibuka di halaman Beranda (Home).
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Sistem **TIDAK MENGUNCI** driver untuk membuka hari baru saat hari lama belum final.
2. Sebagai gantinya, sistem wajib menampilkan **Banner Pengingat Mencolok** (warna Kuning/Merah) di bagian paling atas layar beranda:
   > ⚠️ *"Tanggal 12 Okt belum difinalisasi (Selisih Cash -Rp15.000). Klik di sini untuk investigasi!"*
3. **Spesifikasi Endpoint:**
   - **Route:** `GET /api/v1/reconciliation/unfinalized-dates`
   - Mengambil seluruh record `work_days` yang berstatus `needs_reconciliation` dengan tanggal sebelum hari ini (`work_date < today`).
   - Menyertakan estimasi selisih terakhir yang terdeteksi dan format tanggal human-readable.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Api/UnfinalizedDatesBannerController.php` — Controller endpoint API.
2. `routes/api.php` — Mendaftarkan route endpoint `GET /api/v1/reconciliation/unfinalized-dates`.
3. `tests/Feature/Api/UnfinalizedDatesBannerApiTest.php` — Automated test HTTP banner endpoint.

---

## 3. Spesifikasi Anatomi Kode (`UnfinalizedDatesBannerController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\Http\JsonResponse;

class UnfinalizedDatesBannerController extends Controller
{
    public function index(ReconciliationEngineService $reconciliationService): JsonResponse
    {
        $today = now()->format('Y-m-d');

        $pendingDays = WorkDay::where('status', WorkDay::STATUS_NEEDS_RECONCILIATION)
            ->where('work_date', '<', $today)
            ->orderBy('work_date', 'asc')
            ->get();

        $bannerData = $pendingDays->map(function (WorkDay $day) use ($reconciliationService) {
            $report = $reconciliationService->reconcileWorkDay($day, []);

            return [
                'work_day_id' => $day->id,
                'work_date' => $day->work_date->format('Y-m-d'),
                'human_date' => $day->work_date->translatedFormat('d M Y'),
                'status' => $day->status,
                'total_discrepancy' => $report['total_absolute_discrepancy'],
                'message' => "Tanggal {$day->work_date->format('d M')} belum difinalisasi (Selisih Rp" . number_format($report['total_absolute_discrepancy'], 0, ',', '.') . "). Klik untuk selesaikan!",
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $bannerData,
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `GET /api/v1/reconciliation/unfinalized-dates` merespons `200 OK`.
2. Jika ada hari kerja lampau yang belum final, mengembalikan array daftar hari tersebut dengan pesan pengingat.
3. Jika seluruh hari kerja lampau sudah berstatus `final`, mengembalikan array kosong `[]` (indikator banner disembunyikan).

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/UnfinalizedDatesBannerApiTest.php`
- **Metode Uji:**
  1. `test_returns_pending_days_with_banner_messages()`:
     - Membuat hari kemarin berstatus `needs_reconciliation` $\rightarrow$ Endpoint mengembalikan data banner.
  2. `test_returns_empty_when_all_past_days_are_finalized()`:
     - Semua hari lampau status `final` $\rightarrow$ Endpoint mengembalikan data `[]`.
