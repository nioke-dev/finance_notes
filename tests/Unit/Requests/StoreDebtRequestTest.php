<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Debt\StoreDebtRequest;
use App\Models\Account;
use App\Enums\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreDebtRequestTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $account = Account::create([
            'name' => 'BCA Rekening',
            'type' => AccountType::BANK,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $this->accountId = $account->id;
    }

    public function test_store_debt_validation_passes_with_valid_future_due_date(): void
    {
        $payload = [
            'type' => 'payable',
            'person_or_institution' => 'SeaBank Pinjaman',
            'principal_amount' => 5000000,
            'admin_fee' => 50000,
            'disbursement_account_id' => $this->accountId,
            'due_date' => now()->addDays(30)->toDateString(),
        ];

        $request = new StoreDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_store_debt_validation_fails_with_past_due_date(): void
    {
        $payload = [
            'type' => 'payable',
            'person_or_institution' => 'SeaBank Pinjaman',
            'principal_amount' => 5000000,
            'admin_fee' => 50000,
            'disbursement_account_id' => $this->accountId,
            'due_date' => now()->subDay()->toDateString(),
        ];

        $request = new StoreDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
    }

    public function test_store_debt_validation_fails_with_invalid_type(): void
    {
        $payload = [
            'type' => 'invalid_type',
            'person_or_institution' => 'SeaBank Pinjaman',
            'principal_amount' => 5000000,
            'disbursement_account_id' => $this->accountId,
            'due_date' => now()->addDays(30)->toDateString(),
        ];

        $request = new StoreDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }
}
