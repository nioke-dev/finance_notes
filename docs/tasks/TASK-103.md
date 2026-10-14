# TASK-103: Resource API JSON Transformation Mutasi Transaksi (`TransactionResource`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Transformasi Standar JSON Response Transaksi & Relasi Akun Sumber/Tujuan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas API Resource mandiri `TransactionResource` untuk memformat data transaksi dan baris jurnal double-entry:
1. **Transformasi Output JSON:**
   - `id`: Integer.
   - `reference_number`: String.
   - `transaction_time`: Format ISO 8601 & formatted string waktu lokal ("14:25 WIB").
   - `amount`: Integer (presisi Rp1).
   - `category`: String / Object relasi kategori.
   - `source_account`: Mini object (`id`, `name`).
   - `destination_account`: Mini object (`id`, `name`).
   - `notes`: String nullable.
   - `is_reversal`: Boolean (menandai transaksi koreksi pembatalan).
2. **Kenyamanan Mobile Client:**
   - Menyertakan label arah aliran dana yang mudah dibaca oleh widget riwayat transaksi di smartphone.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Resources/TransactionResource.php` — API Resource JSON transaksi.
2. `tests/Unit/Resources/TransactionResourceTest.php` — Unit test serialisasi resource transaksi.

---

## 3. Spesifikasi Anatomi Kode (`TransactionResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number ?? ('TX-' . $this->id),
            'transaction_time' => $this->transaction_time?->toISOString(),
            'formatted_time' => $this->transaction_time?->format('H:i') . ' WIB',
            'amount' => (int) $this->amount,
            'category' => $this->category,
            'source_account' => $this->whenLoaded('sourceAccount', fn() => [
                'id' => $this->sourceAccount->id,
                'name' => $this->sourceAccount->name,
            ]),
            'destination_account' => $this->whenLoaded('destinationAccount', fn() => [
                'id' => $this->destinationAccount->id,
                'name' => $this->destinationAccount->name,
            ]),
            'notes' => $this->notes,
            'is_reversal' => (bool) $this->is_reversal,
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. `TransactionResource` merender output transaksi dengan relasi akun tanpa circular reference.
2. Nominal `amount` dipastikan bertipe integer murni.
3. Unit test memverifikasi kelengkapan key array output.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Resources/TransactionResourceTest.php`
- **Metode Uji:**
  1. `test_transaction_resource_renders_expected_keys_and_integer_amount()`:
     - Buat dummy transaksi Rp20.000.
     - Verifikasi `amount === 20000` dan key `reference_number` tersedia.
