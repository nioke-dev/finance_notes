# TASK-038: API Endpoint Pengambilan Daftar Voice Memo (`GET /api/v1/voice-memos`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Pengambilan Daftar & URL Streaming Audio Memo per Hari Kerja  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun endpoint API untuk mengambil daftar seluruh rekaman voice memo yang pernah direkam pada satu hari kerja tertentu (`work_day_id`).
Endpoint ini digunakan oleh:
1. **Web Super App:** Saat driver melakukan investigasi closing malam di kos untuk mendengarkan kembali konteks lapangan (catatan celah parkir gratis, tip lebih dari pelanggan, dsb).
2. **Mobile Client:** Untuk memutar ulang catatan suara di sela waktu menunggu makanan matang di resto.
- **Route:** `GET /api/v1/voice-memos?work_day_id={id}`
- **Response Data:**
  - Mengembalikan array daftar memo diurutkan dari yang paling baru (`ORDER BY recorded_at DESC`).
  - Menyertakan `memo_id`, `audio_url`, `duration_seconds`, `latitude`, `longitude`, dan `recorded_at`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Api/VoiceMemoController.php` — Menambahkan method `index`.
2. `routes/api.php` — Mendaftarkan route endpoint `GET /api/v1/voice-memos`.
3. `tests/Feature/Api/ListVoiceMemosApiTest.php` — Automated test HTTP list endpoint.

---

## 3. Spesifikasi Anatomi Kode

### Controller Method (`VoiceMemoController.php`)
```php
public function index(Request $request): JsonResponse
{
    $request->validate([
        'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
    ]);

    $memos = VoiceMemo::where('work_day_id', $request->query('work_day_id'))
        ->orderBy('recorded_at', 'desc')
        ->get()
        ->map(fn (VoiceMemo $memo) => [
            'id' => $memo->id,
            'audio_url' => $memo->audio_url,
            'duration_seconds' => $memo->duration_seconds,
            'latitude' => $memo->latitude,
            'longitude' => $memo->longitude,
            'recorded_at' => $memo->recorded_at->toISOString(),
        ]);

    return response()->json([
        'status' => 'success',
        'data' => $memos,
    ], 200);
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `GET /api/v1/voice-memos?work_day_id=1` merespons `200 OK`.
2. Data terurut dari rekaman terbaru ke rekaman terlama.
3. Jika hari kerja belum memiliki rekaman voice memo, mengembalikan array kosong `[]`.
4. Jika parameter `work_day_id` tidak disertakan atau tidak eksis, menghasilkan error `422 Unprocessable Entity`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/ListVoiceMemosApiTest.php`
- **Metode Uji:**
  1. `test_can_get_list_of_voice_memos_ordered_by_newest()`:
     - Membuat 3 memo dengan waktu berbeda $\rightarrow$ memverifikasi urutan kembalian.
  2. `test_returns_empty_array_when_no_memos_for_work_day()`:
     - Mengakses hari kerja tanpa memo $\rightarrow$ data bernilai `[]`.
  3. `test_fails_validation_if_work_day_id_missing()`:
     - Mengakses endpoint tanpa query param $\rightarrow$ status 422.
