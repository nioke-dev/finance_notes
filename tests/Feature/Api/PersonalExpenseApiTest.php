<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_personal_expense_defaults_to_bca_and_deducts_balance(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $expenseAccount = Account::where('name', 'Beban Makanan & Minuman Driver')->first();

        $payload = [
            'work_day_id' => $workDay->id,
            'amount' => 10000,
            'description' => 'Beli Air Mineral Indomaret',
        ];

        $response = $this->postJson('/api/v1/quick/personal-expense', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'amount' => 10000,
                    'account_name' => 'BCA',
                    'description' => 'Beli Air Mineral Indomaret',
                ],
            ]);

        $this->assertEquals(490000, $bca->fresh()->current_balance);
        $this->assertEquals(10000, $expenseAccount->fresh()->current_balance);
    }

    public function test_personal_expense_fails_with_amount_under_500(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'amount' => 200,
            'description' => 'Permen',
        ];

        $response = $this->postJson('/api/v1/quick/personal-expense', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_personal_expense_with_custom_account(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $gopay = Account::where('name', 'GoPay')->first();
        $gopay->update(['current_balance' => 100000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'account_id' => $gopay->id,
            'amount' => 15000,
            'description' => 'Kopi Janji Jiwa',
        ];

        $response = $this->postJson('/api/v1/quick/personal-expense', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'amount' => 15000,
                    'account_name' => 'GoPay',
                ],
            ]);

        $this->assertEquals(85000, $gopay->fresh()->current_balance);
    }
}
