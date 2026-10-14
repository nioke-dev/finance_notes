# TASK-036: Model Eloquent `VoiceMemo` (Accessor URL Audio & Relasi ke `WorkDay`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Model Domain Voice Memo Intercom Helm & Helper Audio Streaming  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `VoiceMemo` yang menghubungkan rekaman audio ke aplikasi web dan mobile.
Model ini wajib menyediakan:
1. Relasi domain:
   - `belongsTo(WorkDay::class)`: Mengaitkan memo suara ke hari kerja aktif.
2. Property casting:
   - `recorded_at` $\rightarrow$ `datetime`
   - `duration_seconds` $\rightarrow$ `integer`
   - `latitude` & `longitude` $\rightarrow$ `decimal:8`
3. Computed Accessor `audio_url`:
   - Menghasilkan URL publik mutlak (`Storage::disk('public')->url($this->file_path)`) agar audio player di web dashboard atau HP bisa langsung memutar file audio tanpa kendala routing.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/VoiceMemo.php` — Model Eloquent `VoiceMemo`.
2. `tests/Unit/Models/VoiceMemoModelTest.php` — Automated test verifikasi model dan accessor URL.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/VoiceMemo.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VoiceMemo extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'file_path',
        'duration_seconds',
        'latitude',
        'longitude',
        'transcription_text',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'duration_seconds' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function audioUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->file_path ? Storage::disk('public')->url($this->file_path) : null
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `VoiceMemo` tersimpan di namespace `App\Models`.
2. Relasi `$memo->workDay` mengembalikan instance `WorkDay`.
3. Accessor `$memo->audio_url` mengembalikan link storage publik yang valid.
4. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/VoiceMemoModelTest.php`
- **Metode Uji:**
  1. `test_voice_memo_belongs_to_work_day()`:
     - Menguji integritas foreign key relasi.
  2. `test_audio_url_accessor_generates_valid_storage_link()`:
     - Memverifikasi path `'voice_memos/test.m4a'` menghasilkan URL berawalan `http` atau `/storage/`.
