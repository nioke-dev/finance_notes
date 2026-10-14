<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriverLoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_login_with_valid_credentials_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'driver@keuangan.test',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'email' => 'driver@keuangan.test',
            'password' => 'password123',
            'device_name' => 'Redmi Note 12 - Helm Intercom',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Verify token works on authenticated endpoint
        $authResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
        $authResponse->assertStatus(200);
    }

    public function test_driver_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'driver@keuangan.test',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'email' => 'driver@keuangan.test',
            'password' => 'wrongpassword',
            'device_name' => 'Redmi Note 12',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_driver_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create([
            'email' => 'driver@keuangan.test',
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('Redmi Note 12')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Redmi Note 12',
        ]);
    }
}
