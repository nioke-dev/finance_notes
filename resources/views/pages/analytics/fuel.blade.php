<x-layouts.app title="Analitik Pengeluaran Bensin & Kendaraan">
    <div class="space-y-6">
        {{-- Header & Insight --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Efisiensi Biaya Operasional Motor</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Analitik Pengeluaran Bensin (SPBU vs Madura)
                </h2>
            </div>
            <a href="/dashboard" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                &larr; Ke Dashboard
            </a>
        </div>

        {{-- Insight Warning jika eceran Madura terlalu sering --}}
        @if($isMaduraHigh)
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-center space-x-3 text-amber-300 text-xs font-medium">
                <span class="text-xl">💡</span>
                <div>
                    <strong class="font-bold text-amber-200 block text-sm">Rekomendasi Hemat Finansial:</strong>
                    Ketergantungan bensin eceran Madura kamu mencapai {{ $maduraRatio }}%! Usahakan isi full tank di SPBU Pertamina resmi (Rp10.000/L) sebelum jam on-beat agar menghemat selisih markup Rp2.000 per liter.
                </div>
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Pengeluaran Bensin --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Pengeluaran Bensin</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-white">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Total SPBU Resmi --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">SPBU Pertamina (Rp10k/L)</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-emerald-400">Rp {{ number_format($totalSpbuSpent, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-400 block mt-0.5">~{{ number_format($totalSpbuLiters, 2) }} Liter</span>
                </div>
            </div>

            {{-- Total Eceran Madura --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Eceran Madura (Rp12k)</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-amber-400">Rp {{ number_format($totalMaduraSpent, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-400 block mt-0.5">~{{ $totalMaduraBottles }} Botol</span>
                </div>
            </div>

            {{-- Rasio Ketergantungan Eceran --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Rasio Eceran Madura</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold {{ $isMaduraHigh ? 'text-rose-400' : 'text-emerald-400' }}">{{ $maduraRatio }}%</span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">dari total rupiah bensin</span>
                </div>
            </div>
        </div>

        {{-- Tabel Histori Pembelian Bensin --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white border-b border-slate-700/60 pb-3">
                Histori Pengisian Bensin
            </h3>
            @if($fuelTransactions->isEmpty())
                <div class="py-12 text-center text-slate-500 text-sm">
                    Belum ada riwayat pembelian bensin yang tercatat.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-400 uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Waktu</th>
                                <th class="pb-3 font-semibold">Tipe Vendor</th>
                                <th class="pb-3 font-semibold">Deskripsi</th>
                                <th class="pb-3 font-semibold text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($fuelTransactions as $tx)
                                @php
                                    $isSpbu = str_contains(strtolower($tx->description), 'spbu') || ($tx->metadata['vendor_type'] ?? '') === 'spbu';
                                @endphp
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-medium text-slate-300">
                                        {{ $tx->transaction_time->format('d M Y, H:i') }}
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $isSpbu ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20' }}">
                                            {{ $isSpbu ? 'SPBU Resmi' : 'Warung Madura' }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-200">
                                        {{ $tx->description }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-white text-sm">
                                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
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
