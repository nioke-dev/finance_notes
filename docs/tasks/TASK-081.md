# TASK-081: Form Request Validasi Transfer Antar Akun (`TransferFundsRequest`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Transfer Antar Akun & Biaya Admin  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `TransferFundsRequest` untuk memvalidasi parameter transaksi pemindahan dana antar akun milik sendiri:
1. **Aturan Validasi Parameter:**
   - `source_account_id`: Wajib (`required`), harus ada di tabel `accounts` (`exists:accounts,id`).
   - `destination_account_id`: Wajib (`required`), harus ada di tabel `accounts` (`exists:accounts,id`), dan **tidak boleh sama dengan akun sumber** (`different:source_account_id`).
   - `amount`: Wajib (`required`), integer utuh (`integer`), nilai minimum Rp1 (`min:1`).
   - `admin_fee`: Opsional (`nullable`), integer utuh (`integer`), minimum Rp0 (`min:0`).
   - `notes`: Opsional (`nullable|string|max:255`).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan spesifik jika pengguna mencoba mentransfer ke akun yang sama (misal: "Akun tujuan transfer tidak boleh sama dengan akun sumber").

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Account/TransferFundsRequest.php` — Form Request validasi transfer dana.
2. `tests/Unit/Requests/TransferFundsRequestTest.php` — Unit test validasi form request transfer.

---

## 3. Spesifikasi Anatomi Kode (`TransferFundsRequest.php`)
```php
namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class TransferFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['required', 'exists:accounts,id', 'different:source_account_id'],
            'amount' => ['required', 'integer', 'min:1'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_account_id.different' => 'Akun tujuan transfer tidak boleh sama dengan akun sumber.',
            'amount.min' => 'Nominal transfer minimal Rp1.',
            'amount.integer' => 'Nominal transfer harus berupa bilangan bulat Rupiah.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Transfer dengan akun sumber dan tujuan yang sama otomatis gagal validasi HTTP 422.
2. Transfer dengan nominal pecahan atau minus ditolak.
3. Unit test memverifikasi seluruh kemungkinan validasi payload transfer.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/TransferFundsRequestTest.php`
- **Metode Uji:**
  1. `test_transfer_validation_fails_when_source_and_destination_are_identical()`:
     - Berikan `source_account_id: 1` dan `destination_account_id: 1` $\rightarrow$ Validasi gagal.
  2. `test_transfer_validation_passes_with_distinct_accounts_and_positive_integer()`:
     - Berikan `source_account_id: 1`, `destination_account_id: 2`, `amount: 50000` $\rightarrow$ Validasi lulus.
