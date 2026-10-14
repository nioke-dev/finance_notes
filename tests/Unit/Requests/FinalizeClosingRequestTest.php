<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Reconciliation\FinalizeClosingRequest;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FinalizeClosingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_closing_validation_passes_with_valid_denominations_array(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'denominations' => [
                ['nominal' => 100000, 'quantity' => 2],
                ['nominal' => 50000, 'quantity' => 1],
                ['nominal' => 20000, 'quantity' => 0],
            ],
            'closing_notes' => 'Closing normal aman lancar',
        ];

        $request = new FinalizeClosingRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_finalize_closing_validation_fails_with_negative_quantity_or_invalid_nominal(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $invalidPayload = [
            'work_day_id' => $workDay->id,
            'denominations' => [
                ['nominal' => 75000, 'quantity' => 1], // nominal tidak resmi BI
                ['nominal' => 10000, 'quantity' => -2], // quantity minus
            ],
        ];

        $request = new FinalizeClosingRequest();
        $validator = Validator::make($invalidPayload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('denominations.0.nominal'));
        $this->assertTrue($errors->has('denominations.1.quantity'));
        $this->assertEquals('Pecahan uang harus sesuai denominasi resmi Rupiah.', $errors->first('denominations.0.nominal'));
        $this->assertEquals('Jumlah lembar/keping uang tidak boleh negatif.', $errors->first('denominations.1.quantity'));
    }
}
