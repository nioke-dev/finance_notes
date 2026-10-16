<x-layouts.app title="Pengelolaan Utang, PayLater & Pinjaman">
    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-2xl text-xs font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 p-4 rounded-2xl text-xs font-semibold flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header & Insight --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Portofolio Liabilitas & Piutang</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Pengelolaan PayLater, Pinjaman Tunai & Piutang Teman
                </h2>
                <p class="text-xs text-slate-300 font-medium mt-0.5">Pantau tagihan belanja bulanan, pinjaman tunai berbunga, dan uang kas yang dipinjam teman di lapangan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="openModal('modal-existing-debt')" class="px-3.5 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>➕</span>
                    <span>Catat Tagihan Berjalan</span>
                </button>
                <button type="button" onclick="openModal('modal-paylater')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>🛍️</span>
                    <span>Belanja PayLater</span>
                </button>
                <button type="button" onclick="openModal('modal-cash-loan')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>💰</span>
                    <span>Pinjaman Tunai</span>
                </button>
                <a href="/dashboard" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        {{-- Ringkasan Kartu Metrik (4 Kolom) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Total Tagihan PayLater --}}
            <div class="bg-slate-800/70 border border-indigo-500/30 rounded-2xl p-4 shadow">
                <div class="flex justify-between items-center text-indigo-400 mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Tagihan PayLater (Belanja)</span>
                    <span class="text-base">🛍️</span>
                </div>
                <div class="mt-1">
                    <span class="text-xl font-extrabold text-indigo-300">Rp {{ number_format($totalPayLater, 0, ',', '.') }}</span>
                    <span class="text-[10px] text-slate-300 font-medium block mt-0.5">{{ $paylaterDebts->count() }} transaksi belanja tempo</span>
                </div>
            </div>

            {{-- 2. Total Pinjaman Tunai / KTA --}}
            <div class="bg-slate-800/70 border border-rose-500/30 rounded-2xl p-4 shadow">
                <div class="flex justify-between items-center text-rose-400 mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Pinjaman Tunai / KTA</span>
                    <span class="text-base">💰</span>
                </div>
                <div class="mt-1">
                    <span class="text-xl font-extrabold text-rose-300">Rp {{ number_format($totalCashLoans, 0, ',', '.') }}</span>
                    <span class="text-[10px] text-slate-300 font-medium block mt-0.5">{{ $cashLoans->count() }} pinjaman dana cair</span>
                </div>
            </div>

            {{-- 3. Total Piutang ke Teman --}}
            <div class="bg-slate-800/70 border border-emerald-500/30 rounded-2xl p-4 shadow">
                <div class="flex justify-between items-center text-emerald-400 mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Piutang Kas ke Teman</span>
                    <span class="text-base">🤝</span>
                </div>
                <div class="mt-1">
                    <span class="text-xl font-extrabold text-emerald-300">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</span>
                    <span class="text-[10px] text-slate-300 font-medium block mt-0.5">{{ $friendDebts->count() }} teman meminjam kas</span>
                </div>
            </div>

            {{-- 4. Tagihan Jatuh Tempo < 30 Hari --}}
            <div class="bg-slate-800/70 border border-amber-500/30 rounded-2xl p-4 shadow">
                <div class="flex justify-between items-center text-amber-400 mb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Jatuh Tempo (30 Hari)</span>
                    <span class="text-base">⚠️</span>
                </div>
                <div class="mt-1">
                    <span class="text-xl font-extrabold text-amber-300">
                        Rp {{ number_format($upcomingInstallments->sum('amount_due'), 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-slate-300 font-medium block mt-0.5">{{ $upcomingInstallments->count() }} cicilan segera jatuh tempo</span>
                </div>
            </div>
        </div>

        {{-- Peringatan Jadwal Jatuh Tempo Mendatang --}}
        @if($upcomingInstallments->isNotEmpty())
            <div class="bg-slate-800/80 border border-amber-500/30 rounded-2xl p-5 shadow space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-amber-400">
                        <span class="text-base">⚠️</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider">Jadwal Jatuh Tempo Terdekat (30 Hari ke Depan)</h3>
                    </div>
                    <span class="text-[11px] text-slate-300 font-medium">Prioritaskan pembayaran untuk menghindari denda</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($upcomingInstallments as $inst)
                        @php
                            $daysLeft = (int) now()->startOfDay()->diffInDays($inst->due_date, false);
                            $isUrgent = $daysLeft <= 7;
                        @endphp
                        <div class="p-3 bg-slate-900/60 border {{ $isUrgent ? 'border-rose-500/40 bg-rose-500/5' : 'border-slate-700/50' }} rounded-xl flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-200 text-xs block">{{ $inst->debt->name }}</span>
                                <span class="text-[11px] text-slate-300 font-medium">Cicilan #{{ $inst->installment_number }} &bull; {{ $inst->due_date->format('d M Y') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-xs text-white block">Rp {{ number_format($inst->amount_due, 0, ',', '.') }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $daysLeft < 0 ? 'bg-rose-500/30 text-rose-300' : ($isUrgent ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-700 text-slate-300') }}">
                                    {{ $daysLeft < 0 ? abs($daysLeft).' hari telat' : ($daysLeft == 0 ? 'Hari Ini' : $daysLeft.' hari lagi') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 1. BAGIAN PAYLATER (SPayLater, TikTok PayLater, Kredivo) --}}
        <div class="bg-slate-800/70 border border-indigo-500/20 rounded-2xl p-6 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-base">🛍️</span>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">
                            Tagihan PayLater (Beli Sekarang Bayar Nanti)
                        </h3>
                    </div>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">Shopee PayLater, TikTok PayLater, Kredivo PayLater. Tidak menambah saldo rekening, melainkan belanja barang bertempo.</p>
                </div>
                <button type="button" onclick="openModal('modal-paylater')" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 border border-indigo-500/30 text-xs font-bold rounded-xl transition">
                    ➕ Catat Belanja PayLater
                </button>
            </div>

            @if($paylaterDebts->isEmpty())
                <div class="py-6 text-center text-slate-300 font-medium text-xs bg-slate-900/40 rounded-xl border border-slate-700/30">
                    Belum ada transaksi belanja PayLater aktif.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-300 font-bold uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Nama Belanja / Barang</th>
                                <th class="pb-3 font-semibold">Platform</th>
                                <th class="pb-3 font-semibold">Tenor</th>
                                <th class="pb-3 font-semibold text-right">Total Belanja</th>
                                <th class="pb-3 font-semibold text-right">Sisa Tagihan</th>
                                <th class="pb-3 font-semibold text-center">Status</th>
                                <th class="pb-3 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($paylaterDebts as $debt)
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-bold text-slate-200">
                                        {{ $debt->name }}
                                        <span class="text-[10px] text-slate-300 font-medium block">{{ $debt->started_at ? $debt->started_at->format('d M Y') : '' }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            {{ $debt->getPlatformLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-300">
                                        {{ $debt->tenor_months == 1 ? '1 Bulan (Tempo)' : $debt->tenor_months . ' Bulan Cicilan' }}
                                    </td>
                                    <td class="py-3 text-right text-slate-300">
                                        Rp {{ number_format($debt->total_obligation, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-indigo-400">
                                        Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <x-status-badge :status="$debt->status" />
                                    </td>
                                    <td class="py-3 text-center">
                                        @php
                                            $firstUnpaid = $debt->installments->firstWhere('status', 'unpaid');
                                            $canDelete = ($debt->installments->where('amount_paid', '>', 0)->count() === 0);
                                        @endphp
                                        <div class="flex items-center justify-center space-x-1.5">
                                            @if($firstUnpaid)
                                                <button 
                                                    type="button" 
                                                    onclick="openPayModal('{{ $firstUnpaid->id }}', '{{ addslashes($debt->name) }}', '{{ $firstUnpaid->installment_number }}', '{{ $firstUnpaid->amount_due }}')"
                                                    class="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-[11px] font-bold transition"
                                                >
                                                    💳 Bayar Rp {{ number_format($firstUnpaid->amount_due, 0, ',', '.') }}
                                                </button>
                                            @else
                                                <span class="text-[11px] text-emerald-400 font-semibold">Lunas</span>
                                            @endif

                                            @if($canDelete)
                                                <form method="POST" action="/operational/debts/{{ $debt->id }}" onsubmit="return confirm('Hapus tagihan {{ $debt->name }}? Tagihan ini belum memiliki pembayaran cicilan.')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 hover:bg-rose-950/40 rounded transition" title="Hapus tagihan yang salah catat">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 2. BAGIAN PINJAMAN TUNAI / KTA (Shopee Pinjam, SeaBank, GoPay Pinjam, Superbank, Kredivo) --}}
        <div class="bg-slate-800/70 border border-rose-500/20 rounded-2xl p-6 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-base">💰</span>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">
                            Pinjaman Tunai / KTA (Dana Cair Masuk ke Rekening)
                        </h3>
                    </div>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">SPinjam, SeaBank Pinjam, GoPay Pinjam, Superbank Pinjam, Kredivo Pinjam, Bank. Dana bersih masuk rekening penampungan.</p>
                </div>
                <button type="button" onclick="openModal('modal-cash-loan')" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 text-xs font-bold rounded-xl transition">
                    ➕ Buka Pinjaman Baru
                </button>
            </div>

            @if($cashLoans->isEmpty())
                <div class="py-6 text-center text-slate-300 font-medium text-xs bg-slate-900/40 rounded-xl border border-slate-700/30">
                    Belum ada pinjaman tunai aktif.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-300 font-bold uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Nama Pinjaman</th>
                                <th class="pb-3 font-semibold">Platform</th>
                                <th class="pb-3 font-semibold">Tenor</th>
                                <th class="pb-3 font-semibold text-right">Pokok + Bunga</th>
                                <th class="pb-3 font-semibold text-right">Sisa Kewajiban</th>
                                <th class="pb-3 font-semibold text-center">Status</th>
                                <th class="pb-3 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($cashLoans as $loan)
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-bold text-slate-200">
                                        {{ $loan->name }}
                                        <span class="text-[10px] text-slate-300 font-medium block">Mulai: {{ $loan->started_at ? $loan->started_at->format('d M Y') : '-' }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            {{ $loan->getPlatformLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-300">
                                        {{ $loan->tenor_months }} Bulan
                                    </td>
                                    <td class="py-3 text-right text-slate-300">
                                        Rp {{ number_format($loan->total_obligation, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-rose-400">
                                        Rp {{ number_format($loan->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <x-status-badge :status="$loan->status" />
                                    </td>
                                    <td class="py-3 text-center">
                                        @php
                                            $firstUnpaid = $loan->installments->firstWhere('status', 'unpaid');
                                            $canDelete = ($loan->installments->where('amount_paid', '>', 0)->count() === 0);
                                        @endphp
                                        <div class="flex items-center justify-center space-x-1.5">
                                            @if($firstUnpaid)
                                                <button 
                                                    type="button" 
                                                    onclick="openPayModal('{{ $firstUnpaid->id }}', '{{ addslashes($loan->name) }}', '{{ $firstUnpaid->installment_number }}', '{{ $firstUnpaid->amount_due }}')"
                                                    class="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-[11px] font-bold transition"
                                                >
                                                    💳 Bayar Rp {{ number_format($firstUnpaid->amount_due, 0, ',', '.') }}
                                                </button>
                                            @else
                                                <span class="text-[11px] text-emerald-400 font-semibold">Lunas</span>
                                            @endif

                                            @if($canDelete)
                                                <form method="POST" action="/operational/debts/{{ $loan->id }}" onsubmit="return confirm('Hapus tagihan pinjaman {{ $loan->name }}? Tagihan ini belum memiliki riwayat pembayaran.')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 hover:bg-rose-950/40 rounded transition" title="Hapus pinjaman">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 3. BAGIAN PIUTANG KE TEMAN (Talangan / Kas Dipinjam Teman) --}}
        <div class="bg-slate-800/70 border border-emerald-500/20 rounded-2xl p-6 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-base">🤝</span>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">
                            Piutang ke Teman (Talangan / Pinjaman Kas di Lapangan)
                        </h3>
                    </div>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">Uang kas fisik dompet yang dipinjam teman basecamp. Saat dilunasi, saldo kas/rekening Anda otomatis bertambah.</p>
                </div>
            </div>

            @if($friendDebts->isEmpty())
                <div class="py-6 text-center text-slate-300 font-medium text-xs bg-slate-900/40 rounded-xl border border-slate-700/30">
                    Tidak ada data piutang teman aktif saat ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-300 font-bold uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Nama Teman</th>
                                <th class="pb-3 font-semibold">Keterangan Pinjaman</th>
                                <th class="pb-3 font-semibold">Tanggal Pinjam</th>
                                <th class="pb-3 font-semibold text-right">Total Pinjaman</th>
                                <th class="pb-3 font-semibold text-right">Sisa Piutang</th>
                                <th class="pb-3 font-semibold text-center">Status</th>
                                <th class="pb-3 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($friendDebts as $friend)
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-bold text-white">
                                        {{ $friend->borrower_name ?: $friend->name }}
                                    </td>
                                    <td class="py-3 text-slate-300">
                                        {{ $friend->name }}
                                    </td>
                                    <td class="py-3 text-slate-300 font-medium">
                                        {{ $friend->started_at ? $friend->started_at->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-3 text-right text-slate-300">
                                        Rp {{ number_format($friend->total_obligation, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-emerald-400">
                                        Rp {{ number_format($friend->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <x-status-badge :status="$friend->status" />
                                    </td>
                                    <td class="py-3 text-center">
                                        <button 
                                            type="button" 
                                            onclick="openSettleFriendModal('{{ $friend->id }}', '{{ addslashes($friend->borrower_name ?: $friend->name) }}', '{{ $friend->remaining_amount }}')"
                                            class="px-2.5 py-1 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 rounded-lg text-[11px] font-bold transition"
                                        >
                                            🤝 Catat Pelunasan
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL 1: Belanja Online Menggunakan PayLater (SPayLater / TikTok / Kredivo) --}}
    <div id="modal-paylater" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🛍️ Belanja Online Pakai PayLater</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat belanja barang bertempo/cicilan (SPayLater, TikTok PayLater, Kredivo).</p>
            <form method="POST" action="/operational/spaylater-purchase" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Platform PayLater</label>
                    <select name="platform" onchange="toggleCustomPlatform(this, 'debts-paylater-custom-platform-wrap')" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold">
                        @if(isset($paylaterProviders) && $paylaterProviders->isNotEmpty())
                            @foreach($paylaterProviders as $provider)
                                <option value="{{ $provider->slug }}">{{ $provider->icon ?: '🛍️' }} {{ $provider->name }} (Sisa: Rp {{ number_format($provider->available_credit, 0, ',', '.') }})</option>
                            @endforeach
                        @else
                            <option value="spaylater">🛍️ Shopee PayLater (SPayLater)</option>
                            <option value="gopay_later">🟢 GoPay Later (GoPay Cicil / Findaya)</option>
                        @endif
                        <option value="other_paylater">➕ Platform PayLater Lainnya (Ketik Manual)</option>
                    </select>
                </div>
                <div id="debts-paylater-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-amber-300 mb-1">Nama Platform PayLater Baru / Lainnya</label>
                    <input type="text" name="custom_platform" placeholder="Misal: Blibli PayLater / Traveloka PayLater" class="w-full bg-slate-900 border border-amber-500/50 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Barang / Belanja</label>
                    <input type="text" name="item_name" placeholder="Misal: Jas Hujan Motor / Sepatu / Kemeja Kuliah" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Total Harga Barang (Rp)</label>
                    <input type="number" name="total_price" min="1000" placeholder="150000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilihan Tenor / Waktu Pembayaran</label>
                    <select name="tenor_months" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                        <option value="1">1 Bulan (Tempo - Bayar Bulan Depan)</option>
                        <option value="3">3 Bulan Cicilan</option>
                        <option value="6">6 Bulan Cicilan</option>
                        <option value="12">12 Bulan Cicilan</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-paylater')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Tagihan PayLater</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Buka Pinjaman Tunai Baru (Dana Cair Masuk ke Rekening) --}}
    <div id="modal-cash-loan" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">💰 Pencairan Pinjaman Tunai (KTA)</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Catat pinjaman dana cair yang langsung masuk ke rekening bank Anda.</p>
            <form method="POST" action="/operational/debt" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Platform Pinjaman</label>
                    <select name="platform" onchange="toggleCustomPlatform(this, 'debts-cash-custom-platform-wrap')" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold">
                        <option value="spinjam">Shopee Pinjam (SPinjam)</option>
                        <option value="seabank_pinjam">SeaBank Pinjam</option>
                        <option value="gopay_pinjam">GoPay Pinjam</option>
                        <option value="superbank_pinjam">Superbank Pinjam</option>
                        <option value="kredivo_pinjam">Kredivo Pinjam</option>
                        <option value="bank">Pinjaman Bank Umum</option>
                        <option value="other">➕ Pinjaman Lainnya (Ketik Manual)</option>
                    </select>
                </div>
                <div id="debts-cash-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-amber-300 mb-1">Nama Platform Pinjaman Lainnya</label>
                    <input type="text" name="custom_platform" placeholder="Misal: KTA Tunaiku / Kredit Plus" class="w-full bg-slate-900 border border-amber-500/50 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama / Keterangan Pinjaman</label>
                    <input type="text" name="name" placeholder="Misal: SPinjam Modal Tambahan / SeaBank Pinjam 3x" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Pokok Pinjaman (Rp)</label>
                        <input type="number" name="principal_amount" min="1000" placeholder="1000000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Bunga / Biaya (Rp)</label>
                        <input type="number" name="fee_amount" min="0" placeholder="50000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" min="1" max="36" value="3" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Rekening Penampung</label>
                        <select name="destination_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                            @foreach($paymentAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-cash-loan')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Cairkan Dana & Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: Bayar Cicilan PayLater / Pinjaman --}}
    <div id="modal-pay-installment-debt" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">💳 Bayar Tagihan / Cicilan</h3>
            <p id="pay-inst-title" class="text-xs text-slate-300 font-medium mb-4">Bayar tagihan menggunakan rekening bank atau saldo e-wallet.</p>
            <form method="POST" action="/operational/pay-installment" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <input type="hidden" name="installment_id" id="pay-inst-id">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Yang Dibayar (Rp)</label>
                    <input type="number" name="paid_amount" id="pay-inst-amount" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Sumber Rekening Pembayaran</label>
                    <select name="source_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                        @foreach($paymentAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Voucher Diskon / Koin Pengurang (Jika Ada)</label>
                    <input type="number" name="discount_amount" min="0" value="0" placeholder="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-pay-installment-debt')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Konfirmasi Bayar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: Pelunasan Piutang Teman --}}
    <div id="modal-settle-friend" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🤝 Catat Pembayaran dari Teman</h3>
            <p id="settle-friend-desc" class="text-xs text-slate-300 font-medium mb-4">Terima pengembalian uang kas/transfer dari teman.</p>
            <form method="POST" action="/operational/settle-friend-debt" class="space-y-4">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $currentWorkDay->id ?? '' }}">
                <input type="hidden" name="debt_id" id="settle-debt-id">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Diterima (Rp)</label>
                    <input type="number" name="received_amount" id="settle-amount" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Masuk ke Rekening / Kas Mana?</label>
                    <select name="receiving_account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                        @foreach($paymentAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" name="forgive_remainder" id="forgive_remainder" value="1" class="rounded bg-slate-900 border-slate-700 text-blue-600">
                    <label for="forgive_remainder" class="text-xs text-slate-300">Ikhlaskan sisa kekurangan (diakui sebagai sedekah/bantuan)</label>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-settle-friend')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" {{ !$currentWorkDay ? 'disabled' : '' }} class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow {{ !$currentWorkDay ? 'opacity-50 cursor-not-allowed' : '' }}">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Catat Tagihan Utang Berjalan (Existing Debt) --}}
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
                    <select name="platform" onchange="toggleCustomPlatform(this, 'debts-existing-custom-platform-wrap')" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
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

                <div id="debts-existing-custom-platform-wrap" class="hidden">
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
                            <button type="button" onclick="document.getElementById('debts-existing-due-date').value = '2026-10-31'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">31 Okt</button>
                            <button type="button" onclick="document.getElementById('debts-existing-due-date').value = '2026-10-25'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">25 Okt</button>
                            <button type="button" onclick="document.getElementById('debts-existing-due-date').value = '2026-10-20'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">20 Okt</button>
                            <button type="button" onclick="document.getElementById('debts-existing-due-date').value = '2026-11-05'" class="text-[10px] px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold hover:bg-purple-500/30">5 Nov</button>
                        </div>
                    </div>
                    <input type="date" name="due_date" id="debts-existing-due-date" value="{{ now()->endOfMonth()->toDateString() }}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
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

    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('hidden');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        }

        function openPayModal(installmentId, debtName, instNumber, amount) {
            document.getElementById('pay-inst-id').value = installmentId;
            document.getElementById('pay-inst-amount').value = amount;
            document.getElementById('pay-inst-title').innerText = debtName + ' (Cicilan #' + instNumber + ')';
            openModal('modal-pay-installment-debt');
        }

        function openSettleFriendModal(debtId, friendName, amount) {
            document.getElementById('settle-debt-id').value = debtId;
            document.getElementById('settle-amount').value = amount;
            document.getElementById('settle-friend-desc').innerText = 'Penerimaan pembayaran dari ' + friendName;
            openModal('modal-settle-friend');
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
    </script>
</x-layouts.app>
