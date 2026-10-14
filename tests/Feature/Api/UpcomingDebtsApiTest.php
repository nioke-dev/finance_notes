<?php

namespace Tests\Feature\Api;

use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingDebtsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_upcoming_debts_endpoint_returns_unpaid_installments_within_30_days(): void
    {
        $debt = Debt::create([
            'name' => 'SPayLater Beli Sepatu',
            'platform' => 'spaylater',
            'principal_amount' => 500000,
            'fee_amount' => 0,
            'total_obligation' => 500000,
            'tenor_months' => 2,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Cicilan 1: Jatuh tempo dalam 10 hari (masuk rentang)
        $nearInstallment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'amount_due' => 250000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Cicilan 2: Jatuh tempo dalam 60 hari (di luar rentang 30 hari)
        $farInstallment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 2,
            'due_date' => now()->addDays(60)->format('Y-m-d'),
            'amount_due' => 250000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->getJson('/api/v1/debts/upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'total_upcoming_obligation' => 250000,
                ],
            ]);

        $installments = $response->json('data.installments');
        $this->assertCount(1, $installments);
        $this->assertEquals($nearInstallment->id, $installments[0]['installment_id']);
        $this->assertEquals('SPayLater Beli Sepatu', $installments[0]['debt_name']);
        $this->assertEquals(250000, $installments[0]['amount_due']);
        $this->assertEquals(10, $installments[0]['days_remaining']);
    }

    public function test_paid_installments_are_excluded_from_upcoming_list(): void
    {
        $debt = Debt::create([
            'name' => 'SeaBank Pinjam',
            'platform' => 'seabank_pinjam',
            'principal_amount' => 1000000,
            'fee_amount' => 0,
            'total_obligation' => 1000000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'amount_due' => 1000000,
            'amount_paid' => 1000000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/debts/upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'total_upcoming_obligation' => 0,
                    'installments' => [],
                ],
            ]);
    }
}
