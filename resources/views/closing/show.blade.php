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
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Audit & Closing Shift Narik</span>
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

        {{-- SEKSI EVALUASI SHOPEEFOOD & KOMPARASI PARKIR VS TIP --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border border-purple-500/40 rounded-2xl p-5 sm:p-6 shadow-2xl space-y-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-3">
                    <span class="text-3xl">🛵</span>
                    <div>
                        <h3 class="text-base font-black text-white">Evaluasi ShopeeFood Driver & Komparasi Parkir vs Tip</h3>
                        <p class="text-xs text-slate-300">Bandingkan beban parkir vs tip, serta selaraskan omzet ShopeeFood ke saldo rekening sebelum closing.</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold">
                    Audit Pra-Closing
                </span>
            </div>

            <form method="POST" action="/operational/digital-reconciliation" class="space-y-5">
                @csrf
                <input type="hidden" name="work_day_id" value="{{ $workDay->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    {{-- Kolom Kiri: Input Data Aplikasi ShopeeFood Driver --}}
                    {{-- Kolom Kiri: Rincian Pendapatan yang Diterima (Layar ShopeeFood Driver) --}}
                    <div class="lg:col-span-6 bg-slate-950/80 border border-orange-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-base">📱</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-orange-400">Rincian Pendapatan yang Diterima</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-orange-500/20 text-orange-300 font-bold border border-orange-500/30">
                                ShopeeFood Driver
                            </span>
                        </div>

                        {{-- Total Pendapatan yang Diterima (Tampilan Atas Layar HP Driver) --}}
                        <div class="p-3 bg-slate-900/90 border border-orange-500/30 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 block">Total Pendapatan yang Diterima</span>
                                <span id="closing-disp-shopee-total" class="text-xl font-black text-orange-400">Rp 0</span>
                            </div>
                            <div class="text-right">
                                <span id="closing-disp-completed-orders" class="px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-300 font-black text-xs border border-orange-500/30 inline-block">
                                    {{ old('completed_orders', $latestSnapshot?->completed_orders ?? 0) }} Pesanan Selesai
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                    Biaya Pengantaran (Rp)
                                </label>
                                <input 
                                    type="number" 
                                    inputmode="numeric" 
                                    pattern="[0-9]*" 
                                    name="delivery_fee" 
                                    id="closing-delivery-fee" 
                                    value="{{ old('delivery_fee', $latestSnapshot?->delivery_fee ?? 0) }}" 
                                    min="0" 
                                    step="1" 
                                    oninput="calculateClosingReconDiff()" 
                                    placeholder="0" 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                >
                                <span class="text-[10px] text-slate-400">Sesuai baris <strong>Biaya Pengantaran</strong></span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                    Tip dari Pelanggan (Rp)
                                </label>
                                <input 
                                    type="number" 
                                    inputmode="numeric" 
                                    pattern="[0-9]*" 
                                    name="customer_tips" 
                                    id="closing-customer-tips" 
                                    value="{{ old('customer_tips', $latestSnapshot?->customer_tips ?? 0) }}" 
                                    min="0" 
                                    step="1" 
                                    oninput="calculateClosingReconDiff()" 
                                    placeholder="0" 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                >
                                <span class="text-[10px] text-slate-400">Sesuai baris <strong>Tip dari Pelanggan</strong></span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                    Bonus Insentif (Rp)
                                </label>
                                <input 
                                    type="number" 
                                    inputmode="numeric" 
                                    pattern="[0-9]*" 
                                    name="hub_bonus" 
                                    id="closing-hub-bonus" 
                                    value="{{ old('hub_bonus', $latestSnapshot?->hub_bonus ?? 0) }}" 
                                    min="0" 
                                    step="1" 
                                    oninput="calculateClosingReconDiff()" 
                                    placeholder="0" 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                >
                                <span class="text-[10px] text-slate-400">Sesuai baris <strong>Bonus Insentif</strong></span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                    Pendapatan Hub (Rp)
                                </label>
                                <input 
                                    type="number" 
                                    inputmode="numeric" 
                                    pattern="[0-9]*" 
                                    name="hub_income" 
                                    id="closing-hub-income" 
                                    value="{{ old('hub_income', $latestSnapshot?->hub_income ?? 0) }}" 
                                    min="0" 
                                    step="1" 
                                    oninput="calculateClosingReconDiff()" 
                                    placeholder="0" 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                >
                                <span class="text-[10px] text-slate-400">Sesuai baris <strong>Pendapatan Hub</strong></span>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                    Pesanan Selesai
                                </label>
                                <input 
                                    type="number" 
                                    inputmode="numeric" 
                                    pattern="[0-9]*" 
                                    name="completed_orders" 
                                    id="closing-completed-orders" 
                                    value="{{ old('completed_orders', $latestSnapshot?->completed_orders ?? 0) }}" 
                                    min="0" 
                                    step="1" 
                                    oninput="calculateClosingReconDiff()" 
                                    placeholder="Contoh: 12" 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                >
                                <span class="text-[10px] text-slate-400">Sesuai tulisan <strong>... Pesanan Selesai</strong></span>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Posisi Saldo Terkini di Wadah Digital & Kas --}}
                    <div class="lg:col-span-6 bg-slate-950/80 border border-emerald-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-base">💼</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-emerald-400">2. Posisi Saldo Terkini di Wadah Narik</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                                Wadah Riil
                            </span>
                        </div>

                        <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                            @foreach($digitalReconAccounts as $idx => $dAcc)
                                <div class="bg-slate-900/90 border border-slate-700/70 rounded-xl p-2.5 space-y-1.5">
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
                                        <span class="text-[11px] text-slate-300">
                                            Buku: <strong class="text-white font-black">Rp {{ number_format($dAcc->current_balance, 0, ',', '.') }}</strong>
                                        </span>
                                    </div>

                                    <input type="hidden" name="balances[{{ $idx }}][account_id]" value="{{ $dAcc->id }}">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-slate-300 font-bold">Rp</span>
                                        <input 
                                            type="number" 
                                            inputmode="numeric" 
                                            pattern="[0-9]*" 
                                            name="balances[{{ $idx }}][actual_balance]" 
                                            id="closing-recon-input-{{ $dAcc->id }}"
                                            value="{{ $dAcc->name === 'Cash' && ($denominations->total_physical_amount ?? 0) > 0 ? $denominations->total_physical_amount : $dAcc->current_balance }}"
                                            data-recorded="{{ $dAcc->current_balance }}"
                                            data-account-name="{{ $dAcc->name }}"
                                            oninput="calculateClosingReconDiff()"
                                            class="closing-recon-digital-input w-full bg-slate-950 border border-slate-600 rounded-xl pl-8 pr-3 py-1.5 text-white text-xs font-black focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        >
                                    </div>
                                    <div id="closing-recon-diff-msg-{{ $dAcc->id }}" class="text-[10px] font-semibold text-slate-300">
                                        Status: Cocok Sempurna (Rp 0)
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-2 bg-slate-900/90 border border-slate-800 rounded-xl flex justify-between items-center text-xs">
                            <span class="text-slate-300 font-bold">Total Uang Wadah:</span>
                            <strong id="closing-disp-wallets-total" class="text-emerald-400 font-extrabold text-sm">Rp 0</strong>
                        </div>
                    </div>
                </div>

                {{-- PEMISAH POKOK COD VIA QRIS (PENGEMBALIAN MODAL MAKANAN vs TIP MURNI) --}}
                <div class="bg-amber-950/40 border border-amber-500/40 rounded-2xl p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">🍜</span>
                            <div>
                                <label for="closing-cod-qris-amount" class="text-xs font-black text-amber-300 block">
                                    Tagihan Pokok COD Dibayar Customer via QRIS (Rp)
                                </label>
                                <p class="text-[11px] text-slate-300 leading-snug">
                                    Pernah antar pesanan COD tapi customer membayar tagihan makanannya transfer lewat QRIS GoPay/Merchant?
                                </p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 shrink-0">
                            Pengurang Modal Makanan
                        </span>
                    </div>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-amber-400 font-bold">Rp</span>
                        <input 
                            type="number" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            name="cod_qris_amount" 
                            id="closing-cod-qris-amount" 
                            value="0" 
                            min="0" 
                            step="1" 
                            oninput="calculateClosingReconDiff()" 
                            placeholder="0 jika tidak ada customer COD bayar QRIS"
                            class="w-full bg-slate-900 border border-amber-500/50 rounded-xl pl-9 pr-3 py-2 text-white text-sm font-black focus:ring-2 focus:ring-amber-500 focus:outline-none"
                        >
                    </div>

                    @if(!empty($todayCodQrisBillTotal) && $todayCodQrisBillTotal > 0)
                        <div class="flex items-center justify-between bg-amber-900/30 border border-amber-500/30 rounded-xl px-3 py-1.5">
                            <span class="text-[11px] text-amber-200">
                                💡 Terdeteksi tagihan order COD shift ini: <strong>Rp {{ number_format($todayCodQrisBillTotal, 0, ',', '.') }}</strong>
                            </span>
                            <button 
                                type="button" 
                                onclick="document.getElementById('closing-cod-qris-amount').value = {{ $todayCodQrisBillTotal }}; calculateClosingReconDiff();"
                                class="px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-black transition"
                            >
                                Pakai Angka Ini
                            </button>
                        </div>
                    @endif

                    <div class="text-[10px] text-amber-200/90 leading-relaxed bg-slate-900/70 p-2.5 rounded-xl border border-amber-500/20">
                        🛡️ <strong>Prinsip Anti Ambil Mentah-Mentah:</strong> Uang masuk QRIS tidak akan ditelan mentah-mentah sebagai tip! Sebesar nominal makanan COD ini dialokasikan ke <em>Talangan Pesanan COD</em> (pengembalian modal ShopeePay Anda). Hanya sisa kelebihannya yang diakui sebagai <strong>Tip QRIS Murni</strong> untuk diadu dengan biaya parkir.
                    </div>
                </div>

                {{-- BOX KOMPARASI EVALUASI PARKIR VS TIP & QRIS INFLOW --}}
                <div id="closing-parking-eval-box" class="bg-gradient-to-br from-slate-950 to-indigo-950/40 border border-indigo-500/50 rounded-2xl p-4 space-y-3" data-parking-expense="{{ $dayParkingExpense }}" data-parking-paid-count="{{ $dayPaidParkingCount }}" data-parking-resto-count="{{ $dayRestoPaidParkingCount }}">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">🅿️</span>
                            <h4 class="text-xs font-black uppercase tracking-wider text-indigo-300">Komparasi Parkir vs Tip & Pendapatan QRIS</h4>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-500/30">
                            {{ $dayPaidParkingCount }}x Parkir Bayar &bull; {{ $dayRestoPaidParkingCount }}x Gratis Resto
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block text-[10px]">Total Beban Parkir:</span>
                            <strong id="closing-parking-expense-disp" class="text-rose-400 font-black text-xs">Rp {{ number_format($dayParkingExpense, 0, ',', '.') }}</strong>
                            <span class="text-[9px] text-slate-500 block">Biaya riil motor di spot</span>
                        </div>
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block text-[10px]">Total Tip & Inflow QRIS:</span>
                            <strong id="closing-parking-tips-disp" class="text-emerald-400 font-black text-xs">Rp 0</strong>
                            <span class="text-[9px] text-slate-500 block">Tip Shopee + Inflow GoPay/QRIS</span>
                        </div>
                        <div class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block text-[10px]">Evaluasi Bersih Parkir:</span>
                            <strong id="closing-parking-net-disp" class="text-slate-300 font-black text-xs">-</strong>
                            <span class="text-[9px] text-slate-500 block">Tip - Parkir</span>
                        </div>
                    </div>

                    <div id="closing-parking-status-banner" class="p-3 rounded-xl text-xs leading-relaxed font-medium">
                        <!-- Status diupdate live via JS -->
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                    <span class="text-xs text-slate-400">
                        Setelah tombol ditekan, omzet ShopeeFood & tip QRIS akan otomatis dicatat ke jurnal, dan saldo rekening siap seimbang (Rp0).
                    </span>
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-black rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                        <span>💾</span>
                        <span>Simpan & Selaraskan Saldo Shift Ini</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sisi Kiri: Form Hitung Fisik Kas Dompet --}}
            <form method="POST" action="/closing/{{ $workDay->id }}/denominations" class="lg:col-span-7 bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-5">
                @csrf
                <div class="border-b border-slate-700/60 pb-3 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Denominasi Uang Tunai Fisik</h3>
                        <p class="text-xs text-slate-300 font-medium mt-0.5">Ketik jumlah lembar & keping uang di dompet setelah pulang narik</p>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow">
                        Simpan Hitungan Fisik
                    </button>
                </div>

                {{-- Pecahan Uang Kertas --}}
                <div class="space-y-3">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Uang Kertas (Lembar)</span>
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
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Uang Logam / Koin (Keping)</span>
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
                        <span class="text-xs font-bold uppercase text-slate-300 block">Total Akumulasi Fisik Dompet</span>
                        <span id="closing-total-pieces" class="text-[11px] text-slate-300 font-medium block">{{ $denominations->total_pieces ?? 0 }} lembar & koin</span>
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
                            <span class="text-[10px] text-slate-300 font-medium block">Tandai jika ada uang sobek/rusak di dompet yang perlu segera dibelanjakan ke SPBU/minimarket.</span>
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
                                step="1"
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
                                    <span class="text-[11px] text-slate-300 font-medium">
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
                                <span class="text-[10px] text-slate-300 font-medium">Dengar rekaman suara Anda di jalan sebelum menyesuaikan selisih</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-slate-900 text-slate-300">
                            {{ $workDay->voiceMemos->count() }} rekaman
                        </span>
                    </div>

                    @if($workDay->voiceMemos->isEmpty())
                        <div class="py-4 text-center text-slate-300 font-medium text-xs">
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
                                <span class="text-[10px] text-slate-300 font-medium">Ada salah ketik tadi siang? Koreksi langsung di sini</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-slate-900 text-slate-300">
                            {{ count($cashTransactions) }} mutasi
                        </span>
                    </div>

                    @if(count($cashTransactions) === 0)
                        <div class="py-4 text-center text-slate-300 font-medium text-xs">
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
                                        <span class="text-[10px] text-slate-300 font-medium">
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

            // Sinkronkan ke input kas di seksi rekonsiliasi jika ada
            const cashReconInput = document.getElementById('closing-recon-input-{{ $cashAccount?->id }}');
            if (cashReconInput && total > 0) {
                cashReconInput.value = total;
                calculateClosingReconDiff();
            }
        }

        function calculateClosingReconDiff() {
            // 1. Hitung Total ShopeeFood
            const deliveryFee = parseInt(document.getElementById('closing-delivery-fee')?.value) || 0;
            const hubIncome = parseInt(document.getElementById('closing-hub-income')?.value) || 0;
            const hubBonus = parseInt(document.getElementById('closing-hub-bonus')?.value) || 0;
            const customerTips = parseInt(document.getElementById('closing-customer-tips')?.value) || 0;
            const completedOrders = parseInt(document.getElementById('closing-completed-orders')?.value) || 0;
            const totalShopee = deliveryFee + hubIncome + hubBonus + customerTips;

            const dispShopee = document.getElementById('closing-disp-shopee-total');
            if (dispShopee) dispShopee.innerText = 'Rp ' + totalShopee.toLocaleString('id-ID');

            const dispOrders = document.getElementById('closing-disp-completed-orders');
            if (dispOrders) dispOrders.innerText = completedOrders + ' Pesanan Selesai';

            // 2. Hitung Saldo Wadah & Inflow QRIS / GoPay
            let totalActualWallets = 0;
            let gopayInflow = 0;
            let gopayMerchantInflow = 0;
            let cashInflow = 0;

            document.querySelectorAll('.closing-recon-digital-input').forEach(input => {
                const accName = input.getAttribute('data-account-name') || '';
                const recorded = parseInt(input.getAttribute('data-recorded')) || 0;
                const actual = parseInt(input.value) || 0;
                const diff = actual - recorded;
                const accId = input.id.replace('closing-recon-input-', '');
                const msgEl = document.getElementById('closing-recon-diff-msg-' + accId);

                totalActualWallets += actual;

                if (diff > 0) {
                    if (accName === 'GoPay') {
                        gopayInflow += diff;
                    } else if (accName === 'GoPay Merchant') {
                        gopayMerchantInflow += diff;
                    } else if (accName === 'Cash') {
                        cashInflow += diff;
                    }
                }

                if (msgEl) {
                    if (diff > 0) {
                        msgEl.innerText = '🎉 Surplus / Inflow: +Rp ' + diff.toLocaleString('id-ID');
                        msgEl.className = 'text-[10px] font-bold mt-1 text-emerald-400';
                    } else if (diff < 0) {
                        msgEl.innerText = '⚠️ Selisih Kurang: -Rp ' + Math.abs(diff).toLocaleString('id-ID');
                        msgEl.className = 'text-[10px] font-bold mt-1 text-rose-400';
                    } else {
                        msgEl.innerText = '✓ Status: Saldo Cocok (Rp 0)';
                        msgEl.className = 'text-[10px] font-semibold mt-1 text-slate-300';
                    }
                }
            });

            const dispWallets = document.getElementById('closing-disp-wallets-total');
            if (dispWallets) dispWallets.innerText = 'Rp ' + totalActualWallets.toLocaleString('id-ID');

            // 3. Komparasi Evaluasi Parkir vs Tip & Pendapatan QRIS
            const parkingBox = document.getElementById('closing-parking-eval-box');
            if (parkingBox) {
                const parkingExpense = parseInt(parkingBox.getAttribute('data-parking-expense')) || 0;
                const parkingPaidCount = parseInt(parkingBox.getAttribute('data-parking-paid-count')) || 0;

                // PERHITUNGAN TELITI COD VIA QRIS:
                // Jangan ambil mentah-mentah inflow QRIS sebagai tip!
                const codQrisBill = parseInt(document.getElementById('closing-cod-qris-amount')?.value) || 0;
                const grossQrisInflow = gopayInflow + gopayMerchantInflow;
                const codReimbursed = Math.min(grossQrisInflow, codQrisBill);
                const netQrisTip = Math.max(0, grossQrisInflow - codQrisBill);

                const totalTipsAndInflow = customerTips + netQrisTip + cashInflow;
                const netParkingResult = totalTipsAndInflow - parkingExpense;

                const expenseDisp = document.getElementById('closing-parking-expense-disp');
                const tipsDisp = document.getElementById('closing-parking-tips-disp');
                const netDisp = document.getElementById('closing-parking-net-disp');
                const statusBanner = document.getElementById('closing-parking-status-banner');

                if (expenseDisp) expenseDisp.innerText = 'Rp ' + parkingExpense.toLocaleString('id-ID');
                if (tipsDisp) tipsDisp.innerText = 'Rp ' + totalTipsAndInflow.toLocaleString('id-ID');

                if (netDisp) {
                    if (netParkingResult > 0) {
                        netDisp.innerText = '🎉 Surplus +Rp ' + netParkingResult.toLocaleString('id-ID');
                        netDisp.className = 'text-emerald-400 font-black text-xs';
                    } else if (netParkingResult < 0) {
                        netDisp.innerText = '⚠️ Minus -Rp ' + Math.abs(netParkingResult).toLocaleString('id-ID');
                        netDisp.className = 'text-rose-400 font-black text-xs';
                    } else {
                        netDisp.innerText = '✓ Impas (Rp 0)';
                        netDisp.className = 'text-emerald-300 font-black text-xs';
                    }
                }

                if (statusBanner) {
                    let codNoticeHtml = '';
                    if (codReimbursed > 0) {
                        codNoticeHtml = `<div class="mt-2 p-2 rounded-lg bg-amber-950/60 border border-amber-500/40 text-[11px] text-amber-200 leading-snug">` +
                            `🛡️ <strong>Alokasi COD Presisi:</strong> Dari total inflow QRIS Rp ${grossQrisInflow.toLocaleString('id-ID')}, sebesar <strong>Rp ${codReimbursed.toLocaleString('id-ID')}</strong> diperhitungkan sebagai pembayaran tagihan pokok makanan COD (pengembalian modal ShopeePay, bukan tip). Hanya sisa <strong>Rp ${netQrisTip.toLocaleString('id-ID')}</strong> yang diakui sebagai Tip Murni untuk diadu dengan biaya parkir.` +
                            `</div>`;
                    }

                    if (netParkingResult > 0) {
                        statusBanner.className = 'p-3 bg-emerald-950/40 border border-emerald-500/40 rounded-xl text-xs text-emerald-200 leading-relaxed font-medium';
                        statusBanner.innerHTML = `<strong>🎉 SURPLUS PARKIR (UNTUNG): +Rp ${netParkingResult.toLocaleString('id-ID')}</strong><br>` +
                            `Total tip riil & QRIS murni (Rp ${totalTipsAndInflow.toLocaleString('id-ID')}) <strong>MENUTUP 100%</strong> biaya parkir shift ini (Rp ${parkingExpense.toLocaleString('id-ID')}) dengan sisa untung Rp ${netParkingResult.toLocaleString('id-ID')}. Narik hari ini tidak boncos di parkiran!` + codNoticeHtml;
                    } else if (netParkingResult < 0) {
                        statusBanner.className = 'p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-xs text-rose-200 leading-relaxed font-medium';
                        statusBanner.innerHTML = `<strong>⚠️ MINUS / BONCOS PARKIR: -Rp ${Math.abs(netParkingResult).toLocaleString('id-ID')}</strong><br>` +
                            `Biaya parkir hari ini (Rp ${parkingExpense.toLocaleString('id-ID')} dari ${parkingPaidCount} spot bayar) <strong>LEBIH BESAR</strong> daripada total tip riil & QRIS murni yang terkumpul (Rp ${totalTipsAndInflow.toLocaleString('id-ID')}). Biaya parkir Anda minus Rp ${Math.abs(netParkingResult).toLocaleString('id-ID')}.` + codNoticeHtml;
                    } else {
                        statusBanner.className = 'p-3 bg-indigo-950/40 border border-indigo-500/40 rounded-xl text-xs text-indigo-200 leading-relaxed font-medium';
                        statusBanner.innerHTML = `<strong>✓ PARKIR IMPAS SEMPURNA (Rp 0)</strong><br>` +
                            `Total tip riil & QRIS murni yang terkumpul (Rp ${totalTipsAndInflow.toLocaleString('id-ID')}) pas persis menutup biaya parkir (Rp ${parkingExpense.toLocaleString('id-ID')}). Beban parkir tertutup tanpa sisa minus.` + codNoticeHtml;
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            calculateClosingReconDiff();
        });

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
    <div id="modal-adjust-discrepancy" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">⚖️</span>
                    <h3 class="text-base font-bold text-white">Sesuaikan Selisih Kas Fisik</h3>
                </div>
                <button type="button" onclick="closeModal('modal-adjust-discrepancy')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            @php 
                $cashAccModal = collect($reconciliationReport['accounts'] ?? [])->firstWhere('account_name', 'Cash'); 
                $discModal = $cashAccModal['discrepancy'] ?? 0;
            @endphp

            <form method="POST" action="/closing/{{ $workDay->id }}/adjust-discrepancy" class="space-y-4">
                @csrf
                <div class="p-3.5 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-300 font-medium">Saldo Menurut Sistem:</span>
                        <span class="font-bold text-white">Rp {{ number_format($cashAccModal['ledger_balance'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-300 font-medium">Uang Fisik di Dompet:</span>
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
                    <span class="text-[10px] text-slate-300 font-medium block mt-1">
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
    <div id="modal-correct-closing-tx" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-base font-bold text-white">Koreksi Transaksi Kas</h3>
                </div>
                <button type="button" onclick="closeModal('modal-correct-closing-tx')" class="text-slate-300 hover:text-white font-bold">&times;</button>
            </div>

            <form id="form-correct-tx" method="POST" action="" class="space-y-4">
                @csrf
                <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700 text-xs space-y-1">
                    <span class="text-slate-300 font-medium block">Transaksi yang dikoreksi:</span>
                    <span id="correct-tx-desc" class="font-bold text-white block"></span>
                    <span class="text-slate-300 font-medium block mt-1">Nominal saat ini: <span id="correct-tx-current" class="text-amber-400 font-bold"></span></span>
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
                    <span class="text-[10px] text-slate-300 font-medium block mt-1">
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
