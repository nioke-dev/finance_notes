<x-layouts.app>
    <x-slot:title>Manajemen Wadah Finansial | Driver Keuangan</x-slot:title>

    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-950/40 border border-emerald-500/50 rounded-2xl flex items-center justify-between text-emerald-300 text-sm shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <span class="text-xl">✅</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 text-xs font-bold px-2 py-1">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-950/40 border border-rose-500/50 rounded-2xl flex items-center justify-between text-rose-300 text-sm shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <span class="text-xl">⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-200 text-xs font-bold px-2 py-1">&times;</button>
            </div>
        @endif

        {{-- Header & Insight --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Master Portofolio & Akun</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    💼 Manajemen Wadah Finansial
                </h2>
                <p class="text-xs text-slate-300 font-medium mt-0.5">Kelola seluruh rekening bank, e-wallet, dompet kas fisik, serta wadah PayLater & limit pinjaman Anda. Hapus wadah kosong yang belum pernah dipakai, atau tutup/arsipkan wadah yang sudah memiliki riwayat transaksi.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="openModal('modal-add-account')" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>➕</span>
                    <span>Wadah Simpanan</span>
                </button>
                <button type="button" onclick="openDeductAccountModal()" class="px-3.5 py-1.5 bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 border border-rose-500/40 text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow" title="Kurangi saldo rekening untuk biaya admin atau bunga">
                    <span>🔻</span>
                    <span>Kurangi Saldo</span>
                </button>
                <button type="button" onclick="openManageDeductionsModal()" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 text-xs font-semibold rounded-xl transition flex items-center space-x-1" title="Atur jadwal potongan bulanan wadah">
                    <span>⚙️</span>
                    <span>Jadwal Potongan</span>
                </button>
                <button type="button" onclick="openModal('modal-add-paylater')" class="px-3.5 py-1.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>🛍️</span>
                    <span>Wadah PayLater</span>
                </button>
                <button type="button" onclick="openModal('modal-existing-debt')" class="px-3.5 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow">
                    <span>📝</span>
                    <span>Catat Tagihan</span>
                </button>
                @if(config('app.trial_mode', false))
                    <button type="button" onclick="openModal('modal-reset-trial-data')" class="px-3.5 py-1.5 bg-rose-600/40 hover:bg-rose-600/60 text-rose-200 border border-rose-500/60 text-xs font-bold rounded-xl transition flex items-center space-x-1 shadow" title="Reset semua data ke kondisi fresh seeder">
                        <span>🔄</span>
                        <span>Reset Data Uji Coba</span>
                    </button>
                @endif
                <a href="/dashboard" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        {{-- Ringkasan Metrik (4 Kolom) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900/80 border border-emerald-500/30 rounded-2xl p-4 shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300 uppercase">Total Saldo Aset Simpanan</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-400">Rp {{ number_format($totalAssetBalance, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-300 font-bold">{{ $activeAccounts->count() }} Wadah</span>
                </div>
            </div>

            <div class="bg-slate-900/80 border border-cyan-500/30 rounded-2xl p-4 shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300 uppercase">Total Plafon PayLater</span>
                    <span class="text-lg">🛍️</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-cyan-400">Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-300 font-bold">Sisa: Rp {{ number_format($totalAvailableCredit, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-slate-900/80 border border-purple-500/30 rounded-2xl p-4 shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300 uppercase">Total Kewajiban Utang</span>
                    <span class="text-lg">💳</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-purple-400">Rp {{ number_format($totalDebtBalance, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-300 font-bold">{{ $debts->where('status', 'active')->count() }} Tagihan</span>
                </div>
            </div>

            <div class="bg-slate-900/80 border border-blue-500/30 rounded-2xl p-4 shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300 uppercase">Kekayaan Bersih (Net Worth)</span>
                    <span class="text-lg">⚖️</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black {{ $netWorth >= 0 ? 'text-blue-400' : 'text-rose-400' }}">Rp {{ number_format($netWorth, 0, ',', '.') }}</span>
                    <span class="text-xs text-slate-300 font-bold">Aset &minus; Utang</span>
                </div>
            </div>
        </div>

        {{-- ================= BAGIAN 1: WADAH ASET & SIMPANAN (KAS, BANK, E-WALLET) ================= --}}
        <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-5 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                        <span>🏦</span>
                        <span>Wadah Simpanan & Uang (Kas, Rekening Bank, E-Wallet)</span>
                    </h3>
                    <p class="text-xs text-slate-300">Wadah tempat uang fisik atau saldo digital disimpan dan digunakan untuk operasional.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openDeductAccountModal()" class="px-3 py-1.5 bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 border border-rose-500/40 text-xs font-bold rounded-xl transition flex items-center space-x-1">
                        <span>🔻</span>
                        <span>Kurangi Saldo</span>
                    </button>
                    <button type="button" onclick="openManageDeductionsModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-600 text-xs font-semibold rounded-xl transition flex items-center space-x-1">
                        <span>⚙️</span>
                        <span>Jadwal Potongan</span>
                    </button>
                    <button type="button" onclick="openModal('modal-add-account')" class="px-3 py-1.5 bg-blue-600/30 hover:bg-blue-600/50 text-blue-300 border border-blue-500/40 text-xs font-bold rounded-xl transition flex items-center space-x-1">
                        <span>➕</span>
                        <span>Tambah Wadah Baru</span>
                    </button>
                </div>
            </div>

            {{-- Grid Wadah Aset Aktif --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($activeAccounts as $acc)
                    @php
                        $icon = match($acc->type->value ?? $acc->type) {
                            'cash' => '💵',
                            'bank' => '🏦',
                            'ewallet' => '📱',
                            default => '💼',
                        };
                        $canDeletePermanently = ($acc->ledger_entries_count == 0 && $acc->current_balance == 0);
                    @endphp
                    <div class="bg-slate-800/80 border border-slate-700/80 hover:border-slate-600 rounded-xl p-4 flex flex-col justify-between shadow transition space-y-3">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="text-2xl">{{ $icon }}</span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-white">{{ $acc->name }}</h4>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300">
                                            {{ ucfirst($acc->type->value ?? $acc->type) }}
                                        </span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    Aktif
                                </span>
                            </div>

                            <div class="pt-2 border-t border-slate-700/60 flex items-baseline justify-between">
                                <span class="text-xs text-slate-300">Saldo Riil:</span>
                                <span class="text-base font-black text-white">Rp {{ number_format($acc->current_balance, 0, ',', '.') }}</span>
                            </div>

                            {{-- Daftar Jadwal Potongan Rutin Wadah (Jika Ada) --}}
                            @if($acc->activeDeductions && $acc->activeDeductions->isNotEmpty())
                                <div class="space-y-1.5 pt-1">
                                    @foreach($acc->activeDeductions as $ded)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-900/90 rounded-lg px-2.5 py-1.5 border border-slate-700/80">
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

                        {{-- Metadata Transaksi & Aksi --}}
                        <div class="pt-2 border-t border-slate-700/60 flex flex-col gap-2 text-xs">
                            <div class="flex items-center gap-1.5 w-full">
                                <button type="button" onclick="openDeductAccountModal({{ $acc->id }}, '{{ addslashes($acc->name) }}', {{ $acc->current_balance }})" class="flex-1 py-1 px-2 bg-slate-900 hover:bg-rose-950/40 text-slate-200 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 transition" title="Kurangi saldo wadah ini untuk biaya admin, bunga, atau potongan lain">
                                    <span>🔻</span>
                                    <span>Kurangi Saldo</span>
                                </button>
                                <button type="button" onclick="openManageDeductionsModal({{ $acc->id }})" class="p-1 px-2 bg-slate-900 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 rounded-lg text-[11px] font-bold transition" title="Atur jadwal potongan bulanan wadah ini">
                                    ⚙️ Jadwal
                                </button>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                @if($acc->ledger_entries_count == 0)
                                    <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-500/30">
                                        ✨ 0 Transaksi
                                    </span>
                                @else
                                    <span class="text-[10px] font-medium text-slate-300 bg-slate-900 px-2 py-0.5 rounded border border-slate-700">
                                        📊 {{ $acc->ledger_entries_count }} Transaksi
                                    </span>
                                @endif

                                {{-- Tombol Aksi Hapus / Tutup --}}
                                <div class="flex items-center space-x-2">
                                    @if($canDeletePermanently)
                                        <form method="POST" action="/operational/account/{{ $acc->id }}" onsubmit="return confirm('Apakah Anda yakin ingin MENGHAPUS PERMANEN wadah {{ $acc->name }}? Wadah ini belum memiliki riwayat transaksi apa pun.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 text-[11px] font-bold text-rose-400 hover:text-rose-300 bg-rose-950/40 hover:bg-rose-950/70 border border-rose-500/40 rounded-lg transition" title="Hapus permanen karena belum ada catatan transaksi">
                                                🗑️ Hapus Wadah
                                            </button>
                                        </form>
                                    @elseif($acc->current_balance == 0)
                                        <form method="POST" action="/operational/account/{{ $acc->id }}/toggle-active" onsubmit="return confirm('Tutup / arsipkan wadah {{ $acc->name }}? Wadah ini memiliki catatan transaksi dan akan disimpan rapi di arsip.')">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 text-[11px] font-bold text-amber-400 hover:text-amber-300 bg-amber-950/40 hover:bg-amber-950/70 border border-amber-500/40 rounded-lg transition" title="Arsipkan wadah">
                                                📦 Tutup Wadah
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-medium italic" title="Pindahkan sisa saldo Rp {{ number_format($acc->current_balance, 0, ',', '.') }} ke rekening lain untuk dapat menutup/menghapus wadah ini">
                                            Ada Saldo
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Wadah Yang Diarsipkan / Ditutup --}}
            @if($inactiveAccounts->isNotEmpty())
                <div class="mt-6 pt-4 border-t border-slate-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-3 flex items-center space-x-1.5">
                        <span>📦</span>
                        <span>Wadah Nonaktif / Sedang Ditutup ({{ $inactiveAccounts->count() }})</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($inactiveAccounts as $inacc)
                            <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-3 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-300 line-through">{{ $inacc->name }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ ucfirst($inacc->type->value ?? $inacc->type) }} &bull; {{ $inacc->ledger_entries_count }} Transaksi tersimpan</span>
                                </div>
                                <form method="POST" action="/operational/account/{{ $inacc->id }}/toggle-active" onsubmit="return confirm('Aktifkan kembali wadah {{ $inacc->name }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 bg-emerald-950/40 hover:bg-emerald-950/70 border border-emerald-500/30 rounded-lg transition">
                                        ♻️ Buka Kembali
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ================= BAGIAN 2: WADAH KREDIT & PAYLATER (LIMIT PLAFON) ================= --}}
        <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-5 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                        <span>🛍️</span>
                        <span>Wadah Kredit & PayLater (Plafon Limit & Saldo Terpakai)</span>
                    </h3>
                    <p class="text-xs text-slate-300">Wadah kredit bertempo (SPayLater, GoPay Later, TikTok PayLater, dll). Gunakan langsung saat belanja tanpa memotong kas/bank.</p>
                </div>
                <button type="button" onclick="openModal('modal-add-paylater')" class="px-3 py-1.5 bg-cyan-600/30 hover:bg-cyan-600/50 text-cyan-300 border border-cyan-500/40 text-xs font-bold rounded-xl transition flex items-center space-x-1">
                    <span>➕</span>
                    <span>Tambah Wadah PayLater</span>
                </button>
            </div>

            {{-- Grid Wadah PayLater --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($paylaterProviders as $provider)
                    @php
                        $usedPercent = $provider->credit_limit > 0 ? min(100, round(($provider->used_credit / $provider->credit_limit) * 100)) : 0;
                        $progressColor = $usedPercent > 80 ? 'bg-rose-500' : ($usedPercent > 50 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="bg-slate-800/80 border {{ $provider->is_active ? 'border-slate-700/80 hover:border-slate-600' : 'border-slate-800 opacity-60' }} rounded-xl p-4 flex flex-col justify-between shadow transition space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-2.5">
                                <span class="text-2xl">{{ $provider->icon ?: '🛍️' }}</span>
                                <div>
                                    <h4 class="text-sm font-extrabold text-white {{ $provider->is_active ? '' : 'line-through' }}">{{ $provider->name }}</h4>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Wadah Kredit
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                @if($provider->is_active)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-700 text-slate-300 border border-slate-600">
                                        Ditutup
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Plafon & Terpakai --}}
                        <div class="space-y-1.5 pt-2 border-t border-slate-700/60">
                            <div class="flex justify-between items-baseline text-xs">
                                <span class="text-slate-400">Plafon Limit:</span>
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-extrabold text-white">Rp {{ number_format($provider->credit_limit, 0, ',', '.') }}</span>
                                    <button type="button" onclick="openEditLimitModal('{{ $provider->id }}', '{{ addslashes($provider->name) }}', '{{ $provider->credit_limit }}')" class="text-cyan-400 hover:text-cyan-300 text-[10px] font-bold hover:underline" title="Ubah Plafon Kredit">
                                        ✏️ Atur
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between items-baseline text-xs">
                                <span class="text-slate-400">Tagihan Terpakai:</span>
                                <span class="font-extrabold {{ $provider->used_credit > 0 ? 'text-purple-400' : 'text-slate-400' }}">Rp {{ number_format($provider->used_credit, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between items-baseline text-xs">
                                <span class="text-slate-400">Sisa Limit Tersedia:</span>
                                <span class="font-extrabold text-emerald-400">Rp {{ number_format($provider->available_credit, 0, ',', '.') }}</span>
                            </div>

                            {{-- Progress Bar --}}
                            @if($provider->credit_limit > 0)
                                <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden mt-1">
                                    <div class="{{ $progressColor }} h-1.5 rounded-full transition-all duration-300" style="width: {{ $usedPercent }}%"></div>
                                </div>
                                <div class="text-[9px] text-right text-slate-400 font-semibold">
                                    {{ $usedPercent }}% Limit Terpakai
                                </div>
                            @endif
                        </div>

                        {{-- Metadata & Actions --}}
                        <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between text-xs">
                            @if($provider->can_be_deleted)
                                <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-500/30">
                                    ✨ 0 Riwayat Tagihan
                                </span>
                            @else
                                <span class="text-[10px] font-medium text-slate-300 bg-slate-900 px-2 py-0.5 rounded border border-slate-700">
                                    📊 {{ $provider->debts->count() }} Tagihan
                                </span>
                            @endif

                            <div class="flex items-center space-x-2">
                                @if($provider->can_be_deleted)
                                    <form method="POST" action="/operational/paylater-providers/{{ $provider->id }}" onsubmit="return confirm('Apakah Anda yakin ingin MENGHAPUS PERMANEN wadah PayLater {{ $provider->name }}? Wadah ini belum pernah ada catatan tagihan sama sekali.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-[11px] font-bold text-rose-400 hover:text-rose-300 bg-rose-950/40 hover:bg-rose-950/70 border border-rose-500/40 rounded-lg transition" title="Hapus permanen karena belum ada riwayat">
                                            🗑️ Hapus Wadah
                                        </button>
                                    </form>
                                @elseif($provider->is_active && $provider->used_credit == 0)
                                    <form method="POST" action="/operational/paylater-providers/{{ $provider->id }}/toggle-active" onsubmit="return confirm('Tutup / arsipkan wadah PayLater {{ $provider->name }}? Riwayat tagihan masa lalu tetap aman.')">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-[11px] font-bold text-amber-400 hover:text-amber-300 bg-amber-950/40 hover:bg-amber-950/70 border border-amber-500/40 rounded-lg transition" title="Tutup / arsipkan wadah PayLater">
                                            📦 Tutup Wadah
                                        </button>
                                    </form>
                                @elseif(!$provider->is_active)
                                    <form method="POST" action="/operational/paylater-providers/{{ $provider->id }}/toggle-active" onsubmit="return confirm('Aktifkan kembali wadah PayLater {{ $provider->name }}?')">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 bg-emerald-950/40 hover:bg-emerald-950/70 border border-emerald-500/40 rounded-lg transition" title="Aktifkan kembali wadah PayLater">
                                            ♻️ Buka Kembali
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[10px] text-purple-300 font-semibold bg-purple-950/40 px-2 py-0.5 rounded border border-purple-500/30" title="Wadah ini sedang memiliki cicilan/tagihan aktif berjalan">
                                        Tagihan Berjalan
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ================= BAGIAN 3: WADAH LIABILITAS & DAFTAR TAGIHAN ================= --}}
        <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-5 shadow space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                        <span>💳</span>
                        <span>Daftar Tagihan & Cicilan Berjalan (PayLater & Pinjaman)</span>
                    </h3>
                    <p class="text-xs text-slate-300">Daftar seluruh kewajiban bertempo atau cicilan berjalan. Tagihan yang belum pernah dibayar cicilannya dapat dibatalkan/dihapus.</p>
                </div>
                <button type="button" onclick="openModal('modal-existing-debt')" class="px-3 py-1.5 bg-purple-600/30 hover:bg-purple-600/50 text-purple-300 border border-purple-500/40 text-xs font-bold rounded-xl transition flex items-center space-x-1">
                    <span>➕</span>
                    <span>Tambah Tagihan PayLater</span>
                </button>
            </div>

            @if($debts->isEmpty())
                <div class="p-6 text-center text-slate-300 bg-slate-800/40 border border-slate-700/60 rounded-xl space-y-2">
                    <span class="text-3xl block">🎉</span>
                    <span class="text-xs font-bold text-white block">Tidak Ada Tagihan Utang atau PayLater</span>
                    <span class="text-[11px] text-slate-300 block">Seluruh portofolio Anda bersih dari beban kredit dan pinjaman.</span>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-200">
                        <thead class="bg-slate-800/90 text-slate-300 uppercase text-[10px] tracking-wider border-b border-slate-700">
                            <tr>
                                <th class="py-3 px-3">Platform & Keterangan</th>
                                <th class="py-3 px-3">Kategori</th>
                                <th class="py-3 px-3 text-right">Total Kewajiban</th>
                                <th class="py-3 px-3 text-right">Sisa Tagihan</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($debts as $debt)
                                <tr class="hover:bg-slate-800/50 transition">
                                    <td class="py-3 px-3">
                                        <div class="font-extrabold text-white">{{ $debt->name }}</div>
                                        <div class="flex items-center space-x-2 mt-0.5">
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                {{ $debt->getPlatformLabel() }}
                                            </span>
                                            <span class="text-[10px] text-slate-300">{{ $debt->tenor_months }} Bulan Tenor</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-[11px] text-slate-300">{{ $debt->getCategoryLabel() }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-white">
                                        Rp {{ number_format($debt->total_obligation, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-rose-400">
                                        Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if($debt->isPaidOff())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                ✓ Lunas
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                Aktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        @if($debt->can_be_deleted)
                                            <form method="POST" action="/operational/debts/{{ $debt->id }}" onsubmit="return confirm('Hapus tagihan {{ $debt->name }}? Tagihan ini belum memiliki pembayaran cicilan.')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-rose-400 hover:text-rose-300 bg-rose-950/40 hover:bg-rose-950/70 border border-rose-500/40 rounded-lg transition" title="Hapus tagihan yang salah catat">
                                                    🗑️ Hapus Tagihan
                                                </button>
                                            </form>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-semibold text-slate-400 bg-slate-800 rounded border border-slate-700" title="Tagihan ini sudah memiliki riwayat cicilan yang dibayar (Rp {{ number_format($debt->paid_sum, 0, ',', '.') }}), sehingga dikunci demi integritas laporan audit.">
                                                🔒 Ada Cicilan Terbayar
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL 1: Buka Rekening / Dompet Baru --}}
    <div id="modal-add-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">➕ Buka Wadah / Dompet Baru</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Tambahkan rekening bank baru (Mandiri, BRI, BCA) atau e-wallet (DANA, OVO, GoPay).</p>
            <form method="POST" action="/operational/account" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Wadah / Akun <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" placeholder="Misal: Bank Mandiri / DANA / OVO" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tipe Wadah <span class="text-rose-400">*</span></label>
                    <select name="type" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="bank">Rekening Bank</option>
                        <option value="ewallet">E-Wallet (Digital)</option>
                        <option value="cash">Uang Kas Fisik</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Saldo Modal Awal (Rp)</label>
                    <input type="number" name="initial_balance" min="0" placeholder="0" value="0" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('modal-add-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow">Simpan Wadah Baru</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Tambah Wadah PayLater Baru --}}
    <div id="modal-add-paylater" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">🛍️ Tambah Wadah PayLater Baru</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Daftarkan platform PayLater atau kartu kredit baru yang Anda miliki (contoh: SPayLater, GoPay Later, TikTok PayLater, Kredivo, Akulaku, Indodana, Traveloka PayLater).</p>
            <form method="POST" action="{{ route('operational.paylater-providers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama Platform PayLater <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" placeholder="Misal: SPayLater / GoPay Later / Blibli PayLater" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Limit Plafon Kredit (Rp)</label>
                    <input type="number" name="credit_limit" min="0" step="10000" placeholder="Contoh: 3000000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Total limit saldo kredit yang diberikan platform kepada Anda.</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Ikon / Emoji</label>
                    <input type="text" name="icon" value="🛍️" maxlength="10" class="w-24 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm text-center">
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeModal('modal-add-paylater')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl shadow">Simpan Wadah PayLater</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: Atur Limit Plafon PayLater --}}
    <div id="modal-edit-paylater-limit" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 max-w-sm w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-white mb-1">✏️ Atur Limit Plafon Kredit</h3>
            <p class="text-xs text-slate-300 font-medium mb-4">Perbarui limit kredit <span id="edit-limit-provider-name" class="font-bold text-cyan-400"></span> sesuai aplikasi resmi.</p>
            <form id="form-edit-paylater-limit" method="POST" action="" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Limit Plafon Baru (Rp) <span class="text-rose-400">*</span></label>
                    <input type="number" id="edit-limit-input" name="credit_limit" min="0" step="10000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-700">
                    <button type="button" onclick="closeModal('modal-edit-paylater-limit')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl shadow">Perbarui Limit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: Catat Tagihan Utang Berjalan (Existing Debt) --}}
    <div id="modal-existing-debt" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-purple-500/50 max-w-md w-full rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center space-x-2.5 mb-1">
                <span class="text-2xl">📝</span>
                <div>
                    <h3 class="text-base font-extrabold text-white">Catat Tagihan Utang / PayLater</h3>
                    <p class="text-xs text-slate-300">Masukkan tagihan PayLater atau Pinjaman yang Anda miliki.</p>
                </div>
            </div>

            <form method="POST" action="/operational/existing-debt" class="space-y-3.5 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Platform Utang <span class="text-rose-400">*</span></label>
                    <select name="platform" onchange="toggleCustomPlatform(this, 'wallets-existing-custom-platform-wrap')" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <optgroup label="🛍️ Wadah PayLater Terdaftar">
                            @foreach($paylaterProviders as $provider)
                                <option value="{{ $provider->slug }}">{{ $provider->icon ?: '🛍️' }} {{ $provider->name }}</option>
                            @endforeach
                        </optgroup>
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

                <div id="wallets-existing-custom-platform-wrap" class="hidden">
                    <label class="block text-xs font-bold text-purple-300 mb-1">Nama Platform Baru / Lainnya <span class="text-rose-400">*</span></label>
                    <input type="text" name="custom_platform" placeholder="Misal: Blibli PayLater / Indodana / Lainnya" class="w-full bg-slate-800 border border-purple-500/50 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nama / Keterangan Tagihan <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Tagihan GoPay Later Jas Hujan / SeaBank Cicilan 2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Total Tagihan (Rp) <span class="text-rose-400">*</span></label>
                        <input type="number" inputmode="numeric" name="total_obligation" required min="1000" step="1000" placeholder="500000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" value="1" min="1" max="36" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal Jatuh Tempo <span class="text-rose-400">*</span></label>
                    <input type="date" name="due_date" value="{{ now()->endOfMonth()->toDateString() }}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
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

        function openEditLimitModal(id, name, currentLimit) {
            const modal = document.getElementById('modal-edit-paylater-limit');
            const form = document.getElementById('form-edit-paylater-limit');
            const nameEl = document.getElementById('edit-limit-provider-name');
            const input = document.getElementById('edit-limit-input');
            
            if (form) form.action = `/operational/paylater-providers/${id}/limit`;
            if (nameEl) nameEl.textContent = name;
            if (input) input.value = currentLimit;
            if (modal) modal.classList.remove('hidden');
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
