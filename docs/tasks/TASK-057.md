# TASK-057: Setup Tailwind CSS Theme Tokens & Master Layout Blade Web Super App

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Master Layout Responsif & Konfigurasi Tema Visual Anti-Mentah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Menyiapkan konfigurasi Tailwind CSS dan Master Layout Blade (`resources/views/layouts/app.blade.php`).
Sesuai aturan panduan visual di `UI-DESIGN-SYSTEM.md` dan `AGENTS.md`:
1. **DILARANG KERAS** meninggalkan antarmuka bergaya HTML mentah bawaan browser tanpa styling.
2. Mengonfigurasi token warna resmi pada `tailwind.config.js`:
   - Latar Belakang Utama: Dark Slate (`#0F172A` / `#1E293B`) untuk kenyamanan mata driver malam hari saat closing di kos.
   - Brand Finansial: Emerald Green (`#059669`).
   - Aksen Driver Shopee: Shopee Orange (`#EE4D2D`).
   - Alert Status: Amber (`#F59E0B`) dan Red (`#EF4444`).
3. Layout master mencakup:
   - Header navigasi bersih dengan jam kerja aktif dan link cepat (Dashboard, Closing, Utang, Analitik).
   - Area penampung Banner Pengingat global.
   - Container konten utama yang responsif (nyaman dibuka di laptop maupun layar HP).

---

## 2. File-File yang Disentuh / Dibuat
1. `tailwind.config.js` — Konfigurasi palet warna dan ekstensi theme.
2. `resources/css/app.css` — Import direktif Tailwind dan utilitas kustom.
3. `resources/views/layouts/app.blade.php` — Master layout Blade.
4. `tests/Feature/Web/MasterLayoutRenderingTest.php` — Automated test rendering master view layout.

---

## 3. Spesifikasi Anatomi Kode (`layouts/app.blade.php`)
```html
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Keuangan Pribadi & ShopeeFood Driver' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen antialiased flex flex-col font-sans">
    <nav class="bg-slate-800/80 backdrop-blur border-b border-slate-700/80 sticky top-0 z-50 px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-extrabold text-base tracking-wide text-white">DRIVER FINANCE</span>
            </div>
            <div class="hidden md:flex space-x-6 text-sm font-medium text-slate-300">
                <a href="/dashboard" class="hover:text-emerald-400 transition">Dashboard</a>
                <a href="/closing" class="hover:text-emerald-400 transition">Closing & Rekonsiliasi</a>
                <a href="/debts" class="hover:text-emerald-400 transition">Utang & PayLater</a>
                <a href="/analytics/driver" class="hover:text-emerald-400 transition">Analitik Driver</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto w-full px-4 py-6 flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
        Keuangan Pribadi & ShopeeFood Driver Malang &bull; Presisi Finansial Rp1
    </footer>
</body>
</html>
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. File `resources/views/layouts/app.blade.php` terpasang dengan struktur HTML5 semantik.
2. Class Tailwind dark theme (`bg-slate-900`, `text-slate-100`) terintegrasi.
3. Test rendering master layout mengembalikan HTTP `200 OK`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/MasterLayoutRenderingTest.php`
- **Metode Uji:**
  1. `test_master_layout_renders_with_title_and_navigation()`:
     - Merender view uji menggunakan master layout $\rightarrow$ Memverifikasi teks 'DRIVER FINANCE' dan elemen navigasi ada di DOM HTML.
