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
    @if (app()->environment('testing') && !file_exists(public_path('build/manifest.json')))
        <script src="https://cdn.tailwindcss.com"></script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen antialiased flex flex-col font-sans">
    <nav class="bg-slate-800/80 backdrop-blur border-b border-slate-700/80 sticky top-0 z-50 px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-extrabold text-base tracking-wide text-white">DRIVER FINANCE</span>
            </div>
            <div class="hidden md:flex items-center space-x-5 text-sm font-medium text-slate-300">
                <a href="/dashboard" class="hover:text-emerald-400 transition">Dashboard</a>
                <a href="/dashboard#tab-onbeat" class="hover:text-emerald-400 transition">Closing & Rekonsiliasi</a>
                <a href="/debts" class="hover:text-emerald-400 transition">Utang & PayLater</a>
                <a href="/analytics/driver" class="hover:text-emerald-400 transition">Analitik Driver</a>
                <a href="/analytics/fuel" class="hover:text-emerald-400 transition">Konsumsi BBM</a>
                <a href="/settings/shopee-bonus" class="hover:text-emerald-400 transition">Skema Bonus</a>
                <a href="/audit-logs" class="hover:text-emerald-400 transition">Audit Log</a>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-semibold px-2 py-1 rounded bg-rose-500/10 border border-rose-500/20">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto w-full px-3 sm:px-4 py-4 sm:py-6 flex-1 pb-24 md:pb-6">
        {{ $slot }}
    </main>

    {{-- Mobile Bottom Navigation Bar (Khusus Smartphone & Layar Sentuh) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-2 py-2 flex items-center justify-around shadow-2xl safe-area-pb">
        <a href="/dashboard" class="flex flex-col items-center justify-center py-1 px-2 text-slate-300 hover:text-emerald-400 transition min-w-[56px] {{ request()->is('dashboard') ? 'text-emerald-400 font-bold' : '' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] tracking-tight">Dashboard</span>
        </a>

        <a href="/dashboard#tour-driver-summary" class="flex flex-col items-center justify-center py-1 px-2 text-slate-300 hover:text-amber-400 transition min-w-[56px]">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="text-[10px] tracking-tight">On-Bid</span>
        </a>

        {{-- Center Voice Recorder Action Pill --}}
        <button type="button" onclick="window.triggerVoiceRecorder && window.triggerVoiceRecorder()" class="flex flex-col items-center justify-center -mt-5 bg-gradient-to-tr from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white rounded-full w-12 h-12 shadow-lg shadow-rose-900/50 border-2 border-slate-900 transition transform active:scale-95">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
            </svg>
            <span class="sr-only">Voice Memo Helm</span>
        </button>

        <a href="/debts" class="flex flex-col items-center justify-center py-1 px-2 text-slate-300 hover:text-cyan-400 transition min-w-[56px] {{ request()->is('debts*') ? 'text-cyan-400 font-bold' : '' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-[10px] tracking-tight">Utang</span>
        </a>

        <a href="/analytics/driver" class="flex flex-col items-center justify-center py-1 px-2 text-slate-300 hover:text-indigo-400 transition min-w-[56px] {{ request()->is('analytics*') ? 'text-indigo-400 font-bold' : '' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="text-[10px] tracking-tight">Analitik</span>
        </a>
    </nav>

    <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-500 hidden md:block">
        Keuangan Pribadi & ShopeeFood Driver Malang &bull; Presisi Finansial Rp1
    </footer>
</body>
</html>
