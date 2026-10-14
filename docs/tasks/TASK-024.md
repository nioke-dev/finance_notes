# TASK-024: Form Request & API Evaluasi Performa Jam 20.00 (`POST /api/v1/driver/evaluate-performance`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** HTTP Endpoint Evaluasi Syarat Mutlak Bonus Hub (Online $\ge$ 9 Jam)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani dialog pop-up evaluasi performa pada jam 20.00 malam.
Sesuai kesepakatan tanya jawab di `BUSINESS-RULES.md`:
1. Jam 20.00 adalah cut-off waktu pesanan bonus.
2. Pengguna memasukkan data performa dari aplikasi driver ShopeeFood:
   - `online_hours` (desimal jam, contoh: 9.15 jam)
   - `peak_hour_rate` (persentase jam sibuk 11–13 & 17–19, contoh: 85.0%)
   - `completion_rate` (persentase penyelesaian, contoh: 92.5%)
   - `eligible_orders_count` (jumlah order eligible jam 10–20)
3. **Logika Kelayakan Mutlak:**
   - Wajib memenuhi: $\text{online\_hours} \ge 9.00$, $\text{peak\_hour\_rate} \ge 70.0$, dan $\text{completion\_rate} \ge 80.0$.
   - **Jika Lolos:**
     - Bonus dihitung menggunakan `ShopeeBonusCalculatorService`.
     - Kolom `work_days.is_performance_eligible` diset `true`.
     - Bonus langsung diakui detik itu juga ke dalam estimasi total pendapatan hari ini.
   - **Jika Gugur (misal online 8.75 jam):**
     - Bonus otomatis diset **Rp0**.
     - Kolom `work_days.is_performance_eligible` diset `false`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/EvaluatePerformanceRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/DriverPerformanceController.php` — Controller endpoint evaluasi performa.
3. `routes/api.php` — Mendaftarkan route `POST /api/v1/driver/evaluate-performance`.
4. `tests/Feature/Api/EvaluatePerformanceApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/EvaluatePerformanceRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluatePerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'online_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'peak_hour_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'completion_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'eligible_orders_count' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

### B. Controller Action (`DriverPerformanceController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluatePerformanceRequest;
use App\Models\WorkDay;
use App\Services\ShopeeBonusCalculatorService;
use Illuminate\Http\JsonResponse;

class DriverPerformanceController extends Controller
{
    public function evaluate(
        EvaluatePerformanceRequest $request,
        ShopeeBonusCalculatorService $calculatorService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $isEligible = ($validated['online_hours'] >= 9.0)
            && ($validated['peak_hour_rate'] >= 70.0)
            && ($validated['completion_rate'] >= 80.0);

        $calculatedBonus = $isEligible 
            ? $calculatorService->calculateBonus($validated['eligible_orders_count']) 
            : 0;

        $workDay->update([
            'online_hours' => $validated['online_hours'],
            'is_performance_eligible' => $isEligible,
            'total_orders' => $validated['eligible_orders_count'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'work_day_id' => $workDay->id,
                'is_eligible' => $isEligible,
                'calculated_bonus' => $calculatedBonus,
                'message' => $isEligible 
                    ? "Performa memenuhi syarat. Bonus Hub Rp{$calculatedBonus} diakui di estimasi pendapatan hari ini."
                    : "Waktu online atau performa belum memenuhi syarat. Bonus Hub hari ini Rp0.",
            ],
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/driver/evaluate-performance` merespons `200 OK`.
2. Driver dengan online $\ge 9.0$ jam mendapat status `is_eligible = true` dan nilai bonus bertingkat yang benar.
3. Driver dengan online $< 9.0$ jam (misal 8.9 jam) mendapat `is_eligible = false` dan `calculated_bonus = 0`.
4. Tabel `work_days` ter-update dengan data `online_hours` dan `is_performance_eligible`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/EvaluatePerformanceApiTest.php`
- **Metode Uji:**
  1. `test_driver_with_9_hours_online_qualifies_for_calculated_bonus()`:
     - Online 9.1 jam dengan 26 order $\rightarrow$ Bonus Rp14.500 diakui.
  2. `test_driver_with_under_9_hours_online_gets_zero_bonus_even_with_high_orders()`:
     - Online 8.5 jam dengan 30 order $\rightarrow$ Bonus Rp0.
  3. `test_work_day_database_is_updated_with_performance_results()`:
     - Memastikan kolom `online_hours` dan `is_performance_eligible` tersimpan di database.
