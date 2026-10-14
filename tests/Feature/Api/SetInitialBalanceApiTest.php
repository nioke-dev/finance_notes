<?php

namespace Tests\Feature\Api;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetInitialBalanceApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi set initial balance via API menghasilkan HTTP 200 dan format JSON sesuai spesifikasi.
     */
    public function test_can_set_initial_balance_via_api(): void
    {
        $account = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/initial-balance", [
            'initial_balance' => 2500000,
            'reason' => 'Setup saldo awal BCA',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Saldo awal berhasil diatur.',
                'data' => [
                    'account_id' => $account->id,
                    'name' => 'BCA',
                    'current_balance' => 2500000,
                    'initial_balance' => 2500000,
                ],
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'current_balance' => 2500000,
            'initial_balance' => 2500000,
        ]);
    }

    /**
     * Memverifikasi validasi menolak saldo negatif dengan HTTP 422.
     */
    public function test_cannot_set_negative_initial_balance(): void
    {
        $account = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/initial-balance", [
            'initial_balance' => -50000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['initial_balance']);
    }

    /**
     * Memverifikasi request pada ID akun yang tidak ada mengembalikan 404 Not Found.
     */
    public function test_cannot_set_initial_balance_for_non_existent_account(): void
    {
        $response = $this->postJson("/api/v1/accounts/999999/initial-balance", [
            'initial_balance' => 50000,
        ]);

        $response->assertStatus(404);
    }
}
