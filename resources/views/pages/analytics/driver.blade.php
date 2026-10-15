<x-layouts.app title="Analitik Performa Driver ShopeeFood">
    <div class="space-y-6">
        {{-- Header & Filter Periode --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Statistik Operasional Lapangan</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Performa Driver ShopeeFood Malang
                </h2>
            </div>
            <div class="flex items-center space-x-2 bg-slate-900/60 p-1.5 rounded-xl border border-slate-700/60 text-xs font-bold">
                <a 
                    href="/analytics/driver?days=7" 
                    class="px-3.5 py-1.5 rounded-lg transition {{ $days === 7 ? 'bg-emerald-600 text-white shadow' : 'text-slate-300 hover:text-white' }}"
                >
                    7 Hari
                </a>
                <a 
                    href="/analytics/driver?days=30" 
                    class="px-3.5 py-1.5 rounded-lg transition {{ $days === 30 ? 'bg-emerald-600 text-white shadow' : 'text-slate-300 hover:text-white' }}"
                >
                    30 Hari
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Order Selesai --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Total Order Selesai</span>
                <div class="flex items-baseline space-x-2 mt-2">
                    <span class="text-3xl font-extrabold text-white">{{ number_format($totalTrips, 0, ',', '.') }}</span>
                    <span class="text-xs font-semibold text-slate-300">trip</span>
                </div>
            </div>

            {{-- Total Pendapatan Bersih --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Total Pendapatan Shopee</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-emerald-400">Rp {{ number_format($totalShopeeIncome, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Hari Tembus Bonus --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Frekuensi Tembus Bonus</span>
                <div class="flex items-baseline space-x-2 mt-2">
                    <span class="text-3xl font-extrabold text-amber-400">{{ $bonusDaysCount }}</span>
                    <span class="text-xs font-semibold text-slate-300">hari gacor</span>
                </div>
            </div>

            {{-- Pendapatan per Jam Online --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Rata-rata per Jam Online</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-white">Rp {{ number_format($hourlyRate, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-300 font-medium block mt-0.5">/jam aktif</span>
                </div>
            </div>
        </div>

        {{-- Tabel Riwayat Harian Shift --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white border-b border-slate-700/60 pb-3">
                Rincian Shift On-Beat ({{ $days }} Hari Terakhir)
            </h3>
            @if($workDays->isEmpty())
                <div class="py-12 text-center text-slate-300 font-medium text-sm">
                    Belum ada rekaman shift kerja pada periode ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-300 font-bold uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Tanggal</th>
                                <th class="pb-3 font-semibold">Trip Selesai</th>
                                <th class="pb-3 font-semibold">Ongkir Hub</th>
                                <th class="pb-3 font-semibold">Bonus</th>
                                <th class="pb-3 font-semibold">Tip QRIS/Tunai</th>
                                <th class="pb-3 font-semibold text-right">Total Pendapatan</th>
                                <th class="pb-3 font-semibold text-center">Status Closing</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($workDays as $day)
                                @php
                                    $snap = $day->latestSnapshot;
                                    $trips = (int) ($day->total_orders ?? $snap?->completed_orders ?? 0);
                                    $hub = (int) ($snap?->hub_income ?? 0);
                                    $bonus = (int) ($snap?->hub_bonus ?? 0);
                                    $tip = (int) ($snap?->customer_tips ?? 0);
                                    $dayTotal = $hub + $bonus + $tip;
                                @endphp
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-bold text-white">
                                        {{ $day->work_date->translatedFormat('D, d M Y') }}
                                    </td>
                                    <td class="py-3 font-semibold text-slate-200">
                                        {{ $trips }} trip
                                    </td>
                                    <td class="py-3 text-slate-300">
                                        Rp {{ number_format($hub, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 font-bold {{ $bonus > 0 ? 'text-amber-400' : 'text-slate-400 font-medium' }}">
                                        Rp {{ number_format($bonus, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-emerald-400">
                                        Rp {{ number_format($tip, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-emerald-400 text-sm">
                                        Rp {{ number_format($dayTotal, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <x-status-badge :status="$day->status" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
