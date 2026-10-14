# TASK-041: Form Request & API Endpoint Pengeluaran Pribadi Driver via BCA (`POST /api/v1/quick/personal-expense`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Pengeluaran Pribadi On-Beat (Isolasi Mutasi via BCA)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk mencatat pengeluaran pribadi driver saat sedang on-beat (misal: beli air mineral di Indomaret, beli kopi, atau makan siang).
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. **Isolasi Akun Operasional:** Driver sengaja menggunakan rekening `BCA` untuk seluruh pengeluaran pribadi selama narik di jalan, agar dompet `ShopeePay` dan `GoPay` murni menampung uang masuk dari pelanggan/orderan.
2. **Alur Finansial:**
   - Memotong saldo akun `BCA` secara default (Kredit).
   - Menambah akun `Beban Makanan & Minuman Driver` (Debit).
3. **Kecepatan Input:** Form hanya membutuhkan nominal dan deskripsi singkat (misal: "Beli Air Mineral Indomaret"), selesai dalam waktu $< 5$ detik.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/PersonalExpenseRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/PersonalExpenseController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/quick/personal-expense`.
4. `tests/Feature/Api/PersonalExpenseApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/PersonalExpenseRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonalExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'amount' => ['required', 'integer', 'min:500'],
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'], // Default otomatis ke BCA
        ];
    }
}
```

### B. Controller Action (`PersonalExpenseController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalExpenseRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class PersonalExpenseController extends Controller
{
    public function store(
        PersonalExpenseRequest $request,
        LedgerService $ledgerService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        // Default ke akun BCA jika account_id tidak disertakan
        $sourceAccount = isset($validated['account_id'])
            ? Account::findOrFail($validated['account_id'])
            : Account::where('name', 'BCA')->firstOrFail();

        $expenseAccount = Account::where('name', 'Beban Makanan & Minuman Driver')->firstOrFail();

        $transaction = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $validated['description'],
            transactionTime: now(),
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $validated['amount']],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $validated['amount']],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'personal_on_beat_expense']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran pribadi berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $validated['amount'],
                'account_name' => $sourceAccount->name,
                'description' => $validated['description'],
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/quick/personal-expense` merespons `201 Created`.
2. Jika `account_id` tidak dikirim, sistem otomatis memotong rekening `BCA`.
3. Saldo akun BCA berkurang dan beban makanan/minuman bertambah secara tepat via `LedgerService`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/PersonalExpenseApiTest.php`
- **Metode Uji:**
  1. `test_personal_expense_defaults_to_bca_and_deducts_balance()`:
     - Input Rp10.000 tanpa `account_id` $\rightarrow$ Saldo BCA berkurang Rp10.000.
  2. `test_personal_expense_fails_with_amount_under_500()`:
     - Input amount Rp200 $\rightarrow$ status 422.
