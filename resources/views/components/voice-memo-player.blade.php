@props(['memo'])

<div class="bg-slate-800/90 border border-slate-700/80 rounded-xl p-3.5 flex flex-col space-y-2.5 shadow-sm hover:border-slate-600 transition">
    <div class="flex items-center justify-between text-xs text-slate-400 font-medium">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Rekaman: {{ $memo->recorded_at?->format('H:i') ?? '-' }} WIB</span>
            @if($memo->duration_seconds)
                <span class="text-slate-500">&bull; {{ $memo->duration_seconds }}s</span>
            @endif
        </div>
        @if($memo->latitude && $memo->longitude)
            @php
                $lat = (float) $memo->latitude;
                $lng = (float) $memo->longitude;
            @endphp
            <a 
                href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" 
                target="_blank" 
                class="text-emerald-400 hover:underline flex items-center space-x-1"
                title="Buka Lokasi di Google Maps"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                <span>GPS</span>
            </a>
        @endif
    </div>

    <audio controls class="w-full h-8 rounded-lg accent-emerald-500 focus:outline-none">
        <source src="{{ $memo->audio_url }}">
        Browser Anda tidak mendukung pemutar audio HTML5.
    </audio>
</div>
