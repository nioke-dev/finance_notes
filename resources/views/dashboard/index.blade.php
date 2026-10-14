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

        {{-- FILTER TANGGAL HARIAN & HEADER AKTIVITAS --}}
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">📅</span>
                <div>
                    <h3 class="text-sm font-bold text-white">Evaluasi & Filter Aktivitas Harian</h3>
                    <p class="text-xs text-slate-400">Pilih tanggal untuk melihat breakdown pendapatan narik & pengeluaran hari tersebut.</p>
                </div>
            </div>
            <form method="GET" action="/dashboard" class="flex items-center space-x-2 w-full sm:w-auto">
                <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl">Filter</button>
            </form>
        </div>

        {{-- 1. KOTAK KINERJA HARIAN DRIVER SHOPEEFOOD (Breakdown Pendapatan Bersih vs Kotor) --}}
        <div id="tour-driver-summary" class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-amber-500/30 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 pb-6 border-b border-slate-700/60">
                <div>
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-bold mb-2">
                        <span>🛵</span>
                        <span>Performa ShopeeFood Tanggal: {{ \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="flex items-baseline space-x-3">
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                            Rp {{ number_format($dayNetProfit, 0, ',', '.') }}
                        </h2>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                            Profit Bersih Narik (Netto)
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        (Pendapatan Kotor: Rp {{ number_format($dayTotalIncome, 0, ',', '.') }} &bull; Pengeluaran Operasional On-Beat: Rp {{ number_format($dayFuelExpense + $dayParkingExpense, 0, ',', '.') }})
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    @if($currentWorkDay)
                        <a id="tour-closing-btn" href="/closing/{{ $currentWorkDay->id }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs rounded-xl transition shadow flex items-center space-x-1.5">
                            <span>🔒</span>
                            <span>Closing Kas Shift Ini &rarr;</span>
                        </a>
                    @endif
                    <button type="button" onclick="startDriverTour()" class="px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition flex items-center space-x-1.5 shadow">
                        <span>✨</span>
                        <span>Panduan Animasi</span>
                    </button>
                </div>
            </div>

            {{-- Breakdown Grid 4 Metrik Utama --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 pt-6">
                {{-- Pendapatan Reguler --}}
                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3.5">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">1. Ongkir / Tagihan COD</span>
                    <span class="text-lg font-bold text-white mt-1 block">
                        Rp {{ number_format($dayRegularIncome, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-slate-500 block mt-0.5">Dari {{ $dayOrdersCount }} pesanan hari ini</span>
                </div>

                {{-- Tip Pelanggan --}}
                <div class="bg-slate-900/80 border border-emerald-500/20 rounded-xl p-3.5">
                    <span class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider block">2. Tip Bersih Pelanggan</span>
                    <span class="text-lg font-bold text-emerald-400 mt-1 block">
                        +Rp {{ number_format($dayTipIncome, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-slate-500 block mt-0.5">Surplus uang kembalian ikhlas</span>
                </div>

                {{-- Bensin Motor --}}
                <div class="bg-slate-900/80 border border-rose-500/20 rounded-xl p-3.5">
                    <span class="text-[11px] font-semibold text-rose-400 uppercase tracking-wider block">3. Biaya Bensin (BBM)</span>
                    <span class="text-lg font-bold text-rose-400 mt-1 block">
                        -Rp {{ number_format($dayFuelExpense, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-slate-500 block mt-0.5">Beban motor operasional</span>
                </div>

                {{-- Biaya Parkir & Resto Free --}}
                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3.5">
                    <span class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider block">4. Parkir Motor Driver</span>
                    <span class="text-lg font-bold text-amber-400 mt-1 block">
                        -Rp {{ number_format($dayParkingExpense, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-emerald-400 font-semibold block mt-0.5">
                        ✓ {{ $dayRestoPaidParkingCount }} resto gratis / ditanggung
                    </span>
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
                <button type="button" onclick="switchTab('tab-daily')" id="btn-tab-daily" class="tab-button px-5 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center space-x-2">
                    <span>☕</span>
                    <span>Kebutuhan Sehari-hari (Di Luar On-Bid)</span>
                </button>
            </div>

            {{-- 1. TAB KONTEN ON-BID DRIVER --}}
            <div id="tab-onbeat" class="tab-content py-4 space-y-4">
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Aksi Operasional Narik</h4>
                        <span class="text-[11px] text-slate-400">Aksi cepat saat sedang di jalan</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <button type="button" onclick="openModal('modal-open-day')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🌅</span>
                            <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Buka Shift Narik</span>
                            <span class="text-[10px] text-slate-400">Modal Kas Kembalian</span>
                        </button>

                        <button type="button" onclick="openModal('modal-cod')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">💵</span>
                            <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Pesanan COD</span>
                            <span class="text-[10px] text-slate-400">Tagihan & Tip Customer</span>
                        </button>

                        <button type="button" onclick="openModal('modal-fuel')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">⛽</span>
                            <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Beli Bensin</span>
                            <span class="text-[10px] text-slate-400">SPBU / Warung Madura</span>
                        </button>

                        <button type="button" onclick="openModal('modal-parking')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🅿️</span>
                            <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Parkir Motor</span>
                            <span class="text-[10px] text-slate-400">Mandiri / Ditanggung Resto</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. TAB KONTEN KEBUTUHAN SEHARI-HARI --}}
            <div id="tab-daily" class="tab-content py-4 space-y-4 hidden">
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Kebutuhan Hidup & Finansial Pribadi</h4>
                        <span class="text-[11px] text-slate-400">Makan pagi/malam, belanja dapur, transfer tabungan</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button type="button" onclick="openModal('modal-expense')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🍜</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Makan & Pengeluaran Pribadi</span>
                            <span class="text-[10px] text-slate-400">Sarapan, jajan, rokok, kebutuhan rumah</span>
                        </button>

                        <button type="button" onclick="openModal('modal-transfer')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🔄</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Pindah Uang / Tarik Tunai</span>
                            <span class="text-[10px] text-slate-400">Transfer antar BCA, ShopeePay, DANA</span>
                        </button>

                        <a href="/debts" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group block">
                            <span class="text-lg block mb-1">💳</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Bayar Cicilan / SPayLater</span>
                            <span class="text-[10px] text-slate-400">Kelola tagihan & utang teman</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan Total Kekayaan (Net Worth Card) --}}
        <div id="tour-net-worth" class="bg-gradient-to-br from-slate-800 to-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Kekayaan Bersih (Net Worth)</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-1">
                        Rp {{ number_format($netWorth, 0, ',', '.') }}
                    </h2>
                </div>
                <div class="flex gap-4 text-xs font-semibold">
                    <div class="bg-slate-900/60 border border-slate-700/50 px-3.5 py-2 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase">Aset Liquid (Kas + Bank)</span>
                        <span class="text-emerald-400 font-bold text-sm">Rp {{ number_format($totalDigital + $totalCash, 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-slate-900/60 border border-slate-700/50 px-3.5 py-2 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase">Total Utang Aktif</span>
                        <span class="text-rose-400 font-bold text-sm">Rp {{ number_format($totalDebt, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid Kartu Akun Driver & Inisialisasi Saldo Permanen --}}
        <div id="tour-accounts-grid">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Daftar Akun Keuangan Aktif</h3>
                    <p class="text-xs text-slate-500">Saldo modal awal hanya diatur sekali saat pendaftaran akun baru.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="openModal('modal-init-account')" class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition">
                        🔒 Kunci Saldo Awal Akun Baru
                    </button>
                    <button type="button" onclick="openModal('modal-correct-account')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700 text-xs font-semibold transition">
                        📝 Koreksi Audit Saldo
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($accounts as $acc)
                    @php $isLocked = $acc->initial_balance > 0; @endphp
                    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-4 flex items-center justify-between shadow hover:border-slate-600 transition">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider block">
                                    {{ $acc->name }}
                                </span>
                                @if($isLocked)
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold" title="Saldo Awal Sudah Terkunci Permanen">
                                        🔒 Locked
                                    </span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold" title="Belum Diinisialisasi">
                                        ⚠️ Unset
                                    </span>
                                @endif
                            </div>
                            <span class="text-lg font-bold text-white mt-1 block">
                                Rp {{ number_format($acc->current_balance, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-slate-500 block">
                                Modal Awal: Rp {{ number_format($acc->initial_balance, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-700/60 text-slate-300 uppercase">
                            {{ $acc->type->value ?? $acc->type }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Riwayat Transaksi Terbaru --}}
        @if($recentTransactions->isNotEmpty())
            <div id="tour-recent-tx" class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">15 Transaksi Terakhir</h3>
                <div class="divide-y divide-slate-700/50">
                    @foreach($recentTransactions as $tx)
                        <div class="py-3 flex justify-between items-center text-xs">
                            <div>
                                <span class="font-bold text-white block">{{ $tx->description }}</span>
                                <span class="text-[10px] text-slate-400">{{ $tx->transaction_time->format('d M Y H:i') }} &bull; Shift {{ $tx->workDay->work_date->format('d/m/Y') }}</span>
                            </div>
                            <span class="font-bold px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-200">
                                {{ strtoupper($tx->type) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- ================= MODALS SECTION ================= --}}

    {{-- 1. Modal Buka Shift & Modal Kas Shift --}}
    <div id="modal-open-day" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🌅 Buka Hari Kerja (Shift Baru)</h3>
            <p class="text-xs text-slate-400 mb-4">Membuka hari kerja baru dan menginput modal kas fisik di dompet untuk kembalian hari ini.</p>
            <form method="POST" action="/operational/open-day" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal Shift</label>
                    <input type="date" name="work_date" value="{{ now()->toDateString() }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Uang Tunai Fisik di Dompet (Shift Modal)</label>
                    <input type="number" name="cash_initial_balance" min="0" placeholder="Contoh: 100000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    <span class="text-[10px] text-slate-500">Uang pecahan fisik yang kamu bawa hari ini untuk uang kembalian COD.</span>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-open-day')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Buka Shift Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Modal Beli Bensin --}}
    <div id="modal-fuel" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">⛽ Catat Pembelian Bensin</h3>
            <p class="text-xs text-slate-400 mb-4">Pengeluaran bensin operasional motor driver.</p>
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
                    <select name="source_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $acc->name === 'Cash' ? 'selected' : '' }}>
                                {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Pembelian (Rp)</label>
                    <input type="number" name="amount" min="1000" step="1000" placeholder="25000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-fuel')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Bensin</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Modal Parkir Motor (Mendukung Parkir Mandiri vs Ditanggung Resto) --}}
    <div id="modal-parking" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🅿️ Catat Parkir Motor Driver</h3>
            <p class="text-xs text-slate-400 mb-4">Catat pengeluaran parkir atau resto yang menggratiskan parkir driver.</p>
            <form method="POST" action="/operational/parking" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Restoran / Lokasi</label>
                    <input type="text" name="restaurant_name" placeholder="Misal: Mie Gacoan / McD / Kober" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="p-3 bg-slate-900/80 border border-slate-700 rounded-xl">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_covered_by_resto" value="1" id="chk-covered" onchange="toggleParkingAmount(this)" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-emerald-400">Gratis / Ditanggung Restoran</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1 pl-6">Centang jika resto menyediakan parkir bebas biaya khusus driver (uang kas tidak akan dipotong).</p>
                </div>
                <div id="div-parking-amount">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Parkir Mandiri (Rp)</label>
                    <input type="number" name="amount" id="input-parking-amount" value="2000" min="0" step="500" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-parking')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Parkir</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. Modal Pesanan COD ShopeeFood --}}
    <div id="modal-cod" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">💵 Catat Orderan COD ShopeeFood</h3>
            <p class="text-xs text-slate-400 mb-4">Sistem otomatis mencatat pertambahan uang fisik di dompet & kelebihan tip jika ada.</p>
            <form method="POST" action="/operational/cod" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nomor Pesanan</label>
                    <input type="text" name="order_number" placeholder="Contoh: SPF-8921" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Total Tagihan COD (Rp)</label>
                    <input type="number" name="bill_amount" placeholder="Contoh: 45000" min="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Uang Tunai Fisik Diterima (Rp)</label>
                    <input type="number" name="received_amount" placeholder="Contoh: 50000" min="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    <span class="text-[10px] text-slate-500">Jika customer kasih lebih (misal tagihan 45rb bayar 50rb ikhlas), otomatis tercatat sebagai Tip Surplus Kas.</span>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-cod')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Order COD</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 5. Modal Transfer Antar Rekening --}}
    <div id="modal-transfer" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🔄 Transfer Saldo Antar Rekening</h3>
            <p class="text-xs text-slate-400 mb-4">Tarik tunai ATM ke dompet, atau top-up ShopeePay.</p>
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

    {{-- 6. Modal Pengeluaran Pribadi Sehari-hari --}}
    <div id="modal-expense" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🍜 Pengeluaran Sehari-hari / Pribadi</h3>
            <p class="text-xs text-slate-400 mb-4">Catat sarapan pagi, makan malam, kopi, belanja, atau kebutuhan rumah di luar operasional motor.</p>
            <form method="POST" action="/operational/personal-expense" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Keterangan Pengeluaran</label>
                    <input type="text" name="description" placeholder="Contoh: Sarapan pagi jam 7 sebelum narik" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Bayar Menggunakan</label>
                    <select name="source_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
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

    {{-- 7. Modal Inisialisasi Saldo Akun PERTAMA KALI (Sekali Seumur Hidup & Terkunci) --}}
    <div id="modal-init-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xl">🔒</span>
                <h3 class="text-base font-bold text-white">Inisialisasi Saldo Awal (Modal Tetap)</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4">
                Saldo awal ini menjadi titik awal (baseline) pembukuan akun Anda. Setelah dikunci, saldo ini tidak dapat diubah-ubah bebas agar laporan keuangan tetap presisi.
            </p>
            <form method="POST" action="/operational/set-initial-balance" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Akun yang Belum Dikunci</label>
                    <select name="account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts->where('initial_balance', 0) as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Saat ini: Rp 0)</option>
                        @endforeach
                        @if($accounts->where('initial_balance', 0)->isEmpty())
                            <option value="" disabled selected>Semua akun sudah memiliki saldo awal terkunci</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Saldo Modal Awal (Rp)</label>
                    <input type="number" name="amount" min="0" placeholder="Contoh: 500000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-[11px] text-amber-300">
                    ⚠️ <strong>Penting:</strong> Masukkan jumlah riil yang ada di rekening/dompet Anda saat mulai memakai aplikasi ini.
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-init-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">Kunci Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. Modal Koreksi Audit Khusus (Dengan Alasan Wajib Minimal 5 Karakter) --}}
    <div id="modal-correct-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xl">📝</span>
                <h3 class="text-base font-bold text-white">Koreksi Audit Saldo Awal</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4">
                Hanya digunakan jika terjadi salah ketik fatal pada saldo awal. Setiap perubahan akan disimpan permanen dalam **Audit Trail Log** demi integritas keuangan.
            </p>
            <form method="POST" action="/operational/set-initial-balance" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Akun yang Ingin Dikoreksi</label>
                    <select name="account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts as $acc)
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

    {{-- Script Modal Handling, Tabs, & Interactive Tour --}}
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function toggleParkingAmount(chk) {
            const div = document.getElementById('div-parking-amount');
            const input = document.getElementById('input-parking-amount');
            if (chk.checked) {
                div.classList.add('opacity-40', 'pointer-events-none');
                input.value = 0;
            } else {
                div.classList.remove('opacity-40', 'pointer-events-none');
                input.value = 2000;
            }
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('border-emerald-500', 'text-emerald-400');
                el.classList.add('border-transparent', 'text-slate-400');
            });

            document.getElementById(tabId).classList.remove('hidden');

            if (tabId === 'tab-onbeat') {
                const btn = document.getElementById('btn-tab-onbeat');
                btn.classList.add('border-emerald-500', 'text-emerald-400');
                btn.classList.remove('border-transparent', 'text-slate-400');
            } else {
                const btn = document.getElementById('btn-tab-daily');
                btn.classList.add('border-indigo-500', 'text-indigo-400');
                btn.classList.remove('border-transparent', 'text-slate-400');
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
                            title: '🛵 Kinerja & Profit Bersih Harian', 
                            description: 'Menampilkan pendapatan riil ShopeeFood harian Anda: Total Ongkir COD, Tip Bersih Customer, dikurangi Biaya Bensin & Parkir.',
                            side: "bottom", 
                            align: 'start' 
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
                            description: 'Pisahkan uang narik dengan kebutuhan hidup! Di sini tempat mencatat sarapan pagi jam 7, belanja pribadi, dan bayar tagihan PayLater.',
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
                            title: '🏦 Daftar Akun & Kunci Saldo Awal', 
                            description: 'Saldo modal awal diatur SEKALI saja saat pendaftaran akun baru agar tidak mengubah baseline keuangan.',
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
