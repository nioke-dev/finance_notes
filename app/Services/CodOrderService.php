<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CodOrderService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordCodOrder(
        WorkDay $workDay,
        int $billAmount,
        int $receivedAmount,
        ?string $orderNumber = null,
        ?Carbon $transactionTime = null
    ): Transaction {
        if ($billAmount <= 0) {
            throw new InvalidArgumentException("Tagihan COD harus lebih besar dari Rp0.");
        }

        if ($receivedAmount < $billAmount) {
            throw new InvalidArgumentException("Uang tunai diterima tidak boleh kurang dari nominal tagihan COD.");
        }

        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $codSettlementAccount = Account::where('name', 'ShopeePay')->firstOrFail();
        $tipIncomeAccount = Account::where('name', 'Tip Cash / Surplus COD')->firstOrFail();

        $surplus = $receivedAmount - $billAmount;
        $orderLabel = $orderNumber ? " Pesanan #{$orderNumber}" : "";
        $description = "Penyelesaian COD{$orderLabel}: Tagihan Rp{$billAmount}, Terima Rp{$receivedAmount}";

        $entries = [];

        // 1. Debit Cash penuh sebesar uang riil yang masuk kantong
        $entries[] = [
            'account_id' => $cashAccount->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => $receivedAmount,
        ];

        // 2. Kredit akun penampung tagihan
        $entries[] = [
            'account_id' => $codSettlementAccount->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => $billAmount,
        ];

        // 3. Kredit akun surplus tip jika ada kelebihan uang
        if ($surplus > 0) {
            $entries[] = [
                'account_id' => $tipIncomeAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $surplus,
            ];
        }

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: $entries,
            isEstimatedTime: false,
            metadata: [
                'type' => 'cod_order',
                'order_number' => $orderNumber,
                'bill_amount' => $billAmount,
                'received_amount' => $receivedAmount,
                'surplus_amount' => $surplus,
            ]
        );
    }
}
