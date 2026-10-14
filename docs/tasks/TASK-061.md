# TASK-061: Komponen Blade Audio Player Voice Memo HTML5 (`<x-voice-memo-player>`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** UI Component Pemutar Rekaman Suara Intercom Helm di Halaman Closing  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun komponen Blade mandiri `<x-voice-memo-player>` untuk memutar file audio rekaman memo suara dari headset intercom helm driver saat melakukan investigasi closing malam di laptop.
Sesuai rancangan di `PRD.md` dan `BUSINESS-RULES.md`:
1. **Fitur Audio Player:**
   - Elemen audio semantik HTML5 dengan antarmuka yang rapi (tidak memakai styling mentah bawaan browser).
   - Menampilkan:
     - Waktu kejadian rekaman (`recorded_at` dalam format jam lokal, misal 14:25 WIB).
     - Durasi audio dalam detik (misal: "0:18").
     - Indikator koordinat GPS jika ada (link/badge kecil latitude & longitude).
     - Tombol Play / Pause yang responsif dan indikator progress bar.
2. Membantu driver mendengarkan kembali detail pesanan COD, celah parkir gratis resto, atau tip tambahan tanpa harus membaca teks panjang.

---

## 2. File-File yang Disentuh / Dibuat
1. `resources/views/components/voice-memo-player.blade.php` — Komponen Blade audio player.
2. `tests/Feature/Web/VoiceMemoPlayerComponentTest.php` — Automated test rendering komponen audio player.

---

## 3. Spesifikasi Anatomi Kode (`components/voice-memo-player.blade.php`)
```html
@props(['memo'])

<div class="bg-slate-800/90 border border-slate-700/80 rounded-xl p-3.5 flex flex-col space-y-2.5 shadow-sm hover:border-slate-600 transition">
    <div class="flex items-center justify-between text-xs text-slate-400 font-medium">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Rekaman: {{ $memo->recorded_at->format('H:i') }} WIB</span>
            @if($memo->duration_seconds)
                <span class="text-slate-500">&bull; {{ $memo->duration_seconds }}s</span>
            @endif
        </div>
        @if($memo->latitude && $memo->longitude)
            <a 
                href="https://www.google.com/maps?q={{ $memo->latitude }},{{ $memo->longitude }}" 
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
        <source src="{{ $memo->audio_url }}" type="audio/mp4">
        Browser Anda tidak mendukung pemutar audio HTML5.
    </audio>
</div>
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Komponen `<x-voice-memo-player>` merender tag audio dengan `src` mengarah ke URL audio file storage.
2. Menampilkan waktu rekaman dan link koordinat GPS jika latitude/longitude tersedia.
3. Test rendering komponen berhasil 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/VoiceMemoPlayerComponentTest.php`
- **Metode Uji:**
  1. `test_voice_memo_player_renders_audio_source_and_timestamp()`:
     - Merender komponen dengan dummy memo $\rightarrow$ memverifikasi tag `<audio>` dan teks jam rekaman ada di DOM.
  2. `test_voice_memo_player_renders_gps_link_when_coordinates_present()`:
     - Memberi lat/long $\rightarrow$ memverifikasi link Google Maps tercipta di HTML.
