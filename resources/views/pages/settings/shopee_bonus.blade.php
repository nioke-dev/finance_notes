<x-layouts.app title="Pengaturan Skema Bonus ShopeeFood">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Konfigurasi Insentif & Aturan Bonus Hub Malang</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Skema Bonus Bertingkat ShopeeFood
                </h2>
            </div>
            <a href="/dashboard" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                &larr; Ke Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif

        {{-- Info Box Aturan Khusus Malang --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400">Syarat Sah Kelayakan Bonus (Malang Hub):</h3>
            <ul class="text-xs text-slate-300 list-disc list-inside space-y-1">
                <li>Jam On-Beat Cut-off: Tepat pukul <strong>20.00 WIB</strong> (Trip setelah jam 20.00 tidak dihitung ke bonus harian).</li>
                <li>Minimum Jam Online: <strong>9.0 Jam</strong> akumulasi on-beat di hari kerja terkait.</li>
                <li>Tingkat Penyelesaian Pesanan: Minimum <strong>80%</strong>.</li>
            </ul>
        </div>

        {{-- Form Edit Tiering Bonus --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white border-b border-slate-700/60 pb-3 mb-4">
                Tingkatan Tiering & Nilai Bonus Per Order
            </h3>

            <form action="{{ route('settings.shopee-bonus.update') }}" method="POST" class="space-y-6">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-300 font-bold uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Tier Level</th>
                                <th class="pb-3 font-semibold">Min Order</th>
                                <th class="pb-3 font-semibold">Max Order</th>
                                <th class="pb-3 font-semibold">Rate Bonus / Order (Rp)</th>
                                <th class="pb-3 font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($schemes as $index => $scheme)
                                <tr>
                                    <td class="py-3 font-bold text-white">
                                        Tier {{ $scheme->tier_level }}
                                        <input type="hidden" name="schemes[{{ $index }}][id]" value="{{ $scheme->id }}">
                                    </td>
                                    <td class="py-3">
                                        <input type="number" name="schemes[{{ $index }}][min_orders]" value="{{ $scheme->min_orders }}" class="w-24 px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-semibold focus:outline-none focus:border-emerald-500">
                                    </td>
                                    <td class="py-3">
                                        <input type="number" name="schemes[{{ $index }}][max_orders]" value="{{ $scheme->max_orders }}" placeholder="&infin;" class="w-24 px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-semibold focus:outline-none focus:border-emerald-500">
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-slate-300 font-semibold">Rp</span>
                                            <input type="number" name="schemes[{{ $index }}][rate_per_order]" value="{{ $scheme->rate_per_order }}" class="w-32 px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-emerald-400 font-bold focus:outline-none focus:border-emerald-500">
                                        </div>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $scheme->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-300 font-bold' }}">
                                            {{ $scheme->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-700/60">
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition shadow">
                        Simpan Perubahan Skema
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
