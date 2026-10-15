<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DeployWebhookTest extends TestCase
{
    public function test_deploy_fails_with_invalid_secret_key(): void
    {
        $response = $this->getJson('/api/deploy?key=wrong-key');
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_deploy_executes_with_valid_key(): void
    {
        $response = $this->getJson('/api/deploy?key=keuangan-secret-deploy-2026');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'git_pull',
            'artisan_migrate',
            'artisan_optimize',
            'message',
        ]);
    }

    public function test_deploy_supports_database_reset(): void
    {
        $response = $this->getJson('/api/deploy?key=keuangan-secret-deploy-2026&reset_db=yes');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'artisan_migrate' => 'Database reset and seeded successfully.',
        ]);
    }
}
