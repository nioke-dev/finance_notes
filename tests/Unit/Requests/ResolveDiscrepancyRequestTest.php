<?php

namespace Tests\Unit\Requests;

use App\Enums\AccountType;
use App\Http\Requests\Reconciliation\ResolveDiscrepancyRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResolveDiscrepancyRequestTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDay = WorkDay::create([
            'work_date' => now()->subDay()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $this->account = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 50000,
            'initial_balance' => 50000,
        ]);

        $this->category = Category::create([
            'name' => 'Bensin & BBM',
            'type' => 'expense',
        ]);
    }

    public function test_resolve_discrepancy_validation_passes_with_complete_audit_reason(): void
    {
        $payload = [
            'work_day_id' => $this->workDay->id,
            'amount' => 15000,
            'category_id' => $this->category->id,
            'source_account_id' => $this->account->id,
            'reason' => 'Lupa catat bensin malam hari',
        ];

        $request = new ResolveDiscrepancyRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_resolve_discrepancy_validation_fails_without_reason_or_short_reason(): void
    {
        $payload = [
            'work_day_id' => $this->workDay->id,
            'amount' => 15000,
            'category_id' => $this->category->id,
            'source_account_id' => $this->account->id,
            'reason' => 'lupa', // kurang dari 5 karakter
        ];

        $request = new ResolveDiscrepancyRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('reason'));
        $this->assertEquals('Alasan minimal terdiri dari 5 karakter.', $errors->first('reason'));
    }

    public function test_resolve_discrepancy_validation_fails_with_invalid_amount_or_missing_fields(): void
    {
        $payload = [
            'work_day_id' => 9999, // non existent
            'amount' => 0, // minimal Rp1
            'category_id' => 9999,
            'source_account_id' => 9999,
        ];

        $request = new ResolveDiscrepancyRequest();
        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('work_day_id'));
        $this->assertTrue($errors->has('amount'));
        $this->assertTrue($errors->has('category_id'));
        $this->assertTrue($errors->has('source_account_id'));
        $this->assertTrue($errors->has('reason'));
        $this->assertEquals('Nominal transaksi minimal Rp1.', $errors->first('amount'));
    }
}
