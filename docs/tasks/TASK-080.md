# TASK-080: Form Request Validasi Inisialisasi Saldo Akun (`SetInitialBalanceRequest`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Input Saldo Awal & Audit Note  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `SetInitialBalanceRequest` untuk memisahkan logika validasi HTTP dari controller:
1. **Aturan Validasi Ketat:**
   - `initial_balance`: Wajib (`required`), integer utuh (`integer`), nilai minimum 0 (`min:0`). Dilarang ada nilai desimal atau minus.
   - `reason`: Opsional pada input pertama (`nullable|string|max:255`), tetapi jika akun sudah memiliki saldo awal sebelumnya (proses koreksi saldo modal), `reason` menjadi **wajib diisi** (`required_if`) untuk memenuhi audit trail.
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan berbahasa Indonesia yang jelas untuk feedback UI mobile & web (misal: "Saldo awal harus berupa bilangan bulat Rupiah tanpa desimal").

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Account/SetInitialBalanceRequest.php` — Form Request validasi saldo awal.
2. `tests/Unit/Requests/SetInitialBalanceRequestTest.php` — Unit test validasi form request.

---

## 3. Spesifikasi Anatomi Kode (`SetInitialBalanceRequest.php`)
```php
namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class SetInitialBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'initial_balance' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'initial_balance.required' => 'Saldo awal wajib diisi.',
            'initial_balance.integer' => 'Saldo awal harus berupa bilangan bulat Rupiah.',
            'initial_balance.min' => 'Saldo awal tidak boleh bernilai negatif.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan nilai string atau desimal ditolak dengan error validasi HTTP 422.
2. Request dengan nilai integer $\ge 0$ lolos validasi.
3. Unit test memverifikasi seluruh skenario aturan validasi.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/SetInitialBalanceRequestTest.php`
- **Metode Uji:**
  1. `test_set_initial_balance_validation_passes_with_valid_integer()`:
     - Berikan `initial_balance: 500000` $\rightarrow$ Validasi lulus.
  2. `test_set_initial_balance_fails_with_negative_or_decimal_value()`:
     - Berikan `initial_balance: -100` atau `50000.50` $\rightarrow$ Validasi gagal.
