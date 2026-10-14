# TASK-100: Form Request Validasi Pelunasan Utang Teman (`SettleFriendDebtRequest`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Uang Diterima & Opsi Ikhlas Sisa Sedekah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `SettleFriendDebtRequest` untuk memisahkan validasi pelunasan utang teman dari controller:
1. **Aturan Validasi Parameter:**
   - `debt_id`: Wajib (`required|exists:debts,id`).
   - `amount_received`: Wajib (`required|integer|min:1`) (nominal uang yang ditransfer/diberikan oleh teman).
   - `destination_account_id`: Wajib (`required|exists:accounts,id`) (rekening penampung pelunasan, misal BCA atau Kas Tunai).
   - `forgive_remainder`: Wajib (`required|boolean`) (jika `true`, selisih kekurangan otomatis dibebankan ke pos Sedekah dan status utang ditutup lunas; jika `false`, kekurangan tetap menjadi sisa piutang aktif).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang jelas: *"Pilihan pengiklasan sisa utang harus berupa nilai boolean (true/false)"*, *"Nominal pelunasan minimal Rp1"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Debt/SettleFriendDebtRequest.php` — Form Request validasi pelunasan utang teman.
2. `tests/Unit/Requests/SettleFriendDebtRequestTest.php` — Unit test aturan validasi pelunasan utang teman.

---

## 3. Spesifikasi Anatomi Kode (`SettleFriendDebtRequest.php`)
```php
namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class SettleFriendDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_id' => ['required', 'exists:debts,id'],
            'amount_received' => ['required', 'integer', 'min:1'],
            'destination_account_id' => ['required', 'exists:accounts,id'],
            'forgive_remainder' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_received.min' => 'Nominal uang yang diterima minimal Rp1.',
            'forgive_remainder.boolean' => 'Opsi pengiklasan sisa utang harus bernilai boolean.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request tanpa flag `forgive_remainder` ditolak HTTP 422.
2. Parameter `debt_id` dan `destination_account_id` tervalidasi di database.
3. Unit test memverifikasi seluruh kemungkinan payload validasi pelunasan utang teman.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/SettleFriendDebtRequestTest.php`
- **Metode Uji:**
  1. `test_settle_friend_debt_validation_passes_with_boolean_flag()`:
     - Berikan payload valid dengan `forgive_remainder: true` $\rightarrow$ Validasi lulus.
  2. `test_settle_friend_debt_validation_fails_with_non_boolean_flag()`:
     - Berikan `forgive_remainder: 'ikhlas_banget'` $\rightarrow$ Validasi gagal.
