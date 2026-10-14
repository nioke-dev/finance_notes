<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoriesMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi kolom yang diharapkan pada tabel categories.
     */
    public function test_categories_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));

        $expectedColumns = ['id', 'name', 'type', 'is_system', 'icon', 'color', 'created_at', 'updated_at'];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('categories', $column),
                "Kolom {$column} tidak ditemukan pada tabel categories."
            );
        }
    }
}
