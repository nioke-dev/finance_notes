<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?User $authenticatedUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        // In tests where web routes are accessed without explicit guest intentions,
        // automatically act as an authenticated user if needed.
        if (! $this instanceof \Tests\Feature\Web\WebAuthProtectionTest &&
            ! $this instanceof \Tests\Feature\Web\WebLoginTest &&
            ! $this instanceof \Tests\Feature\Auth\SanctumSetupTest &&
            ! $this instanceof \Tests\Feature\Auth\DriverLoginApiTest) {
            $user = new User([
                'id' => 1,
                'name' => 'Driver ShopeeFood Test',
                'email' => 'driver.test@keuangan.test',
            ]);
            $this->actingAs($user);
            $this->authenticatedUser = $user;
        }
    }
}
