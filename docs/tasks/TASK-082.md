# TASK-082: Form Request Validasi Quick Parkir (`QuickParkingRequest`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Parkir 1-Tap & Koordinat GPS  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `QuickParkingRequest` untuk memisahkan validasi parameter parkir lapangan dari controller:
1. **Aturan Validasi Input:**
   - `amount`: Wajib (`required`), integer utuh (`integer`), hanya boleh bernilai Rp1.000 atau Rp2.000 (`in:1000,2000`).
   - `latitude`: Opsional (`nullable`), format koordinat numerik (`numeric|between:-90,90`).
   - `longitude`: Opsional (`nullable`), format koordinat numerik (`numeric|between:-180,180`).
   - `restaurant_name`: Opsional (`nullable|string|max:100`).
2. **Kustomisasi Pesan Error:**
   - Memberikan pesan validasi yang jelas: *"Nominal parkir cepat hanya diperbolehkan Rp1.000 atau Rp2.000"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickAction/QuickParkingRequest.php` — Form Request validasi parkir cepat.
2. `tests/Unit/Requests/QuickParkingRequestTest.php` — Unit test aturan validasi parkir.

---

## 3. Spesifikasi Anatomi Kode (`QuickParkingRequest.php`)
```php
namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class QuickParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'in:1000,2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'restaurant_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.in' => 'Nominal parkir cepat hanya diperbolehkan Rp1.000 atau Rp2.000.',
            'latitude.between' => 'Format latitude GPS tidak valid.',
            'longitude.between' => 'Format longitude GPS tidak valid.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request dengan `amount` selain 1000 atau 2000 gagal dengan status validasi HTTP 422.
2. Koordinat GPS latitude dan longitude divalidasi dalam rentang derajat bumi yang valid.
3. Unit test memverifikasi seluruh skenario validasi berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/QuickParkingRequestTest.php`
- **Metode Uji:**
  1. `test_quick_parking_validation_passes_with_1000_and_gps()`:
     - Berikan `amount: 1000, latitude: -7.9839, longitude: 112.6214` $\rightarrow$ Validasi lulus.
  2. `test_quick_parking_validation_fails_with_invalid_amount()`:
     - Berikan `amount: 5000` $\rightarrow$ Validasi gagal dengan error pesan in.
