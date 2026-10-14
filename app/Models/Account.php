<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'current_balance',
        'initial_balance',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'current_balance' => 'integer',
        'initial_balance' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDigital(Builder $query): Builder
    {
        return $query->whereIn('type', [AccountType::BANK->value, AccountType::EWALLET->value]);
    }

    public function scopePhysical(Builder $query): Builder
    {
        return $query->where('type', AccountType::CASH->value);
    }

    public function scopeAssets(Builder $query): Builder
    {
        return $query->whereIn('type', [
            AccountType::BANK->value,
            AccountType::EWALLET->value,
            AccountType::CASH->value,
            AccountType::RECEIVABLE->value,
        ]);
    }

    public function ledgerEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
