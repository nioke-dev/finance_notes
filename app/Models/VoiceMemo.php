<?php

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
