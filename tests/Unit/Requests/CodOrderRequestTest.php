<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\QuickAction\CodOrderRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CodOrderRequestTest extends TestCase
{
    public function test_cod_validation_passes_when_cash_collected_is_greater_than_or_equal_to_order_amount(): void
    {
        $request = new CodOrderRequest();

        $data = [
            'order_amount' => 45000,
            'cash_collected' => 50000,
            'order_number' => 'SPF-999',
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_cod_validation_passes_when_exact_amount(): void
    {
        $request = new CodOrderRequest();

        $data = [
            'order_amount' => 25000,
            'cash_collected' => 25000,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_cod_validation_fails_when_cash_collected_is_less_than_order_amount(): void
    {
        $request = new CodOrderRequest();

        $data = [
            'order_amount' => 50000,
            'cash_collected' => 40000,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('cash_collected', $validator->errors()->toArray());
        $this->assertEquals(
            'Uang tunai yang diterima tidak boleh kurang dari nominal tagihan pesanan COD.',
            $validator->errors()->first('cash_collected')
        );
    }
}
