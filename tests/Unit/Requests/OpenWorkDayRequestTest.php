<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\WorkDay\OpenWorkDayRequest;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OpenWorkDayRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_work_day_validation_passes_with_valid_unique_date(): void
    {
        $payload = [
            'work_date' => '2026-10-15',
        ];

        $request = new OpenWorkDayRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_open_work_day_validation_fails_with_invalid_date_format(): void
    {
        $payload = [
            'work_date' => 'kemarin',
        ];

        $request = new OpenWorkDayRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('work_date'));
        $this->assertEquals('Format tanggal harus YYYY-MM-DD.', $errors->first('work_date'));
    }

    public function test_open_work_day_validation_fails_when_date_already_exists(): void
    {
        WorkDay::create([
            'work_date' => '2026-10-15',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $payload = [
            'work_date' => '2026-10-15',
        ];

        $request = new OpenWorkDayRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('work_date'));
        $this->assertEquals('Shift kerja untuk tanggal ini sudah pernah dibuka.', $errors->first('work_date'));
    }
}
