<?php

namespace Tests\Unit\Requests;

use App\Enums\AccountType;
use App\Http\Requests\Debt\PayDebtInstallmentRequest;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PayDebtInstallmentRequestTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private int $installmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $account = Account::create([
            'name' => 'SeaBank Tabungan',
            'type' => AccountType::BANK,
            'current_balance' => 1000000,
            'initial_balance' => 1000000,
            'is_active' => true,
        ]);
        $this->accountId = $account->id;

        $debt = Debt::create([
            'name' => 'SPayLater Beli Helm',
            'platform' => 'spaylater',
            'principal_amount' => 500000,
            'fee_amount' => 0,
            'total_obligation' => 500000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $installment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addMonth()->toDateString(),
            'amount_due' => 500000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
        $this->installmentId = $installment->id;
    }

    public function test_pay_debt_installment_validation_passes_with_valid_payload(): void
    {
        $payload = [
            'debt_installment_id' => $this->installmentId,
            'payment_account_id' => $this->accountId,
            'amount_paid' => 500000,
            'promo_discount' => 15000,
        ];

        $request = new PayDebtInstallmentRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_pay_debt_installment_validation_fails_with_negative_discount(): void
    {
        $payload = [
            'debt_installment_id' => $this->installmentId,
            'payment_account_id' => $this->accountId,
            'amount_paid' => 500000,
            'promo_discount' => -5000,
        ];

        $request = new PayDebtInstallmentRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('promo_discount', $validator->errors()->toArray());
    }

    public function test_pay_debt_installment_validation_fails_with_invalid_installment_id(): void
    {
        $payload = [
            'debt_installment_id' => 99999,
            'payment_account_id' => $this->accountId,
            'amount_paid' => 500000,
        ];

        $request = new PayDebtInstallmentRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('debt_installment_id', $validator->errors()->toArray());
    }
}
