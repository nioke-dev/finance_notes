# TASK-048: API Endpoint Ringkasan Jadwal Jatuh Tempo Utang 30 Hari (`GET /api/v1/debts/upcoming`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** HTTP Endpoint Monitoring Jatuh Tempo Angsuran Bulanan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun endpoint API untuk menampilkan seluruh cicilan utang, PayLater, dan tagihan bulanan yang akan jatuh tempo dalam rentang 30 hari ke depan.
Endpoint ini digunakan oleh:
1. **Web Super App Dashboard:** Menampilkan widget kalender tagihan mendatang di laptop.
2. **Mobile Client:** Kartu ringkasan tagihan terdekat di layar keuangan.
- **Route:** `GET /api/v1/debts/upcoming`
- **Response Data:**
  - `total_upcoming_obligation`: Akumulasi nominal seluruh tagihan yang harus dibayar dalam 30 hari ke depan.
  - `installments`: Daftar cicilan yang berstatus `unpaid` dan jatuh tempo antara `now()` s/d `now() + 30 days`, terurut dari tanggal terdekat (`ORDER BY due_date ASC`).
  - Menyertakan hitungan sisa hari (*days_remaining*) untuk setiap cicilan.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Api/DebtScheduleController.php` — Controller endpoint API.
2. `routes/api.php` — Mendaftarkan route endpoint `GET /api/v1/debts/upcoming`.
3. `tests/Feature/Api/UpcomingDebtsApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode (`DebtScheduleController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebtInstallment;
use Illuminate\Http\JsonResponse;

class DebtScheduleController extends Controller
{
    public function upcoming(): JsonResponse
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(30)->endOfDay();

        $installments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'asc')
            ->get();

        $totalObligation = $installments->sum('amount_due');

        $data = $installments->map(function (DebtInstallment $item) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays($item->due_date, false);

            return [
                'installment_id' => $item->id,
                'debt_id' => $item->debt_id,
                'debt_name' => $item->debt->name,
                'platform' => $item->debt->platform,
                'installment_number' => $item->installment_number,
                'total_tenor' => $item->debt->tenor_months,
                'amount_due' => $item->amount_due,
                'due_date' => $item->due_date->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_upcoming_obligation' => $totalObligation,
                'installments' => $data,
            ],
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `GET /api/v1/debts/upcoming` merespons `200 OK`.
2. Hanya mengambil cicilan yang berstatus `unpaid`. Cicilan yang sudah `paid` tidak muncul dalam daftar.
3. Hanya mengambil cicilan dalam jendela 30 hari ke depan.
4. Response mengembalikan total akumulasi kewajiban `total_upcoming_obligation`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/UpcomingDebtsApiTest.php`
- **Metode Uji:**
  1. `test_upcoming_debts_endpoint_returns_unpaid_installments_within_30_days()`:
     - Membuat 1 cicilan jatuh tempo dalam 10 hari (unpaid) dan 1 cicilan jatuh tempo dalam 60 hari $\rightarrow$ Hanya cicilan 10 hari yang muncul.
  2. `test_paid_installments_are_excluded_from_upcoming_list()`:
     - Cicilan dengan status `paid` tidak muncul dalam daftar response.
