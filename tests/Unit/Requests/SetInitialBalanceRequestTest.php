<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Account\SetInitialBalanceRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SetInitialBalanceRequestTest extends TestCase
{
    /**
     * Memverifikasi validasi sukses dengan integer positif atau nol.
     */
    public function test_set_initial_balance_validation_passes_with_valid_integer(): void
    {
        $request = new SetInitialBalanceRequest();

        $payload = [
            'initial_balance' => 500000,
            'reason' => 'Saldo awal modal narik',
        ];

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /**
     * Memverifikasi validasi gagal jika initial_balance negatif atau bertipe desimal/string.
     */
    public function test_set_initial_balance_fails_with_negative_or_decimal_value(): void
    {
        $request = new SetInitialBalanceRequest();

        // 1. Kasus negatif
        $validatorNegative = Validator::make(['initial_balance' => -100], $request->rules(), $request->messages());
        $this->assertFalse($validatorNegative->passes());
        $this->assertArrayHasKey('initial_balance', $validatorNegative->errors()->toArray());

        // 2. Kasus desimal
        $validatorDecimal = Validator::make(['initial_balance' => 50000.50], $request->rules(), $request->messages());
        $this->assertFalse($validatorDecimal->passes());

        // 3. Kasus string non-angka
        $validatorString = Validator::make(['initial_balance' => 'lima_puluh_ribu'], $request->rules(), $request->messages());
        $this->assertFalse($validatorString->passes());

        // 4. Kasus kosong
        $validatorEmpty = Validator::make([], $request->rules(), $request->messages());
        $this->assertFalse($validatorEmpty->passes());
    }
}
