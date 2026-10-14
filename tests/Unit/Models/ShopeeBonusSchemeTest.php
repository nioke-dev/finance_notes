<?php

namespace Tests\Unit\Models;

use App\Models\ShopeeBonusScheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShopeeBonusSchemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopee_bonus_schemes_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('shopee_bonus_schemes'));
        $this->assertTrue(Schema::hasColumns('shopee_bonus_schemes', [
            'id',
            'tier_level',
            'min_orders',
            'max_orders',
            'rate_per_order',
            'is_active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_scope_active_filters_only_active_schemes(): void
    {
        ShopeeBonusScheme::create([
            'tier_level' => 1,
            'min_orders' => 1,
            'max_orders' => 15,
            'rate_per_order' => 0,
            'is_active' => true,
        ]);

        ShopeeBonusScheme::create([
            'tier_level' => 2,
            'min_orders' => 16,
            'max_orders' => 20,
            'rate_per_order' => 1000,
            'is_active' => true,
        ]);

        ShopeeBonusScheme::create([
            'tier_level' => 99,
            'min_orders' => 50,
            'max_orders' => 100,
            'rate_per_order' => 5000,
            'is_active' => false, // Non-aktif
        ]);

        $activeSchemes = ShopeeBonusScheme::active()->get();

        $this->assertCount(2, $activeSchemes);
        $this->assertSame([1, 2], $activeSchemes->pluck('tier_level')->all());
    }

    public function test_rate_per_order_casts_to_integer(): void
    {
        $scheme = ShopeeBonusScheme::create([
            'tier_level' => 3,
            'min_orders' => 21,
            'max_orders' => 25,
            'rate_per_order' => 1500,
            'is_active' => true,
        ]);

        $this->assertIsInt($scheme->rate_per_order);
        $this->assertSame(1500, $scheme->rate_per_order);
        $this->assertIsInt($scheme->tier_level);
        $this->assertTrue($scheme->is_active);
    }
}
