<?php

namespace App\Exceptions;

use Exception;

class UnresolvedDiscrepancyException extends Exception
{
    protected array $discrepantAccounts;

    public function __construct(array $discrepantAccounts)
    {
        $this->discrepantAccounts = $discrepantAccounts;
        parent::__construct("Penutupan hari kerja gagal: Masih terdapat selisih saldo yang belum teridentifikasi.");
    }

    public function getDiscrepantAccounts(): array
    {
        return $this->discrepantAccounts;
    }
}
