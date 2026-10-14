<?php

namespace Tests\Unit\Resources;

use App\Enums\AccountType;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AccountResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_resource_formats_balance_as_strict_integer(): void
    {
        $account = Account::create([
            'name' => 'ShopeePay Dompet',
            'type' => AccountType::EWALLET,
            'initial_balance' => 25000,
            'current_balance' => 500000,
            'is_active' => true,
        ]);

        $resource = new AccountResource($account);
        $result = $resource->toArray(Request::create('/api/v1/accounts'));

        $this->assertSame($account->id, $result['id']);
        $this->assertSame('ShopeePay Dompet', $result['name']);
        $this->assertSame('ewallet', $result['type']);
        $this->assertIsInt($result['current_balance']);
        $this->assertSame(500000, $result['current_balance']);
        $this->assertIsInt($result['initial_balance']);
        $this->assertSame(25000, $result['initial_balance']);
        $this->assertTrue($result['is_active']);
        $this->assertNotNull($result['updated_at']);
    }
}
