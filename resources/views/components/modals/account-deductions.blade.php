{{-- Modal 1: Kurangi Saldo Wadah (Biaya Admin Debit, Pemeliharaan, Bunga, dll) --}}
<div id="modal-deduct-account" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-slate-800 border border-rose-500/40 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center space-x-2.5 mb-2">
            <span class="text-2xl">🔻</span>
            <div>
                <h3 class="text-base font-extrabold text-white">Kurangi Saldo Wadah (Biaya Admin)</h3>
                <span class="text-[11px] text-slate-300">Potong saldo rekening / kartu untuk biaya debit, admin, atau bunga</span>
            </div>
        </div>
        <p class="text-xs text-slate-300 font-medium mb-4">
            Catat pemotongan saldo m-banking/kartu yang terjadi di tanggal atau jam tertentu agar saldo pembukuan tetap presisi dengan saldo riil bank Anda.
        </p>

        <form method="POST" action="/operational/account-deduct-balance" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Wadah yang Terpotong <span class="text-rose-400">*</span></label>
                <select name="account_id" id="deduct_account_select" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    @php
                        $assetAccounts = \Illuminate\Support\Facades\Schema::hasTable('accounts')
                            ? \App\Models\Account::active()
                                ->whereIn('type', [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])
                                ->orderBy('name')
                                ->get()
                            : collect();
                    @endphp
                    @foreach($assetAccounts as $acc)
                        <option value="{{ $acc->id }}" data-balance="{{ $acc->current_balance }}">
                            {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Preset Cepat Jenis Potongan --}}
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Preset Jenis Pengurangan</label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="selectDeductPreset('Biaya Admin Kartu Debit')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        💳 Admin Debit
                    </button>
                    <button type="button" onclick="selectDeductPreset('Biaya Administrasi Bulanan')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        🏦 Admin Bulanan
                    </button>
                    <button type="button" onclick="selectDeductPreset('Biaya Pemeliharaan Kartu')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        🏷️ Pemeliharaan Kartu
                    </button>
                    <button type="button" onclick="selectDeductPreset('Pajak Bunga Rekening')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        📊 Pajak Bunga
                    </button>
                    <button type="button" onclick="selectDeductPreset('Biaya Transaksi / Transfer')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        🔄 Biaya Transfer
                    </button>
                    <button type="button" onclick="selectDeductPreset('Biaya Notifikasi SMS')" class="px-2.5 py-1 bg-slate-900 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 rounded-lg text-[11px] font-semibold transition">
                        📲 SMS Banking
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Keterangan Pengurangan / Alasan <span class="text-rose-400">*</span></label>
                <input type="text" name="description" id="deduct_description_input" placeholder="Contoh: Biaya Admin Debit BCA bulan ini" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nominal Pengurangan (Rp) <span class="text-rose-400">*</span></label>
                    <input type="number" name="amount" id="deduct_amount_input" min="1" step="1" placeholder="Contoh: 17000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-bold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Waktu Pemotongan</label>
                    <input type="datetime-local" name="transaction_time" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
            </div>

            {{-- Checkbox Simpan Sebagai Aturan Rutin Bulanan --}}
            <div class="p-3 bg-slate-900/90 border border-slate-700 rounded-xl space-y-2">
                <label class="flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="save_as_recurring" value="1" id="chk_save_as_recurring" onchange="toggleRecurringSchedule(this)" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-rose-500 focus:ring-rose-500">
                    <span class="text-xs font-bold text-slate-200">Simpan sebagai Jadwal Potongan Rutin Bulanan</span>
                </label>
                <div id="recurring_schedule_fields" class="hidden pl-6 pt-1">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-300">Potong setiap tanggal:</span>
                        <input type="number" name="deduction_day" min="1" max="31" value="{{ now()->day }}" class="w-16 bg-slate-800 border border-slate-600 rounded-lg px-2 py-1 text-white text-xs font-bold text-center">
                        <span class="text-xs text-slate-300">tiap bulannya</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Aturan ini akan muncul di kartu wadah dengan tombol eksekusi cepat setiap bulannya.</p>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-700/80">
                <button type="button" onclick="closeModal('modal-deduct-account')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-1.5 transition">
                    <span>🔻</span>
                    <span>Potong Saldo Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Kelola Jadwal Potongan Rutin Bulanan Wadah --}}
