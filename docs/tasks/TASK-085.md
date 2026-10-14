# TASK-085: Form Request Validasi Pengeluaran Pribadi BCA (`PersonalExpenseRequest`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Pengeluaran Pribadi Terisolasi BCA  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `PersonalExpenseRequest` untuk memvalidasi pencatatan cepat jajan/makan di jalan via akun BCA:
1. **Aturan Validasi Parameter:**
   - `amount`: Wajib (`required`), integer utuh (`integer`), minimum Rp1.000 (`min:1000`).
   - `category_id`: Wajib (`required`), harus terdaftar di tabel `categories` (`exists:categories,id`).
   - `notes`: Opsional (`nullable|string|max:255`).
   - `source_account_id`: Opsional (default di controller mengarah ke akun BCA jika dikosongkan). Jika dikirim, harus `exists:accounts,id`.
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan jelas: *"Kategori pengeluaran wajib dipilih"*, *"Nominal pengeluaran minimal Rp1.000"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickAction/PersonalExpenseRequest.php` — Form Request validasi pengeluaran pribadi.
2. `tests/Unit/Requests/PersonalExpenseRequestTest.php` — Unit test aturan validasi pengeluaran pribadi.

---

## 3. Spesifikasi Anatomi Kode (`PersonalExpenseRequest.php`)
```php
namespace App\Http\Requests\QuickAction;

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
            'amount' => ['required', 'integer', 'min:1000'],
            'category_id' => ['required', 'exists:categories,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'source_account_id' => ['nullable', 'exists:accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Nominal pengeluaran minimal Rp1.000.',
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request tanpa `category_id` atau category yang tidak ada di database ditolak HTTP 422.
2. Request dengan nominal negatif atau non-integer ditolak.
3. Unit test memverifikasi seluruh kemungkinan validasi input pengeluaran.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/PersonalExpenseRequestTest.php`
- **Metode Uji:**
  1. `test_personal_expense_validation_passes_with_valid_payload()`:
     - Berikan `amount: 15000, category_id: 1, notes: 'Makan siang warteg'` $\rightarrow$ Validasi lulus.
  2. `test_personal_expense_validation_fails_when_category_does_not_exist()`:
     - Berikan `amount: 15000, category_id: 9999` $\rightarrow$ Validasi gagal.
