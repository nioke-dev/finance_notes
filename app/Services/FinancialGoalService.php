<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\DebtInstallment;
use App\Models\FinancialGoal;
use App\Models\Subscription;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialGoalService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Buat Target Belanja / Kebutuhan Survival Baru
     */
    public function createGoal(array $data): FinancialGoal
    {
        $targetAmount = (int) $data['target_amount'];
        if ($targetAmount <= 0) {
            throw new InvalidArgumentException("Target nominal belanja harus lebih besar dari Rp0.");
        }

        $priority = (int) ($data['priority_level'] ?? 3);
        if ($priority < 1 || $priority > 5) {
            $priority = 3;
        }

        $goal = FinancialGoal::create([
            'name' => trim($data['name']),
            'category' => $data['category'] ?? 'work_essential',
            'priority_level' => $priority,
            'target_amount' => $targetAmount,
            'allocated_amount' => 0,
            'target_date' => $data['target_date'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLog::create([
            'auditable_type' => FinancialGoal::class,
            'auditable_id' => $goal->id,
            'action' => 'goal_created',
            'old_values' => null,
            'new_values' => $goal->toArray(),
            'reason' => "Target belanja '{$goal->name}' (Rp " . number_format($targetAmount, 0, ',', '.') . ") level prioritas {$priority} dibuat.",
        ]);

        return $goal;
    }

    /**
     * Sisihkan Dana Bertahap ke Target Belanja
     */
    public function allocateFunds(FinancialGoal $goal, int $amount, ?string $notes = null): FinancialGoal
    {
        if ($goal->status !== 'active') {
            throw new InvalidArgumentException("Hanya target yang berstatus aktif yang dapat disisihkan dananya.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal alokasi dana harus lebih besar dari Rp0.");
        }

        return DB::transaction(function () use ($goal, $amount, $notes) {
            $oldAllocated = $goal->allocated_amount;
            $newAllocated = $oldAllocated + $amount;

            $goal->update([
                'allocated_amount' => $newAllocated,
                'notes' => $notes ? ($goal->notes ? "{$goal->notes} | {$notes}" : $notes) : $goal->notes,
            ]);

            AuditLog::create([
                'auditable_type' => FinancialGoal::class,
                'auditable_id' => $goal->id,
                'action' => 'goal_funds_allocated',
                'old_values' => ['allocated_amount' => $oldAllocated],
                'new_values' => ['allocated_amount' => $newAllocated, 'added' => $amount],
                'reason' => "Dana Rp " . number_format($amount, 0, ',', '.') . " disisihkan ke target '{$goal->name}' (Terkumpul: Rp " . number_format($newAllocated, 0, ',', '.') . ").",
            ]);

            return $goal;
        });
    }

    /**
     * Realisasikan Belanja di Bengkel / Toko (Mencatat Pengeluaran Nyata)
     */
    public function achieveGoal(
        FinancialGoal $goal,
        Account $paymentAccount,
        ?int $actualSpentAmount = null,
        ?WorkDay $workDay = null
    ): FinancialGoal {
        if ($goal->status !== 'active') {
            throw new InvalidArgumentException("Target ini sudah tidak aktif atau sudah pernah direalisasikan.");
        }

        return DB::transaction(function () use ($goal, $paymentAccount, $actualSpentAmount, $workDay) {
            $spent = $actualSpentAmount ?: $goal->target_amount;

            // Tentukan akun beban berdasarkan kategori
            $expenseAccountName = match ($goal->category) {
                'maintenance', 'work_essential' => 'Beban Bensin Kendaraan', // or Perawatan
                'pangan', 'hygiene' => 'Beban Makanan & Minuman Driver',
                default => 'Beban Serba-serbi & Insidental Lapangan',
            };

            // Jika ada akun beban spesifik perawatan motor, gunakan itu
            if (in_array($goal->category, ['maintenance', 'work_essential'])) {
                $expenseAccount = Account::firstOrCreate(
                    ['name' => 'Beban Perawatan & Servis Motor'],
                    ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                );
            } else {
                $expenseAccount = Account::firstOrCreate(
                    ['name' => 'Beban Kebutuhan Hidup Pribadi'],
                    ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                );
            }

            $currentDay = $workDay ?? WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first();

            if ($currentDay) {
                $desc = "Realisasi Belanja Target: {$goal->name} (Rp " . number_format($spent, 0, ',', '.') . ")";
                $this->ledgerService->recordTransaction(
                    workDay: $currentDay,
                    type: 'expense',
                    description: $desc,
                    transactionTime: now(),
                    entries: [
                        ['account_id' => $paymentAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $spent],
                        ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $spent],
                    ],
                    isEstimatedTime: false,
                    metadata: [
                        'type' => 'goal_achieved_expense',
                        'goal_id' => $goal->id,
                        'goal_name' => $goal->name,
                        'actual_spent' => $spent,
                    ]
                );
            }

            $goal->update([
                'status' => 'achieved',
                'allocated_amount' => $spent,
                'achieved_at' => now(),
            ]);

            AuditLog::create([
                'auditable_type' => FinancialGoal::class,
                'auditable_id' => $goal->id,
                'action' => 'goal_achieved',
                'old_values' => ['status' => 'active'],
                'new_values' => ['status' => 'achieved', 'spent' => $spent, 'paid_from' => $paymentAccount->name],
                'reason' => "Target '{$goal->name}' berhasil dibeli seharga Rp " . number_format($spent, 0, ',', '.') . " menggunakan {$paymentAccount->name}.",
            ]);

            return $goal;
        });
    }

    /**
     * Hitung Uang Aman untuk Jajan (Safe-to-Spend)
     * Mengisolasi uang survival agar tidak termakan jajan es krim / cilok
     *
     * @param int $totalLiquidAssets (Total Kas Fisik + Bank/E-Wallet)
     * @return array{total_liquid: int, total_survival_reserved: int, total_wants_reserved: int, debt_reserved: int, subscription_reserved: int, safe_to_spend: int}
     */
    public function calculateSafeToSpend(int $totalLiquidAssets): array
    {
        $activeGoals = FinancialGoal::active()->get();

        $survivalReserved = (int) $activeGoals->whereIn('priority_level', [1, 2, 3])->sum('allocated_amount');
        $wantsReserved = (int) $activeGoals->whereIn('priority_level', [4, 5])->sum('allocated_amount');

        // Kewajiban Cicilan Hutang yang jatuh tempo sampai akhir bulan berjalan
        $debtReserved = (int) DebtInstallment::where('status', 'unpaid')
            ->where('due_date', '<=', now()->endOfMonth())
            ->sum('amount_due');

        // Beban Langganan & Tagihan Rutin aktif yang belum dibayar di bulan berjalan
        $activeSubscriptions = Subscription::active()->get();
        $subscriptionReserved = 0;
        foreach ($activeSubscriptions as $sub) {
            if (!$sub->isPaidThisMonth()) {
                $subscriptionReserved += $sub->amount;
            }
        }

        $totalReserved = $survivalReserved + $wantsReserved + $debtReserved + $subscriptionReserved;
        $safeToSpend = max(0, $totalLiquidAssets - $totalReserved);

        return [
            'total_liquid' => $totalLiquidAssets,
            'total_survival_reserved' => $survivalReserved,
            'total_wants_reserved' => $wantsReserved,
            'debt_reserved' => $debtReserved,
            'subscription_reserved' => $subscriptionReserved,
            'safe_to_spend' => $safeToSpend,
        ];
    }
}
