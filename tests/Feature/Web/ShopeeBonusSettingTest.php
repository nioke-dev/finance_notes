<?php

namespace Tests\Feature\Web;

use App\Models\AuditLog;
use App\Models\ShopeeBonusScheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopeeBonusSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopee_bonus_settings_page_can_be_accessed(): void
    {
        ShopeeBonusScheme::create([
            'tier_level' => 1,
            'min_orders' => 16,
            'max_orders' => 20,
            'rate_per_order' => 1000,
            'is_active' => true,
        ]);

        $response = $this->get('/settings/shopee-bonus');

        $response->assertStatus(200);
        $response->assertViewIs('pages.settings.shopee_bonus');
        $response->assertSee('Skema Bonus Bertingkat ShopeeFood');
        $response->assertSee('Tier 1');
        $response->assertSee('1000');
    }

    public function test_driver_can_update_bonus_rates_successfully(): void
    {
        $scheme = ShopeeBonusScheme::create([
            'tier_level' => 1,
            'min_orders' => 16,
            'max_orders' => 20,
            'rate_per_order' => 1000,
            'is_active' => true,
        ]);

        $payload = [
            'schemes' => [
                [
                    'id' => $scheme->id,
                    'min_orders' => 15,
                    'max_orders' => 20,
                    'rate_per_order' => 1200,
                ],
            ],
        ];

        $response = $this->post('/settings/shopee-bonus', $payload);

        $response->assertRedirect('/settings/shopee-bonus');
        $response->assertSessionHas('success');

        $this->assertEquals(1200, $scheme->fresh()->rate_per_order);
        $this->assertEquals(15, $scheme->fresh()->min_orders);

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update_shopee_bonus_scheme',
            'auditable_type' => ShopeeBonusScheme::class,
            'auditable_id' => $scheme->id,
        ]);
    }
}
