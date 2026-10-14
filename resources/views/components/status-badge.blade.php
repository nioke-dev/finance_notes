@props(['status'])

@php
$normalized = strtolower(trim((string) $status));
$classes = match($normalized) {
    'final', 'paid', 'matched', 'ready_to_settle' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    'needs_reconciliation', 'holding', 'draft' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'shortage', 'surplus', 'unpaid', 'error' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    default => 'bg-slate-700/50 text-slate-300 border-slate-600',
};

$label = match($normalized) {
    'final' => 'FINAL (SEIMBANG)',
    'needs_reconciliation' => 'PERLU REKONSILIASI',
    'matched' => 'COCOK (RP 0)',
    'shortage' => 'KURANG (SHORTAGE)',
    'surplus' => 'LEBIH (SURPLUS)',
    'paid' => 'LUNAS',
    'unpaid' => 'BELUM LUNAS',
    default => strtoupper(str_replace('_', ' ', (string) $status)),
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {$classes} tracking-wide uppercase"]) }}>
    {{ $label }}
</span>
