<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MasterLayoutRenderingTest extends TestCase
{
    public function test_master_layout_renders_with_title_and_navigation(): void
    {
        $renderedHtml = Blade::render(
            '<x-layouts.app :title="$title">{{ $content }}</x-layouts.app>',
            [
                'title' => 'Test Dashboard Layout',
                'content' => '<div id="test-content">Konten Dashboard</div>',
            ]
        );

        $this->assertStringContainsString('DRIVER FINANCE', $renderedHtml);
        $this->assertStringContainsString('Test Dashboard Layout', $renderedHtml);
        $this->assertStringContainsString('Dashboard', $renderedHtml);
        $this->assertStringContainsString('Closing & Rekonsiliasi', $renderedHtml);
        $this->assertStringContainsString('Utang & PayLater', $renderedHtml);
        $this->assertStringContainsString('Analitik Driver', $renderedHtml);
        $this->assertStringContainsString('bg-slate-900', $renderedHtml);
        $this->assertStringContainsString('Konten Dashboard', $renderedHtml);
    }
}
