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
    public function setInitialBalance(Account $account, int $amount, ?array $denominations = null): Account
    {
        if (!$account->type->isAsset()) {
            throw new InvalidArgumentException("Hanya akun aset / wadah simpanan kas (Bank, E-Wallet, Kas) yang dapat memiliki saldo awal.");
        }

        if ($amount < 0) {
            throw new InvalidArgumentException("Saldo awal tidak boleh negatif.");
        }

        return DB::transaction(function () use ($account, $amount, $denominations) {
            $metadata = $account->metadata ?? [];
            $newValues = ['initial_balance' => $amount];

            if (!empty($denominations)) {
                $metadata['denominations'] = $denominations;
                $newValues['denominations'] = $denominations;
            }

            $account->update([
                'initial_balance' => $amount,
                'current_balance' => $amount,
                'metadata' => !empty($metadata) ? $metadata : null,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => $newValues,
                'reason' => 'Inisialisasi saldo awal akun pertama kali.',
            ]);

            return $account->fresh();
        });
    }

    /**
     * Mengoreksi saldo modal awal akun dengan mencatat audit trail alasan koreksi.
     */
    public function correctInitialBalance(Account $account, int $newAmount, string $reason, ?array $denominations = null): Account
    {
        if (!$account->type->isAsset()) {
            throw new InvalidArgumentException("Hanya akun aset / wadah simpanan kas (Bank, E-Wallet, Kas) yang dapat memiliki saldo awal.");
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan koreksi saldo awal wajib diisi minimal 5 karakter.");
        }

        if ($newAmount < 0) {
            throw new InvalidArgumentException("Saldo awal tidak boleh negatif.");
        }

        return DB::transaction(function () use ($account, $newAmount, $reason, $denominations) {
            $oldInitial = $account->initial_balance;
            $difference = $newAmount - $oldInitial;

            $metadata = $account->metadata ?? [];
            $newValues = ['initial_balance' => $newAmount];

            if (!empty($denominations)) {
                $metadata['denominations'] = $denominations;
                $newValues['denominations'] = $denominations;
            }

            $account->update([
                'initial_balance' => $newAmount,
                'current_balance' => $account->current_balance + $difference,
                'metadata' => !empty($metadata) ? $metadata : null,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'update',
                'old_values' => ['initial_balance' => $oldInitial],
                'new_values' => $newValues,
                'reason' => $reason,
            ]);

            return $account->fresh();
        });
    }
}
