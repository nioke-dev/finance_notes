<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi seeder mengisi kategori bawaan sistem.
     */
    public function test_category_seeder_populates_required_system_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $bensin = Category::where('name', 'Bensin Motor')->first();
        $this->assertNotNull($bensin);
        $this->assertEquals('expense', $bensin->type);
        $this->assertTrue($bensin->is_system);

        $hub = Category::where('name', 'Pendapatan Hub Shopee')->first();
        $this->assertNotNull($hub);
        $this->assertEquals('income', $hub->type);
        $this->assertTrue($hub->is_system);

        $this->assertEquals(11, Category::count());
    }

    /**
     * Memverifikasi scope query expense dan income pada Category.
     */
    public function test_category_scopes_filter_by_type(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertEquals(7, Category::expense()->count());
        $this->assertEquals(4, Category::income()->count());
    }
}
