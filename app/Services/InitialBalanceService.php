<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InitialBalanceService
{
    /**
     * Menetapkan saldo modal awal akun untuk pertama kali.
     */
    public function setInitialBalance(Account $account, int $amount): Account
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Saldo awal tidak boleh negatif.");
        }

        return DB::transaction(function () use ($account, $amount) {
            $account->update([
                'initial_balance' => $amount,
                'current_balance' => $amount,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => ['initial_balance' => $amount],
                'reason' => 'Inisialisasi saldo awal akun pertama kali.',
            ]);

            return $account->fresh();
        });
    }

    /**
     * Mengoreksi saldo modal awal akun dengan mencatat audit trail alasan koreksi.
     */
    public function correctInitialBalance(Account $account, int $newAmount, string $reason): Account
    {
        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan koreksi saldo awal wajib diisi minimal 5 karakter.");
        }

        if ($newAmount < 0) {
            throw new InvalidArgumentException("Saldo awal tidak boleh negatif.");
        }

        return DB::transaction(function () use ($account, $newAmount, $reason) {
            $oldInitial = $account->initial_balance;
            $difference = $newAmount - $oldInitial;

            $account->update([
                'initial_balance' => $newAmount,
                'current_balance' => $account->current_balance + $difference,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'update',
                'old_values' => ['initial_balance' => $oldInitial],
                'new_values' => ['initial_balance' => $newAmount],
                'reason' => $reason,
            ]);

            return $account->fresh();
        });
    }
}
