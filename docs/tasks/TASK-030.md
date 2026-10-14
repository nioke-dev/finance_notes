# TASK-030: Form Request & API Konfirmasi Pencairan Bonus Hub ShopeePay Jam 22.00 (`POST /api/v1/driver/confirm-bonus-disbursement`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** HTTP Endpoint Konfirmasi Pencairan Saldo Bonus Hub ke ShopeePay  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani konfirmasi pencairan Bonus Hub ke rekening ShopeePay malam hari.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Pada jam 20.01, Bonus Hub telah diakui ke dalam estimasi total pendapatan hari itu (jika online $\ge$ 9 jam).
2. Namun secara faktual di lapangan, uang bonus baru benar-benar ditransfer oleh sistem Shopee ke dompet `ShopeePay` pada pukul **22.00 – 22.30 malam**.
3. Saat closing malam, sistem memberikan pertanyaan konfirmasi:
   > *"Apakah Bonus Hub (RpX) sudah resmi masuk ke saldo ShopeePay kamu malam ini? [Sudah Cair] / [Belum Cair]"*
4. **Perilaku Sistem:**
   - Jika pengguna memilih **`is_disbursed = true`**:
     - Sistem membuat transaksi pengakuan kas:
       - Debit: `ShopeePay` (sebesar nominal bonus).
       - Kredit: `Bonus Hub ShopeeFood` (sebesar nominal bonus).
     - Saldo `ShopeePay` di database naik detik itu juga, sehingga cocok dengan saldo asli di aplikasi Shopee saat dihitung closing.
   - Jika pengguna memilih **`is_disbursed = false`**:
     - Saldo `ShopeePay` tidak dinaikkan terlebih dahulu (mencegah selisih saldo palsu).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/ConfirmBonusDisbursementRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/BonusDisbursementController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route `POST /api/v1/driver/confirm-bonus-disbursement`.
4. `tests/Feature/Api/ConfirmBonusDisbursementApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/ConfirmBonusDisbursementRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBonusDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'is_disbursed' => ['required', 'boolean'],
            'bonus_amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

### B. Controller Action (`BonusDisbursementController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmBonusDisbursementRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class BonusDisbursementController extends Controller
{
    public function confirm(
        ConfirmBonusDisbursementRequest $request,
        LedgerService $ledgerService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        if (!$validated['is_disbursed'] || $validated['bonus_amount'] === 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status pencairan bonus ditandai belum masuk. Saldo ShopeePay belum disesuaikan.',
                'data' => ['is_disbursed' => false, 'credited_amount' => 0],
            ], 200);
        }

        $shopeepayAccount = Account::where('name', 'ShopeePay')->firstOrFail();
        $bonusIncomeAccount = Account::where('name', 'Bonus Hub ShopeeFood')->firstOrFail();

        $transaction = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: "Pencairan Bonus Hub ShopeeFood Malam (Rp{$validated['bonus_amount']})",
            transactionTime: now(),
            entries: [
                ['account_id' => $shopeepayAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $validated['bonus_amount']],
                ['account_id' => $bonusIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $validated['bonus_amount']],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'shopeepay_bonus_disbursement', 'amount' => $validated['bonus_amount']]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Bonus Hub berhasil dicairkan ke saldo ShopeePay.',
            'data' => [
                'transaction_id' => $transaction->id,
                'is_disbursed' => true,
                'credited_amount' => $validated['bonus_amount'],
                'shopeepay_current_balance' => $shopeepayAccount->fresh()->current_balance,
            ],
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/driver/confirm-bonus-disbursement` merespons `200 OK`.
2. Jika `is_disbursed = true`, saldo akun `ShopeePay` bertambah tepat sebesar `bonus_amount`.
3. Jika `is_disbursed = false`, saldo `ShopeePay` tidak berubah.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/ConfirmBonusDisbursementApiTest.php`
- **Metode Uji:**
  1. `test_confirming_bonus_credits_shopeepay_balance_accurately()`:
     - Konfirmasi bonus Rp14.500 $\rightarrow$ Saldo ShopeePay bertambah Rp14.500.
  2. `test_unconfirmed_bonus_leaves_shopeepay_balance_untouched()`:
     - `is_disbursed = false` $\rightarrow$ Saldo ShopeePay tidak berubah.
