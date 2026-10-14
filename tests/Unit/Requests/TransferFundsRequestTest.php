<?php

namespace Tests\Unit\Requests;

use App\Enums\AccountType;
use App\Http\Requests\Account\TransferFundsRequest;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TransferFundsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_validation_fails_when_source_and_destination_are_identical(): void
    {
        $account = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $request = new TransferFundsRequest();
        $validator = Validator::make([
            'source_account_id' => $account->id,
            'destination_account_id' => $account->id,
            'amount' => 50000,
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('destination_account_id', $validator->errors()->toArray());
        $this->assertSame(
            'Akun tujuan transfer tidak boleh sama dengan akun sumber.',
            $validator->errors()->first('destination_account_id')
        );
    }

    public function test_transfer_validation_passes_with_distinct_accounts_and_positive_integer(): void
    {
        $source = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $destination = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'is_active' => true,
        ]);

        $request = new TransferFundsRequest();
        $validator = Validator::make([
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 50000,
            'admin_fee' => 1000,
            'notes' => 'Top Up ShopeePay',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_transfer_validation_fails_with_invalid_amount_or_admin_fee(): void
    {
        $source = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $destination = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'is_active' => true,
        ]);

        $request = new TransferFundsRequest();

        // Amount <= 0
        $validator1 = Validator::make([
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 0,
        ], $request->rules(), $request->messages());
        $this->assertTrue($validator1->fails());

        // Admin fee negative
        $validator2 = Validator::make([
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 10000,
            'admin_fee' => -500,
        ], $request->rules(), $request->messages());
        $this->assertTrue($validator2->fails());
    }
}
