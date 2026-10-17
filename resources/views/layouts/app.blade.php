<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Keuangan Pribadi & ShopeeFood Driver' }}</title>
    <meta name="theme-color" content="#0F172A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">
    @if (app()->environment('testing') && !file_exists(public_path('build/manifest.json')))
        <script src="https://cdn.tailwindcss.com"></script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        @keyframes liveShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .live-input-shake {
            animation: liveShake 0.35s ease-in-out;
        }
        .live-error-msg {
            animation: fadeIn 0.2s ease-out;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen antialiased flex flex-col font-sans">
    <nav class="bg-slate-900/95 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50 px-4 sm:px-6 lg:px-8 py-2.5 w-full">
        <div class="w-full flex items-center justify-between">
            {{-- Brand / Logo --}}
            <a href="/dashboard" class="flex items-center space-x-2.5 shrink-0 group">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 group-hover:scale-125 transition"></span>
                <span class="font-extrabold text-sm md:text-base tracking-wider text-white">DRIVER FINANCE</span>
            </a>

            {{-- Mobile Only: Compact Reset Button (Only during trial mode, no other buttons) --}}
            @if(config('app.trial_mode', false))
                <button type="button" onclick="openModal('modal-reset-trial-data')" class="md:hidden px-2.5 py-1 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/40 text-xs font-bold flex items-center space-x-1 active:scale-95 shadow" title="Reset data ke kondisi awal seeder">
                    <span>🔥</span>
                    <span>Reset</span>
                </button>
            @endif

            {{-- Desktop Navigation Links & Actions (Hidden on Mobile) --}}
            <div class="hidden md:flex items-center space-x-1.5 lg:space-x-2.5 xl:space-x-3.5 text-xs lg:text-sm font-medium text-slate-300">
                <a href="/dashboard" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('dashboard') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Dashboard
                </a>
                <a href="/dashboard#tab-onbeat" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition">
                    Closing & Rekonsiliasi
                </a>
                <a href="/debts" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('debts*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Utang & PayLater
                </a>
                <a href="/wallets" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('wallets*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Kelola Wadah
                </a>
                <a href="/analytics/driver" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('analytics/driver*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Analitik Driver
                </a>
                <a href="/analytics/fuel" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('analytics/fuel*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Konsumsi BBM
                </a>
                <a href="/settings/shopee-bonus" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('settings*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Skema Bonus
                </a>
                <a href="/audit-logs" class="px-2.5 py-1.5 rounded-lg hover:text-white hover:bg-slate-800 transition {{ request()->is('audit-logs*') ? 'text-emerald-400 font-bold bg-slate-800/90' : '' }}">
                    Audit Log
                </a>

                {{-- Desktop Right Actions: Trial Mode Reset & Logout --}}
                @if(config('app.trial_mode', false))
                    <button type="button" onclick="openModal('modal-reset-trial-data')" class="ml-2 px-3 py-1.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/40 text-xs font-bold transition flex items-center space-x-1.5 shadow-sm active:scale-95" title="Reset semua data ke kondisi awal seeder untuk uji coba ulang">
                        <span>🔥</span>
                        <span>Reset Data Uji Coba</span>
                    </button>
                @endif
                <form method="POST" action="/logout" class="inline ml-1">
                    @csrf
                    <button type="submit" class="text-xs text-slate-300 hover:text-white font-semibold px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 transition active:scale-95">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto w-full px-3 sm:px-4 py-4 sm:py-6 flex-1 pb-32 md:pb-10">
        {{ $slot }}
    </main>

    {{-- Mobile Bottom Navigation Bar (Khusus Smartphone & Layar Sentuh) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-2 py-2 flex items-center justify-around shadow-2xl safe-area-pb">
        <a href="/dashboard" class="flex flex-col items-center justify-center py-1 px-2 transition min-w-[56px] {{ request()->is('dashboard') ? 'text-emerald-400 font-bold' : 'text-slate-300 hover:text-emerald-400' }}">
            <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] tracking-tight">Dashboard</span>
        </a>

        <a href="/wallets" class="flex flex-col items-center justify-center py-1 px-1.5 transition min-w-[52px] {{ request()->is('wallets*') ? 'text-emerald-400 font-bold' : 'text-slate-300 hover:text-emerald-400' }}">
            <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span class="text-[10px] tracking-tight font-medium">Wadah</span>
        </a>

        {{-- Center Voice Recorder Action Pill --}}
        <button type="button" onclick="window.triggerVoiceRecorder && window.triggerVoiceRecorder()" class="flex flex-col items-center justify-center -mt-5 bg-gradient-to-tr from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white rounded-full w-12 h-12 shadow-lg shadow-rose-950/70 border-2 border-slate-900 transition transform active:scale-95 shrink-0" title="Rekam Suara Cepat (Intercom Helm)">
            <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
            </svg>
            <span class="sr-only">Voice Memo Helm</span>
        </button>

        <a href="/debts" class="flex flex-col items-center justify-center py-1 px-2 transition min-w-[56px] {{ request()->is('debts*') ? 'text-cyan-400 font-bold' : 'text-slate-300 hover:text-cyan-400' }}">
            <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-[10px] tracking-tight">Utang</span>
        </a>

        <a href="/analytics/driver" class="flex flex-col items-center justify-center py-1 px-2 transition min-w-[56px] {{ request()->is('analytics*') ? 'text-indigo-400 font-bold' : 'text-slate-300 hover:text-indigo-400' }}">
            <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="text-[10px] tracking-tight">Analitik</span>
        </a>
    </nav>

    <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-400 hidden md:block">
        Keuangan Pribadi & ShopeeFood Driver Malang &bull; Presisi Finansial Rp1
    </footer>

    {{-- PWA Install Banner (Otomatis muncul jika browser mendukung install Web App) --}}
    <div id="pwa-install-banner" class="hidden fixed bottom-20 left-3 right-3 sm:left-auto sm:right-6 sm:bottom-6 z-50 bg-slate-900/95 border border-emerald-500/50 rounded-2xl p-3 shadow-2xl backdrop-blur-md flex items-center justify-between space-x-3 max-w-sm animate-fade-in">
        <div class="flex items-center space-x-3">
            <img src="/icons/icon-192x192.png" alt="Driver Finance" class="w-10 h-10 rounded-xl border border-emerald-500/40 shrink-0 shadow">
            <div>
                <p class="text-xs font-bold text-white">Instal Aplikasi Driver</p>
                <p class="text-[11px] text-slate-300">Jalankan fullscreen tanpa browser bar</p>
            </div>
        </div>
        <div class="flex items-center space-x-1.5 shrink-0">
            <button type="button" onclick="window.installDriverPWA()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-950 transition active:scale-95">
                Instal
            </button>
            <button type="button" onclick="document.getElementById('pwa-install-banner').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-white rounded-lg transition" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Global Floating Toast Alert (Tetap Terlihat Di Mana Pun Posisi Layar) --}}
    @if(session('success') || session('error') || session('status') || (isset($errors) && $errors->any()))
        @php
            $isError = session()->has('error') || (isset($errors) && $errors->any());
            $toastMsg = session('success') ?? session('error') ?? session('status') ?? ($errors->any() ? $errors->first() : null);
        @endphp
        <div id="global-floating-toast" class="fixed top-14 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-50 transform transition-all duration-300 ease-out flex items-center justify-between p-3.5 rounded-2xl shadow-2xl backdrop-blur-md {{ $isError ? 'bg-rose-950/95 border border-rose-500/60 text-rose-200' : 'bg-slate-900/95 border border-emerald-500/60 text-emerald-300' }}">
            <div class="flex items-center space-x-2.5">
                <span class="text-base">{{ $isError ? '⚠️' : '✓' }}</span>
                <span class="text-xs font-bold leading-snug">{{ $toastMsg }}</span>
            </div>
            <button type="button" onclick="document.getElementById('global-floating-toast')?.remove()" class="text-slate-400 hover:text-white ml-2 p-1 rounded-lg text-sm leading-none">
                &times;
            </button>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('global-floating-toast');
                if (toast) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4500);
        </script>
    @endif

    {{-- Modal Pengurangan Saldo Wadah & Jadwal Potongan Rutin Bulanan --}}
    <x-modals.account-deductions />

    @if(config('app.trial_mode', false))
    {{-- Modal Reset Seluruh Data (Khusus Mode Uji Coba) --}}
    <div id="modal-reset-trial-data" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-slate-800 border-2 border-rose-500/60 max-w-md w-full rounded-2xl p-6 shadow-2xl my-8">
            <div class="flex items-center space-x-3 mb-3">
                <span class="text-3xl">⚠️</span>
                <div>
                    <h3 class="text-base font-black text-rose-400">Reset Semua Data (Mode Uji Coba)</h3>
                    <span class="text-[11px] text-slate-300">Kembalikan sistem ke kondisi awal (fresh seeder)</span>
                </div>
            </div>
            <div class="p-3.5 bg-rose-950/40 border border-rose-500/30 rounded-xl text-xs text-rose-200 leading-relaxed mb-4">
                <strong>Perhatian:</strong> Fitur ini disediakan khusus selama masa uji coba. Seluruh data transaksi, hari kerja, catatan utang, dan saldo akun akan <strong>dihapus bersih</strong> kembali ke kondisi bawaan awal seeder (saldo Rp 0). Anda dapat menguji coba skenario dari awal kapan pun secara mandiri tanpa menunggu asisten AI.
            </div>
            <p class="text-xs text-slate-300 mb-5">
                Apakah Anda yakin ingin me-reset seluruh database sekarang?
            </p>
            <form method="POST" action="/operational/reset-trial-data" class="flex justify-end space-x-2.5" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='Sedang Reset...';">
                @csrf
                <button type="button" onclick="closeModal('modal-reset-trial-data')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-black rounded-xl shadow-lg shadow-rose-950/80 flex items-center space-x-1.5 transition">
                    <span>🔥</span>
                    <span>Ya, Reset Semuanya Sekarang</span>
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Floating Back to Top Button --}}
    <button id="btn-back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="hidden fixed bottom-20 right-4 md:bottom-8 md:right-8 z-30 p-2.5 rounded-full bg-slate-800/95 text-emerald-400 border border-slate-700/80 shadow-2xl backdrop-blur transition hover:bg-slate-700 active:scale-90" title="Kembali ke Atas">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <script>
        // Modal global helpers
        window.openModal = function(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('hidden');
        };
        window.closeModal = function(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        };

        // ================= SISTEM PRESERVASI POSISI SCROLL (ANTI-CAPEK SCROLL ULANG) =================
        function saveScrollPosition() {
            sessionStorage.setItem('scroll_pos_' + window.location.pathname, window.scrollY);
        }

        window.addEventListener('beforeunload', saveScrollPosition);

        document.addEventListener('submit', () => {
            saveScrollPosition();
        });

        function restoreScrollPosition() {
            const savedPos = sessionStorage.getItem('scroll_pos_' + window.location.pathname);
            if (savedPos !== null) {
                const pos = parseInt(savedPos, 10);
                if (pos > 0) {
                    window.scrollTo({ top: pos, behavior: 'instant' });
                    setTimeout(() => {
                        window.scrollTo({ top: pos, behavior: 'instant' });
                    }, 50);
                    setTimeout(() => {
                        window.scrollTo({ top: pos, behavior: 'instant' });
                    }, 150);
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', restoreScrollPosition);
        } else {
            restoreScrollPosition();
        }

        // Floating Back-to-Top Toggle
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('btn-back-to-top');
            if (btn) {
                if (window.scrollY > 350) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });

        window.addEventListener('pwa-install-ready', () => {
            const banner = document.getElementById('pwa-install-banner');
            if (banner && !window.matchMedia('(display-mode: standalone)').matches) {
                banner.classList.remove('hidden');
            }
        });
    </script>
    <script src="/js/live-validator.js"></script>
</body>
</html>
