<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\QuickAction\QuickFuelRequest;
use App\Models\Account;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class QuickFuelRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_quick_fuel_validation_passes_with_valid_spbu_payload(): void
    {
        $account = Account::where('name', 'BCA')->first();

        $request = new QuickFuelRequest();

        $data = [
            'type' => 'spbu',
            'amount' => 20000,
            'source_account_id' => $account->id,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_quick_fuel_validation_fails_with_invalid_type_or_small_amount(): void
    {
        $account = Account::where('name', 'BCA')->first();

        $request = new QuickFuelRequest();

        $data = [
            'type' => 'pertamax_turbo',
            'amount' => 500,
            'source_account_id' => $account->id,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertEquals('Jenis pengisian bensin harus berupa SPBU atau Madura.', $validator->errors()->first('type'));
        $this->assertEquals('Nominal bensin minimal Rp1.000.', $validator->errors()->first('amount'));
    }

    public function test_quick_fuel_validation_passes_with_vendor_type_and_account_id(): void
    {
        $account = Account::where('name', 'Cash')->first();

        $request = new QuickFuelRequest();

        $data = [
            'vendor_type' => 'madura',
            'amount' => 12000,
            'account_id' => $account->id,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }
}
