# TASK-037: Form Request & API Endpoint Upload Voice Memo (`POST /api/v1/quick/voice-memo`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Upload File Audio Rekaman Suara Intercom Helm  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk mengunggah file rekaman audio dari headset intercom helm driver di jalan.
- **Route:** `POST /api/v1/quick/voice-memo`
- **Content-Type:** `multipart/form-data`
- **Request Parameters:**
  - `work_day_id`: ID hari kerja aktif (integer, wajib).
  - `audio_file`: File audio (mimes: `m4a,mp3,wav,aac`, maksimal ukuran 10MB, wajib).
  - `duration_seconds`: Durasi audio dalam detik (integer, opsional).
  - `latitude`: Koordinat GPS latitude saat rekam (numerik, opsional).
  - `longitude`: Koordinat GPS longitude saat rekam (numerik, opsional).
- **Proses Penyimpanan:**
  - File disimpan ke disk storage `public` pada folder `voice_memos/`.
  - Nama file digenerate unik menggunakan format: `memo_{work_day_id}_{timestamp}_{uniqid}.{ext}`.
  - Membuat record baru di tabel `voice_memos`.
- Mengembalikan response JSON standar `201 Created` dengan `file_url`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/UploadVoiceMemoRequest.php` — Form request validasi file audio.
2. `app/Http/Controllers/Api/VoiceMemoController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/quick/voice-memo`.
4. `tests/Feature/Api/UploadVoiceMemoApiTest.php` — Automated test HTTP upload dengan fake storage.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/UploadVoiceMemoRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVoiceMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'audio_file' => ['required', 'file', 'mimes:m4a,mp3,wav,aac,ogg', 'max:10240'], // Max 10MB
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
```

### B. Controller Action (`VoiceMemoController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadVoiceMemoRequest;
use App\Models\VoiceMemo;
use App\Models\WorkDay;
use Illuminate\Http\JsonResponse;

class VoiceMemoController extends Controller
{
    public function upload(UploadVoiceMemoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $file = $request->file('audio_file');
        $fileName = sprintf(
            'memo_%d_%s_%s.%s',
            $workDay->id,
            now()->format('Ymd_His'),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs('voice_memos', $fileName, 'public');

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => $path,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Voice memo berhasil disimpan.',
            'data' => [
                'memo_id' => $memo->id,
                'audio_url' => $memo->audio_url,
                'duration_seconds' => $memo->duration_seconds,
                'recorded_at' => $memo->recorded_at->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/quick/voice-memo` merespons `201 Created` saat file audio valid diunggah.
2. File non-audio (misal `.pdf` atau `.exe`) ditolak dengan error `422 Unprocessable Entity`.
3. File tersimpan dengan nama unik di storage `public/voice_memos/`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/UploadVoiceMemoApiTest.php`
- **Metode Uji:**
  1. `test_can_upload_voice_memo_audio_file()`:
     - Menggunakan `Storage::fake('public')` dan `UploadedFile::fake()->create('memo.m4a', 500)` $\rightarrow$ Status 201, file terverifikasi ada di disk.
  2. `test_upload_fails_for_non_audio_files()`:
     - Mengunggah file `.txt` $\rightarrow$ Status 422.
