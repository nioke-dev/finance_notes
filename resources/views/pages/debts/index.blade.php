<x-layouts.app title="Pengelolaan Utang & Jadwal Cicilan">
    <div class="space-y-6">
        {{-- Header & Insight --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Portofolio Liabilitas & Piutang</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Pengelolaan Utang, PayLater & Piutang Teman
                </h2>
            </div>
            <a href="/dashboard" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                &larr; Ke Dashboard
            </a>
        </div>

        {{-- Ringkasan Kartu Metrik --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Total Kewajiban Utang Saya --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Utang Saya (Kewajiban)</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-rose-400">Rp {{ number_format($totalPayable, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ $myDebts->count() }} pinjaman aktif</span>
                </div>
            </div>

            {{-- Total Piutang Teman --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Piutang ke Teman</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-emerald-400">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ $friendDebts->count() }} teman meminjam</span>
                </div>
            </div>

            {{-- Tagihan Jatuh Tempo < 30 Hari --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-5 shadow">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Tagihan 30 Hari Ke Depan</span>
                <div class="mt-2">
                    <span class="text-2xl font-extrabold text-amber-400">
                        Rp {{ number_format($upcomingInstallments->sum('amount_due'), 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ $upcomingInstallments->count() }} cicilan segera jatuh tempo</span>
                </div>
            </div>
        </div>

        {{-- Peringatan Jatuh Tempo Mendatang --}}
        @if($upcomingInstallments->isNotEmpty())
            <div class="bg-slate-800/80 border border-amber-500/30 rounded-2xl p-5 shadow space-y-3">
                <div class="flex items-center space-x-2 text-amber-400">
                    <span class="text-base">⚠️</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider">Jadwal Jatuh Tempo Terdekat (30 Hari)</h3>
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
                                <span class="text-[11px] text-slate-400">Cicilan #{{ $inst->installment_number }} &bull; {{ $inst->due_date->format('d M Y') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-xs text-white block">Rp {{ number_format($inst->amount_due, 0, ',', '.') }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $isUrgent ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300' }}">
                                    {{ $daysLeft < 0 ? abs($daysLeft).' hari telat' : ($daysLeft == 0 ? 'Hari Ini' : $daysLeft.' hari lagi') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tabbed Lists: Utang Saya vs Piutang Teman --}}
        <div class="space-y-4">
            <div class="flex border-b border-slate-700/60 space-x-6 text-sm font-semibold">
                <button type="button" class="pb-2 border-b-2 border-emerald-500 text-emerald-400">
                    Utang Saya (PayLater / Bank)
                </button>
            </div>

            {{-- 1. Utang Saya (PayLater / Pinjaman) --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">
                        Daftar Pinjaman & Cicilan Aktif
                    </h3>
                </div>

                @if($myDebts->isEmpty())
                    <div class="py-8 text-center text-slate-500 text-xs">
                        Tidak ada pinjaman atau utang aktif saat ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-700/60 text-slate-400 uppercase tracking-wider">
                                    <th class="pb-3 font-semibold">Nama Pinjaman</th>
                                    <th class="pb-3 font-semibold">Platform</th>
                                    <th class="pb-3 font-semibold">Tenor</th>
                                    <th class="pb-3 font-semibold text-right">Total Kewajiban</th>
                                    <th class="pb-3 font-semibold text-right">Sisa Tagihan</th>
                                    <th class="pb-3 font-semibold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/40">
                                @foreach($myDebts as $debt)
                                    <tr class="hover:bg-slate-700/30 transition">
                                        <td class="py-3 font-bold text-slate-200">
                                            {{ $debt->name }}
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-700 text-slate-300">
                                                {{ $debt->platform }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-300">
                                            {{ $debt->tenor_months }} Bulan
                                        </td>
                                        <td class="py-3 text-right text-slate-300">
                                            Rp {{ number_format($debt->total_obligation, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 text-right font-bold text-rose-400">
                                            Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <x-status-badge :status="$debt->status" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- 2. Piutang Teman --}}
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">
                        Piutang ke Teman (Talangan / Pinjaman Pribadi)
                    </h3>
                </div>

                @if($friendDebts->isEmpty())
                    <div class="py-8 text-center text-slate-500 text-xs">
                        Tidak ada data piutang teman aktif saat ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-700/60 text-slate-400 uppercase tracking-wider">
                                    <th class="pb-3 font-semibold">Nama Teman</th>
                                    <th class="pb-3 font-semibold">Keterangan</th>
                                    <th class="pb-3 font-semibold">Mulai Pinjam</th>
                                    <th class="pb-3 font-semibold text-right">Total Pinjaman</th>
                                    <th class="pb-3 font-semibold text-right">Sisa Piutang</th>
                                    <th class="pb-3 font-semibold text-center">Status</th>
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
                                        <td class="py-3 text-slate-400">
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
