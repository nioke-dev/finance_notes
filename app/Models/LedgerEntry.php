<?php

namespace App\Models;

use App\Enums\EntryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'entry_type',
        'amount',
        'created_at',
    ];

    protected $casts = [
        'entry_type' => EntryType::class,
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('entry_type', EntryType::DEBIT->value);
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('entry_type', EntryType::CREDIT->value);
    }
}
