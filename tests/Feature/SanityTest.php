<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SanityTest extends TestCase
{
    /**
     * Memastikan environment testing aktif.
     */
    public function test_application_environment_is_set_to_testing(): void
    {
        $this->assertEquals('testing', app()->environment());
    }

    /**
     * Memastikan koneksi database test menggunakan SQLite in-memory (:memory:).
     */
    public function test_database_connection_uses_sqlite_in_memory(): void
    {
        $this->assertEquals('sqlite', DB::connection()->getDriverName());
        $this->assertEquals(':memory:', DB::connection()->getDatabaseName());
    }
}
