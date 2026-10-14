<x-layouts.app title="Closing & Rekonsiliasi Harian">
    <div class="space-y-6">
        {{-- Header Tanggal & Status --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Audit & Closing Shift Narik</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    {{ $workDay->work_date->translatedFormat('l, d F Y') }}
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <x-status-badge :status="$workDay->status" />
                <a href="/dashboard" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                    &larr; Kembali
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sisi Kiri: Form Hitung Fisik Kas Dompet --}}
            <div class="lg:col-span-7 bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-5">
                <div class="border-b border-slate-700/60 pb-3 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Denominasi Uang Tunai Fisik</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ketik jumlah lembar & keping uang di dompet setelah pulang narik</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-lg">
                        Kas Dompet
                    </span>
                </div>

                {{-- Pecahan Uang Kertas --}}
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Uang Kertas (Lembar)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach([100000, 50000, 20000, 10000, 5000, 2000, 1000] as $nominal)
                            @php $field = 'bill_' . $nominal; @endphp
                            <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3">
                                <label for="{{ $field }}" class="text-[11px] font-bold text-slate-300 block mb-1">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </label>
                                <input 
                                    type="number" 
                                    min="0"
                                    name="{{ $field }}" 
                                    id="{{ $field }}" 
                                    value="{{ old($field, $denominations->$field ?? 0) }}"
                                    data-nominal="{{ $nominal }}"
                                    class="denom-input w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pecahan Koin --}}
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Uang Logam / Koin (Keping)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([1000, 500, 200, 100] as $nominal)
                            @php $field = 'coin_' . $nominal; @endphp
                            <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3">
                                <label for="{{ $field }}" class="text-[11px] font-bold text-slate-300 block mb-1">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </label>
                                <input 
                                    type="number" 
                                    min="0"
                                    name="{{ $field }}" 
                                    id="{{ $field }}" 
                                    value="{{ old($field, $denominations->$field ?? 0) }}"
                                    data-nominal="{{ $nominal }}"
                                    class="denom-input w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Total Fisik Terhitung --}}
                <div class="bg-slate-900/80 border border-slate-700 rounded-xl p-4 flex justify-between items-center">
                    <span class="text-xs font-bold uppercase text-slate-400">Total Akumulasi Fisik</span>
                    <span id="total-physical-display" class="text-xl font-extrabold text-emerald-400">
                        Rp {{ number_format($denominations->total_physical_amount ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Sisi Kanan: Audit Rekonsiliasi & Finalisasi --}}
            <div class="lg:col-span-5 space-y-6">
                {{-- Tabel Perbandingan Saldo Akun --}}
                <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white border-b border-slate-700/60 pb-3">
                        Audit Selisih Saldo
                    </h3>
                    <div class="space-y-3">
                        @foreach($reconciliationReport['accounts'] ?? [] as $accReport)
                            <div class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5 flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-white block">{{ $accReport['account_name'] }}</span>
                                    <span class="text-[11px] text-slate-400">
                                        Buku: Rp {{ number_format($accReport['ledger_balance'], 0, ',', '.') }} &bull; 
                                        Fisik/Aktual: Rp {{ number_format($accReport['actual_balance'], 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <x-status-badge :status="$accReport['status']" />
                                    <span class="block text-xs font-bold mt-1 {{ $accReport['discrepancy'] === 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $accReport['discrepancy'] >= 0 ? '+' : '' }}Rp {{ number_format($accReport['discrepancy'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Total Absolute Discrepancy --}}
                    @php $isBalanced = ($reconciliationReport['total_absolute_discrepancy'] ?? 0) === 0; @endphp
                    <div class="p-3.5 rounded-xl border {{ $isBalanced ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-rose-500/10 border-rose-500/30' }} flex justify-between items-center">
                        <span class="text-xs font-bold {{ $isBalanced ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ $isBalanced ? '✓ Saldo Seimbang Sempurna' : '⚠️ Masih Ada Selisih' }}
                        </span>
                        <span class="text-sm font-extrabold {{ $isBalanced ? 'text-emerald-400' : 'text-rose-400' }}">
                            Rp {{ number_format($reconciliationReport['total_absolute_discrepancy'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="space-y-2 pt-2">
                        <button 
                            type="button" 
                            id="btn-finalize"
                            {{ !$isBalanced ? 'disabled' : '' }}
                            class="w-full py-3 px-4 font-bold text-sm rounded-xl transition shadow flex items-center justify-center space-x-2 {{ $isBalanced ? 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer' : 'bg-slate-700 text-slate-400 cursor-not-allowed opacity-60' }}"
                        >
                            <span>🔒 Finalkan Hari Kerja Ini</span>
                        </button>
                        <a 
                            href="/dashboard" 
                            class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition flex items-center justify-center"
                        >
                            Simpan Draft / Tutup Nanti
                        </a>
                    </div>
                </div>

                {{-- Voice Memos Yang Terkait --}}
                @if($workDay->voiceMemos->isNotEmpty())
                    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan Suara (Voice Memos)</h4>
                        <div class="space-y-2">
                            @foreach($workDay->voiceMemos as $memo)
                                <div class="bg-slate-900/60 border border-slate-700/40 rounded-xl p-3 flex justify-between items-center text-xs">
                                    <span class="text-slate-300">{{ $memo->recorded_at->format('H:i') }} &bull; {{ $memo->notes ?? 'Catatan Lapangan' }}</span>
                                    <span class="text-slate-500 font-mono">{{ $memo->duration_seconds ?? 0 }}s</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
