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
                    <p class="text-xs text-slate-400">Pilih tanggal untuk melihat perbandingan pendapatan driver vs keuangan pribadi hari tersebut.</p>
                </div>
            </div>
            <form method="GET" action="/dashboard" class="flex items-center space-x-2 w-full sm:w-auto">
                <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl">Filter</button>
            </form>
        </div>

        {{-- DUA KARTU KOMPARASI HARIAN: 1. OPERASIONAL DRIVER VS 2. KEUANGAN PRIBADI SEHARI-HARI --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- KARTU 1: KINERJA OPERASIONAL DRIVER SHOPEEFOOD --}}
            <div id="tour-driver-summary" class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-amber-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
                <div class="flex justify-between items-start border-b border-slate-700/60 pb-3">
                    <div>
                        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-bold mb-1">
                            <span>🛵</span>
                            <span>Operasional Driver ShopeeFood</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-white">
                            Rp {{ number_format($dayNetProfit, 0, ',', '.') }}
                        </h3>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">
                            Profit Bersih Narik (Netto)
                        </span>
                    </div>
                    @if($currentWorkDay)
                        <a id="tour-closing-btn" href="/closing/{{ $currentWorkDay->id }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs rounded-xl transition shadow flex items-center space-x-1">
                            <span>🔒</span>
                            <span>Closing Kas &rarr;</span>
                        </a>
                    @endif
                </div>

                {{-- Grid 4 Metrik Driver --}}
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                        <span class="text-slate-400 text-[10px] uppercase font-semibold block">Ongkir / Tagihan COD</span>
                        <span class="text-sm font-bold text-white block mt-0.5">Rp {{ number_format($dayRegularIncome, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">{{ $dayOrdersCount }} pesanan</span>
                    </div>

                    <div class="bg-slate-900/80 border border-emerald-500/20 rounded-xl p-3">
                        <span class="text-emerald-400 text-[10px] uppercase font-semibold block">Tip Bersih Pelanggan</span>
                        <span class="text-sm font-bold text-emerald-400 block mt-0.5">+Rp {{ number_format($dayTipIncome, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">Surplus kembalian ikhlas</span>
                    </div>

                    <div class="bg-slate-900/80 border border-rose-500/20 rounded-xl p-3">
                        <span class="text-rose-400 text-[10px] uppercase font-semibold block">Beban Bensin (BBM)</span>
                        <span class="text-sm font-bold text-rose-400 block mt-0.5">-Rp {{ number_format($dayFuelExpense, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">Operasional motor</span>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                        <span class="text-amber-400 text-[10px] uppercase font-semibold block">Parkir Motor Driver</span>
                        <span class="text-sm font-bold text-amber-400 block mt-0.5">-Rp {{ number_format($dayParkingExpense, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-emerald-400 font-semibold block">✓ {{ $dayRestoPaidParkingCount }} gratis/resto</span>
                    </div>
                </div>
            </div>

            {{-- KARTU 2: KEUANGAN PRIBADI & KEBUTUHAN SEHARI-HARI --}}
            <div id="tour-personal-summary" class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-indigo-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
                <div class="flex justify-between items-start border-b border-slate-700/60 pb-3">
                    <div>
                        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-xs font-bold mb-1">
                            <span>☕</span>
                            <span>Kebutuhan Hidup & Finansial Pribadi</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-white">
                            Rp {{ number_format($dayPersonalIncome - $dayPersonalExpense - $dayDebtRepayment, 0, ',', '.') }}
                        </h3>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">
                            Arus Kas Pribadi Hari Ini
                        </span>
                    </div>
                    <button type="button" onclick="startDriverTour()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition flex items-center space-x-1 shadow">
                        <span>✨</span>
                        <span>Panduan Audio & Animasi</span>
                    </button>
                </div>

                {{-- Grid 3 Metrik Pribadi --}}
                <div class="grid grid-cols-3 gap-3 text-xs">
                    <div class="bg-slate-900/80 border border-emerald-500/20 rounded-xl p-3">
                        <span class="text-emerald-400 text-[10px] uppercase font-semibold block">Pemasukan Pribadi</span>
                        <span class="text-sm font-bold text-emerald-400 block mt-0.5">+Rp {{ number_format($dayPersonalIncome, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">Jual barang, project, transfer</span>
                    </div>

                    <div class="bg-slate-900/80 border border-rose-500/20 rounded-xl p-3">
                        <span class="text-rose-400 text-[10px] uppercase font-semibold block">Makan & Belanja</span>
                        <span class="text-sm font-bold text-rose-400 block mt-0.5">-Rp {{ number_format($dayPersonalExpense, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">Sarapan, jajan, kebutuhan</span>
                    </div>

                    <div class="bg-slate-900/80 border border-amber-500/20 rounded-xl p-3">
                        <span class="text-amber-400 text-[10px] uppercase font-semibold block">Bayar Cicilan</span>
                        <span class="text-sm font-bold text-amber-400 block mt-0.5">-Rp {{ number_format($dayDebtRepayment, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-500 block">SPayLater & Pinjaman</span>
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
                <button type="button" onclick="switchTab('tab-daily')" id="btn-tab-daily" class="tab-button px-5 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-200 flex items-center space-x-2">
                    <span>☕</span>
                    <span>Kebutuhan Sehari-hari (Di Luar On-Bid)</span>
                </button>
            </div>

            {{-- 1. TAB KONTEN ON-BID DRIVER --}}
            <div id="tab-onbeat" class="tab-content py-4 space-y-4">
                {{-- Shift Bar & Closing Status --}}
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 shadow-xl">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-xl">
                            🛵
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Status Shift Hari Ini</span>
                                @if($currentWorkDay)
                                    <x-status-badge :status="$currentWorkDay->status" />
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300 font-bold">Belum Ada Shift</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400">
                                @if($currentWorkDay)
                                    Shift {{ $currentWorkDay->work_date->translatedFormat('d M Y') }} &bull; Dibuka {{ $currentWorkDay->opened_at ? $currentWorkDay->opened_at->format('H:i') : '-' }} WIB
                                @else
                                    Buka shift terlebih dahulu untuk mulai menghitung uang modal kas kembalian.
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        @if($currentWorkDay && $currentWorkDay->needsReconciliation())
                            <a href="/closing/{{ $currentWorkDay->id }}" id="tour-closing-btn" class="w-full sm:w-auto px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl shadow flex items-center justify-center space-x-1.5 transition">
                                <span>🔒</span>
                                <span>Audit & Closing Kas Malam Ini</span>
                            </a>
                        @endif
                        <button type="button" onclick="openModal('modal-open-day')" class="w-full sm:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center justify-center space-x-1.5 transition">
                            <span>🌅</span>
                            <span>{{ $currentWorkDay ? 'Update / Buka Shift' : 'Buka Shift Narik' }}</span>
                        </button>
                    </div>
                </div>

                {{-- Alert Rekomendasi Beban Lembaran / Koin Berlebih & Warning Kembalian --}}
                @if(!empty($cashRecommendations))
                    <div class="space-y-2.5">
                        @foreach($cashRecommendations as $rec)
                            <div class="p-4 rounded-2xl border text-xs flex items-start space-x-3 shadow-lg {{ $rec['type'] === 'excess' ? 'bg-amber-500/10 border-amber-500/30 text-amber-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-300' }}">
                                <span class="text-2xl mt-0.5">{{ $rec['type'] === 'excess' ? '⚠️' : '💡' }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-0.5">
                                        <span class="font-extrabold uppercase tracking-wider text-[11px] {{ $rec['type'] === 'excess' ? 'text-amber-400' : 'text-rose-400' }}">
                                            {{ $rec['type'] === 'excess' ? 'Beban Lembaran/Koin Berlebih di Dompet' : 'Peringatan Pecahan Kembalian COD Menipis' }}
                                        </span>
                                        <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-slate-900/60 border border-slate-700 text-slate-300">
                                            {{ $rec['count'] }} pcs
                                        </span>
                                    </div>
                                    <p class="text-slate-200 leading-relaxed font-medium">{{ $rec['message'] }}</p>
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
                                <span class="text-[11px] text-slate-400 block mt-0.5">
                                    Shift: <strong class="text-slate-200">{{ $cashDenomination->workDay?->work_date ? $cashDenomination->workDay->work_date->translatedFormat('l, d F Y') : 'Terakhir' }}</strong> &bull; Diperbarui setiap malam closingan
                                </span>
                            </div>
                            <div class="text-left sm:text-right bg-slate-900/80 border border-slate-700/60 rounded-xl px-3 py-1.5">
                                <span class="text-xs text-slate-400">Total Fisik:</span>
                                <span class="text-sm font-extrabold text-white ml-1">Rp {{ number_format($cashDenomination->total_physical_amount, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-slate-400 block sm:inline">({{ $cashDenomination->total_pieces }} lembar & koin)</span>
                            </div>
                        </div>

                        {{-- Uang Kertas --}}
                        <div class="mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Uang Kertas (Lembaran):</span>
                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                @foreach([100000 => '100k', 50000 => '50k', 20000 => '20k', 10000 => '10k', 5000 => '5k', 2000 => '2k', 1000 => '1k'] as $nom => $label)
                                    @php $field = 'bill_' . $nom; $count = $cashDenomination->$field ?? 0; @endphp
                                    <div class="bg-slate-900/80 border {{ $nom === 5000 ? 'border-amber-500/60 bg-amber-500/10' : ($count >= 20 ? 'border-amber-500/50 bg-amber-500/5' : 'border-slate-700/60') }} rounded-xl p-2 text-center">
                                        <span class="text-[10px] {{ $nom === 5000 ? 'text-amber-300 font-bold' : 'text-slate-400' }} block font-semibold">Rp{{ $label }}</span>
                                        <span class="text-xs font-extrabold {{ $nom === 5000 ? 'text-amber-400' : ($count >= 20 ? 'text-amber-400' : 'text-white') }}">{{ $count }} <span class="text-[9px] font-normal text-slate-400">lbr</span></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Uang Logam --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Uang Logam / Koin:</span>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach([1000 => '1.000', 500 => '500', 200 => '200', 100 => '100'] as $nom => $label)
                                    @php $field = 'coin_' . $nom; $count = $cashDenomination->$field ?? 0; @endphp
                                    <div class="bg-slate-900/80 border {{ $count >= 15 ? 'border-amber-500/50 bg-amber-500/5' : 'border-slate-700/60' }} rounded-xl p-2 text-center">
                                        <span class="text-[10px] text-slate-400 block font-semibold">Rp{{ $label }}</span>
                                        <span class="text-xs font-extrabold {{ $count >= 15 ? 'text-amber-400' : 'text-white' }}">{{ $count }} <span class="text-[9px] font-normal text-slate-400">koin</span></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl text-center">
                        <span class="text-3xl block mb-2">👛</span>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-1">Belum Ada Rekaman Fisik Pecahan Kas</h4>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">Jumlah lembar Rp5.000, Rp2.000, dst. akan otomatis muncul di sini setelah Anda melakukan <strong>Buka Shift</strong> atau <strong>Closing Kas Malam Hari</strong>.</p>
                    </div>
                @endif

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

            {{-- 2. TAB KONTEN KEBUTUHAN SEHARI-HARI LENGKAP --}}
            <div id="tab-daily" class="tab-content py-4 space-y-4 hidden">
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Kebutuhan Hidup & Finansial Pribadi Layaknya Manusia</h4>
                        <span class="text-[11px] text-slate-400">Belanja, sarapan, belanja Shopee SPayLater, pinjaman, transfer</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <button type="button" onclick="openModal('modal-expense')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🍜</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Makan & Pengeluaran</span>
                            <span class="text-[10px] text-slate-400">Sarapan, jajan, rokok</span>
                        </button>

                        <button type="button" onclick="openModal('modal-personal-income')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">💰</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Pemasukan Lain</span>
                            <span class="text-[10px] text-slate-400">Jual barang, project luar</span>
                        </button>

                        <button type="button" onclick="openModal('modal-spaylater-purchase')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🛍️</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Belanja SPayLater</span>
                            <span class="text-[10px] text-slate-400">Beli di Shopee cicil</span>
                        </button>

                        <button type="button" onclick="openModal('modal-apply-loan')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🏦</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Pinjam Uang / Bank</span>
                            <span class="text-[10px] text-slate-400">Pinjol / SeaBank / Teman</span>
                        </button>

                        <button type="button" onclick="openModal('modal-pay-installment')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">💳</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Bayar Cicilan</span>
                            <span class="text-[10px] text-slate-400">Lunasi tagihan berjalan</span>
                        </button>

                        <button type="button" onclick="openModal('modal-transfer')" class="p-3 bg-slate-900/60 hover:bg-slate-700/70 border border-slate-700 rounded-xl text-left transition group">
                            <span class="text-lg block mb-1">🔄</span>
                            <span class="text-xs font-bold text-white block group-hover:text-indigo-400">Transfer Saldo</span>
                            <span class="text-[10px] text-slate-400">Pindah rekening / ATM</span>
                        </button>
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

        {{-- Grid Kartu Akun Driver & Tambah Rekening / Dompet Baru --}}
        <div id="tour-accounts-grid">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Daftar Akun Keuangan & Rekening Bank</h3>
                    <p class="text-xs text-slate-500">Kelola dompet fisik, rekening bank baru (BCA, Mandiri, BRI), atau e-wallet (GoPay, DANA, OVO).</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="openModal('modal-add-account')" class="px-3 py-1.5 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 text-xs font-bold transition flex items-center space-x-1">
                        <span>➕</span>
                        <span>Tambah Rekening Baru</span>
                    </button>
                    <button type="button" onclick="openModal('modal-init-account')" class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition">
                        🔒 Kunci Saldo Awal
                    </button>
                    <button type="button" onclick="openModal('modal-correct-account')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700 text-xs font-semibold transition">
                        📝 Koreksi Audit
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($accounts as $acc)
                    @php $isLocked = $acc->initial_balance > 0; @endphp
                    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-4 flex flex-col justify-between shadow hover:border-slate-600 transition">
                        <div class="flex items-center justify-between w-full">
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

                        @if($acc->type === \App\Enums\AccountType::CASH)
                            <div class="mt-3 pt-2.5 border-t border-slate-700/60 w-full">
                                <button type="button" onclick="openModal('modal-view-cash-denomination')" class="w-full py-2 px-3 bg-slate-900/90 hover:bg-slate-700 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs font-bold flex items-center justify-between transition group shadow">
                                    <span class="flex items-center space-x-1.5">
                                        <span>👛</span>
                                        <span>Lihat Rincian Lembaran Kas</span>
                                    </span>
                                    <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Riwayat Transaksi Terbaru --}}
        @if($recentTransactions->isNotEmpty())
            <div id="tour-recent-tx" class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">20 Transaksi Terakhir</h3>
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

    {{-- 1. Modal Buka Shift Narik (1 Klik Cepat) --}}
    <div id="modal-open-day" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">🌅</span>
                <div>
                    <h3 class="text-base font-bold text-white">Buka Hari Kerja (Shift Baru)</h3>
                    <p class="text-xs text-slate-400">Buka wadah transaksi narik hari ini dengan cepat.</p>
                </div>
            </div>

            <form method="POST" action="/operational/open-day" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal Shift</label>
                    <input type="date" name="work_date" value="{{ now()->toDateString() }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-semibold">
                </div>

                {{-- Status Kas Otomatis Melanjutkan --}}
                <div class="bg-slate-900/80 border border-slate-700/80 rounded-xl p-3.5 space-y-1">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Saldo Kas Berjalan:</span>
                        <span class="font-extrabold text-emerald-400">Rp {{ number_format($totalCash, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed pt-1.5 border-t border-slate-800">
                        ✓ Saldo kas otomatis melanjutkan sisa uang dari hari sebelumnya. Anda <strong>tidak perlu menghitung lembaran uang di pagi hari</strong>. Perhitungan fisik per lembar & per koin dilakukan nanti saat <strong>Closing Kas Malam Hari</strong>.
                    </p>
                </div>

                {{-- Opsi Bawa Modal Kas Baru dari Luar (Opsional) --}}
                <details class="bg-slate-900/40 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-400">
                    <summary class="cursor-pointer font-semibold text-slate-300 hover:text-white">Bawa uang kas baru / modal tambahan pagi ini? (Opsional)</summary>
                    <div class="mt-2.5 space-y-1.5">
                        <label class="block text-[11px] font-bold text-slate-400">Nominal Modal Tambahan (Rp)</label>
                        <input type="number" name="cash_initial_balance" min="0" placeholder="Kosongkan jika hanya pakai saldo kemarin" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                    </div>
                </details>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-open-day')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5">
                        <span>🌅</span>
                        <span>Buka Shift Sekarang</span>
                    </button>
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

    {{-- 5. Modal Tambah Rekening / Akun Baru --}}
    <div id="modal-add-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">➕ Buka Rekening / Dompet Baru</h3>
            <p class="text-xs text-slate-400 mb-4">Tambahkan rekening bank baru (Mandiri, BRI, SeaBank) atau e-wallet (DANA, OVO, GoPay).</p>
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
    <div id="modal-expense" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🍜 Pengeluaran Sehari-hari / Pribadi</h3>
            <p class="text-xs text-slate-400 mb-4">Catat sarapan pagi jam 7, makan siang, kopi, belanja dapur, atau kebutuhan rumah tangga.</p>
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

    {{-- 7. Modal Pemasukan Pribadi (Project Luar / Jual Barang / Transferan) --}}
    <div id="modal-personal-income" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">💰 Pemasukan Pribadi di Luar Driver</h3>
            <p class="text-xs text-slate-400 mb-4">Catat honor freelance, hasil jual barang bekas, atau transferan keluarga (tidak dicampur ke omzet ShopeeFood).</p>
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

    {{-- 8. Modal Belanja Online Pakai SPayLater --}}
    <div id="modal-spaylater-purchase" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🛍️ Belanja Shopee Pakai SPayLater</h3>
            <p class="text-xs text-slate-400 mb-4">Catat belanja barang di Shopee pakai cicilan PayLater. Tagihan otomatis terbuat ke jadwal cicilan bulanan.</p>
            <form method="POST" action="/operational/spaylater-purchase" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
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
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Belanja SPayLater</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 9. Modal Pinjam Uang / Pinjol / Bank / SeaBank --}}
    <div id="modal-apply-loan" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">🏦 Pinjam Uang / Pinjaman Online / Bank</h3>
            <p class="text-xs text-slate-400 mb-4">Catat penerimaan pinjaman dari SeaBank Pinjam, GoPay Pinjam, Bank, atau Teman.</p>
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
                        <select name="platform" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                            <option value="seabank_pinjam">SeaBank Pinjam</option>
                            <option value="spaylater">SPayLater</option>
                            <option value="gopay_pinjam">GoPay Pinjam</option>
                            <option value="bank">Bank / KTA</option>
                            <option value="person">Pinjam ke Teman / Keluarga</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Lama Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" value="3" min="1" max="36" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
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
    <div id="modal-pay-installment" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white mb-1">💳 Bayar Tagihan Cicilan / PayLater</h3>
            <p class="text-xs text-slate-400 mb-4">Lunasi cicilan pinjaman bulanan agar kewajiban berkurang.</p>
            <form method="POST" action="/operational/pay-installment" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Tagihan yang Ingin Dibayar</label>
                    <select name="installment_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($activeInstallments as $inst)
                            <option value="{{ $inst->id }}">
                                {{ $inst->debt->name }} (Cicilan #{{ $inst->installment_number }} - Rp {{ number_format($inst->amount_due, 0, ',', '.') }})
                            </option>
                        @endforeach
                        @if($activeInstallments->isEmpty())
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
                        <input type="number" name="actual_paid" min="1" placeholder="Nominal cicilan" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
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

    {{-- 12. Modal Inisialisasi Saldo Akun PERTAMA KALI (Sekali Seumur Hidup & Terkunci) --}}
    <div id="modal-init-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-xl w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xl">🔒</span>
                <h3 class="text-base font-bold text-white">Inisialisasi Saldo Awal (Modal Baseline)</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4">
                Saldo awal ini menjadi titik awal (baseline) pembukuan akun Anda. Setelah dikunci, saldo ini tidak dapat diubah-ubah bebas agar laporan keuangan tetap presisi.
            </p>
            <form method="POST" action="/operational/set-initial-balance" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Akun yang Belum Dikunci</label>
                    <select name="account_id" id="init_account_select" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                        @foreach($accounts->where('initial_balance', 0) as $acc)
                            <option value="{{ $acc->id }}" data-type="{{ $acc->type->value }}">{{ $acc->name }} ({{ strtoupper($acc->type->value) }} &bull; Saat ini: Rp 0)</option>
                        @endforeach
                        @if($accounts->where('initial_balance', 0)->isEmpty())
                            <option value="" disabled selected>Semua akun sudah memiliki saldo awal terkunci</option>
                        @endif
                    </select>
                </div>

                {{-- Kalkulator Denominasi Khusus Kas Tunai --}}
                <div id="init-denom-section" class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">💵 Hitung Lembar & Koin Fisik (Kas Dompet)</span>
                        <span class="text-[10px] text-slate-400">Otomatis hitung ke nominal</span>
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
                        <span id="init-denom-pieces" class="text-slate-400">0 lembar/koin</span>
                        <span id="init-denom-total" class="font-bold text-emerald-400">Rp 0</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Saldo Modal Awal (Rp)</label>
                    <input type="number" name="amount" id="init_account_amount" min="0" placeholder="Contoh: 500000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold">
                    <span class="text-[10px] text-slate-400 mt-1 block">Untuk rekening Bank / E-Wallet, ketik langsung saldo riil m-banking Anda. Untuk Kas fisik, nominal di atas terisi otomatis dari hitungan pecahan.</span>
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

    {{-- 14. Modal Rincian Lembaran Fisik Kas Dompet (Hasil Closing Terakhir) --}}
    <div id="modal-view-cash-denomination" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-lg w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-4 pb-3 border-b border-slate-700/80">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">👛</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Rincian Fisik Lembaran Kas</h3>
                        <p class="text-xs text-slate-400">
                            Hasil hitungan closing shift: 
                            <span class="font-bold text-emerald-400">
                                {{ $cashDenomination?->workDay?->work_date ? $cashDenomination->workDay->work_date->translatedFormat('l, d F Y') : ($currentWorkDay ? $currentWorkDay->work_date->translatedFormat('l, d F Y') : 'Belum Ada Shift') }}
                            </span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-view-cash-denomination')" class="text-slate-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            @if($cashDenomination)
                <div class="space-y-4">
                    {{-- Total Banner --}}
                    <div class="bg-slate-900 border border-slate-700 rounded-xl p-3.5 flex justify-between items-center shadow-inner">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Uang Fisik Terhitung:</span>
                            <span class="text-xs text-slate-300 font-semibold">{{ $cashDenomination->total_pieces }} lembar & koin</span>
                        </div>
                        <span class="text-xl font-extrabold text-emerald-400">
                            Rp {{ number_format($cashDenomination->total_physical_amount, 0, ',', '.') }}
                        </span>
                    </div>

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
                                        <span class="text-[10px] text-slate-400">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="text-sm font-extrabold {{ $nom === 5000 ? 'text-amber-400 font-black' : ($count >= 20 ? 'text-amber-400' : 'text-white') }}">
                                        {{ $count }} <span class="text-[10px] font-normal text-slate-400">lbr</span>
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
                                        <span class="text-[10px] text-slate-400">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="text-sm font-extrabold text-white">
                                        {{ $count }} <span class="text-[10px] font-normal text-slate-400">koin</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3 bg-slate-900/60 border border-slate-700 rounded-xl text-[11px] text-slate-400">
                        ℹ️ <strong>Catatan:</strong> Data lembaran kas fisik ini direkam setiap malam saat Anda melakukan <strong>Closing Kas Shift</strong> sepulang narik.
                    </div>
                </div>
            @else
                <div class="py-8 text-center space-y-2">
                    <span class="text-3xl block">👛</span>
                    <h4 class="text-sm font-bold text-white">Belum Ada Rekaman Pecahan Fisik</h4>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto">
                        Lakukan closing shift malam hari atau buka shift narik untuk merekam jumlah lembaran pertama kali.
                    </p>
                </div>
            @endif

            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-700/80 mt-4">
                <button type="button" onclick="closeModal('modal-view-cash-denomination')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Tutup</button>
                @if($currentWorkDay)
                    <a href="/closing/{{ $currentWorkDay->id }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">
                        Perbarui di Closing Shift &rarr;
                    </a>
                @endif
            </div>
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
                            title: '🏦 Tambah Rekening & Kunci Saldo', 
                            description: 'Di sini Anda bisa bebas menambah rekening bank baru (Mandiri, BRI, SeaBank, DANA) dan mengunci saldo modal awal secara permanen.',
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
