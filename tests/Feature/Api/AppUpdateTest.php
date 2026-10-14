<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\AppUpdateController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_update_returns_has_update_when_client_version_is_lower(): void
    {
        $response = $this->getJson('/api/v1/app/check-update?version_code=1');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'has_update' => true,
            'latest_version_code' => AppUpdateController::LATEST_VERSION_CODE,
            'latest_version_name' => AppUpdateController::LATEST_VERSION_NAME,
        ]);
        $response->assertJsonStructure([
            'changelog',
            'download_url',
            'title',
        ]);
    }

    public function test_check_update_returns_no_update_when_client_version_is_up_to_date(): void
    {
        $response = $this->getJson('/api/v1/app/check-update?version_code=' . AppUpdateController::LATEST_VERSION_CODE);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'has_update' => false,
        ]);
    }

    public function test_download_apk_endpoint_returns_404_instruction_when_file_not_present(): void
    {
        $response = $this->get('/download/driver-finance-latest.apk');

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'latest_version_name' => AppUpdateController::LATEST_VERSION_NAME,
        ]);
    }
}
