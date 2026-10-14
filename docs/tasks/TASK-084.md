# TASK-084: Form Request Validasi Pesanan COD Lapangan (`CodOrderRequest`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Tagihan COD & Uang Tunai Diterima  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `CodOrderRequest` untuk memvalidasi transaksi penerimaan uang tunai pesanan COD di lapangan:
1. **Aturan Validasi Parameter:**
   - `order_amount`: Wajib (`required`), integer utuh (`integer`), nominal tagihan sistem minimum Rp1.000 (`min:1000`).
   - `cash_collected`: Wajib (`required`), integer utuh (`integer`), **harus lebih besar atau sama dengan nominal tagihan** (`gte:order_amount`). Uang tunai yang diterima driver tidak boleh kurang dari tagihan aplikasi.
   - `order_number`: Opsional (`nullable|string|max:50`).
2. **Kustomisasi Pesan Error:**
   - Memberikan pesan validasi presisi: *"Uang tunai yang diterima tidak boleh kurang dari nominal tagihan pesanan COD (gte:order_amount)"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickAction/CodOrderRequest.php` — Form Request validasi pesanan COD.
2. `tests/Unit/Requests/CodOrderRequestTest.php` — Unit test aturan validasi COD.

---

## 3. Spesifikasi Anatomi Kode (`CodOrderRequest.php`)
```php
namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class CodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_amount' => ['required', 'integer', 'min:1000'],
            'cash_collected' => ['required', 'integer', 'gte:order_amount'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_amount.min' => 'Tagihan pesanan COD minimal Rp1.000.',
            'cash_collected.gte' => 'Uang tunai yang diterima tidak boleh kurang dari nominal tagihan pesanan COD.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request di mana `cash_collected < order_amount` otomatis ditolak HTTP 422.
2. Request dengan nilai integer dan uang tunai mencukupi lolos validasi.
3. Unit test memverifikasi aturan batas `gte:order_amount` lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/CodOrderRequestTest.php`
- **Metode Uji:**
  1. `test_cod_validation_passes_when_cash_collected_is_greater_than_or_equal_to_order_amount()`:
     - Berikan `order_amount: 45000, cash_collected: 50000` $\rightarrow$ Validasi lulus (surplus tip 5k).
  2. `test_cod_validation_fails_when_cash_collected_is_less_than_order_amount()`:
     - Berikan `order_amount: 50000, cash_collected: 40000` $\rightarrow$ Validasi gagal.
