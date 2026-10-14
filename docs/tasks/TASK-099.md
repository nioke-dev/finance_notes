# TASK-099: Form Request Validasi Pembayaran Cicilan Utang (`PayDebtInstallmentRequest`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Kelas Form Request Mandiri Validasi Pembayaran Cicilan & Diskon Promo 0%  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun kelas Form Request mandiri `PayDebtInstallmentRequest` untuk memisahkan validasi pembayaran cicilan utang / PayLater dari controller:
1. **Aturan Validasi Parameter:**
   - `debt_installment_id`: Wajib (`required|exists:debt_installments,id`).
   - `payment_account_id`: Wajib (`required|exists:accounts,id`) (rekening sumber pembayaran, misal SeaBank / BCA).
   - `amount_paid`: Wajib (`required|integer|min:1`) (nominal rupiah yang dibayarkan).
   - `promo_discount`: Opsional (`nullable|integer|min:0`) (diskon promo bunga 0% SPayLater).
2. **Kustomisasi Pesan Error:**
   - Menyediakan pesan yang jelas: *"Nominal pembayaran cicilan minimal Rp1"*, *"Akun pembayaran tidak valid"*.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Debt/PayDebtInstallmentRequest.php` — Form Request validasi bayar cicilan.
2. `tests/Unit/Requests/PayDebtInstallmentRequestTest.php` — Unit test aturan validasi bayar cicilan.

---

## 3. Spesifikasi Anatomi Kode (`PayDebtInstallmentRequest.php`)
```php
namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class PayDebtInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_installment_id' => ['required', 'exists:debt_installments,id'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'amount_paid' => ['required', 'integer', 'min:1'],
            'promo_discount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.min' => 'Nominal pembayaran cicilan minimal Rp1.',
            'payment_account_id.exists' => 'Akun sumber pembayaran tidak valid.',
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Request tanpa id cicilan atau cicilan yang tidak ada di database ditolak HTTP 422.
2. Request dengan nilai promo diskon negatif ditolak.
3. Unit test memverifikasi seluruh kemungkinan payload validasi pembayaran cicilan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Requests/PayDebtInstallmentRequestTest.php`
- **Metode Uji:**
  1. `test_pay_debt_installment_validation_passes_with_valid_payload()`:
     - Berikan payload valid $\rightarrow$ Validasi lulus.
  2. `test_pay_debt_installment_validation_fails_with_negative_discount()`:
     - Berikan `promo_discount: -5000` $\rightarrow$ Validasi gagal.
