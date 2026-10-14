<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\DebtResource;
use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DebtResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_debt_resource_calculates_overdue_flag_and_days_remaining(): void
    {
        $debt = Debt::create([
            'name' => 'SPayLater Cicilan HP',
            'platform' => 'spaylater',
            'principal_amount' => 1200000,
            'fee_amount' => 0,
            'total_obligation' => 1200000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(5)->toDateString(),
            'amount_due' => 400000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $resource = new DebtResource($debt);
        $data = $resource->toArray(Request::create('/api/v1/debts'));

        $this->assertEquals($debt->id, $data['id']);
        $this->assertEquals('spaylater', $data['platform']);
        $this->assertEquals(1200000, $data['principal_amount']);
        $this->assertEquals(1200000, $data['remaining_amount']);
        $this->assertEquals(5, $data['days_until_due']);
        $this->assertFalse($data['is_overdue']);
        $this->assertEquals('active', $data['status']);
    }

    public function test_debt_resource_detects_overdue_debt(): void
    {
        $debt = Debt::create([
            'name' => 'Pinjaman Teman Budi',
            'borrower_name' => 'Budi',
            'platform' => 'personal',
            'principal_amount' => 50000,
            'fee_amount' => 0,
            'total_obligation' => 50000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->subDays(10)->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->subDays(3)->toDateString(),
            'amount_due' => 50000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $resource = new DebtResource($debt);
        $data = $resource->toArray(Request::create('/api/v1/debts'));

        $this->assertEquals(-3, $data['days_until_due']);
        $this->assertTrue($data['is_overdue']);
    }
}
