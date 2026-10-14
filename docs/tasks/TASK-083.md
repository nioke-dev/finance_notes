# TASK-083: Form Request Validasi Quick Bensin (`QuickFuelRequest`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Bensin SPBU vs Madura & Sumber Rekening  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `QuickFuelRequest` untuk memisahkan validasi parameter pembelian bensin lapangan:
1. **Aturan Validasi Input:**
   - `type`: Wajib (`required`), string, bernilai salah satu dari `spbu` atau `madura` (`in:spbu,madura`).
   - `amount`: Wajib (`required`), integer utuh (`integer`), minimum Rp1.000 (`min:1000`).
   - `source_account_id`: Wajib (`required`), integer, harus terdaftar di tabel `accounts` (`exists:accounts,id`).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang presisi: *"Jenis bensin hanya boleh SPBU atau Madura"*, *"Nominal pembelian bensin minimal Rp1.000"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickAction/QuickFuelRequest.php` — Form Request validasi bensin cepat.
2. `tests/Unit/Requests/QuickFuelRequestTest.php` — Unit test aturan validasi bensin.

---

## 3. Spesifikasi Anatomi Kode (`QuickFuelRequest.php`)
```php
namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class QuickFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:spbu,madura'],
            'amount' => ['required', 'integer', 'min:1000'],
            'source_account_id' => ['required', 'exists:accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Jenis pengisian bensin harus berupa SPBU atau Madura.',
            'amount.min' => 'Nominal bensin minimal Rp1.000.',
            'source_account_id.exists' => 'Akun sumber pembayaran tidak valid.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan jenis bensin selain `spbu` atau `madura` otomatis ditolak HTTP 422.
2. Request dengan nominal bensin < Rp1.000 atau format non-integer ditolak.
3. Unit test memverifikasi seluruh kemungkinan kombinasi payload validasi bensin.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/QuickFuelRequestTest.php`
- **Metode Uji:**
  1. `test_quick_fuel_validation_passes_with_valid_spbu_payload()`:
     - Berikan `type: 'spbu', amount: 20000, source_account_id: 1` $\rightarrow$ Validasi lulus.
  2. `test_quick_fuel_validation_fails_with_invalid_type_or_small_amount()`:
     - Berikan `type: 'pertamax_turbo', amount: 500` $\rightarrow$ Validasi gagal.
