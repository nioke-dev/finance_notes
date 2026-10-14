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
        {{-- Kartu Panduan Penggunaan & Quick Action --}}
        <div class="bg-gradient-to-r from-emerald-950/70 via-slate-800 to-slate-800 border border-emerald-500/30 rounded-2xl p-6 shadow-xl">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 text-xs font-semibold">
                        <span>💡</span>
                        <span>Panduan Cepat Driver</span>
                    </div>
                    <h3 class="text-xl font-bold text-white">Selamat Datang di Super App Driver ShopeeFood & Keuangan Pribadi</h3>
                    <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                        Aplikasi ini mencatat mutasi dompet tunai/bank driver secara presisi Rp1 (double-entry ledger), auto rekonsiliasi pesanan COD vs tunai, pemisahan uang bensin/parkir, dan simulasi bonus ShopeeFood harian.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    <a href="/export/report/pdf" target="_blank" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition flex items-center space-x-2 shadow-lg shadow-rose-600/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Cetak Laporan PDF</span>
                    </a>
                    <a href="/export/transactions/csv" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 font-bold text-xs rounded-xl transition flex items-center space-x-2">
                        <span>📊 Download CSV</span>
                    </a>
                </div>
            </div>

            {{-- 4 Langkah Alur Harian Driver --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-700/60">
                <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5">
                    <div class="text-emerald-400 font-bold text-xs flex items-center space-x-1.5 mb-1">
                        <span class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-[10px]">1</span>
                        <span>Shift Pagi (Buka Hari)</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Driver membuka hari kerja dengan menginput modal awal fisik di dompet (Cash on Hand) sebelum mulai narik.
                    </p>
                </div>

                <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5">
                    <div class="text-amber-400 font-bold text-xs flex items-center space-x-1.5 mb-1">
                        <span class="w-5 h-5 rounded-full bg-amber-500/20 flex items-center justify-center text-[10px]">2</span>
                        <span>Di Jalan (Operasional)</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Catat pesanan COD (uang fisik bertambah), beli bensin, parkir Rp2.000, serta tips QRIS secara instan via aplikasi mobile.
                    </p>
                </div>

                <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5">
                    <div class="text-blue-400 font-bold text-xs flex items-center space-x-1.5 mb-1">
                        <span class="w-5 h-5 rounded-full bg-blue-500/20 flex items-center justify-center text-[10px]">3</span>
                        <span>Closing Malam (Audit)</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Hitung lembaran uang fisik di dompet, sistem mencocokkan selisih kas secara otomatis hingga Rp0.
                    </p>
                </div>

                <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5">
                    <div class="text-purple-400 font-bold text-xs flex items-center space-x-1.5 mb-1">
                        <span class="w-5 h-5 rounded-full bg-purple-500/20 flex items-center justify-center text-[10px]">4</span>
                        <span>Cairkan Bonus Shopee</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Insentif ShopeeFood otomatis dihitung berdasarkan poin & rating, lalu dikonfirmasi masuk ke ShopeePay / Bank driver.
                    </p>
                </div>
            </div>
        </div>

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
