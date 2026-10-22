@props(['memo'])

@php
    $ext = strtolower(pathinfo($memo->file_path ?? '', PATHINFO_EXTENSION));
    $mime = match($ext) {
        'mp4', 'm4a' => 'audio/mp4',
        'ogg' => 'audio/ogg',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        default => 'audio/webm',
    };
    $streamUrl = $memo->id ? route('operational.voice-memo.stream', $memo->id) : $memo->audio_url;

    $recDateTime = $memo->recorded_at ?? $memo->created_at;
    $dateFormatted = $recDateTime ? $recDateTime->translatedFormat('d M Y') : ($memo->workDay ? \Carbon\Carbon::parse($memo->workDay->work_date)->translatedFormat('d M Y') : '-');
    $dayName = $recDateTime ? $recDateTime->translatedFormat('l') : '';
    $timeFormatted = $recDateTime ? $recDateTime->format('H:i') : ($memo->recorded_at?->format('H:i') ?? '-');
    $shiftDateFormatted = $memo->workDay ? \Carbon\Carbon::parse($memo->workDay->work_date)->translatedFormat('d M Y') : null;
@endphp

<div class="bg-slate-800/90 border border-slate-700/80 rounded-xl p-3.5 flex flex-col space-y-2.5 shadow-sm hover:border-slate-600 transition">
    {{-- Baris Header: Tanggal Rekaman & Badge Shift --}}
    <div class="flex items-center justify-between gap-2 flex-wrap">
        <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-200 font-bold text-xs shadow-sm">
            <span>📅</span>
            <span>{{ $dateFormatted }}</span>
            @if($dayName)
                <span class="text-indigo-400 font-medium text-[11px]">({{ $dayName }})</span>
            @endif
        </div>
        @if($shiftDateFormatted)
            <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-900 border border-slate-700 text-slate-300 font-medium" title="Dikaitkan ke Shift {{ $shiftDateFormatted }}">
                Shift: {{ $shiftDateFormatted }}
            </span>
        @endif
    </div>

    {{-- Baris Jam, Durasi & GPS --}}
    <div class="flex items-center justify-between text-xs text-slate-300 font-medium">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
            <span class="font-semibold text-white">Rekaman: {{ $timeFormatted }} WIB</span>
            @if($memo->duration_seconds)
                <span class="text-slate-400 font-medium">&bull; {{ $memo->duration_seconds }}s</span>
            @endif
        </div>
        <div class="flex items-center space-x-2">
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
            <a 
                href="{{ $streamUrl }}" 
                target="_blank" 
                class="text-slate-400 hover:text-emerald-400 p-1 rounded hover:bg-slate-700 transition"
                title="Buka / Putar Audio Langsung"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
        </div>
    </div>

    {{-- Player Audio --}}
    <audio controls preload="metadata" class="w-full h-8 rounded-lg accent-emerald-500 focus:outline-none">
        <source src="{{ $streamUrl }}" type="{{ $mime }}">
        <source src="{{ $memo->audio_url }}" type="{{ $mime }}">
        Browser Anda tidak mendukung pemutar audio HTML5.
    </audio>
</div>
