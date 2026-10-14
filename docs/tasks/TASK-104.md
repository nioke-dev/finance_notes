# TASK-104: Resource API JSON Transformation Hari Kerja (`WorkDayResource`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Standarisasi Output JSON Hari Kerja, Status Rekonsiliasi, & Ringkasan Performa  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas API Resource mandiri `WorkDayResource` untuk mentransformasi data hari kerja, status rekonsiliasi, dan metrik performa lapangan driver:
1. **Transformasi Output JSON:**
   - `id`: Integer.
   - `date`: String `YYYY-MM-DD` & formatted date Indonesia ("Rabu, 14 Okt 2026").
   - `status`: String code (`open`, `needs_reconciliation`, `final`).
   - `total_trips`: Integer.
   - `total_shopee_income`: Integer (pendapatan hub + bonus + tip).
   - `is_balanced`: Boolean (true jika status `final` atau difference = 0).
   - `closing_notes`: String nullable.
2. **Kenyamanan Mobile Client:**
   - Menyediakan status visual yang siap ditampilkan langsung oleh kartu beranda dan widget history harian.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Resources/WorkDayResource.php` — API Resource JSON hari kerja.
2. `tests/Unit/Resources/WorkDayResourceTest.php` — Unit test serialisasi resource hari kerja.

---

## 3. Spesifikasi Anatomi Kode (`WorkDayResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'formatted_date' => $this->date?->translatedFormat('l, d M Y'),
            'status' => $this->status,
            'total_trips' => (int) $this->total_trips,
            'total_shopee_income' => (int) ($this->shopee_hub_income + $this->bonus_amount + $this->shopee_tip_amount),
            'is_balanced' => $this->status === 'final',
            'closing_notes' => $this->closing_notes,
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `WorkDayResource` merender output dengan format tanggal standar dan kalkulasi total pendapatan yang presisi.
2. Unit test memverifikasi integritas tipe data integer dan boolean.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Resources/WorkDayResourceTest.php`
- **Metode Uji:**
  1. `test_work_day_resource_formats_date_and_totals_correctly()`:
     - Buat dummy `WorkDay` dengan 18 trip.
     - Verifikasi output array memiliki `total_trips === 18` dan key `formatted_date` valid.
