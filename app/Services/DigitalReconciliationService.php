<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;

class DigitalReconciliationService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Rekonsiliasi Saldo Digital Malam Hari (Snapshot 4 Saldo Digital & Deteksi Surplus QRIS)
     *
     * @param WorkDay $workDay
     * @param array<int, array{account_id: int, actual_balance: int}> $accountBalances
     * @param int $codQrisAmount Nominal pembayaran tagihan pokok COD yang dibayar via QRIS (Bukan tip)
     * @return array{adjusted_count: int, total_surplus: int, total_shortage: int, cod_reimbursed: int, net_qris_tip: int}
     */
    public function reconcileDigitalAccounts(WorkDay $workDay, array $accountBalances, int $codQrisAmount = 0): array
    {
        return DB::transaction(function () use ($workDay, $accountBalances, $codQrisAmount) {
            $adjustedCount = 0;
            $totalSurplus = 0;
            $totalShortage = 0;
            $remainingCodQris = max(0, $codQrisAmount);
            $totalCodReimbursed = 0;
            $totalNetQrisTip = 0;

            $totalMerchantSettled = 0;

            // 0. Deteksi Otomatis Auto-Settlement & Penyelarasan Transit GoPay Merchant -> GoPay
            // GoPay Merchant adalah wadah penampung transit QRIS sementara yang otomatis mencair (settle) ke GoPay reguler.
            $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
            $gopayAccount = Account::where('name', 'GoPay')->first();

            $merchantItem = null;
            $gopayItem = null;

            if ($merchantAccount && $gopayAccount) {
                foreach ($accountBalances as $item) {
                    if ((int)$item['account_id'] === $merchantAccount->id) {
                        $merchantItem = $item;
                    }
                    if ((int)$item['account_id'] === $gopayAccount->id) {
                        $gopayItem = $item;
                    }
                }

                if ($merchantItem) {
                    $merchantActual = (int)$merchantItem['actual_balance'];
                    $merchantDiff = $merchantActual - (int)$merchantAccount->current_balance;

                    // Jika saldo GoPay Merchant berkurang (misal dari 4.000 menjadi 3.000 karena auto-settle):
                    if ($merchantDiff < 0) {
                        $settleDrop = abs($merchantDiff);

                        if ($gopayItem) {
                            $gopayActual = (int)$gopayItem['actual_balance'];
                            $gopayDiff = $gopayActual - (int)$gopayAccount->current_balance;

                            if ($gopayDiff > 0) {
                                // Sebagian atau seluruh kenaikan di GoPay berasal dari pencairan GoPay Merchant!
                                $settlementTransfer = min($settleDrop, $gopayDiff);

                                if ($settlementTransfer > 0) {
                                    $this->ledgerService->recordTransaction(
                                        workDay: $workDay,
                                        type: 'transfer',
                                        description: "Auto-Settlement GoPay Merchant ke GoPay: Rp " . number_format($settlementTransfer, 0, ',', '.') . " (Pencairan Saldo Transit QRIS)",
                                        transactionTime: now(),
                                        entries: [
                                            ['account_id' => $merchantAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $settlementTransfer],
                                            ['account_id' => $gopayAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $settlementTransfer],
                                        ],
                                        isEstimatedTime: false,
                                        metadata: [
                                            'type' => 'merchant_auto_settlement',
                                            'amount' => $settlementTransfer,
                                        ]
                                    );

                                    AuditLog::create([
                                        'auditable_type' => Account::class,
                                        'auditable_id' => $merchantAccount->id,
                                        'action' => 'merchant_auto_settlement',
                                        'old_values' => ['balance' => $merchantAccount->current_balance + $settlementTransfer],
                                        'new_values' => ['balance' => $merchantAccount->current_balance, 'settled_to_gopay' => $settlementTransfer],
                                        'reason' => "Pencairan otomatis (auto-settlement) saldo transit QRIS GoPay Merchant ke rekening GoPay reguler Rp " . number_format($settlementTransfer, 0, ',', '.'),
                                    ]);

                                    $adjustedCount++;
                                    $totalMerchantSettled += $settlementTransfer;
                                    $settleDrop -= $settlementTransfer;
                                }
                            }
                        }

                        // Jika masih ada sisa penurunan saldo pada GoPay Merchant (misal GoPay sudah lebih dulu diselaraskan ke saldo akhir):
                        // Karena GoPay Merchant HANYA wadah transit penampung sementara QRIS,
                        // sisa saldo ini diselaraskan ke Pendapatan QRIS (karena dana QRIS sudah masuk ke GoPay sebelumnya)
                        if ($settleDrop > 0) {
                            $surplusAccount = Account::firstOrCreate(
                                ['name' => 'Pendapatan QRIS / Tip Non-Tunai'],
                                ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                            );

                            $this->ledgerService->recordTransaction(
                                workDay: $workDay,
                                type: 'income',
                                description: "Penyesuaian Saldo Transit QRIS GoPay Merchant: -Rp " . number_format($settleDrop, 0, ',', '.') . " (Pencairan ke GoPay)",
                                transactionTime: now(),
                                entries: [
                                    ['account_id' => $merchantAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $settleDrop],
                                    ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $settleDrop],
                                ],
                                isEstimatedTime: false,
                                metadata: [
                                    'type' => 'merchant_transit_adjustment',
                                    'amount' => $settleDrop,
                                ]
                            );

                            AuditLog::create([
                                'auditable_type' => Account::class,
                                'auditable_id' => $merchantAccount->id,
                                'action' => 'merchant_transit_adjustment',
                                'old_values' => ['balance' => $merchantAccount->current_balance + $settleDrop],
                                'new_values' => ['balance' => $merchantAccount->current_balance, 'adjusted_amount' => $settleDrop],
                                'reason' => "Penyelarasan saldo transit GoPay Merchant: -Rp " . number_format($settleDrop, 0, ',', '.') . " telah otomatis cair ke GoPay reguler.",
                            ]);

                            $adjustedCount++;
                            $totalMerchantSettled += $settleDrop;
                        }
                    }
                }
            }

            foreach ($accountBalances as $item) {
                $accountId = (int) $item['account_id'];
                $actualBalance = (int) $item['actual_balance'];

                $account = Account::findOrFail($accountId);
                $diff = $actualBalance - $account->current_balance;

                if ($diff === 0) {
                    continue;
                }

                $adjustedCount++;

                if ($diff > 0) {
                    // Surplus terdeteksi (Gaji/ongkir ShopeeFood di ShopeePay, Tip QRIS di GoPay/Merchant, atau penambahan saldo lain)
                    $totalSurplus += $diff;

                    if ($account->name === 'ShopeePay') {
                        $surplusAccount = Account::firstOrCreate(
                            ['name' => 'Pendapatan Hub ShopeeFood'],
                            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        );
                        $txDesc = "Penyelarasan Saldo ShopeePay Driver: +Rp " . number_format($diff, 0, ',', '.');
                        $auditReason = "Penyelarasan saldo ShopeePay: surplus +Rp " . number_format($diff, 0, ',', '.') . " diakui sebagai pendapatan narik ShopeeFood.";

                        $this->ledgerService->recordTransaction(
                            workDay: $workDay,
                            type: 'income',
                            description: $txDesc,
                            transactionTime: now(),
                            entries: [
                                ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diff],
                                ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diff],
                            ],
                            isEstimatedTime: false,
                            metadata: [
                                'type' => 'digital_reconciliation_surplus',
                                'account_id' => $account->id,
                                'diff' => $diff,
                            ]
                        );
                    } elseif (in_array($account->name, ['GoPay', 'GoPay Merchant'])) {
                        // Perhitungan Teliti: Apakah sebagian dari uang masuk QRIS ini adalah pelunasan tagihan COD makanan customer?
                        $codPortion = 0;
                        if ($remainingCodQris > 0) {
                            $codPortion = min($diff, $remainingCodQris);
                            $remainingCodQris -= $codPortion;
                            $totalCodReimbursed += $codPortion;
                        }

                        $tipPortion = $diff - $codPortion;
                        $totalNetQrisTip += $tipPortion;

                        // 1. Jika ada porsi pelunasan tagihan COD customer via QRIS (Pengembalian modal makanan, BUKAN tip!)
                        if ($codPortion > 0) {
                            $talanganAccount = Account::firstOrCreate(
                                ['name' => 'Talangan Pesanan COD'],
                                ['type' => AccountType::RECEIVABLE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                            );

                            $this->ledgerService->recordTransaction(
                                workDay: $workDay,
                                type: 'income',
                                description: "Pelunasan Tagihan COD Customer via QRIS {$account->name}: Rp " . number_format($codPortion, 0, ',', '.') . " (Pengembalian Modal Makanan)",
                                transactionTime: now(),
                                entries: [
                                    ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $codPortion],
                                    ['account_id' => $talanganAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $codPortion],
                                ],
                                isEstimatedTime: false,
                                metadata: [
                                    'type' => 'cod_qris_settlement',
                                    'account_id' => $account->id,
                                    'bill_amount' => $codPortion,
                                ]
                            );
                        }

                        // 2. Jika ada sisa kelebihan di atas tagihan COD, baru diakui sebagai Tip Non-Tunai Murni!
                        if ($tipPortion > 0) {
                            $surplusAccount = Account::firstOrCreate(
                                ['name' => 'Pendapatan QRIS / Tip Non-Tunai'],
                                ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                            );

                            $this->ledgerService->recordTransaction(
                                workDay: $workDay,
                                type: 'income',
                                description: "Surplus Tip QRIS Murni {$account->name}: +Rp " . number_format($tipPortion, 0, ',', '.'),
                                transactionTime: now(),
                                entries: [
                                    ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $tipPortion],
                                    ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $tipPortion],
                                ],
                                isEstimatedTime: false,
                                metadata: [
                                    'type' => 'digital_reconciliation_surplus',
                                    'account_id' => $account->id,
                                    'diff' => $tipPortion,
                                    'cod_portion' => $codPortion,
                                ]
                            );
                        }

                        $auditReason = "Penyelarasan saldo digital {$account->name}: total +Rp " . number_format($diff, 0, ',', '.') . 
                            ($codPortion > 0 ? " (Rp " . number_format($codPortion, 0, ',', '.') . " pokok COD makanan + Rp " . number_format($tipPortion, 0, ',', '.') . " tip murni)." : " diakui sebagai tip QRIS non-tunai.");
                    } elseif ($account->name === 'Cash' || $account->type === AccountType::CASH) {
                        $surplusAccount = Account::firstOrCreate(
                            ['name' => 'Pendapatan Tip Tunai Langsung'],
                            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        );
                        $txDesc = "Surplus Uang Kas Dompet: +Rp " . number_format($diff, 0, ',', '.');
                        $auditReason = "Penyelarasan uang kas dompet: surplus +Rp " . number_format($diff, 0, ',', '.') . " diakui sebagai tip tunai langsung dari pelanggan.";

                        $this->ledgerService->recordTransaction(
                            workDay: $workDay,
                            type: 'income',
                            description: $txDesc,
                            transactionTime: now(),
                            entries: [
                                ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diff],
                                ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diff],
                            ],
                            isEstimatedTime: false,
                            metadata: [
                                'type' => 'digital_reconciliation_surplus',
                                'account_id' => $account->id,
                                'diff' => $diff,
                            ]
                        );
                    } else {
                        $surplusAccount = Account::firstOrCreate(
                            ['name' => 'Pendapatan Selisih Kas (Surplus Tak Terlacak)'],
                            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        );
                        $txDesc = "Surplus Digital Nyusul {$account->name}: +Rp " . number_format($diff, 0, ',', '.');
                        $auditReason = "Penyelarasan saldo digital {$account->name}: surplus +Rp " . number_format($diff, 0, ',', '.') . " diakui sebagai pendapatan digital nyusul.";

                        $this->ledgerService->recordTransaction(
                            workDay: $workDay,
                            type: 'income',
                            description: $txDesc,
                            transactionTime: now(),
                            entries: [
                                ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diff],
                                ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diff],
                            ],
                            isEstimatedTime: false,
                            metadata: [
                                'type' => 'digital_reconciliation_surplus',
                                'account_id' => $account->id,
                                'diff' => $diff,
                            ]
                        );
                    }

                    AuditLog::create([
                        'auditable_type' => Account::class,
                        'auditable_id' => $account->id,
                        'action' => 'digital_reconciliation_surplus',
                        'old_values' => ['balance' => $account->current_balance],
                        'new_values' => ['balance' => $actualBalance, 'surplus' => $diff],
                        'reason' => $auditReason,
                    ]);
                } else {
                    // Shortage terdeteksi (Biaya admin bank, potongan auto-debit, atau selisih kurang)
                    $shortageAmount = abs($diff);
                    $totalShortage += $shortageAmount;

                    $expenseAccount = ($account->name === 'Cash' || $account->type === AccountType::CASH)
                        ? Account::firstOrCreate(
                            ['name' => 'Beban Selisih Kas Kurang'],
                            ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        )
                        : Account::firstOrCreate(
                            ['name' => 'Beban Biaya Admin Transfer'],
                            ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        );

                    $txDesc = ($account->name === 'Cash' || $account->type === AccountType::CASH)
                        ? "Penyesuaian Selisih Kas Dompet: -Rp " . number_format($shortageAmount, 0, ',', '.')
                        : "Penyesuaian Saldo Digital {$account->name}: -Rp " . number_format($shortageAmount, 0, ',', '.');

                    $auditReason = ($account->name === 'Cash' || $account->type === AccountType::CASH)
                        ? "Penyelarasan uang kas dompet: selisih -Rp " . number_format($shortageAmount, 0, ',', '.') . " disesuaikan sebagai beban selisih kas."
                        : "Penyelarasan saldo digital {$account->name}: selisih -Rp " . number_format($shortageAmount, 0, ',', '.') . " disesuaikan.";

                    $tx = $this->ledgerService->recordTransaction(
                        workDay: $workDay,
                        type: 'expense',
                        description: $txDesc,
                        transactionTime: now(),
                        entries: [
                            ['account_id' => $account->id, 'entry_type' => EntryType::CREDIT, 'amount' => $shortageAmount],
                            ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $shortageAmount],
                        ],
                        isEstimatedTime: false,
                        metadata: [
                            'type' => 'digital_reconciliation_shortage',
                            'account_id' => $account->id,
                            'diff' => $diff,
                        ]
                    );

                    AuditLog::create([
                        'auditable_type' => Account::class,
                        'auditable_id' => $account->id,
                        'action' => 'digital_reconciliation_shortage',
                        'old_values' => ['balance' => $account->current_balance],
                        'new_values' => ['balance' => $actualBalance, 'shortage' => $shortageAmount],
                        'reason' => $auditReason,
                    ]);
                }
            }

            return [
                'adjusted_count' => $adjustedCount,
                'total_surplus' => $totalSurplus,
                'total_shortage' => $totalShortage,
                'cod_reimbursed' => $totalCodReimbursed,
                'net_qris_tip' => $totalNetQrisTip,
                'merchant_settled' => $totalMerchantSettled,
            ];
        });
    }
}
