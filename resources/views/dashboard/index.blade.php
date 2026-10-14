<x-layouts.app title="Dashboard Ringkasan Keuangan Driver">
    <div class="space-y-6">
        {{-- Banner Pengingat Hari Gantung --}}
        @if($unfinalizedDays->isNotEmpty())
            <div class="space-y-3">
                @foreach($unfinalizedDays as $unfinalized)
                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-amber-400 text-xl">⚠️</span>
                            <div>
                                <h4 class="text-sm font-bold text-amber-200">
                                    Hari Kerja Belum Difinalisasi: {{ $unfinalized->work_date->translatedFormat('d M Y') }}
                                </h4>
                                <p class="text-xs text-amber-300/80 mt-0.5">
                                    Ada shift narik lampau yang belum closing atau masih ada selisih audit kas.
                                </p>
                            </div>
                        </div>
                        <a href="/closing/{{ $unfinalized->id }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl transition shadow">
                            Investigasi Sekarang &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Ringkasan Total Kekayaan (Net Worth Card) --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Kekayaan Bersih (Net Worth)</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-1">
                        Rp {{ number_format($netWorth, 0, ',', '.') }}
                    </h2>
                </div>
                <div class="flex gap-4 text-xs font-semibold">
                    <div class="bg-slate-900/60 border border-slate-700/50 px-3.5 py-2 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase">Aset Liquid</span>
                        <span class="text-emerald-400 font-bold text-sm">Rp {{ number_format($totalDigital + $totalCash, 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-700/50 px-3.5 py-2 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase">Total Utang Aktif</span>
                        <span class="text-rose-400 font-bold text-sm">Rp {{ number_format($totalDebt, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid Kartu Akun Driver --}}
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">Daftar Akun Keuangan Aktif</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($accounts as $acc)
                    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-4 flex items-center justify-between shadow hover:border-slate-600 transition">
                        <div>
                            <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider block">
                                {{ $acc->name }}
                            </span>
                            <span class="text-lg font-bold text-white mt-1 block">
                                Rp {{ number_format($acc->current_balance, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-700/60 text-slate-300 uppercase">
                            {{ $acc->type->value ?? $acc->type }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
