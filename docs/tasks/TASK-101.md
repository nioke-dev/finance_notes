# TASK-101: Form Request Validasi Pembukaan Hari Kerja Baru (`OpenWorkDayRequest`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Format Tanggal Buka Shift Baru  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `OpenWorkDayRequest` untuk memisahkan validasi tanggal saat driver membuka shift narik baru:
1. **Aturan Validasi Parameter:**
   - `date`: Wajib (`required`), format tanggal standar `YYYY-MM-DD` (`date_format:Y-m-d`).
   - Tidak boleh ada duplikasi pembukaan pada tanggal yang sama (`unique:work_days,date`).
   - Sesuai aturan bisnis `BUSINESS-RULES.md`, pembukaan hari baru diizinkan meskipun ada hari lama yang belum selesai rekonsiliasi (*Anti-Deadlock rule*).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang jelas: *"Format tanggal harus YYYY-MM-DD"*, *"Shift kerja untuk tanggal ini sudah pernah dibuka sebelumnya"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/WorkDay/OpenWorkDayRequest.php` — Form Request validasi buka shift baru.
2. `tests/Unit/Requests/OpenWorkDayRequestTest.php` — Unit test aturan validasi buka shift.

---

## 3. Spesifikasi Anatomi Kode (`OpenWorkDayRequest.php`)
```php
namespace App\Http\Requests\WorkDay;

use Illuminate\Foundation\Http\FormRequest;

class OpenWorkDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'unique:work_days,date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal kerja wajib diisi.',
            'date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'date.unique' => 'Shift kerja untuk tanggal ini sudah pernah dibuka.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan format tanggal salah (misal `14/10/2026`) ditolak HTTP 422.
2. Request dengan tanggal yang sudah ada di tabel `work_days` ditolak HTTP 422.
3. Unit test memverifikasi aturan tanggal dan keunikan hari.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/OpenWorkDayRequestTest.php`
- **Metode Uji:**
  1. `test_open_work_day_validation_passes_with_valid_unique_date()`:
     - Berikan `date: '2026-10-15'` $\rightarrow$ Validasi lulus.
  2. `test_open_work_day_validation_fails_with_invalid_date_format()`:
     - Berikan `date: 'kemarin'` $\rightarrow$ Validasi gagal.
