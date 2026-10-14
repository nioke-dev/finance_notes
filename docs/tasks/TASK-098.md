# TASK-098: Form Request Validasi Pinjaman Baru (`StoreDebtRequest`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Pencairan Pinjaman & Biaya Provisi  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `StoreDebtRequest` untuk memisahkan validasi pencatatan utang/piutang baru dari controller:
1. **Aturan Validasi Parameter:**
   - `type`: Wajib (`required`), string, bernilai salah satu dari `payable` (utang saya) atau `receivable` (piutang ke orang lain) (`in:payable,receivable`).
   - `person_or_institution`: Wajib (`required|string|max:100`) (misal: "SeaBank Pinjaman", "SPayLater", "Budi").
   - `principal_amount`: Wajib (`required|integer|min:1000`) (pokok pinjaman integer Rupiah).
   - `admin_fee`: Opsional (`nullable|integer|min:0`) (biaya provisi/admin).
   - `disbursement_account_id`: Wajib (`required|exists:accounts,id`) (rekening bank penampung dana cair).
   - `due_date`: Wajib (`required|date|after_or_equal:today`).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang jelas: *"Tanggal jatuh tempo tidak boleh berada di masa lalu"*, *"Nominal pokok pinjaman minimal Rp1.000"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Debt/StoreDebtRequest.php` — Form Request validasi pinjaman baru.
2. `tests/Unit/Requests/StoreDebtRequestTest.php` — Unit test aturan validasi pinjaman.

---

## 3. Spesifikasi Anatomi Kode (`StoreDebtRequest.php`)
```php
namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:payable,receivable'],
            'person_or_institution' => ['required', 'string', 'max:100'],
            'principal_amount' => ['required', 'integer', 'min:1000'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'disbursement_account_id' => ['required', 'exists:accounts,id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Tipe kewajiban harus payable (utang) atau receivable (piutang).',
            'principal_amount.min' => 'Pokok pinjaman minimal Rp1.000.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh berada di masa lalu.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan tanggal jatuh tempo kemarin ditolak HTTP 422.
2. Request dengan tipe selain payable/receivable ditolak.
3. Unit test memverifikasi seluruh kemungkinan payload validasi pinjaman.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/StoreDebtRequestTest.php`
- **Metode Uji:**
  1. `test_store_debt_validation_passes_with_valid_future_due_date()`:
     - Berikan payload valid dengan `due_date: besok` $\rightarrow$ Validasi lulus.
  2. `test_store_debt_validation_fails_with_past_due_date()`:
     - Berikan `due_date: kemarin` $\rightarrow$ Validasi gagal.
