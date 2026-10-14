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

        {{-- ACTION HUB: Panel Tombol Aksi Operasional Utama --}}
        <div id="tour-action-hub" class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-3 border-b border-slate-700/60">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>Action Hub: Input Operasional Driver</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pilih aksi untuk mencatat aktivitas keuangan secara presisi langsung dari web browser.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    {{-- Tombol Mulai Tour Interaktif --}}
                    <button type="button" onclick="startDriverTour()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition flex items-center space-x-1.5 shadow">
                        <span>✨</span>
                        <span>Mulai Panduan Interaktif</span>
                    </button>

                    @if($currentWorkDay)
                        <span class="text-xs font-semibold px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Shift: {{ $currentWorkDay->work_date->format('d M Y') }}
                        </span>
                        <a id="tour-closing-btn" href="/closing/{{ $currentWorkDay->id }}" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl transition shadow">
                            Closing Kas Shift Ini &rarr;
                        </a>
                    @else
                        <span class="text-xs font-semibold px-3 py-1 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            Belum Ada Shift Terbuka
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tombol Pemicu Modal Aksi --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <button id="tour-btn-open-day" type="button" onclick="openModal('modal-open-day')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">🌅</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Buka Shift</span>
                    <span class="text-[10px] text-slate-400">Modal Kas Shift</span>
                </button>

                <button id="tour-btn-fuel" type="button" onclick="openModal('modal-fuel')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">⛽</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Beli Bensin</span>
                    <span class="text-[10px] text-slate-400">SPBU / Madura</span>
                </button>

                <button id="tour-btn-parking" type="button" onclick="openModal('modal-parking')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">🅿️</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Parkir Motor</span>
                    <span class="text-[10px] text-slate-400">Cepat Rp2.000 / Resto</span>
                </button>

                <button id="tour-btn-cod" type="button" onclick="openModal('modal-cod')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">💵</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Pesanan COD</span>
                    <span class="text-[10px] text-slate-400">Tagihan & Uang Kas</span>
                </button>

                <button id="tour-btn-transfer" type="button" onclick="openModal('modal-transfer')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">🔄</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Transfer Saldo</span>
                    <span class="text-[10px] text-slate-400">Tarik Tunai / E-Wallet</span>
                </button>

                <button id="tour-btn-expense" type="button" onclick="openModal('modal-expense')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                    <span class="text-lg block mb-1">🍜</span>
                    <span class="text-xs font-bold text-white block group-hover:text-emerald-400">Beban Pribadi</span>
                    <span class="text-[10px] text-slate-400">Makan & Jajan Driver</span>
                </button>
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
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">10 Transaksi Terakhir</h3>
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

    {{-- 3. Modal Parkir Motor --}}
    <div id="modal-parking" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🅿️ Catat Parkir Motor</h3>
            <p class="text-xs text-slate-400 mb-4">Pengeluaran kas tunai fisik untuk parkir resto.</p>
            <form method="POST" action="/operational/parking" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Resto / Titik Parkir</label>
                    <input type="text" name="restaurant_name" placeholder="Misal: Mie Gacoan Ciliwung" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Parkir (Rp)</label>
                    <input type="number" name="amount" value="2000" min="500" step="500" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
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

    {{-- 6. Modal Pengeluaran Pribadi --}}
    <div id="modal-expense" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🍜 Pengeluaran Pribadi Driver</h3>
            <p class="text-xs text-slate-400 mb-4">Catat makan siang, kopi, rokok, atau kebutuhan pribadi saat narik.</p>
            <form method="POST" action="/operational/personal-expense" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Deskripsi / Keterangan</label>
                    <input type="text" name="description" placeholder="Makan siang nasi padang + es teh" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
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

    {{-- Script Modal Handling & Interactive Tour --}}
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
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
                        element: '#tour-action-hub', 
                        popover: { 
                            title: '🚀 Action Hub (Operasional)', 
                            description: 'Ini adalah pusat kendali input harian Anda. Seluruh aktivitas narik ShopeeFood (bensin, parkir, COD, makan) dicatat dari sini.',
                            side: "bottom", 
                            align: 'start' 
                        } 
                    },
                    { 
                        element: '#tour-btn-open-day', 
                        popover: { 
                            title: '🌅 1. Buka Shift Setiap Pagi', 
                            description: 'Sebelum mulai narik orderan, klik tombol ini untuk membuka shift hari ini dan menginput uang fisik di dompet (uang pecahan untuk kembalian COD).',
                            side: "bottom", 
                            align: 'center' 
                        } 
                    },
                    { 
                        element: '#tour-btn-fuel', 
                        popover: { 
                            title: '⛽ 2. Catat Beli Bensin', 
                            description: 'Saat isi bensin di SPBU atau Madura, langsung klik ini. Sistem otomatis menghitung liter bensin dan efisiensi konsumsi BBM motor Anda.',
                            side: "bottom", 
                            align: 'center' 
                        } 
                    },
                    { 
                        element: '#tour-btn-cod', 
                        popover: { 
                            title: '💵 3. Catat Orderan COD', 
                            description: 'Saat dapat orderan COD dari resto ke customer, catat di sini. Jika customer kasih tip berlebih (kembalian diikhlaskan), sistem otomatis memisahkannya sebagai bonus tip Anda.',
                            side: "bottom", 
                            align: 'center' 
                        } 
                    },
                    { 
                        element: '#tour-closing-btn', 
                        popover: { 
                            title: '🔒 4. Closing Kas Malam Hari', 
                            description: 'Setelah selesai narik, klik tombol ini untuk menghitung lembaran uang di dompet. Sistem akan mencocokkan uang fisik dengan catatan sampai selisihnya Rp0 (seimbang).',
                            side: "bottom", 
                            align: 'end' 
                        } 
                    },
                    { 
                        element: '#tour-net-worth', 
                        popover: { 
                            title: '💰 Total Kekayaan Bersih (Net Worth)', 
                            description: 'Kartu ini menghitung seluruh uang cair Anda (Kas Fisik + Rekening Bank + ShopeePay) dikurangi total utang/PayLater aktif.',
                            side: "top", 
                            align: 'center' 
                        } 
                    },
                    { 
                        element: '#tour-accounts-grid', 
                        popover: { 
                            title: '🏦 Daftar Akun & Kunci Saldo Awal', 
                            description: 'Saldo awal akun di sini hanya diatur SEKALI seumur hidup saat pertama kali menggunakan aplikasi (tombol hijau Kunci Saldo). Setelah terkunci (Locked), saldo terlindungi agar pembukuan tidak berantakan.',
                            side: "top", 
                            align: 'center' 
                        } 
                    }
                ]
            });

            driverObj.drive();
        }

        // Jalankan tour otomatis saat pertama kali dibuka jika belum pernah melihatnya
        window.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('driver_tour_completed')) {
                setTimeout(() => {
                    startDriverTour();
                    localStorage.setItem('driver_tour_completed', 'true');
                }, 800);
            }
        });
    </script>
</x-layouts.app>
