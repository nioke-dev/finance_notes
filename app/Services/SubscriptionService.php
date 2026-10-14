<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function createSubscription(array $data): Subscription
    {
        $amount = (int) $data['amount'];
        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal tagihan langganan harus lebih besar dari Rp0.");
        }

        $dueDay = (int) ($data['due_day_of_month'] ?? 1);
        if ($dueDay < 1 || $dueDay > 31) {
            $dueDay = 1;
        }

        $sub = Subscription::create([
            'name' => trim($data['name']),
            'category' => $data['category'] ?? 'work_essential',
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'amount' => $amount,
            'due_day_of_month' => $dueDay,
            'payment_account_id' => $data['payment_account_id'] ?? null,
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLog::create([
            'auditable_type' => Subscription::class,
            'auditable_id' => $sub->id,
            'action' => 'subscription_created',
            'old_values' => null,
            'new_values' => $sub->toArray(),
            'reason' => "Tagihan rutin '{$sub->name}' (Rp " . number_format($amount, 0, ',', '.') . ") jatuh tempo tgl {$dueDay} dibuat.",
        ]);

        return $sub;
    }

    public function paySubscription(
        WorkDay $workDay,
        Subscription $subscription,
        Account $paymentAccount,
        int $actualAmount,
        ?string $notes = null
    ): Subscription {
        if ($actualAmount <= 0) {
            throw new InvalidArgumentException("Nominal pembayaran langganan harus lebih besar dari Rp0.");
        }

        if ($paymentAccount->current_balance < $actualAmount) {
            throw new InvalidArgumentException(
                "Saldo {$paymentAccount->name} (Rp " . number_format($paymentAccount->current_balance, 0, ',', '.') . 
                ") tidak mencukupi untuk bayar tagihan {$subscription->name} (Rp " . number_format($actualAmount, 0, ',', '.') . ")."
            );
        }

        return DB::transaction(function () use ($workDay, $subscription, $paymentAccount, $actualAmount, $notes) {
            $expenseAccount = Account::firstOrCreate(
                ['name' => 'Beban Langganan & Tagihan Rutin'],
                [
                    'type' => AccountType::EXPENSE,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'subscription_payment',
                description: "Bayar Tagihan Rutin: {$subscription->name}" . ($notes ? " ({$notes})" : ""),
                transactionTime: now(),
                entries: [
                    ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $actualAmount],
                    ['account_id' => $paymentAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $actualAmount],
                ],
                isEstimatedTime: false,
                metadata: [
                    'type' => 'subscription_payment',
                    'subscription_id' => $subscription->id,
                    'subscription_name' => $subscription->name,
                    'amount' => $actualAmount,
                ]
            );

            $subscription->update([
                'last_paid_at' => now(),
            ]);

            AuditLog::create([
                'auditable_type' => Subscription::class,
                'auditable_id' => $subscription->id,
                'action' => 'subscription_paid',
                'old_values' => null,
                'new_values' => ['paid_amount' => $actualAmount, 'account' => $paymentAccount->name],
                'reason' => "Tagihan rutin '{$subscription->name}' dibayar sebesar Rp " . number_format($actualAmount, 0, ',', '.') . " menggunakan {$paymentAccount->name}.",
            ]);

            return $subscription->fresh();
        });
    }
}
