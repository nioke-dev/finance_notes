<?php

namespace App\Enums;

enum AccountType: string
{
    case BANK = 'bank';
    case EWALLET = 'ewallet';
    case CASH = 'cash';
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case DEBT = 'debt';
    case RECEIVABLE = 'receivable';

    public function isAsset(): bool
    {
        return in_array($this, [self::BANK, self::EWALLET, self::CASH, self::RECEIVABLE]);
    }

    public function isDigital(): bool
    {
        return in_array($this, [self::BANK, self::EWALLET]);
    }

    public function isPhysical(): bool
    {
        return $this === self::CASH;
    }
}
