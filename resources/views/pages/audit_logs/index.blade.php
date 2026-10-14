<x-layouts.app title="Audit Log Explorer">
    <div class="space-y-6">
        {{-- Header & Insight --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Integritas Data & Audit Trail Finansial</span>
                <h2 class="text-2xl font-extrabold text-white mt-1">
                    Audit Log Explorer
                </h2>
            </div>
            <a href="/dashboard" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition">
                &larr; Ke Dashboard
            </a>
        </div>

        {{-- Filter Sederhana --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-4 shadow">
            <form action="{{ route('audit-logs.index') }}" method="GET" class="flex flex-wrap items-center gap-4 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1 font-semibold">Tipe Entitas</label>
                    <input type="text" name="entity_type" value="{{ request('entity_type') }}" placeholder="Misal: Debt, Transaction..." class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-semibold">Aksi</label>
                    <select name="action" class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-emerald-500">
                        <option value="">Semua Aksi</option>
                        <option value="reversal" {{ request('action') === 'reversal' ? 'selected' : '' }}>Reversal (Batal Transaksi)</option>
                        <option value="update_shopee_bonus_scheme" {{ request('action') === 'update_shopee_bonus_scheme' ? 'selected' : '' }}>Update Skema Bonus</option>
                        <option value="discrepancy_resolution" {{ request('action') === 'discrepancy_resolution' ? 'selected' : '' }}>Resolusi Selisih Kas</option>
                    </select>
                </div>
                <div class="self-end">
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['entity_type', 'action']))
                        <a href="{{ route('audit-logs.index') }}" class="ml-2 text-slate-400 hover:text-slate-200">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel Log Audit --}}
        <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white border-b border-slate-700/60 pb-3">
                Riwayat Jejak Audit Perubahan
            </h3>

            @if($logs->isEmpty())
                <div class="py-12 text-center text-slate-500 text-xs">
                    Belum ada catatan aktivitas audit di database.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-700/60 text-slate-400 uppercase tracking-wider">
                                <th class="pb-3 font-semibold">Waktu (WIB)</th>
                                <th class="pb-3 font-semibold">Aksi</th>
                                <th class="pb-3 font-semibold">Entitas</th>
                                <th class="pb-3 font-semibold">Alasan Perubahan</th>
                                <th class="pb-3 font-semibold">Perubahan (Old &rarr; New)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/40">
                            @foreach($logs as $log)
                                @php
                                    $isReversal = str_contains(strtolower($log->action), 'reversal');
                                    $isUpdate = str_contains(strtolower($log->action), 'update') || str_contains(strtolower($log->action), 'resolution');
                                    $entityBase = class_basename($log->auditable_type);
                                @endphp
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 font-medium text-slate-300">
                                        {{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '-' }}
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $isReversal ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : ($isUpdate ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30') }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-300 font-mono text-[11px]">
                                        {{ $entityBase }} #{{ $log->auditable_id }}
                                    </td>
                                    <td class="py-3 text-slate-200">
                                        {{ $log->reason }}
                                    </td>
                                    <td class="py-3 text-[11px] font-mono text-slate-400 max-w-xs truncate">
                                        @if($log->old_values || $log->new_values)
                                            <span class="text-rose-400">{{ json_encode($log->old_values) }}</span>
                                            &rarr;
                                            <span class="text-emerald-400">{{ json_encode($log->new_values) }}</span>
                                        @else
                                            <span class="text-slate-600">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-700/60">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
