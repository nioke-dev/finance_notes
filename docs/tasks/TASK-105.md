# TASK-105: Resource API JSON Transformation Utang & Cicilan (`DebtResource`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Standarisasi Output JSON Portofolio Utang/Piutang & Jadwal Cicilan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas API Resource mandiri `DebtResource` untuk menyajikan data utang dan piutang teman dalam format JSON terstandarisasi:
1. **Transformasi Output JSON:**
   - `id`: Integer.
   - `type`: String (`payable` atau `receivable`).
   - `person_or_institution`: String (nama teman/penyedia pinjaman).
   - `principal_amount`: Integer (pokok awal).
   - `remaining_amount`: Integer (sisa pokok yang belum lunas).
   - `due_date`: String `YYYY-MM-DD`.
   - `days_until_due`: Integer (sisa hari hingga tanggal jatuh tempo; negatif jika sudah telat).
   - `status`: String (`active`, `settled`).
   - `installments`: Array of mini object cicilan (jika loaded).
2. **Kenyamanan Visual UI:**
   - Menyediakan `is_overdue` (boolean) untuk mempercepat pewarnaan merah pada badge mobile/web.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Resources/DebtResource.php` — API Resource JSON data utang/piutang.
2. `tests/Unit/Resources/DebtResourceTest.php` — Unit test serialisasi resource utang.

---

## 3. Spesifikasi Anatomi Kode (`DebtResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dueDate = Carbon::parse($this->due_date);
        $daysUntilDue = (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'person_or_institution' => $this->person_or_institution,
            'principal_amount' => (int) $this->principal_amount,
            'remaining_amount' => (int) $this->remaining_amount,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'days_until_due' => $daysUntilDue,
            'is_overdue' => $daysUntilDue < 0 && $this->status === 'active',
            'status' => $this->status,
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `DebtResource` menghitung `days_until_due` dan `is_overdue` secara akurat.
2. Nominal rupiah dijamin integer tanpa desimal.
3. Unit test memverifikasi seluruh key JSON output.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Resources/DebtResourceTest.php`
- **Metode Uji:**
  1. `test_debt_resource_calculates_overdue_flag_and_days_remaining()`:
     - Buat dummy debt aktif dengan jatuh tempo besok.
     - Verifikasi `days_until_due === 1` dan `is_overdue === false`.
