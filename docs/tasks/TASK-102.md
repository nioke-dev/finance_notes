# TASK-102: Resource API JSON Transformation Master Akun (`AccountResource`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Transformasi Standar JSON Response Master Akun & Saldo Integer Rupiah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas API Resource mandiri `AccountResource` untuk memastikan output JSON master akun selalu konsisten dan ramah konsumsi mobile Flutter:
1. **Transformasi Output JSON:**
   - `id`: Integer.
   - `name`: String (misal "BCA", "ShopeePay", "Cash / Tunai").
   - `type`: String code.
   - `category`: String code.
   - `current_balance`: Integer (saldo riil saat ini dalam Rupiah utuh).
   - `is_active`: Boolean.
   - `updated_at`: Format ISO 8601.
2. **Proteksi Presisi Finansial:**
   - Memastikan `current_balance` dipaksa ke tipe `(int)` agar tidak pernah bergeser menjadi floating point atau string di mobile client.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Resources/AccountResource.php` — API Resource JSON master akun.
2. `tests/Unit/Resources/AccountResourceTest.php` — Unit test serialisasi resource akun.

---

## 3. Spesifikasi Anatomi Kode (`AccountResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'current_balance' => (int) $this->current_balance,
            'is_active' => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `AccountResource` merender struktur JSON yang konsisten dengan tipe data primitif yang tepat (`int`, `bool`, `string`).
2. Unit test memverifikasi `current_balance` selalu bertipe integer.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Resources/AccountResourceTest.php`
- **Metode Uji:**
  1. `test_account_resource_formats_balance_as_strict_integer()`:
     - Buat dummy account dengan saldo 500000.
     - Verifikasi output array memiliki `current_balance === 500000` (integer).
