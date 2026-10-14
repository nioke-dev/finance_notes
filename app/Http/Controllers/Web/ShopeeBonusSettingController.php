<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ShopeeBonusScheme;
use Illuminate\Http\Request;

class ShopeeBonusSettingController extends Controller
{
    public function index()
    {
        $schemes = ShopeeBonusScheme::orderBy('tier_level', 'asc')->get();
        return view('pages.settings.shopee_bonus', compact('schemes'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'schemes' => ['required', 'array'],
            'schemes.*.id' => ['required', 'exists:shopee_bonus_schemes,id'],
            'schemes.*.min_orders' => ['required', 'integer', 'min:0'],
            'schemes.*.max_orders' => ['nullable', 'integer', 'min:0'],
            'schemes.*.rate_per_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['schemes'] as $item) {
            $scheme = ShopeeBonusScheme::findOrFail($item['id']);
            $oldRate = $scheme->rate_per_order;

            $scheme->update([
                'min_orders' => $item['min_orders'],
                'max_orders' => $item['max_orders'] ?? null,
                'rate_per_order' => $item['rate_per_order'],
            ]);

            // Catat audit trail perubahan skema jika rate berubah
            if ($oldRate !== (int) $item['rate_per_order']) {
                AuditLog::create([
                    'action' => 'update_shopee_bonus_scheme',
                    'auditable_type' => ShopeeBonusScheme::class,
                    'auditable_id' => $scheme->id,
                    'reason' => "Update rate Tier {$scheme->tier_level} dari Rp{$oldRate} ke Rp{$item['rate_per_order']}",
                    'old_values' => ['rate_per_order' => $oldRate],
                    'new_values' => ['rate_per_order' => $item['rate_per_order']],
                    'created_at' => now(),
                ]);
            }
        }

        return redirect()->route('settings.shopee-bonus')->with('success', 'Skema bonus ShopeeFood berhasil diperbarui.');
    }
}
