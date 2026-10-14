# TASK-058: Komponen Blade Reusable Form (`<x-currency-input>` & `<x-status-badge>`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** UI Components Reusable Anti-Mentah untuk Input Rupiah & Badge Status  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun dua komponen Blade mandiri untuk memastikan seluruh form di web memiliki tampilan profesional, konsisten, dan tidak tampak seperti komponen bawaan browser:
1. `<x-currency-input>`:
   - Komponen input angka khusus mata uang Rupiah.
   - Dilengkapi prefix visual `"Rp "` statis di sisi kiri yang menyatu dengan border input.
   - Memiliki styling focus ring emerald (`focus:ring-2 focus:ring-emerald-500`).
   - Mendukung handling error validation Laravel otomatis (border merah jika ada pesan error).
2. `<x-status-badge>`:
   - Komponen pill indikator status status rekonsiliasi dan utang.
   - Otomatis memilih warna berdasarkan nilai status:
     - `'final'` / `'paid'` / `'matched'` $\rightarrow$ Background Hijau (`bg-emerald-500/10 text-emerald-400 border-emerald-500/20`).
     - `'needs_reconciliation'` / `'holding'` $\rightarrow$ Background Kuning (`bg-amber-500/10 text-amber-400 border-amber-500/20`).
     - `'shortage'` / `'surplus'` / `'unpaid'` $\rightarrow$ Background Merah (`bg-rose-500/10 text-rose-400 border-rose-500/20`).

---

## 2. File-File yang Disentuh / Dibuat
1. `resources/views/components/currency-input.blade.php` — Komponen input mata uang.
2. `resources/views/components/status-badge.blade.php` — Komponen badge status.
3. `tests/Feature/Web/BladeComponentsTest.php` — Automated test rendering komponen Blade.

---

## 3. Spesifikasi Anatomi Kode

### A. `resources/views/components/currency-input.blade.php`
```html
@props(['name', 'label' => null, 'value' => '', 'placeholder' => '0'])

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
        </label>
    @endif
    <div class="relative rounded-xl shadow-sm">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
            Rp
        </div>
        <input 
            type="number" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'block w-full pl-11 pr-4 py-2.5 bg-slate-800 border ' . ($errors->has($name) ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700 focus:ring-emerald-500 focus:border-emerald-500') . ' rounded-xl text-white placeholder-slate-500 text-sm font-medium focus:outline-none focus:ring-2 transition']) }}
        >
    </div>
    @error($name)
        <p class="mt-1.5 text-xs text-rose-400 font-medium">{{ $message }}</p>
    @enderror
</div>
```

### B. `resources/views/components/status-badge.blade.php`
```html
@props(['status'])

@php
$normalized = strtolower(trim($status));
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
    default => strtoupper(str_replace('_', ' ', $status)),
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {$classes} tracking-wide uppercase"]) }}>
    {{ $label }}
</span>
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Komponen `<x-currency-input>` menampilkan prefix "Rp" dan menangani validasi error class.
2. Komponen `<x-status-badge>` menghasilkan badge warna hijau untuk status `'final'`, kuning untuk `'needs_reconciliation'`, dan merah untuk `'shortage'`.
3. Unit test rendering kedua komponen lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/BladeComponentsTest.php`
- **Metode Uji:**
  1. `test_currency_input_component_renders_with_rupiah_prefix_and_name()`
  2. `test_status_badge_applies_correct_color_class_for_final_status()`
  3. `test_status_badge_applies_correct_color_class_for_needs_reconciliation_status()`
