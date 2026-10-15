<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaInstallabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_endpoint_returns_valid_pwa_configuration()
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');

        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertEquals('Driver Finance - Keuangan ShopeeFood Malang', $data['name']);
        $this->assertEquals('Driver Finance', $data['short_name']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertEquals('/?source=pwa', $data['start_url']);
        $this->assertEquals('#0F172A', $data['theme_color']);
        $this->assertEquals('#0F172A', $data['background_color']);

        // Must have at least 192x192 and 512x512 icons for Chrome installability
        $this->assertNotEmpty($data['icons']);
        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        // Verify maskable icon exists
        $purposes = array_column($data['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
    }

    public function test_service_worker_endpoint_returns_valid_javascript_and_headers()
    {
        $response = $this->get('/sw.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $response->assertHeader('Service-Worker-Allowed', '/');

        $content = $response->getContent();
        $this->assertStringContainsString('self.addEventListener', $content);
        $this->assertStringContainsString("'fetch'", $content);
        $this->assertStringContainsString('skipWaiting', $content);
    }

    public function test_pwa_icon_files_exist_and_are_valid()
    {
        $this->assertFileExists(public_path('icons/icon-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-512x512.png'));
        $this->assertFileExists(public_path('icons/icon-512x512-maskable.png'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));

        // Check image dimensions
        $size192 = getimagesize(public_path('icons/icon-192x192.png'));
        $this->assertEquals(192, $size192[0]);
        $this->assertEquals(192, $size192[1]);

        $size512 = getimagesize(public_path('icons/icon-512x512.png'));
        $this->assertEquals(512, $size512[0]);
        $this->assertEquals(512, $size512[1]);
    }

    public function test_login_page_renders_pwa_manifest_and_meta_tags()
    {
        \Illuminate\Support\Facades\Auth::logout();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('<meta name="theme-color" content="#0F172A">', false);
        $response->assertSee('icon-192x192.png', false);
        $response->assertSee('pwa-login-install', false);
    }

    public function test_dashboard_page_renders_pwa_meta_tags_and_install_banner()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">', false);
        $response->assertSee('pwa-install-banner', false);
        $response->assertSee('window.installDriverPWA', false);
    }
}
