<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\QuickAction\QuickParkingRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class QuickParkingRequestTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    public function test_quick_parking_validation_passes_with_1000_and_gps(): void
    {
        $workDay = \App\Models\WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $request = new QuickParkingRequest();

        $data = [
            'work_day_id' => $workDay->id,
            'amount' => 1000,
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'restaurant_name' => 'Depot Slamet',
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_quick_parking_validation_passes_with_2000_without_gps(): void
    {
        $workDay = \App\Models\WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $request = new QuickParkingRequest();

        $data = [
            'work_day_id' => $workDay->id,
            'amount' => 2000,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_quick_parking_validation_fails_with_invalid_amount(): void
    {
        $request = new QuickParkingRequest();

        $data = [
            'amount' => 5000,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertEquals(
            'Nominal parkir cepat hanya diperbolehkan Rp1.000 atau Rp2.000.',
            $validator->errors()->first('amount')
        );
    }

    public function test_quick_parking_validation_fails_with_invalid_gps_coordinates(): void
    {
        $request = new QuickParkingRequest();

        $data = [
            'amount' => 1000,
            'latitude' => 105.0, // Invalid latitude (> 90)
            'longitude' => 200.0, // Invalid longitude (> 180)
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());
        $this->assertArrayHasKey('longitude', $validator->errors()->toArray());
    }
}