<div id="modal-manage-deductions" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-slate-800 border border-slate-700 max-w-lg w-full rounded-2xl p-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center space-x-2.5 mb-2">
            <span class="text-2xl">⚙️</span>
            <div>
                <h3 class="text-base font-extrabold text-white">Jadwal Potongan Rutin Bulanan Wadah</h3>
                <span class="text-[11px] text-slate-300">Atur pemotongan pasti bulanan per rekening (admin debit, bunga, pemeliharaan)</span>
            </div>
        </div>
        <p class="text-xs text-slate-300 font-medium mb-4">
            Setiap wadah memiliki jadwal dan biaya admin yang berbeda-beda. Di sini Anda bisa mengatur potongan rutin per wadah agar mudah dieksekusi setiap bulannya.
        </p>

        {{-- Form Tambah Jadwal Potongan Baru --}}
        <div class="bg-slate-900/90 border border-slate-700 rounded-xl p-4 mb-4 space-y-3">
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">➕ Tambah Aturan Potongan Rutin</span>
            <form method="POST" action="/operational/account-deduction" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih Wadah</label>
                    <select name="account_id" id="manage_deduction_account_select" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($assetAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst($acc->type->value ?? $acc->type) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Potongan</label>
                        <input type="text" name="name" placeholder="Misal: Admin Debit BCA" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" min="1" placeholder="17000" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Tanggal Pemotongan (1 - 31)</label>
                        <input type="number" name="deduction_day" min="1" max="31" value="25" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" placeholder="Misal: Otomatis potong tgl 25 malam" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow transition">
                        Simpan Jadwal Potongan
                    </button>
                </div>
            </form>
        </div>

        {{-- Daftar Seluruh Jadwal Potongan Rutin yang Aktif --}}
        <div>
            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block mb-2">📋 Daftar Potongan Rutin Terdaftar</span>
            @php
                $allDeductions = \Illuminate\Support\Facades\Schema::hasTable('account_deductions')
                    ? \App\Models\AccountDeduction::with('account')->orderBy('deduction_day')->get()
                    : collect();
            @endphp
            @if($allDeductions->isEmpty())
                <div class="p-4 bg-slate-900/60 border border-slate-800 rounded-xl text-center text-xs text-slate-400">
                    Belum ada jadwal potongan rutin yang disimpan. Silakan buat menggunakan form di atas.
                </div>
            @else
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    @foreach($allDeductions as $item)
                        <div class="bg-slate-900/90 border border-slate-700/80 rounded-xl p-3 flex items-center justify-between">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-extrabold text-xs text-white">{{ $item->name }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $item->account->name }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-300 font-medium mt-0.5">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }} &bull; Setiap tanggal <strong class="text-amber-400">{{ $item->deduction_day }}</strong>
                                    @if($item->last_deducted_at)
                                        &bull; <span class="text-slate-400 italic">Terakhir: {{ $item->last_deducted_at->format('d M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                @if(!$item->isDeductedThisMonth())
                                    <form method="POST" action="/operational/account-deduction/{{ $item->id }}/execute" onsubmit="return confirm('Eksekusi potongan rutin {{ $item->name }} (Rp {{ number_format($item->amount, 0, ',', '.') }}) sekarang?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-bold bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 rounded-lg transition" title="Eksekusi pemotongan saldo bulan ini">
                                            ⚡ Eksekusi
                                        </button>
                                    </form>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        ✓ Lunas Bln Ini
                                    </span>
                                @endif
                                <form method="POST" action="/operational/account-deduction/{{ $item->id }}" onsubmit="return confirm('Hapus aturan potongan rutin ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 rounded-lg transition" title="Hapus jadwal">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-700/80 mt-4">
            <button type="button" onclick="closeModal('modal-manage-deductions')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openDeductAccountModal(accountId = null, accountName = '', currentBalance = 0) {
        if (accountId) {
            const select = document.getElementById('deduct_account_select');
            if (select) {
                select.value = accountId;
            }
        }
        openModal('modal-deduct-account');
    }

    function selectDeductPreset(presetName) {
        const input = document.getElementById('deduct_description_input');
        if (input) {
            input.value = presetName;
            input.focus();
        }
    }

    function toggleRecurringSchedule(checkbox) {
        const fields = document.getElementById('recurring_schedule_fields');
        if (fields) {
            if (checkbox.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }
    }

    function openManageDeductionsModal(accountId = null) {
        if (accountId) {
            const select = document.getElementById('manage_deduction_account_select');
            if (select) {
                select.value = accountId;
            }
        }
        openModal('modal-manage-deductions');
    }
</script>
