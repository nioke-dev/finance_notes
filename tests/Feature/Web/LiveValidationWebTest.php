<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveValidationWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => 'active',
            'day_type' => WorkDay::TYPE_ON_BID,
        ]);

        $this->cashAccount = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);
    }

    public function test_live_validator_asset_file_exists_and_contains_expected_engines(): void
    {
        $path = public_path('js/live-validator.js');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('window.showGlobalToast', $content);
        $this->assertStringContainsString('validateField', $content);
        $this->assertStringContainsString('live-input-shake', $content);
        $this->assertStringContainsString('initLiveValidation', $content);
        $this->assertStringContainsString('live-error-msg', $content);
    }

    public function test_master_layout_renders_live_validation_script_and_styles(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Memastikan tag script live-validator.js dan CSS di-render di halaman
        $response->assertSee('/js/live-validator.js');
        $response->assertSee('live-input-shake');
        $response->assertSee('liveShake');
    }

    public function test_cash_denominations_backend_rejects_negative_piece_count(): void
    {
        $response = $this->post(route('operational.cash-denominations'), [
            'work_day_id' => $this->workDay->id,
            'bill_50000' => -2,
        ]);

        $response->assertSessionHasErrors(['bill_50000']);
    }

    public function test_cash_denominations_backend_rejects_negative_damaged_notes(): void
    {
        $response = $this->post(route('operational.cash-denominations'), [
            'work_day_id' => $this->workDay->id,
            'damaged_notes_amount' => -5000,
        ]);

        $response->assertSessionHasErrors(['damaged_notes_amount']);
    }

    public function test_floating_toast_displays_session_error_message(): void
    {
        // Mengunjungi dashboard dengan flash message error di session
        $response = $this->withSession(['error' => 'Input nominal tidak valid'])->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Input nominal tidak valid');
        $response->assertSee('global-floating-toast');
    }
}
