<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_and_authenticate_via_sanctum_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Driver ShopeeFood',
            'email' => 'driver@keuangan.test',
        ]);

        $token = $user->createToken('mobile-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => 'driver@keuangan.test',
            ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
