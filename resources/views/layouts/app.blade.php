<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Keuangan Pribadi & ShopeeFood Driver' }}</title>
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
                <span class="font-extrabold text-base tracking-wide text-white">DRIVER FINANCES</span>
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

    <main class="max-w-7xl mx-auto w-full px-4 py-6 flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
        Keuangan Pribadi & ShopeeFood Driver Malang &bull; Presisi Finansial Rp1
    </footer>
</body>
</html>
