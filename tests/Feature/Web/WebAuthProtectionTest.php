<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_access_analytics(): void
    {
        $response = $this->get('/analytics/driver');

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_authenticated_users_can_access_debts_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/debts');

        $response->assertStatus(200);
    }
}
