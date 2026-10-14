# TASK-086: Form Request Validasi Finalisasi Closing (`FinalizeClosingRequest`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Input Denominasi Lembar & Keping Kas Fisik  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `FinalizeClosingRequest` untuk memisahkan validasi parameter closing harian dari controller:
1. **Aturan Validasi Parameter:**
   - `work_day_id`: Wajib (`required`), harus terdaftar di tabel `work_days` (`exists:work_days,id`).
   - `denominations`: Wajib (`required`), bertipe array (`array`).
   - `denominations.*.nominal`: Wajib (`required`), integer, harus merupakan pecahan resmi rupiah (`in:100000,50000,20000,10000,5000,2000,1000,500,200,100`).
   - `denominations.*.quantity`: Wajib (`required`), integer utuh, minimum 0 (`integer|min:0`).
   - `closing_notes`: Opsional (`nullable|string|max:500`).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang jelas: *"Pecahan uang harus sesuai dengan standar Rupiah Bank Indonesia"*, *"Jumlah lembar tidak boleh negatif"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Reconciliation/FinalizeClosingRequest.php` — Form Request validasi closing harian.
2. `tests/Unit/Requests/FinalizeClosingRequestTest.php` — Unit test aturan validasi closing.

---

## 3. Spesifikasi Anatomi Kode (`FinalizeClosingRequest.php`)
```php
namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'exists:work_days,id'],
            'denominations' => ['required', 'array'],
            'denominations.*.nominal' => ['required', 'integer', 'in:100000,50000,20000,10000,5000,2000,1000,500,200,100'],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'denominations.*.nominal.in' => 'Pecahan uang harus sesuai denominasi resmi Rupiah.',
            'denominations.*.quantity.min' => 'Jumlah lembar/keping uang tidak boleh negatif.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan pecahan asing (misal Rp75.000 atau Rp30.000) ditolak HTTP 422.
2. Request dengan quantity lembar bernilai minus ditolak.
3. Unit test memverifikasi seluruh kemungkinan struktur array denominasi.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/FinalizeClosingRequestTest.php`
- **Metode Uji:**
  1. `test_finalize_closing_validation_passes_with_valid_denominations_array()`:
     - Berikan payload pecahan 100k (2 lembar) dan 50k (1 lembar) $\rightarrow$ Validasi lulus.
  2. `test_finalize_closing_validation_fails_with_negative_quantity_or_invalid_nominal()`:
     - Berikan quantity -1 $\rightarrow$ Validasi gagal.
