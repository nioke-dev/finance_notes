<?php

namespace App\Enums;

enum EntryType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }
}
