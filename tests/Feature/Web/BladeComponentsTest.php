<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BladeComponentsTest extends TestCase
{
    public function test_currency_input_component_renders_with_rupiah_prefix_and_name(): void
    {
        $renderedHtml = Blade::render(
            '<x-currency-input name="amount" label="Nominal Uang" value="50000" placeholder="10000" />'
        );

        $this->assertStringContainsString('Rp', $renderedHtml);
        $this->assertStringContainsString('name="amount"', $renderedHtml);
        $this->assertStringContainsString('id="amount"', $renderedHtml);
        $this->assertStringContainsString('value="50000"', $renderedHtml);
        $this->assertStringContainsString('Nominal Uang', $renderedHtml);
        $this->assertStringContainsString('placeholder="10000"', $renderedHtml);
    }

    public function test_status_badge_applies_correct_color_class_for_final_status(): void
    {
        $renderedHtml = Blade::render(
            '<x-status-badge status="final" />'
        );

        $this->assertStringContainsString('bg-emerald-500/10', $renderedHtml);
        $this->assertStringContainsString('text-emerald-400', $renderedHtml);
        $this->assertStringContainsString('FINAL (SEIMBANG)', $renderedHtml);
    }

    public function test_status_badge_applies_correct_color_class_for_needs_reconciliation_status(): void
    {
        $renderedHtml = Blade::render(
            '<x-status-badge status="needs_reconciliation" />'
        );

        $this->assertStringContainsString('bg-amber-500/10', $renderedHtml);
        $this->assertStringContainsString('text-amber-400', $renderedHtml);
        $this->assertStringContainsString('PERLU REKONSILIASI', $renderedHtml);
    }

    public function test_status_badge_applies_correct_color_class_for_shortage_status(): void
    {
        $renderedHtml = Blade::render(
            '<x-status-badge status="shortage" />'
        );

        $this->assertStringContainsString('bg-rose-500/10', $renderedHtml);
        $this->assertStringContainsString('text-rose-400', $renderedHtml);
        $this->assertStringContainsString('KURANG (SHORTAGE)', $renderedHtml);
    }
}
