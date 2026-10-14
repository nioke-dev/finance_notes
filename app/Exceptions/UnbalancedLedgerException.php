<?php

namespace App\Exceptions;

use Exception;

class UnbalancedLedgerException extends Exception
{
    protected int $totalDebit;
    protected int $totalCredit;
    protected int $discrepancy;

    public function __construct(int $totalDebit, int $totalCredit)
    {
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;
        $this->discrepancy = abs($totalDebit - $totalCredit);

        parent::__construct(
            "Transaksi ditolak: Buku ledger tidak seimbang. Total Debit (Rp{$totalDebit}) != Total Kredit (Rp{$totalCredit}). Selisih: Rp{$this->discrepancy}."
        );
    }

    public function getTotalDebit(): int
    {
        return $this->totalDebit;
    }

    public function getTotalCredit(): int
    {
        return $this->totalCredit;
    }

    public function getDiscrepancy(): int
    {
        return $this->discrepancy;
    }
}
