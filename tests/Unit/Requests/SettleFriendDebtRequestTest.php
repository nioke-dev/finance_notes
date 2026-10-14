<?php

namespace Tests\Unit\Requests;

use App\Enums\AccountType;
use App\Http\Requests\Debt\SettleFriendDebtRequest;
use App\Models\Account;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SettleFriendDebtRequestTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private int $debtId;

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

        $debt = Debt::create([
            'name' => 'Talangan Makan Siang Budi',
            'borrower_name' => 'Budi',
            'platform' => 'personal',
            'principal_amount' => 50000,
            'fee_amount' => 0,
            'total_obligation' => 50000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);
        $this->debtId = $debt->id;
    }

    public function test_settle_friend_debt_validation_passes_with_boolean_flag(): void
    {
        $payload = [
            'debt_id' => $this->debtId,
            'amount_received' => 45000,
            'destination_account_id' => $this->accountId,
            'forgive_remainder' => true,
        ];

        $request = new SettleFriendDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_settle_friend_debt_validation_fails_with_non_boolean_flag(): void
    {
        $payload = [
            'debt_id' => $this->debtId,
            'amount_received' => 45000,
            'destination_account_id' => $this->accountId,
            'forgive_remainder' => 'ikhlas_banget',
        ];

        $request = new SettleFriendDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('forgive_remainder', $validator->errors()->toArray());
    }

    public function test_settle_friend_debt_validation_fails_with_invalid_debt_id(): void
    {
        $payload = [
            'debt_id' => 99999,
            'amount_received' => 45000,
            'destination_account_id' => $this->accountId,
            'forgive_remainder' => false,
        ];

        $request = new SettleFriendDebtRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('debt_id', $validator->errors()->toArray());
    }
}
