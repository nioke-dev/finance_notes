# TASK-087: Form Request Validasi Resolusi Selisih Kas Lampau (`ResolveDiscrepancyRequest`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Input Transaksi Susulan untuk Menutup Selisih  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `ResolveDiscrepancyRequest` untuk memvalidasi transaksi susulan yang dimasukkan ke hari kerja lampau yang masih gantung (`needs_reconciliation`):
1. **Aturan Validasi Parameter:**
   - `work_day_id`: Wajib (`required`), harus terdaftar di tabel `work_days` (`exists:work_days,id`).
   - `amount`: Wajib (`required`), integer utuh (`integer`), minimum Rp1 (`min:1`).
   - `category_id`: Wajib (`required`), harus terdaftar di tabel `categories` (`exists:categories,id`).
   - `source_account_id`: Wajib (`required`), harus terdaftar di tabel `accounts` (`exists:accounts,id`).
   - `reason`: Wajib (`required`), string, minimum 5 karakter (`string|min:5|max:255`) — Menjelaskan alasan mengapa transaksi ini terlambat dicatat saat narik.
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang ketat: *"Alasan keterlambatan pencatatan wajib diisi minimal 5 karakter untuk audit trail"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Reconciliation/ResolveDiscrepancyRequest.php` — Form Request validasi resolusi selisih.
2. `tests/Unit/Requests/ResolveDiscrepancyRequestTest.php` — Unit test aturan validasi resolusi selisih.

---

## 3. Spesifikasi Anatomi Kode (`ResolveDiscrepancyRequest.php`)
```php
namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'exists:work_days,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'source_account_id' => ['required', 'exists:accounts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan koreksi/transaksi terlambat wajib diisi untuk audit trail.',
            'reason.min' => 'Alasan minimal terdiri dari 5 karakter.',
            'amount.min' => 'Nominal transaksi minimal Rp1.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request tanpa kolom `reason` atau `reason < 5 karakter` ditolak dengan status HTTP 422.
2. Parameter `work_day_id`, `category_id`, dan `source_account_id` tervalidasi keberadaannya di database.
3. Unit test memverifikasi seluruh aturan validasi resolusi selisih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/ResolveDiscrepancyRequestTest.php`
- **Metode Uji:**
  1. `test_resolve_discrepancy_validation_passes_with_complete_audit_reason()`:
     - Berikan payload valid dengan `reason: 'Lupa catat bensin malam'` $\rightarrow$ Validasi lulus.
  2. `test_resolve_discrepancy_validation_fails_without_reason()`:
     - Kirim payload tanpa `reason` $\rightarrow$ Validasi gagal.
