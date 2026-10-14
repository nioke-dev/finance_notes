<x-layouts.app title="Closing & Rekonsiliasi Harian">
    <div class="space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
                ⚠️ {{ session('error') ?? $errors->first() }}
            </div>
        @endif

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
                    &larr; Simpan Draft / Tutup Nanti
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sisi Kiri: Form Hitung Fisik Kas Dompet --}}
            <form method="POST" action="/closing/{{ $workDay->id }}/denominations" class="lg:col-span-7 bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-5">
                @csrf
                <div class="border-b border-slate-700/60 pb-3 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Denominasi Uang Tunai Fisik</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ketik jumlah lembar & keping uang di dompet setelah pulang narik</p>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow">
                        Simpan Hitungan Fisik
                    </button>
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
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    min="0"
                                    name="{{ $field }}" 
                                    id="{{ $field }}" 
                                    value="{{ old($field, $denominations->$field ?? 0) }}"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
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
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    min="0"
                                    name="{{ $field }}" 
                                    id="{{ $field }}" 
                                    value="{{ old($field, $denominations->$field ?? 0) }}"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Total Fisik Terhitung --}}
                <div class="bg-slate-900/80 border border-slate-700 rounded-xl p-4 flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold uppercase text-slate-400 block">Total Akumulasi Fisik Dompet</span>
                        <span id="closing-total-pieces" class="text-[11px] text-slate-400 block">{{ $denominations->total_pieces ?? 0 }} lembar & koin</span>
                    </div>
                    <span id="closing-total-amount" class="text-xl font-extrabold text-emerald-400">
                        Rp {{ number_format($denominations->total_physical_amount ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                {{-- Penanda Uang Rusak / Sobek (Opsional) --}}
                <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">✂️</span>
                        <div>
                            <span class="text-xs font-bold text-white block">Catatan Uang Rusak / Sobek (Opsional)</span>
                            <span class="text-[10px] text-slate-400 block">Tandai jika ada uang sobek/rusak di dompet yang perlu segera dibelanjakan ke SPBU/minimarket.</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nominal Uang Rusak (Rp)</label>
                            <input 
                                type="number" 
                                inputmode="numeric"
                                pattern="[0-9]*"
                                name="damaged_notes_amount" 
                                min="0" 
                                step="1000"
                                value="{{ old('damaged_notes_amount', $denominations->damaged_notes_amount ?? 0) }}" 
                                placeholder="0"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white text-xs"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Keterangan / Kondisi</label>
                            <input 
                                type="text" 
                                name="damaged_notes_note" 
                                value="{{ old('damaged_notes_note', $denominations->damaged_notes_note ?? '') }}" 
                                placeholder="Contoh: 1 lembar 50rb sobek ujung"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white text-xs"
                            >
                        </div>
                    </div>
                </div>

                {{-- Smart Denomination Analysis / Recommendations --}}
                @php $closingRecs = $denominations->getRecommendations(); @endphp
                @if(count($closingRecs) > 0)
                    <div class="space-y-2 pt-2 border-t border-slate-700/60">
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">⚡ Rekomendasi Kas & Beban Dompet:</span>
                        @foreach($closingRecs as $rec)
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
                            @endphp
                            <div class="p-3 rounded-xl border text-xs flex items-start space-x-2.5 {{ $cardClass }}">
                                <span class="text-base">{{ $icon }}</span>
                                <div class="flex-1">
                                    <span class="font-bold block">{{ $rec['label'] }}</span>
                                    <span class="mt-0.5 block leading-relaxed">{{ $rec['message'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </form>

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
                    @php 
                        $isBalanced = ($reconciliationReport['total_absolute_discrepancy'] ?? 0) === 0;
                        $cashAccReport = collect($reconciliationReport['accounts'] ?? [])->firstWhere('account_name', 'Cash');
                        $cashDiscrepancy = $cashAccReport['discrepancy'] ?? 0;
                    @endphp
                    <div class="p-3.5 rounded-xl border {{ $isBalanced ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-rose-500/10 border-rose-500/30' }} flex justify-between items-center">
                        <span class="text-xs font-bold {{ $isBalanced ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ $isBalanced ? '✓ Saldo Seimbang Sempurna (Rp0)' : '⚠️ Masih Ada Selisih' }}
                        </span>
                        <span class="text-sm font-extrabold {{ $isBalanced ? 'text-emerald-400' : 'text-rose-400' }}">
                            Rp {{ number_format($reconciliationReport['total_absolute_discrepancy'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Bantuan Selisih Tak Terlacak (Kas Hilang / Lebih) --}}
                    @if(!$isBalanced && $cashDiscrepancy !== 0)
                        <div class="p-4 rounded-xl border bg-amber-500/10 border-amber-500/30 space-y-3">
                            <div class="flex items-start space-x-2.5">
                                <span class="text-amber-400 text-base">💡</span>
                                <div>
                                    <span class="text-xs font-bold text-amber-200 block">
                                        Selisih Kas Fisik: {{ $cashDiscrepancy > 0 ? '+Rp ' . number_format($cashDiscrepancy, 0, ',', '.') . ' (Lebih)' : '-Rp ' . number_format(abs($cashDiscrepancy), 0, ',', '.') . ' (Kurang)' }}
                                    </span>
                                    <span class="text-[11px] text-amber-300/80 block mt-0.5 leading-relaxed">
                                        Sudah dicari tapi tetap tidak ketemu? Misal uang receh jatuh di jalan, tercecer, atau lupa kembalian. Anda bisa mencatat penyesuaian selisih secara resmi dengan catatan audit agar hari ini bisa langsung difinalisasi.
                                    </span>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                onclick="openModal('modal-adjust-discrepancy')" 
                                class="w-full py-2.5 px-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl transition shadow flex items-center justify-center space-x-2"
                            >
                                <span>⚖️</span>
                                <span>Sesuaikan Kas Hilang / Lebih Tak Terlacak</span>
                            </button>
                        </div>
                    @endif

                    {{-- Form Finalisasi Hari Kerja --}}
                    <form method="POST" action="/closing/{{ $workDay->id }}/finalize" class="space-y-3 pt-2">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Catatan Closing (Opsional)</label>
                            <input type="text" name="closing_notes" placeholder="Catatan shift narik hari ini..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                        </div>

                        <button 
                            type="submit" 
                            {{ !$isBalanced ? 'disabled' : '' }}
                            class="w-full py-3 px-4 font-bold text-sm rounded-xl transition shadow flex items-center justify-center space-x-2 {{ $isBalanced ? 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer' : 'bg-slate-700 text-slate-400 cursor-not-allowed opacity-60' }}"
                        >
                            <span>🔒 Finalkan Hari Kerja Ini</span>
                        </button>
                    </form>
                </div>

                {{-- Kotak Hitam: Voice Memos Helm Hari Ini --}}
                <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">🎙️</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Kotak Hitam Helm (Voice Memos)</h3>
                                <span class="text-[10px] text-slate-400">Dengar rekaman suara Anda di jalan sebelum menyesuaikan selisih</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-slate-900 text-slate-300">
                            {{ $workDay->voiceMemos->count() }} rekaman
                        </span>
                    </div>

                    @if($workDay->voiceMemos->isEmpty())
                        <div class="py-4 text-center text-slate-500 text-xs">
                            Belum ada rekaman suara helm yang diunggah untuk shift ini.
                        </div>
                    @else
                        <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                            @foreach($workDay->voiceMemos as $memo)
                                <div class="p-3 bg-slate-900/70 border border-slate-700/50 rounded-xl space-y-2">
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="font-bold text-white flex items-center space-x-1.5">
                                            <span>⏰</span>
                                            <span>{{ $memo->recorded_at ? $memo->recorded_at->format('H:i:s') : $memo->created_at->format('H:i:s') }}</span>
                                        </span>
                                        @if($memo->latitude && $memo->longitude)
                                            <a href="https://www.google.com/maps?q={{ $memo->latitude }},{{ $memo->longitude }}" target="_blank" class="text-blue-400 hover:text-blue-300 hover:underline flex items-center space-x-1 font-semibold">
                                                <span>📍</span>
                                                <span>Lihat di Maps</span>
                                            </a>
                                        @endif
                                    </div>
                                    <audio controls class="w-full h-8 rounded">
                                        <source src="{{ asset('storage/' . $memo->file_path) }}" type="audio/mpeg">
                                        Browser Anda tidak mendukung pemutar audio.
                                    </audio>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Mutasi Kas Shift Ini & Koreksi Cepat di Tempat (In-Place Edit) --}}
                <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">📋</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Mutasi Kas Shift Ini</h3>
                                <span class="text-[10px] text-slate-400">Ada salah ketik tadi siang? Koreksi langsung di sini</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-slate-900 text-slate-300">
                            {{ count($cashTransactions) }} mutasi
                        </span>
                    </div>

                    @if(count($cashTransactions) === 0)
                        <div class="py-4 text-center text-slate-500 text-xs">
                            Belum ada mutasi kas fisik yang tercatat untuk shift ini.
                        </div>
                    @else
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($cashTransactions as $tx)
                                @php
                                    $isReversed = \App\Models\Transaction::where('metadata->reversed_transaction_id', $tx->id)->exists();
                                    $isReversal = $tx->type === 'reversal';
                                @endphp
                                <div class="p-3 bg-slate-900/70 border border-slate-700/50 rounded-xl flex items-center justify-between text-xs {{ $isReversed ? 'opacity-40' : '' }}">
                                    <div class="flex-1 pr-2">
                                        <span class="font-bold text-white block">{{ $tx->description }}</span>
                                        <span class="text-[10px] text-slate-400">
                                            {{ $tx->transaction_time ? $tx->transaction_time->format('H:i') : '' }} &bull; 
                                            <span class="uppercase font-semibold text-slate-300">{{ $tx->type }}</span>
                                            @if($isReversed)
                                                &bull; <span class="text-rose-400 font-semibold">(Telah Dikoreksi)</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="text-right flex items-center space-x-2">
                                        <span class="font-bold text-white block">
                                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                        </span>
                                        @if(!$isReversed && !$isReversal)
                                            <button 
                                                type="button" 
                                                onclick="openCorrectModal('{{ $tx->id }}', '{{ addslashes($tx->description) }}', '{{ $tx->amount }}')"
                                                class="px-2 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 border border-amber-500/30 rounded-lg text-[10px] font-bold transition flex items-center space-x-1"
                                                title="Koreksi Nominal"
                                            >
                                                <span>✏️</span>
                                                <span>Koreksi</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        const denomValues = {
            'bill_100000': 100000,
            'bill_50000': 50000,
            'bill_20000': 20000,
            'bill_10000': 10000,
            'bill_5000': 5000,
            'bill_2000': 2000,
            'bill_1000': 1000,
            'coin_1000': 1000,
            'coin_500': 500,
            'coin_200': 200,
            'coin_100': 100
        };

        function calculateClosingTotal() {
            let total = 0;
            let pieces = 0;
            for (const [id, value] of Object.entries(denomValues)) {
                const el = document.getElementById(id);
                if (el) {
                    const count = parseInt(el.value) || 0;
                    total += count * value;
                    pieces += count;
                }
            }
            const totalEl = document.getElementById('closing-total-amount');
            const piecesEl = document.getElementById('closing-total-pieces');
            if (totalEl) totalEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            if (piecesEl) piecesEl.innerText = pieces + ' lembar & koin';
        }

        document.querySelectorAll('input[name^="bill_"], input[name^="coin_"]').forEach(input => {
            input.addEventListener('input', calculateClosingTotal);
        });

        function openModal(id) {
            document.getElementById(id)?.classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id)?.classList.add('hidden');
        }

        function openCorrectModal(txId, desc, currentAmount) {
            const form = document.getElementById('form-correct-tx');
            if (form) {
                form.action = '/closing/{{ $workDay->id }}/transactions/' + txId + '/correct';
            }
            const descEl = document.getElementById('correct-tx-desc');
            if (descEl) descEl.innerText = desc;
            const currentEl = document.getElementById('correct-tx-current');
            if (currentEl) currentEl.innerText = 'Rp ' + Number(currentAmount).toLocaleString('id-ID');
            const inputEl = document.getElementById('correct-new-amount');
            if (inputEl) inputEl.value = currentAmount;
            openModal('modal-correct-closing-tx');
        }
    </script>

    {{-- Modal Penyesuaian Selisih Kas Hilang / Tak Terlacak --}}
    <div id="modal-adjust-discrepancy" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">⚖️</span>
                    <h3 class="text-base font-bold text-white">Sesuaikan Selisih Kas Fisik</h3>
                </div>
                <button type="button" onclick="closeModal('modal-adjust-discrepancy')" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            @php 
                $cashAccModal = collect($reconciliationReport['accounts'] ?? [])->firstWhere('account_name', 'Cash'); 
                $discModal = $cashAccModal['discrepancy'] ?? 0;
            @endphp

            <form method="POST" action="/closing/{{ $workDay->id }}/adjust-discrepancy" class="space-y-4">
                @csrf
                <div class="p-3.5 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Saldo Menurut Sistem:</span>
                        <span class="font-bold text-white">Rp {{ number_format($cashAccModal['ledger_balance'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Uang Fisik di Dompet:</span>
                        <span class="font-bold text-white">Rp {{ number_format($cashAccModal['actual_balance'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-700 pt-1.5 font-bold">
                        <span class="text-amber-400">Selisih yang Disesuaikan:</span>
                        <span class="{{ $discModal < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                            {{ $discModal < 0 ? '-Rp ' . number_format(abs($discModal), 0, ',', '.') . ' (Uang Kurang/Hilang)' : '+Rp ' . number_format($discModal, 0, ',', '.') . ' (Uang Lebih)' }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Alasan / Catatan Audit Selisih <span class="text-rose-400">*</span>
                    </label>
                    <textarea 
                        name="reason" 
                        required 
                        rows="3" 
                        placeholder="Contoh: Uang koin Rp2.000 jatuh di jalan saat bayar parkir atau lupa minta kembalian di warung..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    ></textarea>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Wajib diisi minimal 5 karakter. Catatan ini disimpan permanen di riwayat audit.
                    </span>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-adjust-discrepancy')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow">
                        Simpan & Seimbangkan Saldo
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Koreksi Transaksi Kas di Tempat --}}
    <div id="modal-correct-closing-tx" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-base font-bold text-white">Koreksi Transaksi Kas</h3>
                </div>
                <button type="button" onclick="closeModal('modal-correct-closing-tx')" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form id="form-correct-tx" method="POST" action="" class="space-y-4">
                @csrf
                <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700 text-xs space-y-1">
                    <span class="text-slate-400 block">Transaksi yang dikoreksi:</span>
                    <span id="correct-tx-desc" class="font-bold text-white block"></span>
                    <span class="text-slate-400 block mt-1">Nominal saat ini: <span id="correct-tx-current" class="text-amber-400 font-bold"></span></span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Nominal Sebenarnya (Rp) <span class="text-emerald-400">*</span>
                    </label>
                    <input 
                        type="number" 
                        inputmode="numeric" 
                        pattern="[0-9]*" 
                        name="new_amount" 
                        id="correct-new-amount" 
                        min="0" 
                        required 
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Alasan Koreksi <span class="text-rose-400">*</span>
                    </label>
                    <textarea 
                        name="reason" 
                        required 
                        rows="2" 
                        placeholder="Contoh: Salah ketik nominal tadi siang, struk bensin aslinya Rp40.000..." 
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    ></textarea>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Minimal 5 karakter. Transaksi lama akan dibalikkan dan dicatat ke audit log.
                    </span>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modal-correct-closing-tx')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow">
                        Simpan Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
