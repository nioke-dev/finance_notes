<x-layouts.app title="Dashboard Ringkasan Keuangan Driver">
    {{-- Driver.js CSS & JS untuk Interactive Tour --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>

    <div class="space-y-6">

        {{-- Flash Messages (Alert Sukses / Error) --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-base">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
                <span>⚠️ {{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        {{-- Banner Cerdas Operasional Dini Hari (00.00 - 05.00 WIB) --}}
        @if($isLateNightHours && $yesterdayUnfinalized)
            <div class="bg-indigo-950/60 border border-indigo-500/40 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg backdrop-blur">
                <div class="flex items-center space-x-3">
                    <span class="text-indigo-400 text-2xl">🌙</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-sm font-bold text-indigo-100">
                                Jam Operasional Dini Hari ({{ now()->format('H:i') }} WIB)
                            </h4>
                            <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-500/30">Mode Istirahat & Input</span>
                        </div>
                        <p class="text-xs text-indigo-200/80 mt-1">
                            Baru bangun atau istirahat? Anda bisa menginput closingan & rincian uang lembaran kemarin ({{ $yesterdayUnfinalized->work_date->translatedFormat('d M Y') }}), atau langsung buka pencatatan untuk hari ini.
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 w-full sm:w-auto shrink-0">
                    <a href="/closing/{{ $yesterdayUnfinalized->id }}" class="flex-1 sm:flex-initial text-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition shadow">
                        🔒 Investigasi Sekarang (Closing Kemarin)
                    </a>
                    @if(!$currentWorkDay || $currentWorkDay->is_finalized)
                        <button type="button" onclick="openModal('modal-open-day')" class="flex-1 sm:flex-initial text-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition shadow">
                            🌅 Buka Hari Ini
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Banner Pengingat Hari Gantung / Belum Finalisasi --}}
        @if($unfinalizedDays->isNotEmpty() && !($isLateNightHours && $yesterdayUnfinalized))
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
                                    Ada shift lampau yang belum closing atau masih ada selisih audit. Anda tetap bisa buka hari baru jika opname fisik kas sudah disimpan.
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

        {{-- Banner Panduan: Hari Kerja Belum Dibuka --}}
        @if(!$currentWorkDay)
            <div id="banner-workday-not-opened" class="bg-gradient-to-r from-emerald-950/70 via-slate-900 to-slate-800/95 border border-emerald-500/40 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center space-x-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-2xl shrink-0">
                        🌅
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-sm font-bold text-white">
                                Hari Kerja Belum Dibuka
                            </h4>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 rounded-md border border-emerald-500/30">
                                Siap On-Bid
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 font-medium mt-0.5">
                            Buka hari kerja untuk mulai mencatat target harian, kilometer odometer awal motor, dan transaksi driver on-bid.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-open-day')" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-950 transition active:scale-95 flex items-center justify-center space-x-1.5 shrink-0">
                    <span>🌅</span>
                    <span>Buka Hari Kerja Sekarang</span>
                </button>
            </div>
        @endif

        {{-- FILTER TANGGAL HARIAN & HEADER AKTIVITAS --}}
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">📅</span>
                <div>
                    <h3 class="text-sm font-bold text-white">Evaluasi & Filter Aktivitas Harian</h3>
                    <p class="text-xs text-slate-300 font-medium">Pilih tanggal untuk melihat perbandingan pendapatan driver vs keuangan pribadi hari tersebut.</p>
                </div>
            </div>
            <form method="GET" action="/dashboard" class="flex items-center space-x-2 w-full sm:w-auto">
                <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl">Filter</button>
            </form>
        </div>

        {{-- DUA KARTU KOMPARASI HARIAN: 1. OPERASIONAL DRIVER VS 2. KEUANGAN PRIBADI SEHARI-HARI --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- KARTU 1: KINERJA OPERASIONAL DRIVER SHOPEEFOOD (ON-BID) --}}
            <div id="tour-driver-summary" class="bg-gradient-to-br from-slate-900 via-slate-800/95 to-slate-900 border border-amber-500/40 rounded-2xl p-6 shadow-2xl space-y-5">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-4">
                    <div>
                        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-500/15 text-amber-300 border border-amber-500/30 text-xs font-extrabold mb-1.5">
                            <span>🛵</span>
                            <span>Operasional Driver ShopeeFood Hari Ini</span>
                        </div>
                        <h3 class="text-3xl font-black text-white tracking-tight">
                            Rp {{ number_format($dayNetProfit, 0, ',', '.') }}
                        </h3>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 mt-1 block">
                            Profit Bersih Narik (Netto)
                        </span>
                    </div>
                    
                    {{-- Aksi Cepat On-Beat: Update Saldo di Jalan (Bukan Closing) --}}
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <button type="button" onclick="openModal('modal-reconcile-digital')" class="w-full sm:w-auto px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs rounded-xl transition shadow flex items-center justify-center space-x-2" title="Update saldo aktual saat nunggu resto tanpa menutup shift">
                            <span class="w-5 h-5 rounded-full bg-white shadow-md flex items-center justify-center shrink-0 border border-white">
                                <svg class="w-3.5 h-3.5 text-amber-600 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>Cek & Update Saldo Narik</span>
                        </button>
                    </div>
                </div>

                {{-- Grid 4 Metrik Driver (High OLED Contrast) --}}
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-3.5 space-y-1">
                        <span class="text-slate-300 text-[11px] uppercase font-bold block">Tagihan COD / Ongkir</span>
                        <span class="text-base font-extrabold text-white block">Rp {{ number_format($dayRegularIncome, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">{{ $dayOrdersCount }} pesanan diselesaikan</span>
                    </div>

                    <div class="bg-slate-900/90 border border-emerald-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-emerald-300 text-[11px] uppercase font-bold block">Tip Bersih Pelanggan</span>
                        <span class="text-base font-extrabold text-emerald-400 block">+Rp {{ number_format($dayTipIncome, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">Surplus kembalian & QRIS</span>
                    </div>

                    <div class="bg-slate-900/90 border border-rose-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-rose-300 text-[11px] uppercase font-bold block">Beban Bensin (BBM)</span>
                        <span class="text-base font-extrabold text-rose-400 block">-Rp {{ number_format($dayFuelExpense, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">Operasional motor jalan</span>
                    </div>

                    <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-amber-300 text-[11px] uppercase font-bold block">Parkir Motor Driver</span>
                        <span class="text-base font-extrabold text-amber-400 block">-Rp {{ number_format($dayParkingExpense, 0, ',', '.') }}</span>
                        <span class="text-xs text-emerald-300 font-bold block">✓ {{ $dayRestoPaidParkingCount }} gratis / ditanggung resto</span>
                    </div>
                </div>
            </div>

            {{-- KARTU 2: KEUANGAN PRIBADI & KEBUTUHAN SEHARI-HARI --}}
            <div id="tour-personal-summary" class="bg-gradient-to-br from-slate-900 via-slate-800/95 to-slate-900 border border-indigo-500/40 rounded-2xl p-6 shadow-2xl space-y-5">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-4">
                    <div>
                        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 text-xs font-extrabold mb-1.5">
                            <span>☕</span>
                            <span>Kebutuhan Hidup & Finansial Pribadi</span>
                        </div>
                        <h3 class="text-3xl font-black text-white tracking-tight">
                            Rp {{ number_format($dayPersonalIncome - $dayPersonalExpense - $dayDebtRepayment, 0, ',', '.') }}
                        </h3>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-300 mt-1 block">
                            Arus Kas Pribadi Hari Ini (Di Luar Driver)
                        </span>
                    </div>
                    <button type="button" onclick="startDriverTour()" class="w-full sm:w-auto px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs rounded-xl transition flex items-center justify-center space-x-1.5 shadow">
                        <span>✨</span>
                        <span>Panduan Aplikasi</span>
                    </button>
                </div>

                {{-- Grid 3 Metrik Pribadi (High OLED Contrast) --}}
                <div class="grid grid-cols-3 gap-3 text-xs">
                    <div class="bg-slate-900/90 border border-emerald-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-emerald-300 text-[11px] uppercase font-bold block">Pemasukan Pribadi</span>
                        <span class="text-sm font-extrabold text-emerald-400 block">+Rp {{ number_format($dayPersonalIncome, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">Freelance / jualan</span>
                    </div>

                    <div class="bg-slate-900/90 border border-rose-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-rose-300 text-[11px] uppercase font-bold block">Makan & Belanja</span>
                        <span class="text-sm font-extrabold text-rose-400 block">-Rp {{ number_format($dayPersonalExpense, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">Sarapan, kos, jajan</span>
                    </div>

                    <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-3.5 space-y-1">
                        <span class="text-amber-300 text-[11px] uppercase font-bold block">Bayar Cicilan</span>
                        <span class="text-sm font-extrabold text-amber-400 block">-Rp {{ number_format($dayDebtRepayment, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-300 font-medium block">SPayLater / pinjaman</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- TAB NAVIGASI: PEMISAHAN ANTARA OPERASIONAL DRIVER (ON-BID) VS KEBUTUHAN SEHARI-HARI --}}
        <div>
            <div class="flex border-b border-slate-700/80 space-x-2">
                <button type="button" onclick="switchTab('tab-onbeat')" id="btn-tab-onbeat" class="tab-button px-5 py-3 text-xs font-bold border-b-2 border-emerald-500 text-emerald-400 flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>🛵 Mode On-Bid: Operasional Narik ShopeeFood</span>
                </button>
                <button type="button" onclick="switchTab('tab-daily')" id="btn-tab-daily" class="tab-button px-5 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-300 hover:text-white font-medium flex items-center space-x-2">
                    <span>☕</span>
                    <span>Kebutuhan Sehari-hari (Di Luar On-Bid)</span>
                </button>
            </div>

            {{-- 1. TAB KONTEN ON-BID DRIVER --}}
            <div id="tab-onbeat" class="tab-content py-4 space-y-6">
                {{-- Shift Bar & Status Narik --}}
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-xl">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-2xl">
                            🛵
                        </div>
                        <div>
                            <div class="flex items-center space-x-2.5">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-200">Status Shift Hari Ini</span>
                                @if($currentWorkDay)
                                    <x-status-badge :status="$currentWorkDay->status" />
                                @else
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-700 text-slate-200 font-bold">Belum Ada Shift</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-300 font-medium block mt-0.5">
                                @if($currentWorkDay)
                                    Shift {{ $currentWorkDay->work_date->translatedFormat('d M Y') }} &bull; Dibuka {{ $currentWorkDay->opened_at ? $currentWorkDay->opened_at->format('H:i') : '-' }} WIB
                                @else
                                    Buka shift narik terlebih dahulu untuk mulai menghitung uang modal kas kembalian di jalan.
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
                        <button type="button" onclick="openModal('modal-reconcile-digital')" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded-xl shadow flex items-center justify-center space-x-1.5 transition" title="Update saldo di jalan tanpa menutup hari">
                            <span>⚡</span>
                            <span>Update Saldo Narik</span>
                        </button>
                        @if($currentWorkDay && $currentWorkDay->needsReconciliation())
                            <a href="/closing/{{ $currentWorkDay->id }}" id="tour-closing-btn" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-black rounded-xl shadow flex items-center justify-center space-x-1.5 transition text-center" title="Lakukan di kos/kamar sebelum tidur">
                                <span>🌙</span>
                                <span>Finalisasi Hari (Closing di Kos)</span>
                            </a>
                        @endif
                        <button type="button" onclick="openModal('modal-open-day')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow flex items-center justify-center space-x-1.5 transition">
                            <span>🌅</span>
                            <span>{{ $currentWorkDay ? 'Update / Buka Shift' : 'Buka Shift Narik' }}</span>
                        </button>
                    </div>
                </div>

                {{-- WIDGET 1: 4 ELEMEN SALDO UANG NARIK DRIVER (LIKUID RIIL) --}}
                <div id="tour-driver-wallet" class="bg-gradient-to-br from-slate-900 via-slate-800/95 to-slate-900 border border-amber-500/40 rounded-2xl p-6 shadow-2xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-xl">
                                💳
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">4 Elemen Saldo Uang Narik Driver</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        Saldo Likuid Riil
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Total uang riil siap pakai yang tersebar di dompet saku & e-wallet narik Anda.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 w-full sm:w-auto justify-between sm:justify-end">
                            <div class="text-left sm:text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-300 block">Total Uang Narik Siap Pakai</span>
                                <span class="text-2xl font-black text-amber-400">Rp {{ number_format($driverTotalBalance, 0, ',', '.') }}</span>
                            </div>
                            <button type="button" onclick="openModal('modal-reconcile-digital')" class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl transition shadow flex items-center space-x-1.5 shrink-0" title="Buka form update saldo kilat">
                                <span>⚡</span>
                                <span>Update di Jalan</span>
                            </button>
                        </div>
                    </div>

                    {{-- Grid 4 Elemen Saldo Riil Driver --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($driverWalletElements as $key => $elem)
                            <div class="bg-slate-900/90 border border-slate-700/80 hover:border-slate-500 rounded-xl p-4 flex flex-col justify-between space-y-2.5">
                                <div>
                                    <div class="flex justify-between items-start mb-1.5">
                                        <span class="text-2xl">{{ $elem['icon'] }}</span>
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase border {{ $elem['badge_color'] }}">
                                            {{ $elem['badge'] }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-bold text-white block">{{ $elem['name'] }}</span>
                                    <span class="text-[11px] text-slate-300 leading-tight block mt-0.5">{{ $elem['desc'] }}</span>
                                </div>
                                <div class="pt-2.5 border-t border-slate-800">
                                    <span class="text-[10px] text-slate-300 block font-semibold">Saldo Terkini:</span>
                                    <span class="text-lg font-black {{ $elem['balance'] < 10000 ? 'text-amber-400' : 'text-slate-100' }}">
                                        Rp {{ number_format($elem['balance'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- WIDGET 2: REKAPITULASI RIWAYAT SHOPEEFOOD DRIVER HARI INI (BUKAN DOMPET UANG) --}}
                <div class="bg-gradient-to-br from-slate-900 via-orange-950/20 to-slate-900 border border-orange-500/40 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-500/20 border border-orange-500/40 flex items-center justify-center text-xl">
                                🛵
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">Riwayat ShopeeFood Driver Hari Ini</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-orange-500/20 text-orange-300 border border-orange-500/30">
                                        Data Riwayat Performa (Bukan Dompet)
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Rekapitulasi data trip & pendapatan dari layar aplikasi driver (uangnya otomatis langsung masuk ke ShopeePay).
                                </p>
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-300 block">Total Pendapatan Narik</span>
                            <span class="text-2xl font-black text-emerald-400">Rp {{ number_format($shopeeReportData['total_income'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Grid 4 Metrik Riwayat Driver --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 text-xs">
                        <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-3 space-y-0.5">
                            <span class="text-slate-300 text-[10px] uppercase font-bold block">Pesanan Selesai</span>
                            <span class="text-base font-black text-white block">{{ $shopeeReportData['total_orders'] }} trip</span>
                            <span class="text-[10px] text-slate-300 block">Hari ini</span>
                        </div>

                        <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-3 space-y-0.5">
                            <span class="text-slate-300 text-[10px] uppercase font-bold block">Ongkir / Tagihan COD</span>
                            <span class="text-base font-black text-white block">Rp {{ number_format($shopeeReportData['regular_income'], 0, ',', '.') }}</span>
                            <span class="text-[10px] text-slate-300 block">Tarif trip pesanan</span>
                        </div>

                        <div class="bg-slate-900/90 border border-emerald-500/30 rounded-xl p-3 space-y-0.5">
                            <span class="text-emerald-300 text-[10px] uppercase font-bold block">Tip Pelanggan (Shopee)</span>
                            <span class="text-base font-black text-emerald-400 block">+Rp {{ number_format($shopeeReportData['tip_income'], 0, ',', '.') }}</span>
                            <span class="text-[10px] text-slate-300 block">Ikhlas dari customer</span>
                        </div>

                        <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-3 space-y-0.5">
                            <span class="text-amber-300 text-[10px] uppercase font-bold block">Bonus / Insentif Hub</span>
                            <span class="text-base font-black text-amber-400 block">+Rp {{ number_format($shopeeReportData['bonus_income'], 0, ',', '.') }}</span>
                            <span class="text-[10px] text-slate-300 block">Performa harian</span>
                        </div>
                    </div>

                    <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 flex items-start space-x-2.5">
                        <span class="text-base text-amber-400 mt-0.5">💡</span>
                        <div class="leading-relaxed">
                            <strong class="text-white">Alur Saldo Real-Time:</strong> Seluruh gaji, ongkir, dan tip dari rekap ShopeeFood Driver di atas <strong>otomatis langsung masuk detik itu juga ke saldo ShopeePay Anda</strong> (tidak ada uang yang tertahan di aplikasi driver). Panel ini hadir khusus untuk mempermudah tracking pendapatan bersih hari ini tanpa harus pusing membedah riwayat mutasi campur aduk di aplikasi ShopeePay.
                        </div>
                    </div>
                </div>

                {{-- WIDGET CHECKPOINT JAM NARIK & KOMPARASI SALDO HARI INI --}}
                @if(isset($driverSnapshots) && $driverSnapshots->isNotEmpty())
                    <div class="bg-gradient-to-br from-slate-900 via-purple-950/20 to-slate-900 border border-purple-500/40 rounded-2xl p-6 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/40 flex items-center justify-center text-xl">
                                    ⏱️
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">Riwayat Checkpoint Jam Narik & Komparasi Saldo</h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                            {{ $driverSnapshots->count() }} Checkpoint Tercatat
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-300 mt-0.5">
                                        Rekam jejak komparasi pendapatan ShopeeFood Driver vs isi kantong & wadah di jalan.
                                    </p>
                                </div>
                            </div>

                            <button type="button" onclick="openModal('modal-reconcile-digital')" class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl transition shadow flex items-center space-x-1.5 shrink-0">
                                <span>⚡</span>
                                <span>Update Jam Sekarang</span>
                            </button>
                        </div>

                        <div class="divide-y divide-slate-700/50">
                            @foreach($driverSnapshots as $snap)
                                @php
                                    $shopeeTotal = $snap->hub_income + $snap->hub_bonus + $snap->customer_tips;
                                    $walletTotal = $snap->shopeepay_balance + $snap->gopay_balance + $snap->gopay_merchant_balance + $snap->cash_balance;
                                    $diff = $walletTotal - $shopeeTotal;
                                @endphp
                                <div class="py-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2.5 text-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                            <span class="px-2.5 py-0.5 rounded-md bg-purple-500/20 text-purple-300 border border-purple-500/30 font-black text-xs">
                                                🕒 Jam {{ $snap->recorded_at->format('H:i') }} WIB
                                            </span>
                                            <span class="text-white font-bold">
                                                {{ $snap->completed_orders }} Pesanan Selesai
                                            </span>
                                            @if($snap->notes)
                                                <span class="text-slate-400 italic text-[11px]">&bull; "{{ $snap->notes }}"</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-3 text-[11px] text-slate-300 flex-wrap gap-y-1">
                                            <span>🛵 ShopeeFood: <strong class="text-orange-400">Rp {{ number_format($shopeeTotal, 0, ',', '.') }}</strong></span>
                                            <span>💼 Total Wadah: <strong class="text-emerald-400">Rp {{ number_format($walletTotal, 0, ',', '.') }}</strong></span>
                                            <span class="text-[10px] text-slate-400">(ShopeePay: {{ number_format($snap->shopeepay_balance, 0, ',', '.') }} &bull; Kas: {{ number_format($snap->cash_balance, 0, ',', '.') }} &bull; QRIS: {{ number_format($snap->gopay_merchant_balance, 0, ',', '.') }})</span>
                                        </div>
                                    </div>
                                    <div>
                                        @if($diff > 0)
                                            <span class="px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-extrabold text-xs inline-flex items-center space-x-1">
                                                <span>🎉</span>
                                                <span>Surplus +Rp {{ number_format($diff, 0, ',', '.') }} (Tip Bersih)</span>
                                            </span>
                                        @elseif($diff < 0)
                                            <span class="px-3 py-1.5 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/40 font-extrabold text-xs inline-flex items-center space-x-1">
                                                <span>⚠️</span>
                                                <span>Selisih -Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                                            </span>
                                        @else
                                            <span class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-200 border border-slate-700 font-bold text-xs inline-flex items-center space-x-1">
                                                <span>✓</span>
                                                <span>Cocok Sempurna (Rp 0)</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Alert Rekomendasi Beban Lembaran / Koin Berlebih & Warning Kembalian --}}
                @if(!empty($cashRecommendations))
                    <div class="space-y-2.5">
                        @foreach($cashRecommendations as $rec)
                            @php
                                $cardClass = match($rec['type'] ?? '') {
                                    'deposit_atm' => 'bg-cyan-500/10 border-cyan-500/30 text-cyan-300',
                                    'deposit_retail' => 'bg-indigo-500/10 border-indigo-500/30 text-indigo-300',
                                    'excess' => 'bg-amber-500/10 border-amber-500/30 text-amber-300',
                                    default => 'bg-rose-500/10 border-rose-500/30 text-rose-300',
                                };
                                $icon = match($rec['type'] ?? '') {
                                    'deposit_atm' => '🏧',
                                    'deposit_retail' => '🏪',
                                    'excess' => '⚠️',
                                    default => '💡',
                                };
                                $title = match($rec['type'] ?? '') {
                                    'deposit_atm' => 'Rekomendasi Setor Tunai ATM (Rp50k/Rp100k)',
                                    'deposit_retail' => 'Rekomendasi Top Up ShopeePay (Indomaret/Alfamart)',
                                    'excess' => 'Beban Lembaran/Koin Berlebih di Dompet',
                                    default => 'Peringatan Pecahan Kembalian COD Menipis',
                                };
                            @endphp
                            <div class="p-4 rounded-2xl border text-xs flex items-start space-x-3 shadow-lg {{ $cardClass }}">
                                <span class="text-2xl mt-0.5">{{ $icon }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-0.5">
                                        <span class="font-extrabold uppercase tracking-wider text-[11px]">
                                            {{ $title }}
                                        </span>
                                        <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-slate-900/60 border border-slate-700 text-slate-300">
                                            {{ $rec['count'] }} pcs
                                        </span>
                                    </div>
                                    <p class="text-slate-200 leading-relaxed font-medium">{{ $rec['message'] }}</p>
                                    @if(in_array($rec['type'] ?? '', ['deposit_atm', 'deposit_retail']))
                                        <div class="mt-2.5">
                                            <button type="button" onclick="openModal('modal-transfer')" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-xl text-white font-bold text-xs inline-flex items-center space-x-1 transition shadow">
                                                <span>{{ $rec['type'] === 'deposit_atm' ? '🏧 Catat Setor Tunai ATM' : '🏪 Catat Top Up ShopeePay' }} &rarr;</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Ringkasan Pecahan Uang Fisik di Dompet (Denomination Card) --}}
                @if($cashDenomination)
                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-base">👛</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Rincian Fisik Kas di Dompet (Hasil Closing Terakhir)</h4>
                                </div>
                                <span class="text-[11px] text-slate-300 block mt-0.5">
                                    Shift: <strong class="text-white">{{ $cashDenomination->workDay?->work_date ? $cashDenomination->workDay->work_date->translatedFormat('l, d F Y') : 'Terakhir' }}</strong> &bull; Diperbarui setiap malam closingan
                                </span>
                            </div>
                            <div class="text-left sm:text-right bg-slate-900/80 border border-slate-700/60 rounded-xl px-3 py-1.5">
                                <span class="text-xs text-slate-300 font-medium">Total Fisik:</span>
                                <span class="text-sm font-extrabold text-white ml-1">Rp {{ number_format($cashDenomination->total_physical_amount, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-slate-300 block sm:inline">({{ $cashDenomination->total_pieces }} lembar & koin)</span>
                            </div>
                        </div>

                        {{-- Uang Kertas --}}
                        <div class="mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block mb-1.5">Uang Kertas (Lembaran):</span>
                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                @foreach([100000 => '100k', 50000 => '50k', 20000 => '20k', 10000 => '10k', 5000 => '5k', 2000 => '2k', 1000 => '1k'] as $nom => $label)
                                    @php $field = 'bill_' . $nom; $count = $cashDenomination->$field ?? 0; @endphp
                                    <div class="bg-slate-900/80 border {{ $nom === 5000 ? 'border-amber-500/60 bg-amber-500/10' : ($count >= 20 ? 'border-amber-500/50 bg-amber-500/5' : 'border-slate-700/60') }} rounded-xl p-2 text-center">
                                        <span class="text-[10px] {{ $nom === 5000 ? 'text-amber-300 font-bold' : 'text-slate-300' }} block font-semibold">Rp{{ $label }}</span>
                                        <span class="text-xs font-extrabold {{ $nom === 5000 ? 'text-amber-400' : ($count >= 20 ? 'text-amber-400' : 'text-white') }}">{{ $count }} <span class="text-[9px] font-medium text-slate-300">lbr</span></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Uang Logam --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block mb-1.5">Uang Logam / Koin:</span>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach([1000 => '1.000', 500 => '500', 200 => '200', 100 => '100'] as $nom => $label)
                                    @php $field = 'coin_' . $nom; $count = $cashDenomination->$field ?? 0; @endphp
                                    <div class="bg-slate-900/80 border {{ $count >= 15 ? 'border-amber-500/50 bg-amber-500/5' : 'border-slate-700/60' }} rounded-xl p-2 text-center">
                                        <span class="text-[10px] text-slate-300 block font-semibold">Rp{{ $label }}</span>
                                        <span class="text-xs font-extrabold {{ $count >= 15 ? 'text-amber-400' : 'text-white' }}">{{ $count }} <span class="text-[9px] font-medium text-slate-300">koin</span></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl text-center">
                        <span class="text-3xl block mb-2">👛</span>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-1">Belum Ada Rekaman Fisik Pecahan Kas</h4>
                        <p class="text-xs text-slate-300 max-w-md mx-auto">Jumlah lembar Rp5.000, Rp2.000, dst. akan otomatis muncul di sini setelah Anda melakukan <strong>Buka Shift</strong> atau <strong>Closing Kas Malam Hari</strong>.</p>
                    </div>
                @endif

                {{-- Card Pesanan COD Aktif di Jalan (Multi-Order On-Delivery) --}}
                @if(isset($activeCodOrders) && $activeCodOrders->isNotEmpty())
                    <div class="bg-gradient-to-r from-emerald-950/80 via-slate-900/90 to-slate-900 border-2 border-emerald-500/60 rounded-2xl p-5 shadow-2xl space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-emerald-500/30 pb-3">
                            <div class="flex items-center space-x-2.5">
                                <span class="text-2xl animate-bounce">🛵</span>
                                <div>
                                    <h3 class="text-sm font-extrabold text-white flex items-center space-x-2">
                                        <span>Pesanan COD Sedang Diantar</span>
                                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 rounded-full text-xs font-black">
                                            {{ $activeCodOrders->count() }} Order Aktif
                                        </span>
                                    </h3>
                                    <p class="text-[11px] text-slate-300">Bawa dobel/tiga order? Selesaikan satu per satu dengan kalkulator kilat saat tiba di customer.</p>
                                </div>
                            </div>
                            <button type="button" onclick="openAcceptCodModal()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition shadow flex items-center space-x-1.5 self-end sm:self-auto">
                                <span>+</span>
                                <span>Tambah COD Lagi (Double COD)</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                            @foreach($activeCodOrders as $cod)
                                <div class="bg-slate-900/90 border border-slate-700/80 hover:border-emerald-500/60 rounded-xl p-4 space-y-3 transition group">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 font-black text-sm rounded-lg tracking-wider">
                                                #{{ $cod->order_code }}
                                            </span>
                                            <div>
                                                <h4 class="text-xs font-bold text-white group-hover:text-emerald-300 transition">
                                                    {{ $cod->merchant_name ?: 'Resto ShopeeFood' }}
                                                </h4>
                                                <span class="text-[10px] text-slate-300 font-medium">
                                                    Diterima: {{ $cod->accepted_at ? $cod->accepted_at->format('H:i') : '' }}
                                                </span>
                                            </div>
                                        </div>
                                        <button 
                                            type="button" 
                                            onclick="openCancelCodModal('{{ $cod->id }}', '{{ $cod->order_code }}')" 
                                            class="text-rose-400/90 hover:text-rose-300 text-xs font-bold p-1 rounded transition" 
                                            title="Batalkan Pesanan (Resto Tutup/Dibatalkan)"
                                        >
                                            ✕ Batal
                                        </button>
                                    </div>

                                    <div class="p-2.5 bg-slate-950/60 rounded-lg border border-slate-800 flex justify-between items-center text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-300 font-medium block">Tagihan Asli Shopee:</span>
                                            <span class="font-bold text-slate-200">Rp {{ number_format($cod->bill_amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[10px] text-amber-400/90 font-semibold block">Saran Bayar (Bulat):</span>
                                            <span class="font-black text-amber-400 text-sm">Rp {{ number_format($cod->rounded_bill_amount, 0, ',', '.') }}</span>
                                        </div>
                                    </div>

                                    <button 
                                        type="button" 
                                        onclick="openCompleteCodModal('{{ $cod->id }}', '{{ $cod->order_code }}', '{{ addslashes($cod->merchant_name ?? '') }}', '{{ $cod->bill_amount }}', '{{ $cod->rounded_bill_amount }}')"
                                        class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold rounded-xl transition shadow flex items-center justify-center space-x-2"
                                    >
                                        <span>🧮</span>
                                        <span>Buka Kalkulator & Selesaikan (#{{ $cod->order_code }})</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Aksi Operasional Narik</h4>
                        <span class="text-[11px] text-slate-300 font-medium">Aksi cepat saat sedang di jalan</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-3.5">
                        <button type="button" onclick="openModal('modal-open-day')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🌅</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Buka Hari Baru</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Start Day</span>
                            </div>
                        </button>

                        <button type="button" onclick="openAcceptCodModal()" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">💵</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Order COD Baru</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Talangi ShopeePay</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-fuel')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">⛽</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Beli Bensin</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">SPBU / Madura</span>
                            </div>
                        </button>

                        <button type="button" onclick="openParkingModal()" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🅿️</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Parkir Kendaraan</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Motor / Mobil</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-field-incidental')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🛠️</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Biaya Lapangan</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Tambal ban / baut</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-lend-friend')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🤝</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Kas Dipinjam</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Talangan Teman</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-cash-withdraw')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-emerald-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🏧</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Tarik Tunai</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Bank &rarr; Dompet</span>
                            </div>
                        </button>

                        <button type="button" onclick="openVoiceRecorderModal()" class="p-3.5 bg-gradient-to-br from-rose-950/60 to-slate-900 hover:from-rose-900/70 hover:to-slate-800 border border-rose-500/50 hover:border-rose-400 rounded-xl text-left transition group shadow-lg flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🎙️</span>
                            <div>
                                <span class="text-xs font-bold text-rose-300 block group-hover:text-rose-200">Intercom Helm</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Suara + GPS</span>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- WIDGET KOTAK HITAM & MEMO SUARA HELM HARI INI --}}
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-700/80 pb-3">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-rose-400 font-bold text-base shrink-0">
                                🎙️
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-white">Kotak Hitam & Memo Suara Helm Hari Ini</h4>
                                <span class="text-[11px] text-slate-300 block mt-0.5">Bukti suara transaksi cepat di jalan & investigasi selisih closing malam</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-700 text-slate-300 font-bold">
                                {{ $todayVoiceMemos->count() }} Rekaman
                            </span>
                            <button type="button" onclick="openVoiceRecorderModal()" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition shadow flex items-center space-x-1 shrink-0">
                                <span>+ Rekam Memo</span>
                            </button>
                        </div>
                    </div>

                    @if($todayVoiceMemos->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($todayVoiceMemos as $memo)
                                <x-voice-memo-player :memo="$memo" />
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-slate-900/60 rounded-xl border border-slate-700/50">
                            <span class="text-2xl block mb-1">🎧</span>
                            <span class="text-xs font-semibold text-slate-200 block">Belum ada rekaman suara helm untuk shift ini.</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Tekan tombol mikrofon di kanan bawah saat di lampu merah/menunggu resto untuk merekam cepat.</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. TAB KONTEN KEBUTUHAN SEHARI-HARI LENGKAP --}}
            <div id="tab-daily" class="tab-content py-4 space-y-4 hidden">
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Kebutuhan Hidup & Finansial Pribadi Layaknya Manusia</h4>
                        <span class="text-[11px] text-slate-300 font-medium">Belanja, sarapan, belanja Shopee SPayLater, pinjaman, transfer</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-3.5">
                        <button type="button" onclick="openModal('modal-expense')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🍜</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Makan & Belanja</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Sarapan, jajan, rokok</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-personal-income')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">💰</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Pemasukan Luar</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Jual barang / project</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-field-incidental')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🛠️</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Biaya Serba-serbi</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Insidental / darurat</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-spaylater-purchase')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🛍️</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Belanja PayLater</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">SPayLater / TikTok</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-apply-loan')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🏦</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Pinjam Uang</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">SeaBank / SPinjam</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-pay-installment')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">💳</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Bayar Cicilan</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Tutup tagihan</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-transfer')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🔄</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Transfer Saldo</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Pindah rekening / ATM</span>
                            </div>
                        </button>

                        <button type="button" onclick="openModal('modal-lend-friend')" class="p-3.5 bg-slate-900/80 hover:bg-slate-700/70 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl text-left transition group flex flex-col justify-between min-h-[96px]">
                            <span class="text-2xl block mb-1">🤝</span>
                            <div>
                                <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Kas Dipinjam</span>
                                <span class="text-[10px] text-slate-300 font-medium leading-tight block mt-0.5">Talangan Teman</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan Total Kekayaan & Safe-to-Spend (Uang Aman Jajan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Kartu Net Worth --}}
            <div id="tour-net-worth" class="bg-gradient-to-br from-slate-900 via-slate-800/95 to-slate-900 border border-slate-700/90 rounded-2xl p-6 shadow-2xl relative overflow-hidden flex flex-col justify-between space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-300">Total Kekayaan Bersih (Net Worth)</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white mt-1 tracking-tight">
                            Rp {{ number_format($netWorth, 0, ',', '.') }}
                        </h2>
                    </div>
                    <div class="flex gap-3 text-xs font-bold">
                        <div class="bg-slate-900/90 border border-slate-700 px-3.5 py-2.5 rounded-xl">
                            <span class="text-slate-300 block text-[10px] uppercase font-bold">Aset Liquid (Kas + Bank)</span>
                            <span class="text-emerald-400 font-black text-sm">Rp {{ number_format($totalDigital + $totalCash, 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-slate-900/90 border border-slate-700 px-3.5 py-2.5 rounded-xl">
                            <span class="text-slate-300 block text-[10px] uppercase font-bold">Total Utang Aktif</span>
                            <span class="text-rose-400 font-black text-sm">Rp {{ number_format($totalDebt, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-700/70 flex items-center justify-between text-xs text-slate-300">
                    <span>Semua kas fisik dompet + seluruh rekening bank & saldo e-wallet</span>
                    <span class="text-slate-300 font-semibold text-[11px]">&bull; Diaudit per mutasi</span>
                </div>
            </div>

            {{-- Kartu Safe-to-Spend (Uang Aman Jajan Driver) --}}
            <div id="tour-safe-to-spend" class="bg-gradient-to-br from-slate-900 via-slate-800/95 to-cyan-950/40 border border-cyan-500/50 rounded-2xl p-6 shadow-2xl flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-black uppercase tracking-wider text-cyan-300 flex items-center space-x-1.5">
                            <span>🛡️</span>
                            <span>Safe-to-Spend (Uang Aman Jajan)</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-cyan-500/20 text-cyan-300 border border-cyan-500/40">
                            Anti Defisit Survival
                        </span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black text-white mt-1 tracking-tight">
                        Rp {{ number_format($safeToSpendData['safe_to_spend'], 0, ',', '.') }}
                    </h2>
                    <p class="text-xs text-slate-200 leading-relaxed mt-1">
                        Uang yang 100% bebas Anda belanjakan untuk jajan es krim, cilok, atau makan enak <strong class="text-cyan-300">tanpa mengorbankan beras pokok, uang sabun kos, atau jadwal ganti oli motor</strong>.
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 pt-3 border-t border-slate-700/70 text-xs">
                    <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-2.5 space-y-0.5">
                        <span class="text-slate-300 block text-[10px] uppercase font-bold">Uang Likuid</span>
                        <span class="text-slate-100 font-extrabold block">Rp {{ number_format($safeToSpendData['total_liquid'], 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/90 border border-rose-500/30 rounded-xl p-2.5 space-y-0.5">
                        <span class="text-rose-300 block text-[10px] uppercase font-bold">Survival (L1-3)</span>
                        <span class="text-rose-400 font-extrabold block">-Rp {{ number_format($safeToSpendData['total_survival_reserved'], 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-2.5 space-y-0.5">
                        <span class="text-amber-300 block text-[10px] uppercase font-bold">Wishlist (L4-5)</span>
                        <span class="text-amber-400 font-extrabold block">-Rp {{ number_format($safeToSpendData['total_wants_reserved'], 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/90 border border-purple-500/30 rounded-xl p-2.5 space-y-0.5">
                        <span class="text-purple-300 block text-[10px] uppercase font-bold">Hutang Bln Ini</span>
                        <span class="text-purple-400 font-extrabold block">-Rp {{ number_format($safeToSpendData['debt_reserved'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/90 border border-indigo-500/30 rounded-xl p-2.5 space-y-0.5 col-span-2 sm:col-span-1">
                        <span class="text-indigo-300 block text-[10px] uppercase font-bold">Tagihan Rutin</span>
                        <span class="text-indigo-400 font-extrabold block">-Rp {{ number_format($safeToSpendData['subscription_reserved'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TARGET BELANJA SURVIVAL & WISHLIST PRIORITAS (NEEDS VS WANTS) --}}
        <div id="tour-financial-goals" class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-2xl space-y-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-2xl">
                        🎯
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">Target Belanja Survival & Wishlist Prioritas</h3>
                            <span class="px-2.5 py-0.5 text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full">5 Level Skala Prioritas</span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">Pisahkan kebutuhan primer hidup (Pangan, Mandi, Ganti Oli, Holder HP) dari keinginan santai.</p>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-add-goal')" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs rounded-xl transition shadow flex items-center space-x-2 shrink-0">
                    <span class="w-5 h-5 rounded-full bg-white shadow-md flex items-center justify-center text-xs text-slate-950 font-black shrink-0 border border-white">➕</span>
                    <span>Tambah Target Belanja</span>
                </button>
            </div>

            @if($financialGoals->isEmpty())
                <div class="py-10 text-center bg-slate-900/60 border border-slate-800 rounded-xl space-y-2">
                    <span class="text-4xl block mb-2">🛒</span>
                    <h4 class="text-sm font-extrabold text-white">Belum Ada Target Belanja / Wishlist Aktif</h4>
                    <p class="text-xs text-slate-300 max-w-md mx-auto leading-relaxed">
                        Buat target belanja seperti <strong class="text-amber-300">Ganti Oli Mesin Honda MPX2</strong>, <strong class="text-amber-300">Holder HP Motor</strong>, atau <strong class="text-amber-300">Beras & Telur</strong> agar uang jajan Anda terkendali dengan aman.
                    </p>
                    <button type="button" onclick="openModal('modal-add-goal')" class="mt-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition">
                        + Buat Target Pertama Sekarang
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($financialGoals as $goal)
                        @php
                            $progress = $goal->target_amount > 0 ? min(100, round(($goal->allocated_amount / $goal->target_amount) * 100)) : 0;
                            $priorityBadgeClass = match($goal->priority_level) {
                                1 => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                                2 => 'bg-orange-500/20 text-orange-300 border-orange-500/40',
                                3 => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                                4 => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                                default => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                            };
                            $isFunded = $goal->allocated_amount >= $goal->target_amount;
                        @endphp
                        <div class="bg-slate-900/90 border border-slate-700/90 hover:border-slate-500 rounded-2xl p-5 flex flex-col justify-between shadow-lg transition space-y-4">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase border {{ $priorityBadgeClass }}">
                                        {{ $goal->getPriorityLabel() }}
                                    </span>
                                    @if($goal->target_date)
                                        <span class="text-[11px] text-slate-300 font-semibold">
                                            📅 {{ $goal->target_date->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>

                                <h4 class="text-sm font-extrabold text-white mb-1 leading-snug">{{ $goal->name }}</h4>
                                @if($goal->notes)
                                    <p class="text-xs text-slate-300 mb-3">{{ $goal->notes }}</p>
                                @endif

                                {{-- Progress Nominal & Bar --}}
                                <div class="space-y-2 my-3">
                                    <div class="flex justify-between items-baseline text-xs">
                                        <span class="text-slate-300 text-[11px]">Terkumpul: <strong class="text-emerald-400 font-black text-sm">Rp {{ number_format($goal->allocated_amount, 0, ',', '.') }}</strong></span>
                                        <span class="text-slate-200 font-extrabold">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="w-full bg-slate-950 rounded-full h-3 overflow-hidden border border-slate-700">
                                        <div class="h-3 rounded-full transition-all duration-500 {{ $isFunded ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-xs text-slate-300">
                                        <span class="font-bold">{{ $progress }}% tercapai</span>
                                        @if($isFunded)
                                            <span class="text-emerald-400 font-black">✓ Siap Dibeli Sekarang!</span>
                                        @else
                                            <span class="font-medium">Kurang: Rp {{ number_format(max(0, $goal->target_amount - $goal->allocated_amount), 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons: Quick Allocate 10k/20k & Realisasi Belanja --}}
                            <div class="pt-3 border-t border-slate-800 space-y-2.5">
                                <div class="flex items-center space-x-2">
                                    {{-- Tombol Cepat Sisihkan 10k --}}
                                    <form method="POST" action="/operational/goals/{{ $goal->id }}/allocate" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="amount" value="10000">
                                        <button type="submit" class="w-full py-2 px-2 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 hover:text-white rounded-xl text-xs font-black transition text-center shadow" title="Sisihkan Rp10.000 ke target ini">
                                            +10k
                                        </button>
                                    </form>

                                    {{-- Tombol Cepat Sisihkan 20k --}}
                                    <form method="POST" action="/operational/goals/{{ $goal->id }}/allocate" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="amount" value="20000">
                                        <button type="submit" class="w-full py-2 px-2 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 hover:text-white rounded-xl text-xs font-black transition text-center shadow" title="Sisihkan Rp20.000 ke target ini">
                                            +20k
                                        </button>
                                    </form>

                                    {{-- Tombol Sisihkan Custom --}}
                                    <button type="button" onclick="openAllocateGoalModal({{ $goal->id }}, '{{ addslashes($goal->name) }}', {{ $goal->target_amount - $goal->allocated_amount }})" class="flex-1 py-2 px-2 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 hover:text-white rounded-xl text-xs font-black transition text-center shadow">
                                        +Custom
                                    </button>
                                </div>

                                {{-- Tombol Realisasikan Belanja di Toko/Bengkel --}}
                                <button type="button" onclick="openAchieveGoalModal({{ $goal->id }}, '{{ addslashes($goal->name) }}', {{ $goal->target_amount }})" class="w-full py-2.5 px-3 {{ $isFunded ? 'bg-emerald-600 hover:bg-emerald-500 text-white font-black' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 font-bold' }} rounded-xl text-xs transition flex items-center justify-center space-x-1.5 shadow">
                                    <span>🛒</span>
                                    <span>Sudah Dibeli / Realisasikan</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Target yang telah selesai / terbeli --}}
            @if($achievedGoals->isNotEmpty())
                <div class="pt-2">
                    <details class="group">
                        <summary class="cursor-pointer text-xs font-extrabold text-slate-300 hover:text-white flex items-center space-x-2 select-none">
                            <span class="group-open:rotate-90 transition transform">▶</span>
                            <span>Riwayat Target yang Sudah Terbeli ({{ $achievedGoals->count() }})</span>
                        </summary>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mt-3">
                            @foreach($achievedGoals as $achieved)
                                <div class="bg-slate-900/80 border border-emerald-500/40 rounded-xl p-3.5 flex justify-between items-center text-xs">
                                    <div>
                                        <span class="text-white font-extrabold block">{{ $achieved->name }}</span>
                                        <span class="text-[11px] text-slate-300 font-medium">Dibeli: {{ $achieved->achieved_at ? $achieved->achieved_at->format('d M Y') : '-' }}</span>
                                    </div>
                                    <span class="text-emerald-400 font-black text-sm">Rp {{ number_format($achieved->allocated_amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif
        </div>

        {{-- ================= MISI BEBAS HUTANG 31 OKTOBER (CLOSING BULAN INI LUNAS!) ================= --}}
        <div id="tour-debt-freedom" class="bg-gradient-to-br from-slate-900 via-purple-950/20 to-slate-900 border border-purple-500/50 rounded-2xl p-6 shadow-2xl space-y-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-700/80 pb-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 border border-purple-500/40 flex items-center justify-center text-2xl">
                        🎯
                    </div>
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <h3 class="text-base font-extrabold uppercase tracking-wider text-white">Misi Bebas Hutang 31 Oktober</h3>
                            <span class="px-2.5 py-0.5 text-[10px] font-black bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-full">
                                Target Lunas: {{ $debtFreedomData['target_label'] }} (Sisa {{ $debtFreedomData['days_remaining'] }} Hari)
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Kendalikan dan lunasi seluruh beban PayLater & Pinjaman online tanpa merusak kebutuhan pokok hidup.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="openModal('modal-existing-debt')" class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center space-x-1.5">
                        <span>➕</span>
                        <span>Catat Tagihan Berjalan</span>
                    </button>
                    <button type="button" onclick="openModal('modal-pay-installment')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 font-bold text-xs rounded-xl shadow transition flex items-center space-x-1.5">
                        <span>💳</span>
                        <span>Bayar Cicilan</span>
                    </button>
                    <a href="/debts" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-purple-300 border border-purple-500/40 font-bold text-xs rounded-xl transition">
                        Kelola Portofolio &rarr;
                    </a>
                </div>
            </div>

            {{-- 4 Kartu Metrik Bebas Hutang --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-slate-900/90 border border-purple-500/30 rounded-xl p-4 space-y-1">
                    <span class="text-[10px] uppercase font-bold text-slate-300 block">Total Seluruh Hutang Aktif</span>
                    <span class="text-xl font-black text-purple-300 block">Rp {{ number_format($debtFreedomData['total_debt_remaining'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-300 font-medium block">SPayLater, SeaBank, TikTok, SPinjam, GoPay</span>
                </div>
                <div class="bg-slate-900/90 border border-rose-500/40 rounded-xl p-4 space-y-1">
                    <span class="text-[10px] uppercase font-bold text-rose-300 block">Jatuh Tempo Bulan Ini</span>
                    <span class="text-xl font-black text-rose-400 block">Rp {{ number_format($debtFreedomData['this_month_due'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-300 font-medium block">Wajib ditutup sebelum tanggal 31</span>
                </div>
                <div class="bg-slate-900/90 border border-amber-500/40 rounded-xl p-4 space-y-1">
                    <span class="text-[10px] uppercase font-bold text-amber-300 block">Target Setor Narik / Hari</span>
                    <span class="text-xl font-black text-amber-400 block">Rp {{ number_format($debtFreedomData['daily_quota'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-300 font-medium block">Disisihkan dari laba harian (16 hari sisa)</span>
                </div>
                <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-4 space-y-1">
                    <span class="text-[10px] uppercase font-bold text-slate-300 block">Jatuh Tempo Terdekat</span>
                    @if($debtFreedomData['installments']->isNotEmpty())
                        @php $firstInst = $debtFreedomData['installments']->first(); @endphp
                        <span class="text-sm font-extrabold text-white block truncate">{{ $firstInst->debt->name }}</span>
                        <span class="text-[11px] text-amber-300 font-bold block">
                            {{ $firstInst->due_date->translatedFormat('d M Y') }} &bull; Rp {{ number_format($firstInst->amount_due, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-sm font-extrabold text-emerald-400 block">Tidak Ada Tagihan!</span>
                        <span class="text-[11px] text-slate-300 font-medium block">Bulan ini bebas dari tagihan mendesak</span>
                    @endif
                </div>
            </div>

            {{-- Daftar Tagihan Jatuh Tempo Bulan Ini --}}
            @if($debtFreedomData['installments']->isNotEmpty())
                <div class="space-y-3 pt-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Tagihan yang Wajib Ditutup Bulan Ini:</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @foreach($debtFreedomData['installments'] as $inst)
                            <div class="bg-slate-900/80 border border-slate-700/80 hover:border-purple-500/50 rounded-xl p-3.5 flex justify-between items-center space-x-3">
                                <div class="min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                            {{ $inst->debt->getPlatformLabel() }}
                                        </span>
                                        <span class="text-xs text-slate-300 font-medium">Cicilan #{{ $inst->installment_number }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-white block mt-1 truncate">{{ $inst->debt->name }}</span>
                                    <span class="text-[11px] text-amber-300 block mt-0.5">Jatuh Tempo: {{ $inst->due_date->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-sm font-black text-rose-400 block">Rp {{ number_format($inst->amount_due, 0, ',', '.') }}</span>
                                    <button type="button" onclick="openPayInstallmentModal({{ $inst->id }}, '{{ addslashes($inst->debt->name) }}', {{ $inst->amount_due }})" class="mt-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] rounded-lg shadow">
                                        Bayar &rarr;
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="p-4 bg-emerald-950/20 border border-emerald-500/30 rounded-xl flex items-center justify-between text-xs text-emerald-300">
                    <span>🎉 Belum ada tagihan berjalan yang terdaftar. Klik <strong>Catat Tagihan Berjalan</strong> untuk memasukkan tagihan SPayLater, SeaBank Pinjam, atau TikTok PayLater Anda.</span>
                </div>
            @endif
        </div>

        {{-- ================= TAGIHAN RUTIN & LANGGANAN BULANAN (SUBSCRIPTIONS) ================= --}}
        <div id="tour-subscriptions" class="bg-gradient-to-br from-slate-900 via-indigo-950/20 to-slate-900 border border-indigo-500/50 rounded-2xl p-6 shadow-2xl space-y-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-700/80 pb-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-2xl">
                        📅
                    </div>
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <h3 class="text-base font-extrabold uppercase tracking-wider text-white">Tagihan Rutin & Langganan Bulanan</h3>
                            <span class="px-2.5 py-0.5 text-[10px] font-black bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full">
                                Fixed Recurring Expenses
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Beban berkala (Paket kuota internet narik, sewa kamar kos, wifi, listrik) yang mengunci Safe-to-Spend agar tidak terpakai jajan.
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="openModal('modal-add-subscription')" class="px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center space-x-1.5">
                        <span>➕</span>
                        <span>Tambah Tagihan Rutin</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-slate-900/90 border border-indigo-500/30 rounded-xl p-4 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-300 block">Total Beban Rutin / Bulan</span>
                        <span class="text-xl font-black text-indigo-300 block">Rp {{ number_format($totalMonthlySubscriptions, 0, ',', '.') }}</span>
                        <span class="text-[11px] text-slate-300 font-medium block">{{ $subscriptions->where('is_active', true)->count() }} langganan terdaftar</span>
                    </div>
                    <span class="text-3xl">📦</span>
                </div>
                <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-4 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-amber-300 block">Belum Dibayar Bulan Ini</span>
                        <span class="text-xl font-black text-amber-400 block">Rp {{ number_format($unpaidSubscriptionsAmount, 0, ',', '.') }}</span>
                        <span class="text-[11px] text-slate-300 font-medium block">{{ $unpaidSubscriptions->count() }} tagihan menunggu pembayaran</span>
                    </div>
                    <span class="text-3xl">⏳</span>
                </div>
            </div>

            @if($subscriptions->isEmpty())
                <div class="py-8 text-center bg-slate-900/60 border border-slate-800 rounded-xl space-y-2">
                    <span class="text-3xl block mb-1">📶</span>
                    <h4 class="text-sm font-extrabold text-white">Belum Ada Tagihan Rutin Terdaftar</h4>
                    <p class="text-xs text-slate-300 max-w-md mx-auto leading-relaxed">
                        Daftarkan paket data internet untuk narik atau sewa kamar kos bulanan agar sistem otomatis mengamankan uangnya sebelum terpakai jajan.
                    </p>
                    <button type="button" onclick="openModal('modal-add-subscription')" class="mt-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs rounded-xl shadow transition">
                        + Tambah Paket Kuota / Kos Sekarang
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($subscriptions as $sub)
                        @php $isPaid = $sub->isPaidThisMonth(); @endphp
                        <div class="bg-slate-900/90 border {{ $isPaid ? 'border-emerald-500/40' : 'border-slate-700/80' }} rounded-xl p-4 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex justify-between items-start mb-1.5">
                                    <span class="text-xs font-bold text-slate-300">{{ $sub->getCategoryLabel() }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $isPaid ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                        {{ $isPaid ? '✓ Lunas Bulan Ini' : 'Belum Dibayar' }}
                                    </span>
                                </div>
                                <span class="text-sm font-extrabold text-white block">{{ $sub->name }}</span>
                                <span class="text-xs text-slate-300 block mt-0.5">Tiap tanggal {{ $sub->due_day_of_month }} per bulan</span>
                            </div>
                            <div class="pt-3 border-t border-slate-800 flex justify-between items-center">
                                <div>
                                    <span class="text-[10px] text-slate-300 block font-semibold">Tarif:</span>
                                    <span class="text-base font-black text-indigo-300">Rp {{ number_format($sub->amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    @if(!$isPaid)
                                        <button type="button" onclick="openPaySubscriptionModal({{ $sub->id }}, '{{ addslashes($sub->name) }}', {{ $sub->amount }})" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg shadow">
                                            Bayar Sekarang
                                        </button>
                                    @else
                                        <span class="text-xs text-emerald-400 font-bold flex items-center space-x-1">
                                            <span>✓</span>
                                            <span>Lunas</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Grid Kartu Akun Driver & Tambah Rekening / Dompet Baru --}}
        <div id="tour-accounts-grid" class="space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-700/80 pb-3">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-200">Daftar Akun Keuangan & Rekening Bank</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Kelola dompet fisik, rekening bank (BCA, SeaBank), atau e-wallet (GoPay, ShopeePay).</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="/wallets" class="px-3.5 py-2 rounded-xl bg-indigo-600/30 hover:bg-indigo-600/40 text-indigo-300 border border-indigo-500/50 text-xs font-bold transition flex items-center space-x-1.5 shadow">
                        <span>💼</span>
                        <span>Kelola Wadah & Arsip</span>
                    </a>
                    <button type="button" onclick="openDeductAccountModal()" class="px-3.5 py-2 rounded-xl bg-rose-600/30 hover:bg-rose-600/40 text-rose-300 border border-rose-500/50 text-xs font-bold transition flex items-center space-x-1.5 shadow">
                        <span>🔻</span>
                        <span>Kurangi Saldo Wadah</span>
                    </button>
                    <button type="button" onclick="openManageDeductionsModal()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 text-xs font-semibold transition flex items-center space-x-1.5 shadow">
                        <span>⚙️</span>
                        <span>Potongan Bulanan</span>
                    </button>
                    <button type="button" onclick="openModal('modal-reconcile-digital')" class="px-3.5 py-2 rounded-xl bg-purple-600/30 hover:bg-purple-600/40 text-purple-300 border border-purple-500/50 text-xs font-bold transition flex items-center space-x-1.5 shadow">
                        <span>📲</span>
                        <span>Sinkron Saldo & QRIS</span>
                    </button>
                    <button type="button" onclick="openModal('modal-add-account')" class="px-3.5 py-2 rounded-xl bg-blue-600/30 hover:bg-blue-600/40 text-blue-300 border border-blue-500/50 text-xs font-bold transition flex items-center space-x-1.5">
                        <span>➕</span>
                        <span>Tambah Rekening Baru</span>
                    </button>
                    <button type="button" onclick="openModal('modal-init-account')" class="px-3.5 py-2 rounded-xl bg-emerald-600/30 hover:bg-emerald-600/40 text-emerald-300 border border-emerald-500/50 text-xs font-bold transition flex items-center space-x-1.5 shadow">
                        <span>💰</span>
                        <span>Atur Saldo Awal</span>
                    </button>
                    <button type="button" onclick="openDeductAccountModal()" class="px-3.5 py-2 rounded-xl bg-rose-600/30 hover:bg-rose-600/40 text-rose-300 border border-rose-500/50 text-xs font-bold transition flex items-center space-x-1.5 shadow" title="Kurangi saldo wadah untuk admin bank, debit, bunga, dll">
                        <span>🔻</span>
                        <span>Kurangi Saldo</span>
                    </button>
                    <button type="button" onclick="openManageDeductionsModal()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 text-xs font-semibold transition flex items-center space-x-1.5" title="Kelola jadwal pemotongan bulanan per rekening">
                        <span>⚙️</span>
                        <span>Jadwal Potongan</span>
                    </button>
                    <button type="button" onclick="openModal('modal-correct-account')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 text-xs font-semibold transition">
                        📝 Koreksi Audit
                    </button>
                    @if(config('app.trial_mode', false))
                        <button type="button" onclick="openModal('modal-reset-trial-data')" class="px-3.5 py-2 rounded-xl bg-rose-600/40 hover:bg-rose-600/60 text-rose-200 border border-rose-500/60 text-xs font-bold transition flex items-center space-x-1.5 shadow" title="Reset semua data ke kondisi fresh seeder">
                            <span>🔄</span>
                            <span>Reset Data Uji Coba</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($accounts->filter(fn($a) => in_array($a->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])) as $acc)
                    @php 
                        $isAsset = in_array($acc->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET, \App\Enums\AccountType::RECEIVABLE]);
                        $isLocked = $acc->initial_balance > 0;
                    @endphp
                    <div class="bg-slate-900/90 border border-slate-700/80 hover:border-slate-500 rounded-2xl p-5 flex flex-col justify-between shadow-lg transition space-y-3">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3 w-full">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-extrabold uppercase text-slate-200 tracking-wider block leading-snug">
                                        {{ $acc->name }}
                                    </span>
                                    <span class="text-xl font-black text-white mt-2 block tracking-tight">
                                        Rp {{ number_format($acc->current_balance, 0, ',', '.') }}
                                    </span>
                                    @if($isAsset)
                                        <span class="text-xs text-slate-300 font-medium block mt-0.5">
                                            Modal Awal: Rp {{ number_format($acc->initial_balance, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 font-medium block mt-0.5">
                                            Akumulasi Berjalan
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-col items-end gap-1.5 shrink-0">
                                    <span class="text-xs font-extrabold px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 uppercase border border-slate-700 tracking-wide">
                                        {{ $acc->type->value ?? $acc->type }}
                                    </span>
                                    @if($isAsset)
                                        @if($isLocked)
                                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold whitespace-nowrap" title="Saldo Awal Terkunci">
                                                🔒 Locked
                                            </span>
                                        @else
                                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold whitespace-nowrap" title="Belum Diinisialisasi">
                                                ⚠️ Unset
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Daftar Jadwal Potongan Rutin Wadah (Jika Ada) --}}
                            @if($isAsset && $acc->activeDeductions && $acc->activeDeductions->isNotEmpty())
                                <div class="space-y-1.5 pt-1">
                                    @foreach($acc->activeDeductions as $ded)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/90 rounded-lg px-2.5 py-1.5 border border-slate-700/80">
                                            <div class="truncate mr-2">
                                                <span class="text-rose-300 font-semibold">{{ $ded->name }}</span>: 
                                                <span class="text-white font-bold">Rp {{ number_format($ded->amount, 0, ',', '.') }}</span>
                                                <span class="text-slate-400 text-[10px] block">Tiap tgl {{ $ded->deduction_day }}</span>
                                            </div>
                                            <div>
                                                @if(!$ded->isDeductedThisMonth())
                                                    <form method="POST" action="/operational/account-deduction/{{ $ded->id }}/execute" onsubmit="return confirm('Eksekusi pemotongan {{ $ded->name }} sebesar Rp {{ number_format($ded->amount, 0, ',', '.') }} dari {{ $acc->name }} sekarang?')">
                                                        @csrf
                                                        <button type="submit" class="text-[10px] bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded-md font-bold transition whitespace-nowrap flex items-center gap-1" title="Potong saldo bulan ini sekarang">
                                                            ⚡ Potong
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-[9px] text-emerald-400 font-bold bg-emerald-950/60 border border-emerald-500/30 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                        ✓ Lunas Bln Ini
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-800 w-full flex flex-col gap-2 text-xs">
                            @if($isAsset)
                                <div class="flex items-center gap-1.5 w-full">
                                    <button type="button" onclick="openDeductAccountModal({{ $acc->id }}, '{{ addslashes($acc->name) }}', {{ $acc->current_balance }})" class="flex-1 py-1.5 px-2 bg-slate-800 hover:bg-rose-950/40 text-slate-200 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition" title="Kurangi saldo wadah ini untuk biaya admin, bunga, atau potongan lain">
                                        <span>🔻</span>
                                        <span>Kurangi Saldo</span>
                                    </button>
                                    <button type="button" onclick="openManageDeductionsModal({{ $acc->id }})" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 rounded-xl text-[11px] font-bold transition" title="Atur jadwal potongan bulanan wadah ini">
                                        ⚙️
                                    </button>
                                </div>
                            @endif

                            @if($acc->type === \App\Enums\AccountType::CASH)
                                <button type="button" onclick="openModal('modal-view-cash-denomination')" class="w-full py-2 px-3 bg-slate-800 hover:bg-slate-700 border border-emerald-500/40 rounded-xl text-emerald-300 text-xs font-extrabold flex items-center justify-between transition group shadow">
                                    <span class="flex items-center space-x-1.5">
                                        <span>👛</span>
                                        <span>Lihat Rincian Lembaran Kas</span>
                                    </span>
                                    <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
                                </button>
                            @else
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-slate-300 font-medium">Status: <span class="text-emerald-400 font-bold">Aktif</span></span>
                                    @if($acc->current_balance == 0)
                                        @if(!$acc->ledgerEntries()->exists())
                                            <form method="POST" action="/operational/account/{{ $acc->id }}" onsubmit="return confirm('Apakah Anda yakin ingin MENGHAPUS PERMANEN wadah {{ $acc->name }}? Wadah ini belum memiliki riwayat transaksi apa pun.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 bg-rose-950/40 hover:bg-rose-950/70 text-rose-300 border border-rose-500/40 rounded-lg text-xs font-semibold transition" title="Hapus permanen karena belum ada catatan transaksi">
                                                    🗑️ Hapus Wadah
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="/operational/account/{{ $acc->id }}/toggle-active" onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan/menutup rekening {{ $acc->name }}?')">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-amber-600/30 text-slate-300 hover:text-amber-300 border border-slate-700 rounded-lg text-xs font-semibold transition" title="Arsipkan / Tutup Rekening">
                                                    📦 Tutup Rekening
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-300 font-medium" title="Rekening hanya bisa ditutup jika saldo Rp0">🔒 Saldo Aktif</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Riwayat Transaksi Terbaru --}}
        @if($recentTransactions->isNotEmpty())
            <div id="tour-recent-tx" class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300 mb-3">20 Transaksi Terakhir</h3>
                <div class="divide-y divide-slate-700/50">
                    @foreach($recentTransactions as $tx)
                        <div class="py-3 flex justify-between items-center text-xs">
                            <div>
                                <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                    <span class="font-bold text-white block">{{ $tx->description }}</span>
                                    @if(!empty($tx->metadata['latitude']) && !empty($tx->metadata['longitude']))
                                        <a href="https://www.google.com/maps?q={{ $tx->metadata['latitude'] }},{{ $tx->metadata['longitude'] }}" target="_blank" class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 border border-blue-500/40 text-[10px] font-bold flex items-center space-x-0.5 transition" title="Buka Koordinat GPS di Google Maps">
                                            <span>📍</span>
                                            <span>Maps</span>
                                        </a>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-300 font-medium">{{ $tx->transaction_time->format('d M Y H:i') }} &bull; Shift {{ $tx->workDay->work_date->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" 
                                    onclick="openEditTxModal({{ $tx->id }}, '{{ addslashes($tx->description) }}', 'Rp {{ number_format($tx->amount, 0, ',', '.') }}', '{{ $tx->transaction_time->format('d M Y H:i') }}')" 
                                    class="px-2 py-1 bg-slate-700/80 hover:bg-slate-600 text-slate-300 hover:text-white rounded-lg border border-slate-600 text-[11px] font-semibold flex items-center space-x-1 transition shadow-sm" 
                                    title="Edit Keterangan / Perbaiki Typo">
                                    <span>✏️</span>
                                    <span>Edit</span>
                                </button>
                                <span class="font-bold px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-200">
                                    {{ strtoupper($tx->type) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- ================= MODALS SECTION ================= --}}

    {{-- 1. Modal Buka Shift Narik (1 Klik Cepat) --}}
    <div id="modal-open-day" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">🌅</span>
                <div>
                    <h3 class="text-base font-bold text-white">Buka Hari Baru (Start Day)</h3>
                    <p class="text-xs text-slate-300 font-medium">Buka sesi pencatatan hari ini dengan 1 klik cepat.</p>
                </div>
            </div>

            <form method="POST" action="/operational/open-day" class="space-y-4 mt-4">
                @csrf
                <input type="hidden" name="day_type" value="on_bid">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal</label>
                    <input type="date" name="work_date" value="{{ now()->toDateString() }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-semibold">
                </div>

                {{-- Status Kas Otomatis Melanjutkan --}}
                <div class="bg-slate-900/80 border border-slate-700/80 rounded-xl p-3.5 space-y-1">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-300 font-medium">Saldo Kas Berjalan:</span>
                        <span class="font-extrabold text-emerald-400">Rp {{ number_format($totalCash, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-300 leading-relaxed pt-1.5 border-t border-slate-800">
                        ✓ Saldo kas otomatis melanjutkan sisa uang dari hari sebelumnya. Anda <strong>tidak perlu menghitung lembaran uang di pagi hari</strong>. Perhitungan fisik per lembar & per koin dilakukan nanti saat <strong>Closing Kas Malam Hari</strong>.
                    </p>
                </div>

                {{-- Opsi Bawa Modal Kas Baru dari Luar (Opsional) --}}
                <details class="bg-slate-900/40 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-300">
                    <summary class="cursor-pointer font-semibold text-slate-300 hover:text-white">Bawa uang kas baru / modal tambahan pagi ini? (Opsional)</summary>
                    <div class="mt-2.5 space-y-1.5">
                        <label class="block text-[11px] font-bold text-slate-200">Nominal Modal Tambahan (Rp)</label>
                        <input type="number" name="cash_initial_balance" min="0" placeholder="Kosongkan jika hanya pakai saldo kemarin" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                    </div>
                </details>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-open-day')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                        <span>🌅</span>
                        <span>Mulai Hari Ini (Start Day)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Modal Beli Bensin --}}
    <div id="modal-fuel" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">⛽ Catat Pembelian Bensin</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Pengeluaran bensin operasional motor driver.</p>
            <form method="POST" action="/operational/fuel" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tempat Pembelian (Vendor)</label>
                    <select name="vendor_type" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        <option value="spbu">SPBU Resmi (Pertalite Rp10.000/L)</option>
                        <option value="madura">Warung Madura / Eceran (Rp12.000/L)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Sumber Akun Pembayaran</label>
                    <select name="source_account_id" id="fuel-source-select" onchange="checkShopeePayCashback(this, 'fuel-cashback-wrap')" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $acc->name === 'Cash' ? 'selected' : '' }}>
                                {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Field Cashback ShopeePay (Muncul Dinamis Saat Memilih ShopeePay) --}}
                <div id="fuel-cashback-wrap" class="hidden p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-amber-300">🎁 Reward Cashback ShopeePay (Rp)</label>
                        <span class="text-[10px] text-slate-400">Bisa diinput Rp 0 jika tidak ada</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="setCashbackAmount('input-fuel-cashback', 0)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-md">Rp 0</button>
                        <button type="button" onclick="setCashbackAmount('input-fuel-cashback', 1000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 1.000</button>
                        <button type="button" onclick="setCashbackAmount('input-fuel-cashback', 2000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 2.000</button>
                        <button type="button" onclick="setCashbackAmount('input-fuel-cashback', 3000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 3.000</button>
                        <button type="button" onclick="setCashbackAmount('input-fuel-cashback', 5000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 5.000</button>
                    </div>
                    <input type="number" name="cashback_amount" id="input-fuel-cashback" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl px-3 py-1.5 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <p class="text-[10px] text-amber-200/70">Cashback ini akan langsung menambah saldo ShopeePay Anda kembali dan dicatat sebagai pendapatan reward.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Pembelian (Rp)</label>
                    <input type="number" name="amount" min="1" step="1" placeholder="25000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-fuel')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Bensin</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Modal Parkir Kendaraan (Motor / Mobil, On-Bid / Off-Bid) --}}
    <div id="modal-parking" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-3 pb-2 border-b border-slate-700/80">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">🅿️</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Catat Parkir Kendaraan</h3>
                        <p class="text-xs text-slate-300 font-medium">Motor on-bid atau mobil saat off-bid / pribadi</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-parking')" class="text-slate-300 hover:text-white font-bold text-xl leading-none">&times;</button>
            </div>

            <form method="POST" action="/operational/parking" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <input type="hidden" name="latitude" id="parking-lat">
                <input type="hidden" name="longitude" id="parking-lng">

                {{-- Status Shift Badge --}}
                <div class="flex items-center justify-between text-xs px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700">
                    <span class="text-slate-300 font-medium">Status Operasional:</span>
                    @if($currentWorkDay)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold">
                            ✓ On-Bid ({{ $currentWorkDay->work_date->translatedFormat('d M Y') }})
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-300 border border-blue-500/30 text-[11px] font-bold">
                            🚗 Off-Bid / Parkir Pribadi
                        </span>
                    @endif
                </div>

                {{-- Pilihan Jenis Kendaraan (Motor vs Mobil) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Jenis Kendaraan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center space-x-2 p-2.5 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer hover:border-emerald-500 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                            <input type="radio" name="vehicle_type" value="motor" checked onchange="onVehicleTypeChange('motor')" class="text-emerald-500 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-white">🛵 Sepeda Motor</span>
                        </label>
                        <label class="flex items-center justify-center space-x-2 p-2.5 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer hover:border-emerald-500 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                            <input type="radio" name="vehicle_type" value="mobil" onchange="onVehicleTypeChange('mobil')" class="text-emerald-500 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-white">🚗 Mobil Pribadi</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Restoran / Lokasi Parkir</label>
                        <input type="text" name="restaurant_name" placeholder="Misal: Mie Gacoan, McD, Mall" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Kode Order <span class="text-slate-400 font-normal text-[11px]">(Opsional)</span>
                        </label>
                        <input type="text" name="order_code" placeholder="Misal: 633" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                {{-- Gratis / Ditanggung Resto --}}
                <div class="p-3 bg-slate-900/80 border border-slate-700 rounded-xl">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_covered_by_resto" value="1" id="chk-covered" onchange="toggleParkingAmount(this)" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-emerald-400">Gratis / Ditanggung Restoran</span>
                    </label>
                    <p class="text-[10px] text-slate-300 font-medium mt-1 pl-6">Centang jika resto menyediakan parkir bebas biaya khusus driver (uang kas tidak akan terpotong).</p>
                </div>

                {{-- Nominal Parkir Dinamis & Preset --}}
                <div id="div-parking-amount" class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-slate-300">Nominal Parkir (Rp)</label>
                        <span class="text-[10px] text-slate-400">Pilih cepat atau ketik bebas</span>
                    </div>

                    {{-- Quick Preset Chips --}}
                    <div class="flex flex-wrap gap-1.5" id="parking-preset-chips">
                        <button type="button" onclick="setParkingAmount(1000)" class="parking-preset-btn px-2.5 py-1 bg-slate-900 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-bold text-slate-200 transition">
                            Rp 1.000
                        </button>
                        <button type="button" onclick="setParkingAmount(2000)" class="parking-preset-btn px-2.5 py-1 bg-slate-900 hover:bg-slate-700 border border-emerald-500 text-emerald-300 rounded-lg text-xs font-bold transition">
                            Rp 2.000
                        </button>
                        <button type="button" onclick="setParkingAmount(3000)" class="parking-preset-btn px-2.5 py-1 bg-slate-900 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-bold text-slate-200 transition">
                            Rp 3.000
                        </button>
                        <button type="button" onclick="setParkingAmount(5000)" class="parking-preset-btn px-2.5 py-1 bg-slate-900 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-bold text-slate-200 transition">
                            Rp 5.000
                        </button>
                        <button type="button" onclick="setParkingAmount(10000)" class="parking-preset-btn px-2.5 py-1 bg-slate-900 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-bold text-slate-200 transition">
                            Rp 10.000
                        </button>
                    </div>

                    <input type="number" name="amount" id="input-parking-amount" value="2000" min="0" step="1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-base font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                {{-- Sumber Dana / Wadah --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Bayar Menggunakan Wadah</label>
                    <select name="source_account_id" id="parking-source-select" onchange="checkShopeePayCashback(this, 'parking-cashback-wrap')" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $acc->name === 'Cash' ? 'selected' : '' }}>
                                {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Field Cashback ShopeePay (Muncul Dinamis Saat Memilih ShopeePay) --}}
                <div id="parking-cashback-wrap" class="hidden p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-amber-300">🎁 Reward Cashback ShopeePay (Rp)</label>
                        <span class="text-[10px] text-slate-400">Bisa diinput Rp 0 jika tidak ada</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="setCashbackAmount('input-parking-cashback', 0)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-md">Rp 0</button>
                        <button type="button" onclick="setCashbackAmount('input-parking-cashback', 1000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 1.000</button>
                        <button type="button" onclick="setCashbackAmount('input-parking-cashback', 2000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 2.000</button>
                    </div>
                    <input type="number" name="cashback_amount" id="input-parking-cashback" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl px-3 py-1.5 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <p class="text-[10px] text-amber-200/70">Cashback ini akan langsung menambah saldo ShopeePay Anda kembali dan dicatat sebagai pendapatan reward.</p>
                </div>

                {{-- Status GPS Otomatis --}}
                <div id="parking-gps-status-wrap" class="flex items-center justify-between p-2.5 bg-slate-900/90 border border-slate-700 rounded-xl text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="text-base" id="parking-gps-icon">📍</span>
                        <div>
                            <span class="font-bold text-white block text-[11px]" id="parking-gps-title">GPS Lokasi Parkir:</span>
                            <span class="text-[10px] text-amber-300 font-medium" id="parking-gps-desc">Mendeteksi koordinat GPS tempat Anda parkir...</span>
                        </div>
                    </div>
                    <button type="button" onclick="detectParkingGps(true)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-[10px] font-bold rounded-lg border border-slate-600 transition flex items-center space-x-1 shrink-0">
                        <span>🔄</span>
                        <span>Perbarui GPS</span>
                    </button>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-700/80">
                    <button type="button" onclick="closeModal('modal-parking')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                        <span>🅿️</span>
                        <span>Simpan Parkir</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. MODAL TERIMA ORDER COD BARU (Fase 1: ShopeePay Dipotong Otomatis) --}}
    <div id="modal-accept-cod" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">🛵</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Order COD Masuk</h3>
                        <p class="text-[11px] text-slate-300 font-medium">Talangi pesanan resto dengan saldo ShopeePay</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-accept-cod')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            @if(!$currentWorkDay)
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl text-xs">
                    ⚠️ Silakan buka shift hari kerja terlebih dahulu sebelum mencatat order COD.
                </div>
            @else
                <form method="POST" action="{{ route('operational.cod-orders.accept') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id }}">
                    <input type="hidden" name="latitude" id="accept-cod-lat">
                    <input type="hidden" name="longitude" id="accept-cod-lng">
                    <input type="hidden" name="rounded_bill_amount" id="accept-cod-rounded">

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Kode Pesanan (1 - 3 Digit Angka) <span class="text-emerald-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-base font-black text-emerald-400">#</span>
                            <input 
                                type="text" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                name="order_code" 
                                id="accept-order-code"
                                required 
                                placeholder="Contoh: 633" 
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-8 pr-3 py-2.5 text-white text-lg font-black tracking-widest focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                        </div>
                        <span class="text-[10px] text-slate-300 font-medium block mt-1">Sesuai 3 digit kode di aplikasi Shopee (misal 633 atau 385).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Restoran / Merchant (Opsional)</label>
                        <input 
                            type="text" 
                            name="merchant_name" 
                            placeholder="Contoh: Geprek Kak Rose Dinoyo / Mie Gacoan" 
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nominal Tagihan COD di Shopee (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            name="bill_amount" 
                            id="accept-cod-bill"
                            required 
                            min="100" 
                            placeholder="Contoh: 51700" 
                            oninput="updateAcceptRounding()"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex space-x-2 mt-2">
                            <button type="button" onclick="document.getElementById('accept-cod-bill').value = 35000; updateAcceptRounding()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">35k</button>
                            <button type="button" onclick="document.getElementById('accept-cod-bill').value = 50000; updateAcceptRounding()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">50k</button>
                            <button type="button" onclick="document.getElementById('accept-cod-bill').value = 75000; updateAcceptRounding()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">75k</button>
                            <button type="button" onclick="document.getElementById('accept-cod-bill').value = 100000; updateAcceptRounding()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">100k</button>
                        </div>
                    </div>

                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700 text-xs space-y-1">
                        <div class="flex justify-between items-center text-slate-300 font-medium">
                            <span>Saran Bayar Lapangan (Bulat):</span>
                            <span id="accept-rounded-display" class="font-bold text-amber-400">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-300 font-medium">
                            <span>Saldo ShopeePay Terpotong:</span>
                            <span id="accept-bill-display" class="font-bold text-rose-400">Rp 0</span>
                        </div>
                        <p class="text-[10px] text-slate-300 font-medium pt-1 border-t border-slate-700 leading-relaxed">
                            💡 Saldo ShopeePay Anda akan langsung berkurang untuk talangan pesanan ini. Order akan masuk ke status <strong>Sedang Diantar</strong> sampai Anda menyelesaikannya di rumah pelanggan.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modal-accept-cod')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                            <span>🛵</span>
                            <span>Ambil Order & Potong ShopeePay</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- 4B. MODAL KALKULATOR PENYELESAIAN COD (Fase 2: Di Depan Rumah Customer) --}}
    <div id="modal-complete-cod" class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border-2 border-emerald-500/50 max-w-lg w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-start border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">🧮</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-extrabold text-white">Selesaikan Pesanan</h3>
                            <span id="calc-order-code" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-black rounded-lg"></span>
                        </div>
                        <p id="calc-merchant-name" class="text-xs text-slate-300 font-semibold"></p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-complete-cod')" class="text-slate-300 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <form id="form-complete-cod" method="POST" action="" class="space-y-4">
                @csrf
                <input type="hidden" name="payment_method" id="calc-payment-method" value="cash">
                <input type="hidden" name="received_amount" id="input-final-received-amount">
                <input type="hidden" name="latitude" id="complete-cod-lat">
                <input type="hidden" name="longitude" id="complete-cod-lng">

                {{-- Ringkasan Tagihan & Saran Pembulatan --}}
                <div class="grid grid-cols-2 gap-2 p-3 bg-slate-950/80 rounded-xl border border-slate-800 text-xs">
                    <div>
                        <span class="text-[11px] text-slate-300 font-medium block">Tagihan Asli Resto:</span>
                        <span id="calc-bill-amount-label" class="text-sm font-bold text-white block mt-0.5"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] text-amber-400/90 font-semibold block">Saran Bayar (Bulat):</span>
                        <span id="calc-rounded-amount-label" class="text-base font-black text-amber-400 block mt-0.5"></span>
                    </div>
                </div>

                {{-- Pilihan Metode Pembayaran (Tunai vs QRIS) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Metode Bayar Customer</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btn-method-cash" onclick="setCodPaymentMethod('cash')" class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center space-x-2 bg-emerald-600 text-white border-emerald-500">
                            <span>💵</span>
                            <span>Uang Tunai (Cash)</span>
                        </button>
                        <button type="button" id="btn-method-qris" onclick="setCodPaymentMethod('qris')" class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center space-x-2 bg-slate-900 text-slate-300 border-slate-700">
                            <span>📱</span>
                            <span>QRIS / E-Wallet</span>
                        </button>
                    </div>
                </div>

                {{-- 1. Kalkulator Uang Tunai (Numpad Ramah Driver) --}}
                <div id="calc-cash-calculator-section" class="space-y-3.5">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-slate-300">
                                Uang Dikasihkan Customer (Rp) <span class="text-rose-400">*</span>
                            </label>
                            <span class="text-[11px] text-slate-400">
                                Kembalian Normal: <span id="calc-std-change-display" class="text-amber-400 font-extrabold">Rp 0</span>
                            </span>
                        </div>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            id="calc-gave-amount" 
                            name="customer_gave_amount"
                            oninput="onGaveAmountChanged()"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-black text-base focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" onclick="setGavePreset('pas')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-emerald-400 font-bold rounded-lg border border-emerald-500/30">Uang Pas</button>
                            <button type="button" onclick="setGavePreset(50000)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">50k</button>
                            <button type="button" onclick="setGavePreset(100000)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">100k</button>
                            <button type="button" onclick="setGavePreset(150000)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">150k</button>
                            <button type="button" onclick="setGavePreset(200000)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">200k</button>
                        </div>
                    </div>

                    {{-- Kembalian yang Diserahkan (Scenario Nyata: Kembalian 20k Saja Mas) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Kembalian yang Diserahkan ke Customer (Rp)
                        </label>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            id="calc-change-returned" 
                            name="change_returned"
                            oninput="recalculateCodAmounts()"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" onclick="setChangePreset('full')" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">Kembalian Penuh</button>
                            <button type="button" onclick="setChangePreset(20000)" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 font-semibold rounded-lg">Kembalikan 20k Saja</button>
                            <button type="button" onclick="setChangePreset(10000)" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 font-semibold rounded-lg">Kembalikan 10k Saja</button>
                            <button type="button" onclick="setChangePreset(0)" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-emerald-300 font-bold rounded-lg border border-emerald-500/40">Rp0 (Ambil Semua)</button>
                        </div>
                    </div>
                </div>

                {{-- 2. Section QRIS (Jika Customer Transfer) --}}
                <div id="calc-qris-section" class="space-y-3 hidden">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Rekening / E-Wallet Penampung QRIS <span class="text-rose-400">*</span>
                        </label>
                        <select name="settlement_account_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            @foreach($accounts->filter(fn($a) => in_array($a->type->value, ['bank', 'ewallet'])) as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nominal yang Ditransfer Customer (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            id="calc-qris-amount" 
                            oninput="recalculateCodAmounts()"
                            placeholder="Contoh: 55000" 
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                    </div>
                </div>

                <input type="hidden" name="parking_reimbursed" id="calc-parking-amount" value="0">

                {{-- Hasil Perhitungan Bersih (Hasil Akhir) --}}
                <div class="p-3.5 bg-emerald-950/40 border border-emerald-500/40 rounded-xl space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-300 font-bold">Uang Masuk ke Kantong/Akun:</span>
                        <span id="calc-net-received-display" class="text-lg font-black text-emerald-400">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-slate-300 font-medium pt-1 border-t border-emerald-500/20">
                        <span>Pemulihan Modal ShopeePay:</span>
                        <span id="calc-breakdown-shopee" class="text-slate-300 font-semibold">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-slate-300 font-medium">
                        <span>Tip Bersih dari Pelanggan:</span>
                        <span id="calc-breakdown-surplus" class="text-amber-400 font-extrabold">Rp 0</span>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-complete-cod')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow flex items-center space-x-2">
                        <span>🚀</span>
                        <span>Selesaikan & Masukkan ke Kas</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4C. MODAL BATALKAN ORDER COD --}}
    <div id="modal-cancel-cod" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">⚠️</span>
                    <h3 class="text-base font-bold text-white">Batalkan Order COD <span id="cancel-cod-code"></span></h3>
                </div>
                <button type="button" onclick="closeModal('modal-cancel-cod')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            <form id="form-cancel-cod" method="POST" action="" class="space-y-4">
                @csrf
                <p class="text-xs text-slate-300 font-medium">
                    Gunakan jika resto tutup atau customer membatalkan pesanan. Saldo ShopeePay Anda yang tadi terpotong akan <strong>otomatis dipulihkan utuh</strong>.
                </p>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Alasan Pembatalan <span class="text-rose-400">*</span>
                    </label>
                    <textarea 
                        name="reason" 
                        required 
                        rows="2" 
                        placeholder="Contoh: Resto tutup saat driver sampai di lokasi..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-cancel-cod')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Tutup</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow">Batalkan & Pulihkan Saldo</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Keterangan Transaksi (Koreksi Typo / Catatan) --}}
    <div id="modal-edit-tx" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8">
            <div class="flex justify-between items-start mb-3 pb-2 border-b border-slate-700/80">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">✏️</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Edit Keterangan Transaksi</h3>
                        <p class="text-xs text-slate-300 font-medium">Koreksi salah ketik (typo) atau perjelas catatan transaksi</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-edit-tx')" class="text-slate-300 hover:text-white font-bold text-xl leading-none">&times;</button>
            </div>

            <form id="form-edit-tx" method="POST" action="" class="space-y-4">
                @csrf
                <div class="p-3 bg-slate-900/80 border border-slate-700 rounded-xl space-y-1 text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>Nominal Transaksi:</span>
                        <strong id="edit-tx-amount" class="text-emerald-400 font-black">Rp 0</strong>
                    </div>
                    <div class="flex justify-between text-slate-400 text-[11px]">
                        <span>Waktu Transaksi:</span>
                        <span id="edit-tx-time" class="text-slate-300 font-medium">-</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Keterangan Transaksi <span class="text-rose-400">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="description" 
                        id="edit-tx-description" 
                        required 
                        maxlength="255"
                        placeholder="Misal: Beli makan siang ayam geprek" 
                        class="w-full bg-slate-900 border border-slate-600 rounded-xl px-3 py-2 text-white text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    >
                    <span class="text-[10px] text-slate-400 mt-1 block">Perbaiki kata yang salah ketik (misal: 'brli' &rarr; 'beli').</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Alasan Koreksi (Opsional)
                    </label>
                    <input 
                        type="text" 
                        name="reason" 
                        placeholder="Misal: Typo pengetikan keterangan" 
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    >
                    <span class="text-[10px] text-slate-400 mt-1 block">Catatan ini otomatis dicatat ke Audit Log demi integritas pembukuan.</span>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-700/80">
                    <button type="button" onclick="closeModal('modal-edit-tx')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                        <span>💾</span>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 5. Modal Tambah Rekening / Akun Baru --}}
    <div id="modal-add-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">➕ Buka Rekening / Dompet Baru</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Tambahkan rekening bank baru (Mandiri, BRI, SeaBank) atau e-wallet (DANA, OVO, GoPay).</p>
            <form method="POST" action="/operational/account" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Rekening / Akun</label>
                    <input type="text" name="name" placeholder="Misal: Bank Mandiri / DANA / SeaBank" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tipe Akun</label>
                    <select name="type" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        <option value="bank">Rekening Bank</option>
                        <option value="ewallet">E-Wallet (Digital)</option>
                        <option value="cash">Uang Kas Fisik</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Saldo Modal Awal Rekening (Rp)</label>
                    <input type="number" name="initial_balance" min="0" placeholder="0" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-add-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow">Simpan Rekening Baru</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 6. Modal Pengeluaran Pribadi Sehari-hari (Makan, Pulsa, Dapur) --}}
    <div id="modal-expense" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🍜 Pengeluaran Sehari-hari / Pribadi</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat sarapan pagi jam 7, makan siang, kopi, belanja dapur, atau kebutuhan rumah tangga.</p>
            <form method="POST" action="/operational/personal-expense" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Keterangan Pengeluaran</label>
                    <input type="text" name="description" placeholder="Contoh: Sarapan pagi jam 7 sebelum narik" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Bayar Menggunakan <span class="text-rose-400">*</span></label>
                    <select name="source_account_id" id="expense-source-select" onchange="toggleExpenseTenor(this); checkShopeePayCashback(this, 'expense-cashback-wrap')" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <optgroup label="💵 Uang Kas & Saldo Rekening">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </optgroup>
                        @if(isset($paylaterProviders) && $paylaterProviders->isNotEmpty())
                            <optgroup label="🛍️ Wadah Kredit & PayLater (Bayar Tempo/Cicil)">
                                @foreach($paylaterProviders as $provider)
                                    <option value="paylater_{{ $provider->slug }}">
                                        {{ $provider->icon ?: '🛍️' }} {{ $provider->name }} (Sisa: Rp {{ number_format($provider->available_credit, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                {{-- Field Cashback ShopeePay (Muncul Dinamis Saat Memilih ShopeePay) --}}
                <div id="expense-cashback-wrap" class="hidden p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-amber-300">🎁 Reward Cashback ShopeePay (Rp)</label>
                        <span class="text-[10px] text-slate-400">Bisa diinput Rp 0 jika tidak ada</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="setCashbackAmount('input-expense-cashback', 0)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-md">Rp 0</button>
                        <button type="button" onclick="setCashbackAmount('input-expense-cashback', 1000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 1.000</button>
                        <button type="button" onclick="setCashbackAmount('input-expense-cashback', 2000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 2.000</button>
                        <button type="button" onclick="setCashbackAmount('input-expense-cashback', 3000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 3.000</button>
                        <button type="button" onclick="setCashbackAmount('input-expense-cashback', 5000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 5.000</button>
                    </div>
                    <input type="number" name="cashback_amount" id="input-expense-cashback" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl px-3 py-1.5 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <p class="text-[10px] text-amber-200/70">Cashback ini akan langsung menambah saldo ShopeePay Anda kembali dan dicatat sebagai pendapatan reward.</p>
                </div>

                {{-- Field Tenor (Muncul Otomatis Jika Memilih Wadah PayLater) --}}
                <div id="expense-tenor-wrapper" class="hidden p-3 bg-slate-900/90 border border-purple-500/40 rounded-xl space-y-1.5">
                    <label class="block text-xs font-bold text-purple-300">Pilihan Tenor Cicilan PayLater</label>
                    <select name="tenor_months" class="w-full bg-slate-800 border border-purple-500/50 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <option value="1">1 Bulan (Bayar Bulan Depan)</option>
                        <option value="2">2 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12">12 Bulan</option>
                    </select>
                    <p class="text-[10px] text-slate-300">Saldo kas fisik/bank tidak akan terpotong sekarang. Tagihan akan masuk ke jadwal cicilan hutang.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Pengeluaran (Rp)</label>
                    <input type="number" name="amount" min="1" placeholder="15000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-expense')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. Modal Pemasukan Pribadi (Project Luar / Jual Barang / Transferan) --}}
    <div id="modal-personal-income" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">💰 Pemasukan Pribadi di Luar Driver</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat honor freelance, hasil jual barang bekas, atau transferan keluarga (tidak dicampur ke omzet ShopeeFood).</p>
            <form method="POST" action="/operational/personal-income" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Keterangan Sumber Pemasukan</label>
                    <input type="text" name="description" placeholder="Contoh: Jual helm second / Honor project freelance" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Masuk ke Rekening / Dompet</label>
                    <select name="destination_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Saat ini: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Uang Masuk (Rp)</label>
                    <input type="number" name="amount" min="1" placeholder="100000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-personal-income')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Pemasukan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. Modal Belanja Online Pakai PayLater --}}
    <div id="modal-spaylater-purchase" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🛍️ Belanja Online Pakai PayLater</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat belanja barang bertempo/cicilan (SPayLater, TikTok PayLater, Kredivo). Tagihan otomatis terbuat ke jadwal cicilan bulanan tanpa mengurangi saldo kas/bank saat ini.</p>
            <form method="POST" action="/operational/spaylater-purchase" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Platform PayLater</label>
                    <select name="platform" onchange="toggleCustomPlatform(this, 'spaylater-custom-platform-wrap')" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold">
                        @if(isset($paylaterProviders) && $paylaterProviders->isNotEmpty())
                            @foreach($paylaterProviders as $provider)
                                <option value="{{ $provider->slug }}">{{ $provider->icon ?: '🛍️' }} {{ $provider->name }} (Sisa: Rp {{ number_format($provider->available_credit, 0, ',', '.') }})</option>
                            @endforeach
                        @else
                            <option value="spaylater">🛍️ Shopee PayLater (SPayLater)</option>
                            <option value="gopay_later">🟢 GoPay Later (GoPay Cicil / Findaya)</option>
                            <option value="tiktok_paylater">🎵 TikTok PayLater</option>
                            <option value="kredivo_paylater">⚡ Kredivo PayLater</option>
                            <option value="akulaku_paylater">🔴 Akulaku PayLater</option>
                            <option value="indodana_paylater">🟡 Indodana PayLater</option>
                        @endif
                        <option value="other_paylater">➕ Platform PayLater Lainnya (Ketik Manual)</option>
                    </select>
                </div>
                <div id="spaylater-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-amber-300 mb-1">Nama Platform PayLater Baru / Lainnya</label>
                    <input type="text" name="custom_platform" placeholder="Misal: Blibli PayLater / Traveloka PayLater" class="w-full bg-slate-900 border border-amber-500/50 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Barang / Belanjaan</label>
                    <input type="text" name="item_name" placeholder="Contoh: Jas Hujan Motor / Sepatu / Oli" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Total Harga Barang (Rp)</label>
                        <input type="number" name="total_price" min="1000" placeholder="150000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Pilihan Cicilan (Tenor)</label>
                        <select name="tenor_months" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                            <option value="1">1 Bulan (Bayar Bulan Depan)</option>
                            <option value="3">3 Bulan</option>
                            <option value="6">6 Bulan</option>
                            <option value="12">12 Bulan</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-spaylater-purchase')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Belanja PayLater</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 9. Modal Pinjam Uang / Pinjol / Bank / SeaBank --}}
    <div id="modal-apply-loan" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🏦 Pinjam Uang / Pinjaman Online / Bank</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat penerimaan pinjaman dari SPinjam, SeaBank Pinjam, GoPay Pinjam, Superbank Pinjam, Kredivo, atau Bank.</p>
            <form method="POST" action="/operational/debt" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Pinjaman / Keterangan</label>
                    <input type="text" name="name" placeholder="Misal: Pinjaman Modal Usaha SeaBank" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Platform / Pemberi Pinjaman</label>
                        <select name="platform" onchange="toggleCustomPlatform(this, 'loan-custom-platform-wrap')" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                            <option value="spinjam">Shopee Pinjam (SPinjam)</option>
                            <option value="seabank_pinjam">SeaBank Pinjam</option>
                            <option value="gopay_pinjam">GoPay Pinjam</option>
                            <option value="superbank_pinjam">Superbank Pinjam</option>
                            <option value="kredivo_pinjam">Kredivo Pinjam</option>
                            <option value="bank">Pinjaman Bank Umum</option>
                            <option value="person">Pinjam ke Teman / Keluarga</option>
                            <option value="other">➕ Pinjaman Lainnya (Ketik Manual)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Lama Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" value="3" min="1" max="36" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                </div>
                <div id="loan-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-amber-300 mb-1">Nama Platform Pinjaman Lainnya</label>
                    <input type="text" name="custom_platform" placeholder="Misal: KTA Tunaiku / Kredit Plus" class="w-full bg-slate-900 border border-amber-500/50 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Dana Bersih Diterima (Rp)</label>
                        <input type="number" name="principal_amount" min="1000" placeholder="1000000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Bunga / Biaya Provisi (Rp)</label>
                        <input type="number" name="fee_amount" value="0" min="0" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Dana Ditransfer Masuk ke Rekening</label>
                    <select name="destination_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Saat ini: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-apply-loan')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Cairkan Pinjaman</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 10. Modal Bayar Cicilan SPayLater / Pinjaman --}}
    <div id="modal-pay-installment" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">💳 Bayar Tagihan Cicilan / PayLater</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Lunasi cicilan pinjaman bulanan agar kewajiban berkurang.</p>
            <form method="POST" action="/operational/pay-installment" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Tagihan yang Ingin Dibayar</label>
                    <select name="installment_id" id="pay-installment-select" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @php $allInstallments = $debtFreedomData['installments']->isNotEmpty() ? $debtFreedomData['installments'] : $activeInstallments; @endphp
                        @foreach($allInstallments as $inst)
                            <option value="{{ $inst->id }}">
                                {{ $inst->debt->name }} (Cicilan #{{ $inst->installment_number }} - Rp {{ number_format($inst->amount_due, 0, ',', '.') }})
                            </option>
                        @endforeach
                        @if($allInstallments->isEmpty())
                            <option value="" disabled selected>Tidak ada tagihan cicilan jatuh tempo saat ini</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Bayar Menggunakan Saldo Rekening</label>
                    <select name="payment_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Bayar (Rp)</label>
                        <input type="number" name="actual_paid" id="pay-installment-amount" min="1" placeholder="Nominal cicilan" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Diskon Promo (Rp)</label>
                        <input type="number" name="promo_discount" value="0" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-pay-installment')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Bayar Cicilan Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 11. Modal Transfer Antar Rekening --}}
    <div id="modal-transfer" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🔄 Transfer Saldo Antar Rekening</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Tarik tunai ATM ke dompet, atau top-up ShopeePay.</p>
            <form method="POST" action="/operational/transfer" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Dari Akun (Sumber)</label>
                    <select name="source_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Ke Akun (Tujuan)</label>
                    <select name="destination_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $acc->name === 'Cash' ? 'selected' : '' }}>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" min="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Biaya Admin (Rp)</label>
                        <input type="number" name="admin_fee" value="0" min="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-transfer')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Transfer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 12. Modal Inisialisasi Saldo Akun PERTAMA KALI (Sekali Seumur Hidup & Terkunci) --}}
    <div id="modal-init-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-xl w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xl">🔒</span>
                <h3 class="text-base font-bold text-white">Inisialisasi Saldo Awal (Modal Baseline)</h3>
            </div>
            <p class="text-xs text-slate-300 font-medium mb-4">
                Saldo awal ini menjadi titik awal (baseline) pembukuan akun Anda. Setelah dikunci, saldo ini tidak dapat diubah-ubah bebas agar laporan keuangan tetap presisi.
            </p>
            <form method="POST" action="/operational/set-initial-balance" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Akun yang Belum Dikunci</label>
                    @php
                        $unlockedAssetAccounts = $accounts->filter(fn($a) => in_array($a->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET]) && $a->initial_balance == 0);
                    @endphp
                    <select name="account_id" id="init_account_select" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($unlockedAssetAccounts as $acc)
                            <option value="{{ $acc->id }}" data-type="{{ $acc->type->value }}">{{ $acc->name }} ({{ strtoupper($acc->type->value) }} &bull; Saat ini: Rp 0)</option>
                        @endforeach
                        @if($unlockedAssetAccounts->isEmpty())
                            <option value="" disabled selected>Semua wadah simpanan sudah memiliki saldo awal terkunci</option>
                        @endif
                    </select>
                </div>

                {{-- Kalkulator Denominasi Khusus Kas Tunai --}}
                <div id="init-denom-section" class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">💵 Hitung Lembar & Koin Fisik (Kas Dompet)</span>
                        <span class="text-[10px] text-slate-300 font-medium">Otomatis hitung ke nominal</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach([100000, 50000, 20000, 10000, 5000, 2000, 1000] as $nom)
                            <div class="bg-slate-800 border border-slate-700 rounded-lg p-2">
                                <label class="text-[10px] font-bold text-slate-300 block mb-1">
                                    Rp {{ number_format($nom, 0, ',', '.') }}
                                </label>
                                <input 
                                    type="number" 
                                    min="0" 
                                    name="bill_{{ $nom }}" 
                                    id="init_bill_{{ $nom }}" 
                                    value="0"
                                    class="init-denom-input w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white font-bold text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                                >
                            </div>
                        @endforeach
                        @foreach([1000, 500, 200, 100] as $nom)
                            <div class="bg-slate-800 border border-slate-700 rounded-lg p-2">
                                <label class="text-[10px] font-bold text-slate-300 block mb-1">
                                    🪙 Rp {{ number_format($nom, 0, ',', '.') }}
                                </label>
                                <input 
                                    type="number" 
                                    min="0" 
                                    name="coin_{{ $nom }}" 
                                    id="init_coin_{{ $nom }}" 
                                    value="0"
                                    class="init-denom-input w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white font-bold text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                                >
                            </div>
                        @endforeach
                    </div>
                    <div class="bg-slate-900 border border-slate-700 rounded-lg p-2.5 flex justify-between items-center text-xs">
                        <span id="init-denom-pieces" class="text-slate-300 font-medium">0 lembar/koin</span>
                        <span id="init-denom-total" class="font-bold text-emerald-400">Rp 0</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Saldo Modal Awal (Rp)</label>
                    <input type="number" name="amount" id="init_account_amount" min="0" placeholder="Contoh: 500000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold">
                    <span class="text-[10px] text-slate-300 font-medium mt-1 block">Untuk rekening Bank / E-Wallet, ketik langsung saldo riil m-banking Anda. Untuk Kas fisik, nominal di atas terisi otomatis dari hitungan pecahan.</span>
                </div>
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-[11px] text-amber-300">
                    ⚠️ <strong>Penting:</strong> Masukkan jumlah riil yang ada di rekening/dompet Anda saat mulai memakai aplikasi ini.
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-init-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Kunci Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 13. Modal Koreksi Audit Khusus (Dengan Alasan Wajib Minimal 5 Karakter) --}}
    <div id="modal-correct-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xl">📝</span>
                <h3 class="text-base font-bold text-white">Koreksi Audit Saldo Awal</h3>
            </div>
            <p class="text-xs text-slate-300 font-medium mb-4">
                Hanya digunakan jika terjadi salah ketik fatal pada saldo awal. Setiap perubahan akan disimpan permanen dalam **Audit Trail Log** demi integritas keuangan.
            </p>
            <form method="POST" action="/operational/set-initial-balance" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Akun yang Ingin Dikoreksi</label>
                    <select name="account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts->filter(fn($a) => in_array($a->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])) as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Modal Awal Saat ini: Rp {{ number_format($acc->initial_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Saldo Awal Hasil Koreksi (Rp)</label>
                    <input type="number" name="amount" min="0" placeholder="0" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Alasan Koreksi (Wajib Disertifikasi)</label>
                    <input type="text" name="reason" placeholder="Contoh: Salah input angka nol saat registrasi awal" required minlength="5" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-correct-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow">Simpan Koreksi Audit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 14. Modal Rincian Lembaran Fisik Kas Dompet (Hasil Closing Terakhir) --}}
    <div id="modal-view-cash-denomination" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-lg w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-4 pb-3 border-b border-slate-700/80">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">👛</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Rincian Fisik Lembaran Kas</h3>
                        <p class="text-xs text-slate-300 font-medium">
                            @if($cashDenomination?->workDay?->work_date)
                                Hasil hitungan closing shift: <span class="font-bold text-emerald-400">{{ $cashDenomination->workDay->work_date->translatedFormat('l, d F Y') }}</span>
                            @elseif($currentWorkDay)
                                Shift aktif: <span class="font-bold text-emerald-400">{{ $currentWorkDay->work_date->translatedFormat('l, d F Y') }}</span>
                            @else
                                Status Dompet: <span class="font-bold text-emerald-400">Saldo Awal Terkunci</span>
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-view-cash-denomination')" class="text-slate-300 hover:text-white font-bold text-xl leading-none">&times;</button>
            </div>

            @if($cashDenomination)
                <div class="space-y-4">
                    {{-- Total Banner --}}
                    <div class="bg-slate-900 border border-slate-700 rounded-xl p-3.5 flex justify-between items-center shadow-inner">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block">Total Uang Fisik Terhitung:</span>
                            <span class="text-xs text-slate-300 font-semibold">{{ $cashDenomination->total_pieces }} lembar & koin</span>
                        </div>
                        <span class="text-xl font-extrabold text-emerald-400">
                            Rp {{ number_format($cashDenomination->total_physical_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Alert Uang Rusak / Sobek jika ada --}}
                    @if(($cashDenomination->damaged_notes_amount ?? 0) > 0 || !empty($cashDenomination->damaged_notes_note))
                        <div class="p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-xs text-rose-300 flex items-start space-x-2.5">
                            <span class="text-xl">✂️</span>
                            <div>
                                <span class="font-bold block">Uang Sobek / Rusak di Dompet: Rp {{ number_format($cashDenomination->damaged_notes_amount ?? 0, 0, ',', '.') }}</span>
                                <span class="text-[11px] text-rose-200/90 block mt-0.5">
                                    {{ $cashDenomination->damaged_notes_note ?: 'Ada lembaran rusak' }} &bull; Belanjakan segera ke SPBU Pertamina/minimarket.
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Uang Kertas --}}
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block mb-2">💵 Uang Kertas (Lembaran):</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @foreach([100000 => 'Rp 100.000', 50000 => 'Rp 50.000', 20000 => 'Rp 20.000', 10000 => 'Rp 10.000', 5000 => 'Rp 5.000', 2000 => 'Rp 2.000', 1000 => 'Rp 1.000'] as $nom => $label)
                                @php 
                                    $field = 'bill_' . $nom; 
                                    $count = $cashDenomination->$field ?? 0; 
                                    $subtotal = $count * $nom;
                                @endphp
                                <div class="bg-slate-900/80 border {{ $nom === 5000 ? 'border-amber-500/60 bg-amber-500/10' : ($count >= 20 ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-700/60') }} rounded-xl p-2.5 flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-white block">{{ $label }}</span>
                                        <span class="text-[10px] text-slate-300 font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="text-sm font-extrabold {{ $nom === 5000 ? 'text-amber-400 font-black' : ($count >= 20 ? 'text-amber-400' : 'text-white') }}">
                                        {{ $count }} <span class="text-[10px] font-medium text-slate-300">lbr</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Uang Logam --}}
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block mb-2">🪙 Uang Logam (Koin):</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @foreach([1000 => 'Rp 1.000', 500 => 'Rp 500', 200 => 'Rp 200', 100 => 'Rp 100'] as $nom => $label)
                                @php 
                                    $field = 'coin_' . $nom; 
                                    $count = $cashDenomination->$field ?? 0; 
                                    $subtotal = $count * $nom;
                                @endphp
                                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-2.5 flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-white block">{{ $label }}</span>
                                        <span class="text-[10px] text-slate-300 font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="text-sm font-extrabold text-white">
                                        {{ $count }} <span class="text-[10px] font-medium text-slate-300">koin</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3 bg-slate-900/60 border border-slate-700 rounded-xl text-[11px] text-slate-300 font-medium">
                        ℹ️ <strong>Catatan:</strong> Data lembaran kas fisik ini direkam setiap malam saat Anda melakukan <strong>Closing Kas Shift</strong> sepulang narik.
                    </div>
                </div>
            @else
                <div class="py-6 text-center space-y-3">
                    <span class="text-4xl block">👛</span>
                    <div>
                        <h4 class="text-base font-bold text-white">Saldo Kas Dompet: Rp {{ number_format($totalCash, 0, ',', '.') }}</h4>
                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold">
                            ✓ Saldo Awal Terkunci Aman
                        </span>
                    </div>
                    <div class="p-3.5 bg-slate-900/80 border border-slate-700/70 rounded-xl text-xs text-slate-300 text-left space-y-2">
                        <p>
                            ℹ️ <strong>Tentang Rincian Lembaran Fisik:</strong><br>
                            Saldo uang kas dompet Anda sebesar <strong>Rp {{ number_format($totalCash, 0, ',', '.') }}</strong> sudah berhasil tercatat dan siap digunakan operasional.
                        </p>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Rincian lembaran fisik per pecahan (Rp 100.000, Rp 50.000, Rp 2.000, koin) akan otomatis diaudit dan terdata setiap kali Anda menyelesaikan <strong>Closing Kas Shift</strong> malam hari sepulang kerja narik.
                        </p>
                    </div>
                </div>
            @endif

            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-700/80 mt-4">
                <button type="button" onclick="closeModal('modal-view-cash-denomination')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Tutup</button>
                @if($currentWorkDay)
                    <a href="/closing/{{ $currentWorkDay->id }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">
                        Perbarui di Closing Shift &rarr;
                    </a>
                @else
                    <button type="button" onclick="closeModal('modal-view-cash-denomination'); openModal('modal-open-day');" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                        <span>🌅</span>
                        <span>Mulai Shift Narik Sekarang &rarr;</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 15. Modal Kas Dipinjam Teman / Talangan --}}
    <div id="modal-lend-friend" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">🤝</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Kas Dipinjam Teman / Talangan</h3>
                        <p class="text-[11px] text-slate-300 font-medium">Dicatat sebagai piutang agar fisik kas dompet tetap akurat</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-lend-friend')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            @if(!$currentWorkDay)
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl text-xs">
                    ⚠️ Silakan buka hari terlebih dahulu sebelum mencatat pinjaman kas.
                </div>
            @else
                <form method="POST" action="{{ route('operational.lend-friend-cash') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id }}">

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nama Teman / Peminjam <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="borrower_name" 
                            required 
                            placeholder="Contoh: Budi Driver / Mas Joko"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nominal Kas Dipinjamkan (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="input-lend-amount"
                            required 
                            min="1" 
                            step="1"
                            placeholder="50000"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex space-x-2 mt-2">
                            <button type="button" onclick="document.getElementById('input-lend-amount').value = 20000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">20k</button>
                            <button type="button" onclick="document.getElementById('input-lend-amount').value = 50000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">50k</button>
                            <button type="button" onclick="document.getElementById('input-lend-amount').value = 100000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">100k</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Catatan / Keperluan (Opsional)
                        </label>
                        <input 
                            type="text" 
                            name="notes" 
                            placeholder="Contoh: Talang modal COD di resto McD Dinoyo"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                    </div>

                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60 text-[11px] text-slate-300 font-medium">
                        💡 Uang kas dompet Anda akan berkurang Rp <span class="font-bold text-white">sesuai nominal</span> dan langsung masuk ke daftar <strong>Piutang Teman</strong> di menu Utang & PayLater.
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modal-lend-friend')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Simpan Pinjaman Kas</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- 16. Modal Tarik Tunai Tambah Modal Kas --}}
    @php
        $cashAccObj = $accounts->firstWhere('name', 'Cash') ?? $accounts->first(fn($a) => $a->type->value === 'cash');
        $digitalAccs = $accounts->filter(fn($a) => in_array($a->type->value, ['bank', 'ewallet']));
    @endphp
    <div id="modal-cash-withdraw" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">🏧</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Tarik Tunai Tambah Modal Kas</h3>
                        <p class="text-[11px] text-slate-300 font-medium">Pindah saldo digital (BCA/ShopeePay) ke uang fisik dompet</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-cash-withdraw')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            @if(!$currentWorkDay)
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl text-xs">
                    ⚠️ Silakan buka hari terlebih dahulu sebelum mencatat tarik tunai.
                </div>
            @elseif(!$cashAccObj)
                <div class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-300 rounded-xl text-xs">
                    ⚠️ Akun Kas fisik belum tersedia di database.
                </div>
            @else
                <form method="POST" action="{{ route('operational.transfer') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id }}">
                    <input type="hidden" name="destination_account_id" value="{{ $cashAccObj->id }}">
                    <input type="hidden" name="note" value="Tarik Tunai Tambah Modal Kas Dompet">

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Tarik Dari Rekening Digital <span class="text-rose-400">*</span>
                        </label>
                        <select name="source_account_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            @foreach($digitalAccs as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nominal Tarik Tunai (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="input-withdraw-amount"
                            required 
                            min="1" 
                            step="1"
                            placeholder="100000"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex space-x-2 mt-2">
                            <button type="button" onclick="document.getElementById('input-withdraw-amount').value = 50000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">50k</button>
                            <button type="button" onclick="document.getElementById('input-withdraw-amount').value = 100000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">100k</button>
                            <button type="button" onclick="document.getElementById('input-withdraw-amount').value = 200000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">200k</button>
                            <button type="button" onclick="document.getElementById('input-withdraw-amount').value = 500000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">500k</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Biaya Admin Penarikan (Rp)
                        </label>
                        <input 
                            type="number" 
                            name="admin_fee" 
                            value="0" 
                            min="0" 
                            step="1"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <span class="text-[10px] text-slate-300 font-medium block mt-1">Biaya admin kasir minimarket atau ATM bersama (default Rp0).</span>
                    </div>

                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60 text-[11px] text-slate-300 font-medium">
                        ✅ Saldo digital akan terpotong dan uang kas fisik dompet langsung bertambah untuk modal kembalian COD.
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modal-cash-withdraw')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Catat Tarik Tunai</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- 17. Modal Biaya Serba-serbi & Insidental Lapangan --}}
    <div id="modal-field-incidental" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">🛠️</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Biaya Serba-serbi & Lapangan</h3>
                        <p class="text-[11px] text-slate-300 font-medium">Tambal ban, lakban packing, bohlam sen, baut lepas, dll.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-field-incidental')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            @if(!$currentWorkDay)
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl text-xs">
                    ⚠️ Silakan buka hari terlebih dahulu sebelum mencatat pengeluaran serba-serbi.
                </div>
            @else
                <form method="POST" action="{{ route('operational.field-incidental-expense') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id }}">

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Keperluan / Keterangan Bebas <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="description" 
                            required 
                            placeholder="Contoh: Tambal ban belakang, Beli lakban ShopeeFood, Bohlam lampu"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" onclick="document.querySelector('#modal-field-incidental input[name=description]').value = 'Tambal ban bocor'; document.getElementById('input-incidental-amount').value = 15000" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">🚲 Tambal Ban (15k)</button>
                            <button type="button" onclick="document.querySelector('#modal-field-incidental input[name=description]').value = 'Beli lakban packing Shopee'; document.getElementById('input-incidental-amount').value = 10000" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">📦 Lakban (10k)</button>
                            <button type="button" onclick="document.querySelector('#modal-field-incidental input[name=description]').value = 'Ganti bohlam lampu sen motor'; document.getElementById('input-incidental-amount').value = 12000" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">💡 Bohlam Sen (12k)</button>
                            <button type="button" onclick="document.querySelector('#modal-field-incidental input[name=description]').value = 'Tambah angin nitrogen'; document.getElementById('input-incidental-amount').value = 5000" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">💨 Angin Ban (5k)</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Nominal Pengeluaran (Rp) <span class="text-rose-400">*</span>
                        </label>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            name="amount" 
                            id="input-incidental-amount"
                            required 
                            min="1" 
                            step="1"
                            placeholder="15000"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <div class="flex space-x-2 mt-2">
                            <button type="button" onclick="document.getElementById('input-incidental-amount').value = 5000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">5k</button>
                            <button type="button" onclick="document.getElementById('input-incidental-amount').value = 10000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">10k</button>
                            <button type="button" onclick="document.getElementById('input-incidental-amount').value = 15000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">15k</button>
                            <button type="button" onclick="document.getElementById('input-incidental-amount').value = 20000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">20k</button>
                            <button type="button" onclick="document.getElementById('input-incidental-amount').value = 50000" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 rounded-lg">50k</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Sumber Dana (Bayar Pakai Apa?) <span class="text-rose-400">*</span>
                        </label>
                        <select name="source_account_id" id="incidental-source-select" onchange="checkShopeePayCashback(this, 'incidental-cashback-wrap')" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            @if($cashAccObj)
                                <option value="{{ $cashAccObj->id }}" selected>💵 Kas Dompet (Fisik Cash) - Saldo: Rp {{ number_format($cashAccObj->current_balance, 0, ',', '.') }}</option>
                            @endif
                            @foreach($digitalAccs as $acc)
                                <option value="{{ $acc->id }}">💳 {{ $acc->name }} (QRIS / Transfer) - Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                        <span class="text-[10px] text-slate-300 font-medium block mt-1">Default memotong Kas Dompet, bisa diganti jika bayar via QRIS BCA/SeaBank.</span>
                    </div>

                    {{-- Field Cashback ShopeePay (Muncul Dinamis Saat Memilih ShopeePay) --}}
                    <div id="incidental-cashback-wrap" class="hidden p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-1.5">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold text-amber-300">🎁 Reward Cashback ShopeePay (Rp)</label>
                            <span class="text-[10px] text-slate-400">Bisa diinput Rp 0 jika tidak ada</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" onclick="setCashbackAmount('input-incidental-cashback', 0)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-md">Rp 0</button>
                            <button type="button" onclick="setCashbackAmount('input-incidental-cashback', 1000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 1.000</button>
                            <button type="button" onclick="setCashbackAmount('input-incidental-cashback', 2000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 2.000</button>
                            <button type="button" onclick="setCashbackAmount('input-incidental-cashback', 3000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 3.000</button>
                            <button type="button" onclick="setCashbackAmount('input-incidental-cashback', 5000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 5.000</button>
                        </div>
                        <input type="number" name="cashback_amount" id="input-incidental-cashback" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl px-3 py-1.5 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <p class="text-[10px] text-amber-200/70">Cashback ini akan langsung menambah saldo ShopeePay Anda kembali dan dicatat sebagai pendapatan reward.</p>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modal-field-incidental')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Catat Biaya Lapangan</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- ================= MODAL SINKRONISASI SALDO DIGITAL & UPDATE DI JALAN ================= --}}
    <div id="modal-reconcile-digital" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-purple-500/50 max-w-2xl w-full rounded-2xl p-6 shadow-2xl max-h-[92vh] overflow-y-auto space-y-4 my-8">
            <div class="flex justify-between items-start border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-3">
                    <span class="text-3xl">📲</span>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Update Saldo & Compare ShopeeFood Driver</h3>
                        <p class="text-xs text-slate-300">Bisa dilakukan kapan saja di menit keberapa jam keberapa pun saat Anda narik.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-reconcile-digital')" class="text-slate-400 hover:text-white font-bold text-xl leading-none">&times;</button>
            </div>

            <div class="bg-purple-950/40 border border-purple-500/30 rounded-xl p-3 text-xs text-purple-200 leading-relaxed">
                💡 <strong>Konsep Snapshot & Rekonsiliasi di Jalan:</strong> Intip layar aplikasi ShopeeFood Driver Anda (berapa pendapatan & orderan saat ini), lalu cek saldo di wadah uang Anda (ShopeePay, Kas dompet saku, QRIS). Sistem langsung menghitung perbandingan (<em>Compare</em>) secara live apakah Anda surplus tip bersih dari pelanggan!
            </div>

            <form method="POST" action="/operational/digital-reconciliation" class="space-y-4">
                @csrf
                @if($currentWorkDay)
                    <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id }}">
                @endif

                {{-- Waktu Checkpoint (100% Otomatis Jam & Tanggal Sekarang) --}}
                <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-3 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-base text-purple-400">⏱️</span>
                        <div>
                            <span class="block text-xs font-bold text-slate-200">Waktu Checkpoint (Otomatis)</span>
                            <span class="text-[10px] text-slate-400">Tercatat realtime saat tombol simpan ditekan</span>
                        </div>
                    </div>
                    <span id="recon-live-clock" class="px-2.5 py-1 rounded-lg bg-purple-500/20 text-purple-300 border border-purple-500/30 font-black text-xs">
                        {{ now()->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                    <input type="hidden" name="recorded_at" id="recon-recorded-at" value="{{ now()->format('Y-m-d H:i:s') }}">
                </div>

                {{-- BAGIAN 1: DATA DARI APLIKASI SHOPEEFOOD DRIVER --}}
                <div class="bg-slate-800/90 border border-orange-500/40 rounded-2xl p-4 space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-700/80 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">🛵</span>
                            <h4 class="text-xs font-black uppercase tracking-wider text-orange-400">1. Data di Layar Aplikasi ShopeeFood Driver</h4>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-orange-500/20 text-orange-300 font-bold border border-orange-500/30">
                            Layar App Driver
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                Pendapatan Bersih / Tarif (Rp) <span class="text-rose-400">*</span>
                            </label>
                            <input 
                                type="number" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                name="hub_income" 
                                id="recon-hub-income" 
                                value="{{ $latestSnapshot?->hub_income ?? $dayRegularIncome ?? 0 }}" 
                                min="0" 
                                step="1" 
                                oninput="calculateDigitalReconDiff()" 
                                placeholder="Contoh: 85000" 
                                required
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                            >
                            <span class="text-[10px] text-slate-400">Ongkir/tarif bersih yang tertera di profil driver</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                Bonus / Insentif Poin (Rp)
                            </label>
                            <input 
                                type="number" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                name="hub_bonus" 
                                id="recon-hub-bonus" 
                                value="{{ $latestSnapshot?->hub_bonus ?? $dayBonusIncome ?? 0 }}" 
                                min="0" 
                                step="1" 
                                oninput="calculateDigitalReconDiff()" 
                                placeholder="0" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                            >
                            <span class="text-[10px] text-slate-400">Bonus misi harian (bisa Rp0 jika belum tembus target)</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                Tip Pelanggan di Aplikasi (Rp)
                            </label>
                            <input 
                                type="number" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                name="customer_tips" 
                                id="recon-customer-tips" 
                                value="{{ $latestSnapshot?->customer_tips ?? $dayTipIncome ?? 0 }}" 
                                min="0" 
                                step="1" 
                                oninput="calculateDigitalReconDiff()" 
                                placeholder="0" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                            >
                            <span class="text-[10px] text-slate-400">Tip resmi yang masuk lewat sistem Shopee</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                Jumlah Pesanan Selesai (Trip)
                            </label>
                            <input 
                                type="number" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                name="completed_orders" 
                                id="recon-completed-orders" 
                                value="{{ $latestSnapshot?->completed_orders ?? $dayOrdersCount ?? 0 }}" 
                                min="0" 
                                step="1" 
                                placeholder="Contoh: 8" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                            >
                            <span class="text-[10px] text-slate-400">Berapa order yang sudah terselesaikan saat jam ini</span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-950/70 border border-slate-800 rounded-xl flex justify-between items-center text-xs">
                        <span class="text-slate-300 font-bold">Total Tercatat di Aplikasi Shopee:</span>
                        <strong id="disp-shopee-total" class="text-orange-400 font-extrabold text-sm">Rp 0</strong>
                    </div>
                </div>

                {{-- BAGIAN 2: SALDO AKTUAL DI WADAH ANDA --}}
                <div class="bg-slate-800/90 border border-emerald-500/40 rounded-2xl p-4 space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-700/80 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">💼</span>
                            <h4 class="text-xs font-black uppercase tracking-wider text-emerald-400">2. Posisi Uang Terkini di Wadah Anda</h4>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                            Dompet & Rekening Riil
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach($digitalReconAccounts as $idx => $dAcc)
                            <div class="bg-slate-950/90 border border-slate-700 rounded-xl p-3 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-extrabold text-white flex items-center space-x-1.5">
                                        <span>
                                            @if($dAcc->name === 'Cash' || $dAcc->type->value === 'cash') 💵
                                            @elseif(str_contains(strtolower($dAcc->name), 'merchant')) 🏪
                                            @elseif(str_contains(strtolower($dAcc->name), 'gopay')) 🟢
                                            @elseif(str_contains(strtolower($dAcc->name), 'shopeepay')) 🟠
                                            @else 🏦
                                            @endif
                                        </span>
                                        <span>{{ $dAcc->name }}</span>
                                    </span>
                                    <span class="text-xs text-slate-300">
                                        Tercatat di Sistem: <strong class="text-white font-black">Rp {{ number_format($dAcc->current_balance, 0, ',', '.') }}</strong>
                                    </span>
                                </div>

                                <input type="hidden" name="balances[{{ $idx }}][account_id]" value="{{ $dAcc->id }}">
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-slate-300 font-bold">Rp</span>
                                    <input 
                                        type="number" 
                                        inputmode="numeric" 
                                        pattern="[0-9]*" 
                                        name="balances[{{ $idx }}][actual_balance]" 
                                        id="recon-input-{{ $dAcc->id }}"
                                        value="{{ $dAcc->current_balance }}"
                                        data-recorded="{{ $dAcc->current_balance }}"
                                        data-account-name="{{ $dAcc->name }}"
                                        oninput="calculateDigitalReconDiff()"
                                        class="recon-digital-input w-full bg-slate-900 border border-slate-600 rounded-xl pl-9 pr-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    >
                                </div>
                                <div id="recon-diff-msg-{{ $dAcc->id }}" class="text-[11px] font-semibold text-slate-300">
                                    Status: Cocok Sempurna (Rp 0)
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-2.5 bg-slate-950/70 border border-slate-800 rounded-xl flex justify-between items-center text-xs">
                        <span class="text-slate-300 font-bold">Total Uang di Wadah Narik:</span>
                        <strong id="disp-wallets-total" class="text-emerald-400 font-extrabold text-sm">Rp 0</strong>
                    </div>
                </div>

                {{-- BAGIAN 3: HASIL COMPARE LIVE (REAL-TIME KOMPARASI) --}}
                <div id="recon-compare-box" class="bg-gradient-to-br from-slate-950 to-purple-950/40 border border-purple-500/50 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center space-x-2 border-b border-slate-800 pb-2">
                        <span class="text-lg">⚖️</span>
                        <h4 class="text-xs font-black uppercase tracking-wider text-purple-300">Hasil Compare: ShopeeFood vs Posisi Wadah</h4>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block text-[10px]">Tercatat di Shopee:</span>
                            <strong id="compare-shopee-display" class="text-orange-400 font-black text-xs">Rp 0</strong>
                        </div>
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block text-[10px]">Terkumpul di Wadah:</span>
                            <strong id="compare-wallets-display" class="text-emerald-400 font-black text-xs">Rp 0</strong>
                        </div>
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800 col-span-2 sm:col-span-1">
                            <span class="text-slate-400 block text-[10px]">Evaluasi Selisih:</span>
                            <strong id="compare-eval-display" class="text-emerald-300 font-black text-xs">Cocok (Rp 0)</strong>
                        </div>
                    </div>

                    <div id="compare-eval-banner" class="p-3 bg-purple-950/40 border border-purple-500/30 rounded-xl text-xs text-purple-200 leading-relaxed font-medium">
                        Ketikkan angka pendapatan ShopeeFood dan saldo wadah di atas untuk melihat perbandingan secara langsung.
                    </div>
                </div>

                {{-- Catatan Checkpoint --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Catatan Checkpoint (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        placeholder="Misal: Cek jam 21.00 setelah orderan ke-10 di Suhat" 
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none"
                    >
                </div>

                <div class="flex justify-end space-x-2.5 pt-3 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-reconcile-digital')" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-black rounded-xl shadow flex items-center space-x-1.5">
                        <span>💾</span>
                        <span>Simpan Checkpoint & Sinkronkan Saldo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL TAMBAH TARGET BELANJA / WISHLIST ================= --}}
    <div id="modal-add-goal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-amber-500/40 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">🎯</span>
                <div>
                    <h3 class="text-base font-bold text-white">Tambah Target Belanja & Wishlist</h3>
                    <p class="text-xs text-slate-300 font-medium">Tentukan target kebutuhan dan skala prioritasnya.</p>
                </div>
            </div>

            <form method="POST" action="/operational/goals" class="space-y-3.5 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Target Belanja <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Ganti Oli Mesin Honda MPX2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Skala Prioritas (Needs vs Wants) <span class="text-rose-400">*</span></label>
                    <select name="priority_level" id="goal-priority-select" onchange="syncGoalCategory(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="1">Level 1 - 🍚 Pangan Pokok (Beras, Telur, Tempe, Ayam)</option>
                        <option value="2">Level 2 - 🧼 Kebersihan & Mandi (Sabun, Sampo, Cuci Piring)</option>
                        <option value="3" selected>Level 3 - 🛵 Nyawa Narik (Ganti Oli Mesin, Phone Holder, Kuota)</option>
                        <option value="4">Level 4 - 🔧 Servis Berkala Motor (CVT, Kampas Rem, Ban)</option>
                        <option value="5">Level 5 - 🍦 Keinginan Santai / Jajan (Es Krim, Cilok, Nongkrong)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Kategori Belanja <span class="text-rose-400">*</span></label>
                    <select name="category" id="goal-category-select" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="work_essential" selected>🛵 Perlengkapan Kerja Narik (Oli / Holder / Kuota)</option>
                        <option value="pangan">🍚 Pangan Pokok & Makanan Mentah</option>
                        <option value="hygiene">🧼 Kebersihan & Perawatan Tubuh</option>
                        <option value="maintenance">🔧 Servis Motor Berkala</option>
                        <option value="wants">🍦 Keinginan / Jajan Bebas</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Target Nominal (Rp) <span class="text-rose-400">*</span></label>
                        <input type="number" inputmode="numeric" name="target_amount" required min="1" step="1" placeholder="55000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Target Tanggal</label>
                        <input type="date" name="target_date" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Catatan Tambahan</label>
                    <input type="text" name="notes" placeholder="Contoh: MPX2 0.8L untuk Vario / Beat" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-add-goal')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL ALOKASI DANA CUSTOM KE TARGET ================= --}}
    <div id="modal-allocate-goal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-amber-500/40 max-w-sm w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">💰</span>
                <div>
                    <h3 class="text-base font-bold text-white">Sisihkan Dana ke Target</h3>
                    <p id="allocate-goal-title" class="text-xs text-amber-300 font-semibold">-</p>
                </div>
            </div>

            <form id="form-allocate-goal" method="POST" action="" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal yang Disisihkan (Rp) <span class="text-rose-400">*</span></label>
                    <input type="number" inputmode="numeric" name="amount" id="allocate-goal-amount" required min="1" step="1" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-base font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    
                    <div class="flex space-x-1.5 mt-2">
                        <button type="button" onclick="document.getElementById('allocate-goal-amount').value = 5000" class="flex-1 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">5k</button>
                        <button type="button" onclick="document.getElementById('allocate-goal-amount').value = 10000" class="flex-1 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">10k</button>
                        <button type="button" onclick="document.getElementById('allocate-goal-amount').value = 20000" class="flex-1 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">20k</button>
                        <button type="button" onclick="document.getElementById('allocate-goal-amount').value = 50000" class="flex-1 py-1 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-lg">50k</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Catatan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Dari tip orderan siang" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-1.5 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-allocate-goal')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow">Sisihkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL REALISASI BELANJA TARGET (DI TOKO / BENGKEL) ================= --}}
    <div id="modal-achieve-goal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-emerald-500/40 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">🛒</span>
                <div>
                    <h3 class="text-base font-bold text-white">Realisasikan Belanja Target</h3>
                    <p id="achieve-goal-title" class="text-xs text-emerald-300 font-semibold">-</p>
                </div>
            </div>

            <p class="text-xs text-slate-300 font-medium mt-2">
                Tandai target ini sebagai selesai dibeli. Sistem akan otomatis memotong saldo rekening pembayar dan mencatat beban pengeluaran riil di pembukuan.
            </p>

            <form id="form-achieve-goal" method="POST" action="" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Sumber Dana (Bayar Pakai Apa?) <span class="text-rose-400">*</span></label>
                    <select name="account_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($accounts->filter(fn($a) => in_array($a->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])) as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Riil yang Dibayar di Kasir / Nota (Rp) <span class="text-rose-400">*</span></label>
                    <input type="number" inputmode="numeric" name="actual_spent" id="achieve-actual-spent" required min="1" step="1" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-[10px] text-slate-300 font-medium block mt-1">Bisa disesuaikan dengan harga riil di struk/nota toko.</span>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-achieve-goal')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">✓ Selesai & Catat Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL CATAT TAGIHAN UTANG BERJALAN (EXISTING DEBT) ================= --}}
    <div id="modal-existing-debt" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-purple-500/50 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">📝</span>
                <div>
                    <h3 class="text-base font-extrabold text-white">Catat Tagihan Utang Berjalan</h3>
                    <p class="text-xs text-slate-300">Masukkan tagihan PayLater/Pinjol yang sudah ada untuk target lunas bulan ini.</p>
                </div>
            </div>

            <form method="POST" action="/operational/existing-debt" class="space-y-3.5 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Platform Utang <span class="text-rose-400">*</span></label>
                    <select name="platform" onchange="toggleCustomPlatform(this, 'dashboard-existing-custom-platform-wrap')" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        @if(isset($paylaterProviders) && $paylaterProviders->isNotEmpty())
                            <optgroup label="🛍️ Wadah PayLater Terdaftar">
                                @foreach($paylaterProviders as $provider)
                                    <option value="{{ $provider->slug }}">{{ $provider->icon ?: '🛍️' }} {{ $provider->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="🏦 Pinjaman Tunai / Bank">
                            <option value="seabank_pinjam">🏦 SeaBank Pinjam</option>
                            <option value="spinjam">🟠 Shopee Pinjam (SPinjam)</option>
                            <option value="gopay_pinjam">🟢 GoPay Pinjam</option>
                            <option value="kredivo_pinjam">💰 Kredivo Pinjam</option>
                            <option value="bank">🏛️ Pinjaman Bank Umum</option>
                        </optgroup>
                        <option value="other">➕ Platform / Pinjaman Lainnya (Ketik Manual)</option>
                    </select>
                </div>

                <div id="dashboard-existing-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-purple-300 mb-1">Nama Platform Baru / Lainnya <span class="text-rose-400">*</span></label>
                    <input type="text" name="custom_platform" placeholder="Misal: Blibli PayLater / Indodana / Lainnya" class="w-full bg-slate-800 border border-purple-500/50 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama / Keterangan Tagihan <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Tagihan SPayLater Oktober / SeaBank Cicilan 2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Total Tagihan (Rp) <span class="text-rose-400">*</span></label>
                        <input type="number" inputmode="numeric" name="total_obligation" required min="1" step="1" placeholder="500000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" value="1" min="1" max="36" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-bold text-slate-300">Tanggal Jatuh Tempo <span class="text-rose-400">*</span></label>
                        <div class="space-x-1">
                            <button type="button" onclick="document.getElementById('existing-debt-due-date').value = '2026-10-31'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">31 Okt</button>
                            <button type="button" onclick="document.getElementById('existing-debt-due-date').value = '2026-10-25'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">25 Okt</button>
                            <button type="button" onclick="document.getElementById('existing-debt-due-date').value = '2026-10-20'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">20 Okt</button>
                            <button type="button" onclick="document.getElementById('existing-debt-due-date').value = '2026-11-05'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">5 Nov</button>
                        </div>
                    </div>
                    <input type="date" name="due_date" id="existing-debt-due-date" value="{{ now()->endOfMonth()->toDateString() }}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="p-3 bg-purple-950/40 border border-purple-500/30 rounded-xl text-[11px] text-purple-200">
                    💡 <strong>Tidak menambah saldo rekening:</strong> Tagihan ini murni dicatat sebagai beban kewajiban yang harus ditutup sebelum/pada tanggal jatuh tempo.
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-existing-debt')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded-xl shadow">Simpan Tagihan Hutang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL TAMBAH LANGGANAN / TAGIHAN RUTIN ================= --}}
    <div id="modal-add-subscription" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-indigo-500/50 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">📅</span>
                <div>
                    <h3 class="text-base font-extrabold text-white">Tambah Tagihan Rutin Bulanan</h3>
                    <p class="text-xs text-slate-300">Daftarkan paket data narik, kos, atau tagihan berulang.</p>
                </div>
            </div>

            <form method="POST" action="/operational/subscriptions" class="space-y-3.5 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Tagihan Rutin <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Paket Data Telkomsel Narik 50GB / Sewa Kamar Kos" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Kategori Beban <span class="text-rose-400">*</span></label>
                    <select name="category" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="work_essential" selected>🛵 Kuota / Perlengkapan Narik</option>
                        <option value="living_expense">🏠 Kos & Tempat Tinggal</option>
                        <option value="utilities">💡 Listrik / Air / WiFi</option>
                        <option value="entertainment">🍿 Hiburan / Langganan Lain</option>
                        <option value="other">📦 Rutin Lainnya</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nominal per Bulan (Rp) <span class="text-rose-400">*</span></label>
                        <input type="number" inputmode="numeric" name="amount" required min="1" step="1" placeholder="60000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Jatuh Tempo (Tiap Tgl) <span class="text-rose-400">*</span></label>
                        <input type="number" name="due_day_of_month" required min="1" max="31" value="20" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-add-subscription')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow">Daftarkan Tagihan Rutin</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL BAYAR LANGGANAN / TAGIHAN RUTIN ================= --}}
    <div id="modal-pay-subscription" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-indigo-500/50 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">💳</span>
                <div>
                    <h3 class="text-base font-extrabold text-white">Bayar Tagihan Rutin</h3>
                    <p id="pay-sub-title" class="text-xs text-indigo-300 font-semibold">-</p>
                </div>
            </div>

            <form id="form-pay-subscription" method="POST" action="" class="space-y-3.5 mt-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <input type="hidden" name="subscription_id" id="pay-sub-id" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Bayar Menggunakan Saldo Rekening <span class="text-rose-400">*</span></label>
                    <select name="payment_account_id" id="subscription-source-select" onchange="checkShopeePayCashback(this, 'subscription-cashback-wrap')" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($accounts->filter(fn($a) => in_array($a->type, [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])) as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Field Cashback ShopeePay (Muncul Dinamis Saat Memilih ShopeePay) --}}
                <div id="subscription-cashback-wrap" class="hidden p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-amber-300">🎁 Reward Cashback ShopeePay (Rp)</label>
                        <span class="text-[10px] text-slate-400">Bisa diinput Rp 0 jika tidak ada</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="setCashbackAmount('input-sub-cashback', 0)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300 rounded-md">Rp 0</button>
                        <button type="button" onclick="setCashbackAmount('input-sub-cashback', 1000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 1.000</button>
                        <button type="button" onclick="setCashbackAmount('input-sub-cashback', 2000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 2.000</button>
                        <button type="button" onclick="setCashbackAmount('input-sub-cashback', 3000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 3.000</button>
                        <button type="button" onclick="setCashbackAmount('input-sub-cashback', 5000)" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-[10px] text-amber-300 rounded-md">Rp 5.000</button>
                    </div>
                    <input type="number" name="cashback_amount" id="input-sub-cashback" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-amber-500/40 rounded-xl px-3 py-1.5 text-white text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <p class="text-[10px] text-amber-200/70">Cashback ini akan langsung menambah saldo ShopeePay Anda kembali dan dicatat sebagai pendapatan reward.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Pembayaran (Rp) <span class="text-rose-400">*</span></label>
                    <input type="number" inputmode="numeric" name="actual_amount" id="pay-sub-amount" required min="1" step="1" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Catatan / Bukti Bayar (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Paket 50GB masa aktif s/d 20 Nov" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-pay-subscription')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">
                        Bayar Tagihan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL PEREKAM FAST VOICE MEMO (INTERCOM HELM) --}}
    <div id="modal-voice-recorder" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
        <div class="bg-slate-900 border border-rose-500/50 rounded-2xl max-w-sm w-full p-6 space-y-5 text-center shadow-2xl relative">
            <button type="button" onclick="closeVoiceRecorderModal()" class="absolute top-4 right-4 text-slate-300 hover:text-white font-bold text-lg">✕</button>
            
            <div class="space-y-1">
                <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 text-xs font-bold border border-rose-500/30">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span>Kotak Hitam Helm &bull; Fast Memo</span>
                </span>
                <h3 class="text-base font-black text-white pt-1">Intercom Rekam Suara Cepat</h3>
                <p class="text-xs text-slate-300 font-medium">Rekam catatan pengeluaran, parkir liar, atau komplain COD di jalan.</p>
            </div>

            {{-- Status GPS --}}
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-2.5 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2">
                    <span class="text-sm">📍</span>
                    <span id="gps-status-text" class="text-slate-300 font-mono text-[11px]">Mencari koordinat GPS...</span>
                </div>
                <button type="button" onclick="detectGps()" class="text-xs text-emerald-400 hover:underline font-semibold">Refresh</button>
            </div>

            {{-- Timer & Gelombang Rekaman --}}
            <div class="py-4 space-y-2">
                <div id="recording-timer" class="text-4xl font-mono font-black text-white tracking-widest">
                    00:00
                </div>
                <div id="recording-pulse" class="flex justify-center items-center space-x-1 hidden">
                    <span class="w-1.5 h-6 bg-rose-500 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-10 bg-amber-400 rounded-full animate-bounce delay-75"></span>
                    <span class="w-1.5 h-4 bg-rose-400 rounded-full animate-bounce delay-150"></span>
                    <span class="w-1.5 h-8 bg-rose-600 rounded-full animate-bounce delay-200"></span>
                    <span class="w-1.5 h-5 bg-amber-500 rounded-full animate-bounce delay-100"></span>
                </div>
                <span id="record-status-label" class="text-xs text-slate-300 font-medium block">
                    Tekan tombol merah di bawah untuk mulai rekam
                </span>
            </div>

            {{-- Tombol Utama 1-Tap Record / Stop --}}
            <div id="section-record-action" class="flex flex-col items-center justify-center space-y-3">
                <button 
                    type="button" 
                    id="btn-record-toggle" 
                    onclick="toggleVoiceRecording()" 
                    class="w-20 h-20 rounded-full bg-gradient-to-tr from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white flex items-center justify-center shadow-xl shadow-rose-950/80 border-4 border-slate-800 transition transform active:scale-90"
                >
                    <svg id="icon-mic" class="w-9 h-9" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                    <svg id="icon-stop" class="w-8 h-8 hidden" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="6" width="12" height="12" rx="2" />
                    </svg>
                </button>
                <span class="text-[11px] text-slate-300 font-medium">Tap mikrofon untuk rekam, tap kotak untuk selesai & mendengarkan.</span>
            </div>

            {{-- Section Preview & Konfirmasi (Setelah Selesai Merekam) --}}
            <div id="section-preview-confirmation" class="space-y-4 hidden">
                <div class="bg-slate-950/90 border border-slate-800 rounded-xl p-3.5 space-y-2 text-left">
                    <span class="text-xs font-bold text-slate-200 block">🎧 Dengarkan Hasil Rekaman:</span>
                    <audio id="preview-audio-player" controls class="w-full h-8 rounded-lg accent-rose-500 focus:outline-none"></audio>
                </div>

                <div class="flex items-center space-x-2.5">
                    <button 
                        type="button" 
                        id="btn-retry-voice-memo"
                        onclick="retryVoiceRecording()" 
                        class="flex-1 py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition flex items-center justify-center space-x-1.5"
                    >
                        <span>🔄</span>
                        <span>Ulang Rekam</span>
                    </button>
                    <button 
                        type="button" 
                        id="btn-save-voice-memo"
                        onclick="confirmAndSaveVoiceMemo()" 
                        class="flex-1 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-950/50 transition flex items-center justify-center space-x-1.5"
                    >
                        <span>💾</span>
                        <span>Simpan Rekaman</span>
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-xs text-slate-300 font-medium">
                <span>Shift ID: #{{ $currentWorkDay ? $currentWorkDay->id : 'Belum Buka' }}</span>
                <button type="button" onclick="closeVoiceRecorderModal()" class="text-slate-300 hover:text-white font-bold">Tutup</button>
            </div>
        </div>
    </div>

    {{-- FLOATING ACTION BUTTON VOICE RECORDER (DESKTOP & MOBILE HIGH VISIBILITY) --}}
    <div class="fixed bottom-20 md:bottom-8 right-4 md:right-8 z-40">
        <button 
            type="button" 
            onclick="openVoiceRecorderModal()" 
            class="flex items-center space-x-2 bg-gradient-to-r from-rose-600 via-rose-500 to-amber-500 hover:from-rose-500 hover:to-amber-400 text-white font-extrabold px-4 py-3 rounded-full shadow-2xl shadow-rose-950/80 border-2 border-slate-900 group transition transform active:scale-95"
            title="Kotak Hitam Memo Suara Helm (Audio + GPS)"
        >
            <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
            </svg>
            <span class="text-xs tracking-wider uppercase font-black hidden sm:inline">Intercom Helm</span>
        </button>
    </div>

    {{-- Script Modal Handling, Tabs, & Interactive Tour --}}
    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                const sourceSelect = el.querySelector('select[name="source_account_id"], select[name="payment_account_id"]');
                const cashbackWrap = el.querySelector('[id$="-cashback-wrap"]');
                if (sourceSelect && cashbackWrap) {
                    checkShopeePayCashback(sourceSelect, cashbackWrap.id);
                }
                if (id === 'modal-reconcile-digital') {
                    const now = new Date();
                    const liveClock = document.getElementById('recon-live-clock');
                    const recordedInput = document.getElementById('recon-recorded-at');
                    if (liveClock) {
                        const day = String(now.getDate()).padStart(2, '0');
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        const month = months[now.getMonth()];
                        const year = now.getFullYear();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        liveClock.innerText = `${day} ${month} ${year}, ${hours}:${minutes} WIB`;
                    }
                    if (recordedInput) {
                        const year = now.getFullYear();
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const day = String(now.getDate()).padStart(2, '0');
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const seconds = String(now.getSeconds()).padStart(2, '0');
                        recordedInput.value = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                    }
                    calculateDigitalReconDiff();
                }
                if (id === 'modal-parking') {
                    detectParkingGps(false);
                }
            }
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function toggleCustomPlatform(selectEl, targetId) {
            const wrap = document.getElementById(targetId);
            if (!wrap) return;
            const isOther = selectEl.value === 'other' || selectEl.value === 'other_paylater';
            if (isOther) {
                wrap.classList.remove('hidden');
                const input = wrap.querySelector('input');
                if (input) input.focus();
            } else {
                wrap.classList.add('hidden');
            }
        }

        // Voice Recorder Logic (Web MediaRecorder, Geolocation, Review & Confirmation)
        let mediaRecorder = null;
        let audioChunks = [];
        let recordingTimer = null;
        let recordingSeconds = 0;
        let currentWorkDayId = {{ $currentWorkDay ? $currentWorkDay->id : 'null' }};
        let currentGpsCoords = { lat: null, lng: null };
        let recordedAudioBlob = null;
        let recordedMimeType = 'audio/webm';
        let previewObjectUrl = null;

        function openVoiceRecorderModal() {
            resetRecorderUI();
            openModal('modal-voice-recorder');
            detectGps();
        }

        function closeVoiceRecorderModal() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                if (confirm('Rekaman sedang berjalan. Ingin menghentikan dan membatalkan?')) {
                    mediaRecorder.stop();
                    clearInterval(recordingTimer);
                    resetRecorderUI();
                    closeModal('modal-voice-recorder');
                }
            } else {
                resetRecorderUI();
                closeModal('modal-voice-recorder');
            }
        }

        window.triggerVoiceRecorder = function() {
            openVoiceRecorderModal();
        };

        function openParkingModal() {
            openModal('modal-parking');
            detectParkingGps(false);
        }

        function detectParkingGps(isManual = false) {
            const latInput = document.getElementById('parking-lat');
            const lngInput = document.getElementById('parking-lng');
            const icon = document.getElementById('parking-gps-icon');
            const title = document.getElementById('parking-gps-title');
            const desc = document.getElementById('parking-gps-desc');

            if (!('geolocation' in navigator)) {
                if (title) title.innerText = 'GPS Tidak Didukung Browser';
                if (desc) {
                    desc.innerText = 'Browser perangkat tidak mendukung Geolocation. Tetap bisa disimpan tanpa GPS.';
                    desc.className = 'text-[10px] text-slate-400 font-medium';
                }
                return;
            }

            if (title) title.innerText = isManual ? 'Memperbarui GPS...' : 'Mencari Koordinat GPS...';
            if (desc) {
                desc.innerText = 'Sedang mengunci satelit GPS lokasi parkir Anda...';
                desc.className = 'text-[10px] text-amber-300 font-medium animate-pulse';
            }
            if (icon) icon.innerText = '🛰️';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude.toFixed(7);
                    const lng = pos.coords.longitude.toFixed(7);
                    const acc = Math.round(pos.coords.accuracy);

                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;

                    if (title) title.innerText = 'GPS Terkunci: ' + lat + ', ' + lng;
                    if (desc) {
                        desc.innerText = 'Akurasi GPS ~' + acc + 'm (Tersimpan otomatis saat Simpan Parkir)';
                        desc.className = 'text-[10px] text-emerald-400 font-bold';
                    }
                    if (icon) icon.innerText = '📍';
                },
                (err) => {
                    console.warn('Parking geolocation error:', err);
                    let errMsg = 'Sinyal GPS belum didapat atau izin lokasi belum aktif.';
                    if (err.code === 1) {
                        errMsg = 'Izin lokasi belum diberikan di browser.';
                    } else if (err.code === 2) {
                        errMsg = 'Posisi GPS tidak dapat ditentukan.';
                    } else if (err.code === 3) {
                        errMsg = 'Waktu pencarian GPS habis.';
                    }

                    if (title) title.innerText = 'GPS Opsional (Belum Terkunci)';
                    if (desc) {
                        desc.innerText = errMsg + ' Anda tetap dapat menyimpan parkir.';
                        desc.className = 'text-[10px] text-amber-400 font-medium';
                    }
                    if (icon) icon.innerText = '⚠️';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 8000,
                    maximumAge: 30000
                }
            );
        }

        function detectGps() {
            const statusEl = document.getElementById('gps-status-text');
            if (!('geolocation' in navigator)) {
                if (statusEl) statusEl.innerText = 'GPS tidak didukung browser';
                return;
            }
            if (statusEl) statusEl.innerText = 'Mencari sinyal GPS...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    currentGpsCoords.lat = pos.coords.latitude;
                    currentGpsCoords.lng = pos.coords.longitude;
                    if (statusEl) {
                        statusEl.innerText = 'GPS Terkunci: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4);
                        statusEl.classList.remove('text-slate-400');
                        statusEl.classList.add('text-emerald-400');
                    }
                },
                (err) => {
                    console.warn('Geolocation error:', err);
                    if (statusEl) statusEl.innerText = 'GPS: Opsional (Tidak Terkunci)';
                },
                { enableHighAccuracy: true, timeout: 6000 }
            );
        }

        async function toggleVoiceRecording() {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                await startVoiceRecording();
            } else if (mediaRecorder.state === 'recording') {
                stopVoiceRecording();
            }
        }

        async function startVoiceRecording() {
            if (!currentWorkDayId) {
                alert('Shift hari ini belum dibuka. Silakan buka shift narik terlebih dahulu.');
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = [];
                let mimeType = 'audio/webm';
                if (!MediaRecorder.isTypeSupported('audio/webm')) {
                    if (MediaRecorder.isTypeSupported('audio/mp4')) {
                        mimeType = 'audio/mp4';
                    } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
                        mimeType = 'audio/ogg';
                    }
                }

                mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };
                mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(t => t.stop());

                    // Buat audio blob dan preview URL
                    recordedMimeType = mimeType || 'audio/webm';
                    recordedAudioBlob = new Blob(audioChunks, { type: recordedMimeType });
                    if (previewObjectUrl) {
                        URL.revokeObjectURL(previewObjectUrl);
                    }
                    previewObjectUrl = URL.createObjectURL(recordedAudioBlob);

                    // Pasang ke preview audio player
                    const player = document.getElementById('preview-audio-player');
                    if (player) {
                        player.src = previewObjectUrl;
                        player.load();
                    }

                    // Tampilkan section konfirmasi & sembunyikan tombol rekam
                    document.getElementById('section-record-action').classList.add('hidden');
                    document.getElementById('section-preview-confirmation').classList.remove('hidden');
                    document.getElementById('record-status-label').innerText = '🎧 Selesai! Putar rekaman Anda di bawah:';
                };

                mediaRecorder.start();
                recordingSeconds = 0;
                document.getElementById('recording-timer').innerText = '00:00';
                document.getElementById('icon-mic').classList.add('hidden');
                document.getElementById('icon-stop').classList.remove('hidden');
                document.getElementById('btn-record-toggle').classList.replace('from-rose-600', 'from-amber-600');
                document.getElementById('btn-record-toggle').classList.replace('to-rose-500', 'to-amber-500');
                document.getElementById('recording-pulse').classList.remove('hidden');
                document.getElementById('record-status-label').innerText = '🔴 Merekam suara helm... Tap kotak untuk berhenti';

                recordingTimer = setInterval(() => {
                    recordingSeconds++;
                    const mins = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
                    const secs = String(recordingSeconds % 60).padStart(2, '0');
                    document.getElementById('recording-timer').innerText = mins + ':' + secs;
                }, 1000);
            } catch (err) {
                alert('Gagal mengakses mikrofon: ' + err.message + '\nIzinkan mikrofon di pengaturan browser Anda.');
            }
        }

        function stopVoiceRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                clearInterval(recordingTimer);
                document.getElementById('recording-pulse').classList.add('hidden');
                document.getElementById('record-status-label').innerText = 'Menyiapkan pemutar pratinjau...';
                mediaRecorder.stop();
            }
        }

        function retryVoiceRecording() {
            const player = document.getElementById('preview-audio-player');
            if (player) {
                player.pause();
                player.removeAttribute('src');
            }
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }
            recordedAudioBlob = null;
            audioChunks = [];
            resetRecorderUI();
        }

        function resetRecorderUI() {
            document.getElementById('icon-mic').classList.remove('hidden');
            document.getElementById('icon-stop').classList.add('hidden');
            document.getElementById('btn-record-toggle').classList.replace('from-amber-600', 'from-rose-600');
            document.getElementById('btn-record-toggle').classList.replace('to-amber-500', 'to-rose-500');
            document.getElementById('recording-timer').innerText = '00:00';
            document.getElementById('recording-pulse').classList.add('hidden');
            document.getElementById('record-status-label').innerText = 'Tekan tombol merah di bawah untuk mulai rekam';

            document.getElementById('section-record-action').classList.remove('hidden');
            document.getElementById('section-preview-confirmation').classList.add('hidden');

            const player = document.getElementById('preview-audio-player');
            if (player) {
                player.pause();
                player.removeAttribute('src');
            }
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }
            recordedAudioBlob = null;
            audioChunks = [];
            recordingSeconds = 0;

            const btnSave = document.getElementById('btn-save-voice-memo');
            const btnRetry = document.getElementById('btn-retry-voice-memo');
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<span>💾</span><span>Simpan Rekaman</span>';
            }
            if (btnRetry) btnRetry.disabled = false;
        }

        async function confirmAndSaveVoiceMemo() {
            if (!recordedAudioBlob) {
                alert('Tidak ada audio rekaman untuk disimpan.');
                return;
            }

            const btnSave = document.getElementById('btn-save-voice-memo');
            const btnRetry = document.getElementById('btn-retry-voice-memo');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<span>⏳</span><span>Menyimpan...</span>';
            }
            if (btnRetry) btnRetry.disabled = true;

            document.getElementById('record-status-label').innerText = '⏳ Mengunggah ke Kotak Hitam...';

            const ext = recordedMimeType.includes('mp4') ? 'mp4' : (recordedMimeType.includes('ogg') ? 'ogg' : 'webm');
            const formData = new FormData();
            formData.append('work_day_id', currentWorkDayId);
            formData.append('audio_file', recordedAudioBlob, `memo_${Date.now()}.${ext}`);
            formData.append('duration_seconds', recordingSeconds);
            if (currentGpsCoords.lat && currentGpsCoords.lng) {
                formData.append('latitude', currentGpsCoords.lat);
                formData.append('longitude', currentGpsCoords.lng);
            }

            try {
                const response = await fetch('/operational/voice-memo', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const resData = await response.json();
                if (response.ok) {
                    document.getElementById('record-status-label').innerText = '✓ Berhasil disimpan ke Kotak Hitam!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    alert('Gagal upload: ' + (resData.message || 'Terjadi kesalahan'));
                    if (btnSave) {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<span>💾</span><span>Simpan Rekaman</span>';
                    }
                    if (btnRetry) btnRetry.disabled = false;
                    document.getElementById('record-status-label').innerText = 'Gagal menyimpan. Silakan coba lagi.';
                }
            } catch (err) {
                alert('Kesalahan jaringan: ' + err.message);
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<span>💾</span><span>Simpan Rekaman</span>';
                }
                if (btnRetry) btnRetry.disabled = false;
                document.getElementById('record-status-label').innerText = 'Kesalahan jaringan. Silakan coba lagi.';
            }
        }

        // Kalkulator Denominasi Modal Awal Akun
        const initDenoms = {
            'init_bill_100000': 100000,
            'init_bill_50000': 50000,
            'init_bill_20000': 20000,
            'init_bill_10000': 10000,
            'init_bill_5000': 5000,
            'init_bill_2000': 2000,
            'init_bill_1000': 1000,
            'init_coin_1000': 1000,
            'init_coin_500': 500,
            'init_coin_200': 200,
            'init_coin_100': 100
        };

        function calculateInitDenomTotal() {
            let total = 0;
            let pieces = 0;
            for (const [id, value] of Object.entries(initDenoms)) {
                const el = document.getElementById(id);
                if (el) {
                    const count = parseInt(el.value) || 0;
                    total += count * value;
                    pieces += count;
                }
            }
            const totalEl = document.getElementById('init-denom-total');
            const piecesEl = document.getElementById('init-denom-pieces');
            const amountInput = document.getElementById('init_account_amount');
            if (totalEl) totalEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            if (piecesEl) piecesEl.innerText = pieces + ' lembar/koin';
            if (total > 0 && amountInput) {
                amountInput.value = total;
            }
        }

        document.querySelectorAll('.init-denom-input').forEach(input => {
            input.addEventListener('input', calculateInitDenomTotal);
        });

        const initAccSelect = document.getElementById('init_account_select');
        const initDenomSection = document.getElementById('init-denom-section');
        function checkInitAccountType() {
            if (!initAccSelect || !initDenomSection) return;
            const opt = initAccSelect.options[initAccSelect.selectedIndex];
            const type = opt ? opt.getAttribute('data-type') : '';
            if (type === 'cash') {
                initDenomSection.classList.remove('hidden');
            } else {
                initDenomSection.classList.add('hidden');
            }
        }
        if (initAccSelect) {
            initAccSelect.addEventListener('change', checkInitAccountType);
            checkInitAccountType();
        }

        function setParkingAmount(amount) {
            const input = document.getElementById('input-parking-amount');
            if (input) {
                input.value = amount;
            }
            document.querySelectorAll('.parking-preset-btn').forEach(btn => {
                if (btn.innerText.includes(amount.toLocaleString('id-ID'))) {
                    btn.classList.add('border-emerald-500', 'text-emerald-300');
                    btn.classList.remove('border-slate-700', 'text-slate-200');
                } else {
                    btn.classList.remove('border-emerald-500', 'text-emerald-300');
                    btn.classList.add('border-slate-700', 'text-slate-200');
                }
            });
        }

        function onVehicleTypeChange(type) {
            const chk = document.getElementById('chk-covered');
            if (chk && chk.checked) return;
            if (type === 'mobil') {
                setParkingAmount(5000);
            } else {
                setParkingAmount(2000);
            }
        }

        function toggleParkingAmount(chk) {
            const div = document.getElementById('div-parking-amount');
            const input = document.getElementById('input-parking-amount');
            if (chk.checked) {
                div.classList.add('opacity-40', 'pointer-events-none');
                input.value = 0;
            } else {
                div.classList.remove('opacity-40', 'pointer-events-none');
                const isMobil = document.querySelector('input[name="vehicle_type"]:checked')?.value === 'mobil';
                setParkingAmount(isMobil ? 5000 : 2000);
            }
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('border-emerald-500', 'text-emerald-400', 'border-indigo-500', 'text-indigo-400');
                el.classList.add('border-transparent', 'text-slate-300');
            });

            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.classList.remove('hidden');
                localStorage.setItem('dashboard_active_tab', tabId);
            }

            if (tabId === 'tab-onbeat') {
                const btn = document.getElementById('btn-tab-onbeat');
                if (btn) {
                    btn.classList.add('border-emerald-500', 'text-emerald-400');
                    btn.classList.remove('border-transparent', 'text-slate-300');
                }
            } else {
                const btn = document.getElementById('btn-tab-daily');
                if (btn) {
                    btn.classList.add('border-indigo-500', 'text-indigo-400');
                    btn.classList.remove('border-transparent', 'text-slate-300');
                }
            }
        }

        // Restore active tab upon page load or reload
        document.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash ? window.location.hash.substring(1) : null;
            const savedTab = hash || localStorage.getItem('dashboard_active_tab');
            if (savedTab && document.getElementById(savedTab)) {
                switchTab(savedTab);
            }
        });

        // ================= JAVASCRIPT LOGIKA KALKULATOR COD PINTAR =================
        let currentCompleteCod = {
            id: null,
            billAmount: 0,
            roundedAmount: 0,
            paymentMethod: 'cash'
        };

        function openAcceptCodModal() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lat = document.getElementById('accept-cod-lat');
                    const lng = document.getElementById('accept-cod-lng');
                    if (lat) lat.value = pos.coords.latitude;
                    if (lng) lng.value = pos.coords.longitude;
                }, () => {}, { timeout: 5000 });
            }
            openModal('modal-accept-cod');
        }

        function updateAcceptRounding() {
            const billInput = document.getElementById('accept-cod-bill');
            const bill = parseInt(billInput?.value) || 0;
            const rounded = bill > 0 ? (bill % 1000 === 0 ? bill : Math.ceil(bill / 1000) * 1000) : 0;
            const billDisp = document.getElementById('accept-bill-display');
            const roundDisp = document.getElementById('accept-rounded-display');
            const roundInput = document.getElementById('accept-cod-rounded');
            if (billDisp) billDisp.innerText = 'Rp ' + bill.toLocaleString('id-ID');
            if (roundDisp) roundDisp.innerText = 'Rp ' + rounded.toLocaleString('id-ID');
            if (roundInput) roundInput.value = rounded;
        }

        function openCompleteCodModal(id, code, merchant, billAmount, roundedAmount) {
            currentCompleteCod.id = id;
            currentCompleteCod.billAmount = parseInt(billAmount);
            currentCompleteCod.roundedAmount = parseInt(roundedAmount);
            currentCompleteCod.paymentMethod = 'cash';

            const form = document.getElementById('form-complete-cod');
            if (form) form.action = '/operational/cod-orders/' + id + '/complete';

            const codeEl = document.getElementById('calc-order-code');
            if (codeEl) codeEl.innerText = '#' + code;

            const merchEl = document.getElementById('calc-merchant-name');
            if (merchEl) merchEl.innerText = merchant || 'Resto ShopeeFood';

            const billLabel = document.getElementById('calc-bill-amount-label');
            if (billLabel) billLabel.innerText = 'Rp ' + currentCompleteCod.billAmount.toLocaleString('id-ID');

            const roundLabel = document.getElementById('calc-rounded-amount-label');
            if (roundLabel) roundLabel.innerText = 'Rp ' + currentCompleteCod.roundedAmount.toLocaleString('id-ID');

            // Set default gave amount = rounded amount
            const gaveInput = document.getElementById('calc-gave-amount');
            if (gaveInput) gaveInput.value = currentCompleteCod.roundedAmount;

            // Reset parkir
            const chkParkir = document.getElementById('chk-reimburse-parking');
            if (chkParkir) chkParkir.checked = false;
            toggleParkingReimburse(false);

            // Set payment method to cash by default
            setCodPaymentMethod('cash');

            // Recalculate
            onGaveAmountChanged();

            // Try get GPS
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lat = document.getElementById('complete-cod-lat');
                    const lng = document.getElementById('complete-cod-lng');
                    if (lat) lat.value = pos.coords.latitude;
                    if (lng) lng.value = pos.coords.longitude;
                }, () => {}, { timeout: 5000 });
            }

            openModal('modal-complete-cod');
        }

        function setCodPaymentMethod(method) {
            currentCompleteCod.paymentMethod = method;
            const methodInput = document.getElementById('calc-payment-method');
            if (methodInput) methodInput.value = method;

            const btnCash = document.getElementById('btn-method-cash');
            const btnQris = document.getElementById('btn-method-qris');
            const qrisSection = document.getElementById('calc-qris-section');
            const cashSection = document.getElementById('calc-cash-calculator-section');

            if (method === 'cash') {
                if (btnCash) {
                    btnCash.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');
                    btnCash.classList.remove('bg-slate-900', 'text-slate-300', 'border-slate-700');
                }
                if (btnQris) {
                    btnQris.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                    btnQris.classList.add('bg-slate-900', 'text-slate-300', 'border-slate-700');
                }
                if (qrisSection) qrisSection.classList.add('hidden');
                if (cashSection) cashSection.classList.remove('hidden');
            } else {
                if (btnQris) {
                    btnQris.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');
                    btnQris.classList.remove('bg-slate-900', 'text-slate-300', 'border-slate-700');
                }
                if (btnCash) {
                    btnCash.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                    btnCash.classList.add('bg-slate-900', 'text-slate-300', 'border-slate-700');
                }
                if (qrisSection) qrisSection.classList.remove('hidden');
                if (cashSection) cashSection.classList.add('hidden');
                const qrisAmountInput = document.getElementById('calc-qris-amount');
                if (qrisAmountInput && !qrisAmountInput.value) {
                    qrisAmountInput.value = currentCompleteCod.roundedAmount;
                }
            }
            recalculateCodAmounts();
        }

        function onGaveAmountChanged() {
            const gaveInput = document.getElementById('calc-gave-amount');
            const gave = parseInt(gaveInput?.value) || 0;
            const stdChange = Math.max(0, gave - currentCompleteCod.roundedAmount);

            const stdChangeDisp = document.getElementById('calc-std-change-display');
            if (stdChangeDisp) stdChangeDisp.innerText = 'Rp ' + stdChange.toLocaleString('id-ID');

            // Default change returned = stdChange
            const changeInput = document.getElementById('calc-change-returned');
            if (changeInput) changeInput.value = stdChange;

            recalculateCodAmounts();
        }

        function setGavePreset(amount) {
            const gaveInput = document.getElementById('calc-gave-amount');
            if (gaveInput) {
                gaveInput.value = amount === 'pas' ? currentCompleteCod.roundedAmount : amount;
                onGaveAmountChanged();
            }
        }

        function setChangePreset(amount) {
            const changeInput = document.getElementById('calc-change-returned');
            const gaveInput = document.getElementById('calc-gave-amount');
            const gave = parseInt(gaveInput?.value) || 0;
            const stdChange = Math.max(0, gave - currentCompleteCod.roundedAmount);

            if (changeInput) {
                if (amount === 'full') {
                    changeInput.value = stdChange;
                } else {
                    changeInput.value = amount;
                }
                recalculateCodAmounts();
            }
        }

        function toggleParkingReimburse(show) {
            // Uang parkir dicatat mandiri melalui tombol Parkir & kode pesanan
        }

        function recalculateCodAmounts() {
            let received = 0;
            if (currentCompleteCod.paymentMethod === 'cash') {
                const gave = parseInt(document.getElementById('calc-gave-amount')?.value) || 0;
                const returned = parseInt(document.getElementById('calc-change-returned')?.value) || 0;
                received = Math.max(0, gave - returned);
            } else {
                received = parseInt(document.getElementById('calc-qris-amount')?.value) || 0;
            }

            const bill = currentCompleteCod.billAmount;
            const tip = Math.max(0, received - bill);

            // Update displays
            const netReceivedInput = document.getElementById('input-final-received-amount');
            if (netReceivedInput) netReceivedInput.value = received;

            const netReceivedDisp = document.getElementById('calc-net-received-display');
            if (netReceivedDisp) netReceivedDisp.innerText = 'Rp ' + received.toLocaleString('id-ID');

            const breakdownShopee = document.getElementById('calc-breakdown-shopee');
            if (breakdownShopee) breakdownShopee.innerText = 'Rp ' + bill.toLocaleString('id-ID');

            const breakdownSurplus = document.getElementById('calc-breakdown-surplus');
            if (breakdownSurplus) breakdownSurplus.innerText = 'Rp ' + tip.toLocaleString('id-ID');
        }

        function openCancelCodModal(id, code) {
            const form = document.getElementById('form-cancel-cod');
            if (form) form.action = '/operational/cod-orders/' + id + '/cancel';
            const codeEl = document.getElementById('cancel-cod-code');
            if (codeEl) codeEl.innerText = '#' + code;
            openModal('modal-cancel-cod');
        }

        function openEditTxModal(id, description, amount, time) {
            const form = document.getElementById('form-edit-tx');
            if (form) form.action = '/operational/transactions/' + id + '/edit-description';

            const descInput = document.getElementById('edit-tx-description');
            if (descInput) descInput.value = description;

            const amountEl = document.getElementById('edit-tx-amount');
            if (amountEl) amountEl.innerText = amount;

            const timeEl = document.getElementById('edit-tx-time');
            if (timeEl) timeEl.innerText = time;

            openModal('modal-edit-tx');
        }

        // ================= JAVASCRIPT LOGIKA DIGITAL REKONSILIASI & SURVIVAL GOALS =================
        function calculateDigitalReconDiff() {
            // 1. Hitung Total ShopeeFood Driver
            const hubIncome = parseInt(document.getElementById('recon-hub-income')?.value) || 0;
            const hubBonus = parseInt(document.getElementById('recon-hub-bonus')?.value) || 0;
            const customerTips = parseInt(document.getElementById('recon-customer-tips')?.value) || 0;
            const completedOrders = parseInt(document.getElementById('recon-completed-orders')?.value) || 0;
            const totalShopee = hubIncome + hubBonus + customerTips;

            const dispShopee = document.getElementById('disp-shopee-total');
            if (dispShopee) dispShopee.innerText = 'Rp ' + totalShopee.toLocaleString('id-ID');

            const compareShopee = document.getElementById('compare-shopee-display');
            if (compareShopee) compareShopee.innerText = 'Rp ' + totalShopee.toLocaleString('id-ID');

            // 2. Hitung Saldo Wadah & Selisih vs Sistem
            let totalActualWallets = 0;
            let totalRecordedWallets = 0;
            let totalSurplus = 0;
            let totalShortage = 0;

            document.querySelectorAll('.recon-digital-input').forEach(input => {
                const recorded = parseInt(input.getAttribute('data-recorded')) || 0;
                const actual = parseInt(input.value) || 0;
                const diff = actual - recorded;
                const accId = input.id.replace('recon-input-', '');
                const msgEl = document.getElementById('recon-diff-msg-' + accId);

                totalActualWallets += actual;
                totalRecordedWallets += recorded;

                if (msgEl) {
                    if (diff > 0) {
                        msgEl.innerText = '🎉 Surplus / Inflow Lebih: +Rp ' + diff.toLocaleString('id-ID');
                        msgEl.className = 'text-[11px] font-bold mt-1 text-emerald-400';
                        totalSurplus += diff;
                    } else if (diff < 0) {
                        msgEl.innerText = '⚠️ Selisih Kurang / Biaya: -Rp ' + Math.abs(diff).toLocaleString('id-ID');
                        msgEl.className = 'text-[11px] font-bold mt-1 text-rose-400';
                        totalShortage += Math.abs(diff);
                    } else {
                        msgEl.innerText = '✓ Status: Saldo Cocok Sempurna (Rp 0)';
                        msgEl.className = 'text-[11px] font-semibold mt-1 text-slate-300';
                    }
                }
            });

            const dispWallets = document.getElementById('disp-wallets-total');
            if (dispWallets) dispWallets.innerText = 'Rp ' + totalActualWallets.toLocaleString('id-ID');

            const compareWallets = document.getElementById('compare-wallets-display');
            if (compareWallets) compareWallets.innerText = 'Rp ' + totalActualWallets.toLocaleString('id-ID');

            // 3. Evaluasi Live Comparison
            const netWalletAdjustment = totalActualWallets - totalRecordedWallets;
            const evalDisplay = document.getElementById('compare-eval-display');
            const evalBanner = document.getElementById('compare-eval-banner');

            if (evalDisplay) {
                if (netWalletAdjustment > 0) {
                    evalDisplay.innerText = 'Surplus Bersih +Rp ' + netWalletAdjustment.toLocaleString('id-ID');
                    evalDisplay.className = 'text-emerald-400 font-black text-xs';
                } else if (netWalletAdjustment < 0) {
                    evalDisplay.innerText = 'Selisih Kurang -Rp ' + Math.abs(netWalletAdjustment).toLocaleString('id-ID');
                    evalDisplay.className = 'text-rose-400 font-black text-xs';
                } else {
                    evalDisplay.innerText = 'Cocok Sempurna (Rp 0)';
                    evalDisplay.className = 'text-emerald-300 font-black text-xs';
                }
            }

            if (evalBanner) {
                if (netWalletAdjustment > 0) {
                    evalBanner.className = 'p-3 bg-emerald-950/40 border border-emerald-500/40 rounded-xl text-xs text-emerald-200 leading-relaxed font-medium';
                    evalBanner.innerHTML = `<strong>🎉 Terdeteksi Surplus Fisik/Digital: +Rp ${netWalletAdjustment.toLocaleString('id-ID')}</strong><br>` +
                        `ShopeeFood mencatat omzet <strong>Rp ${totalShopee.toLocaleString('id-ID')}</strong> (${completedOrders} trip). Di wadah Anda ada kelebihan uang Rp ${netWalletAdjustment.toLocaleString('id-ID')} dibanding pembukuan (kemungkinan tip tunai langsung dari pelanggan di lapangan atau QRIS lebih). Saat disimpan, sistem akan menyimpan snapshot ini dan menyesuaikan saldo wadah Anda secara akurat.`;
                } else if (netWalletAdjustment < 0) {
                    evalBanner.className = 'p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-xs text-rose-200 leading-relaxed font-medium';
                    evalBanner.innerHTML = `<strong>⚠️ Terdeteksi Selisih Kurang: -Rp ${Math.abs(netWalletAdjustment).toLocaleString('id-ID')}</strong><br>` +
                        `ShopeeFood mencatat omzet <strong>Rp ${totalShopee.toLocaleString('id-ID')}</strong> (${completedOrders} trip). Saldo wadah fisik/digital Anda lebih sedikit Rp ${Math.abs(netWalletAdjustment).toLocaleString('id-ID')} dibanding pembukuan sistem (kemungkinan ada pengeluaran operasional kecil di jalan seperti parkir tunai/makan yang belum sempat dicatat). Saat disimpan, saldo akan otomatis disejajarkan dengan posisi riil.`;
                } else {
                    evalBanner.className = 'p-3 bg-slate-900 border border-purple-500/30 rounded-xl text-xs text-purple-200 leading-relaxed font-medium';
                    evalBanner.innerHTML = `<strong>✓ Posisi Wadah & Pembukuan Cocok Sempurna!</strong><br>` +
                        `ShopeeFood mencatat omzet <strong>Rp ${totalShopee.toLocaleString('id-ID')}</strong> (${completedOrders} trip). Total di wadah narik Anda <strong>Rp ${totalActualWallets.toLocaleString('id-ID')}</strong>. Tidak ada selisih uang unaccounted. Checkpoint ini akan disimpan ke histori snapshot perjalanan hari ini.`;
                }
            }
        }

        function syncGoalCategory(priorityLevel) {
            const catSelect = document.getElementById('goal-category-select');
            if (!catSelect) return;
            switch(parseInt(priorityLevel)) {
                case 1:
                    catSelect.value = 'pangan';
                    break;
                case 2:
                    catSelect.value = 'hygiene';
                    break;
                case 3:
                    catSelect.value = 'work_essential';
                    break;
                case 4:
                    catSelect.value = 'maintenance';
                    break;
                case 5:
                    catSelect.value = 'wants';
                    break;
            }
        }

        function openAllocateGoalModal(id, name, remaining) {
            const form = document.getElementById('form-allocate-goal');
            if (form) form.action = '/operational/goals/' + id + '/allocate';

            const titleEl = document.getElementById('allocate-goal-title');
            if (titleEl) titleEl.innerText = name + (remaining > 0 ? ' (Kekurangan: Rp ' + remaining.toLocaleString('id-ID') + ')' : '');

            const inputAmount = document.getElementById('allocate-goal-amount');
            if (inputAmount) inputAmount.value = remaining > 0 ? Math.min(20000, remaining) : 10000;

            openModal('modal-allocate-goal');
        }

        function openAchieveGoalModal(id, name, targetAmount) {
            const form = document.getElementById('form-achieve-goal');
            if (form) form.action = '/operational/goals/' + id + '/achieve';

            const titleEl = document.getElementById('achieve-goal-title');
            if (titleEl) titleEl.innerText = name + ' (Target: Rp ' + targetAmount.toLocaleString('id-ID') + ')';

            const inputSpent = document.getElementById('achieve-actual-spent');
            if (inputSpent) inputSpent.value = targetAmount;

            openModal('modal-achieve-goal');
        }

        function openPayInstallmentModal(installmentId, debtName, amount) {
            const selectEl = document.getElementById('pay-installment-select');
            if (selectEl) {
                selectEl.value = installmentId;
            }
            const amountEl = document.getElementById('pay-installment-amount');
            if (amountEl) {
                amountEl.value = amount;
            }
            openModal('modal-pay-installment');
        }

        function openPaySubscriptionModal(subId, subName, amount) {
            const form = document.getElementById('form-pay-subscription');
            if (form) {
                form.action = '/operational/subscriptions/' + subId + '/pay';
            }
            const titleEl = document.getElementById('pay-sub-title');
            if (titleEl) {
                titleEl.innerText = subName + ' (Tagihan: Rp ' + amount.toLocaleString('id-ID') + ')';
            }
            const idInput = document.getElementById('pay-sub-id');
            if (idInput) {
                idInput.value = subId;
            }
            const amountInput = document.getElementById('pay-sub-amount');
            if (amountInput) {
                amountInput.value = amount;
            }
            openModal('modal-pay-subscription');
        }

        function toggleExpenseTenor(selectEl) {
            const tenorWrap = document.getElementById('expense-tenor-wrapper');
            if (!tenorWrap) return;
            if (selectEl.value && selectEl.value.startsWith('paylater_')) {
                tenorWrap.classList.remove('hidden');
            } else {
                tenorWrap.classList.add('hidden');
            }
        }

        function checkShopeePayCashback(selectEl, wrapperId) {
            const wrap = document.getElementById(wrapperId);
            if (!wrap || !selectEl) return;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            const text = selectedOption ? (selectedOption.textContent || selectedOption.innerText || '').toLowerCase() : '';
            if (text.includes('shopeepay')) {
                wrap.classList.remove('hidden');
            } else {
                wrap.classList.add('hidden');
                const input = wrap.querySelector('input[type="number"]');
                if (input) input.value = 0;
            }
        }

        function setCashbackAmount(inputId, amount) {
            const input = document.getElementById(inputId);
            if (input) {
                input.value = amount;
            }
        }

        // Interactive Tour menggunakan Driver.js
        function startDriverTour() {
            const driverObj = window.driver.js.driver({
                showProgress: true,
                animate: true,
                nextBtnText: 'Lanjut &rarr;',
                prevBtnText: '&larr; Kembali',
                doneBtnText: 'Selesai & Paham',
                steps: [
                    { 
                        element: '#tour-driver-summary', 
                        popover: { 
                            title: '🛵 Kinerja Driver ShopeeFood Hari Ini', 
                            description: 'Menampilkan uang riil hasil narik hari ini: Total Ongkir COD, Tip Bersih Customer, dikurangi Biaya Bensin dan Biaya Parkir.',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#tour-personal-summary', 
                        popover: { 
                            title: '☕ Keuangan Hidup & Pribadi Sehari-Hari', 
                            description: 'Memperlihatkan uang masuk dan keluar di luar pekerjaan driver: Sarapan pagi jam 7, belanja pribadi, pemasukan freelance/jual barang, dan bayar cicilan.',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#tour-safe-to-spend', 
                        popover: { 
                            title: '🛡️ Safe-to-Spend (Uang Aman Jajan)', 
                            description: 'Pilar psikologi finansial: Uang yang 100% aman Anda belanjakan tanpa merusak atau mengorbankan dana beras pokok, sabun, dan ganti oli motor.',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#tour-financial-goals', 
                        popover: { 
                            title: '🎯 Target Belanja Survival (Needs vs Wants)', 
                            description: 'Sisihkan bertahap uang receh narik (+10k / +20k) untuk beli oli MPX2, holder HP, atau beras. Saat dibeli, klik tombol Realisasikan untuk catat ke pembukuan.',
                            side: "top", 
                            align: 'center' 
                        } 
                    },
                    { 
                        element: '#btn-tab-onbeat', 
                        popover: { 
                            title: '🟢 Tab Mode On-Bid', 
                            description: 'Gunakan tab ini khusus saat sedang aktif narik (Buka Shift Modal, Catat COD, Beli Bensin, dan Parkir Motor).',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#btn-tab-daily', 
                        popover: { 
                            title: '☕ Tab Kebutuhan Sehari-Hari', 
                            description: 'Pusat aksi manusiawi: Catat makan & sarapan, belanja Shopee SPayLater, pinjam uang Bank/Pinjol, dan bayar cicilan bulanan.',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#tour-closing-btn', 
                        popover: { 
                            title: '🔒 Closing Kas Malam Hari', 
                            description: 'Setelah selesai shift narik, klik tombol ini untuk hitung fisik kas di dompet sampai selisihnya Rp0 (seimbang sempurna).',
                            side: "bottom", 
                            align: 'end' 
                        } 
                    },
                    { 
                        element: '#tour-accounts-grid', 
                        popover: { 
                            title: '🏦 Tambah Rekening & Sinkron Saldo Malam', 
                            description: 'Sinkronkan saldo digital (GoPay, ShopeeFood, BCA) dalam sekali snapshot dan terima otomatis tip QRIS yang masuk belakangan.',
                            side: "top", 
                            align: 'center' 
                        } 
                    }
                ]
            });

            driverObj.drive();
        }
    </script>
</x-layouts.app>
