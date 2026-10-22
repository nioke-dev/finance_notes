<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ActiveCodOrderService
{
    protected LedgerService $ledgerService;
    protected ReversalService $reversalService;

    public function __construct(
        LedgerService $ledgerService,
        ReversalService $reversalService
    ) {
        $this->ledgerService = $ledgerService;
        $this->reversalService = $reversalService;
    }

    /**
     * Fase 1: Driver menerima pesanan COD (Shopee otomatis memotong saldo ShopeePay driver).
     */
    public function acceptCodOrder(
        WorkDay $workDay,
        string $orderCode,
        int $billAmount,
        ?string $merchantName = null,
        ?int $roundedBillAmount = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $notes = null,
        ?\Illuminate\Support\Carbon $transactionTime = null
    ): ActiveCodOrder {
        if ($billAmount <= 0) {
            throw new InvalidArgumentException("Tagihan COD harus lebih besar dari Rp0.");
        }

        $orderCode = trim($orderCode);
        if (empty($orderCode)) {
            throw new InvalidArgumentException("Kode pesanan (misal 633) wajib diisi.");
        }

        $roundedAmount = $roundedBillAmount ?: ActiveCodOrder::calculateRounding($billAmount);
        $txTime = $transactionTime ?? now();

        return DB::transaction(function () use (
            $workDay,
            $orderCode,
            $billAmount,
            $merchantName,
            $roundedAmount,
            $latitude,
            $longitude,
            $notes,
            $txTime
        ) {
            $shopeePayAccount = Account::where('name', 'ShopeePay')->firstOrFail();
            $talanganAccount = Account::firstOrCreate(
                ['name' => 'Talangan Pesanan COD'],
                [
                    'type' => AccountType::RECEIVABLE,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $merchantDesc = $merchantName ? " - {$merchantName}" : "";
            $desc = "Talangan COD #{$orderCode}{$merchantDesc} (Rp " . number_format($billAmount, 0, ',', '.') . ")";

            // Jurnal: ShopeePay berkurang (Credit), Piutang Talangan COD bertambah (Debit)
            $tx = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'expense',
                description: $desc,
                transactionTime: $txTime,
                entries: [
                    ['account_id' => $shopeePayAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $billAmount],
                    ['account_id' => $talanganAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $billAmount],
                ],
                isEstimatedTime: false,
                metadata: [
                    'type' => 'cod_order_accepted',
                    'order_code' => $orderCode,
                    'merchant_name' => $merchantName,
                    'bill_amount' => $billAmount,
                    'rounded_bill_amount' => $roundedAmount,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );

            $activeOrder = ActiveCodOrder::create([
                'work_day_id' => $workDay->id,
                'order_code' => $orderCode,
                'merchant_name' => $merchantName,
                'bill_amount' => $billAmount,
                'rounded_bill_amount' => $roundedAmount,
                'status' => 'on_delivery',
                'accepted_at' => $txTime,
                'accepted_latitude' => $latitude,
                'accepted_longitude' => $longitude,
                'initial_transaction_id' => $tx->id,
                'notes' => $notes,
            ]);

            AuditLog::create([
                'auditable_type' => ActiveCodOrder::class,
                'auditable_id' => $activeOrder->id,
                'action' => 'cod_order_accepted',
                'old_values' => null,
                'new_values' => [
                    'order_code' => $orderCode,
                    'bill_amount' => $billAmount,
                    'merchant_name' => $merchantName,
                ],
                'reason' => "Order COD #{$orderCode} masuk di sistem Shopee, memotong saldo ShopeePay Rp " . number_format($billAmount, 0, ',', '.'),
            ]);

            return $activeOrder;
        });
    }

    /**
     * Fase 2: Driver tiba di depan rumah customer dan menyelesaikan pembayaran COD.
     */
    public function completeCodOrder(
        ActiveCodOrder $order,
        int $receivedAmount,
        string $paymentMethod = 'cash',
        ?int $customerGaveAmount = null,
        ?int $changeReturned = null,
        ?int $settlementAccountId = null,
        int $parkingReimbursed = 0,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $notes = null,
        ?\Illuminate\Support\Carbon $transactionTime = null
    ): ActiveCodOrder {
        if ($order->status !== 'on_delivery') {
            throw new InvalidArgumentException("Order COD #{$order->order_code} sudah tidak dalam status pengantaran (status: {$order->status}).");
        }

        if ($receivedAmount < $order->bill_amount) {
            throw new InvalidArgumentException("Uang yang diterima (Rp" . number_format($receivedAmount, 0, ',', '.') . ") tidak boleh lebih kecil dari tagihan resto (Rp" . number_format($order->bill_amount, 0, ',', '.') . ").");
        }

        $txTime = $transactionTime ?? now();

        return DB::transaction(function () use (
            $order,
            $receivedAmount,
            $paymentMethod,
            $customerGaveAmount,
            $changeReturned,
            $settlementAccountId,
            $parkingReimbursed,
            $latitude,
            $longitude,
            $notes,
            $txTime
        ) {
            // Tentukan akun tujuan uang masuk
            if ($paymentMethod === 'cash') {
                $settlementAccount = Account::where('name', 'Cash')->firstOrFail();
            } else {
                $settlementAccount = $settlementAccountId
                    ? Account::findOrFail($settlementAccountId)
                    : Account::where('name', 'GoPay')->firstOrFail();
            }

            $talanganAccount = Account::where('name', 'Talangan Pesanan COD')->firstOrFail();
            
            $tipAccount = ($paymentMethod === 'qris')
                ? Account::firstOrCreate(
                    ['name' => 'Pendapatan QRIS / Tip Non-Tunai'],
                    ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                )
                : Account::firstOrCreate(
                    ['name' => 'Tip Cash / Surplus COD'],
                    ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                );

            $totalSurplus = $receivedAmount - $order->bill_amount;
            $actualParkingReimbursed = 0;
            $actualTip = $totalSurplus;

            $entries = [];

            // 1. Debit Akun Penerima (Kas Dompet atau E-Wallet QRIS) sebesar uang riil yang masuk
            $entries[] = [
                'account_id' => $settlementAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $receivedAmount,
            ];

            // 2. Kredit Akun Talangan COD sebesar tagihan resto (Piutang lunas, kembali Rp0)
            $entries[] = [
                'account_id' => $talanganAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $order->bill_amount,
            ];

            // 3. Jika customer mengganti uang parkir resto
            if ($parkingReimbursed > 0 && $totalSurplus >= $parkingReimbursed) {
                $actualParkingReimbursed = $parkingReimbursed;
                $actualTip = $totalSurplus - $actualParkingReimbursed;

                $parkingExpenseAccount = Account::firstOrCreate(
                    ['name' => 'Beban Parkir'],
                    ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                );

                $entries[] = [
                    'account_id' => $parkingExpenseAccount->id,
                    'entry_type' => EntryType::CREDIT, // Membalik/mengurangi beban parkir yang tadi dikeluarkan driver
                    'amount' => $actualParkingReimbursed,
                ];
            }

            // 4. Kredit Akun Tip / Surplus jika ada kelebihan uang
            if ($actualTip > 0) {
                $entries[] = [
                    'account_id' => $tipAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $actualTip,
                ];
            }

            $methodLabel = strtoupper($paymentMethod);
            $desc = "Penyelesaian COD #{$order->order_code}: Tagihan Rp" . number_format($order->bill_amount, 0, ',', '.') . 
                    ", Diterima Rp" . number_format($receivedAmount, 0, ',', '.') . " via {$methodLabel} ({$settlementAccount->name})";

            $tx = $this->ledgerService->recordTransaction(
                workDay: $order->workDay,
                type: 'income',
                description: $desc,
                transactionTime: $txTime,
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'type' => 'cod_order_completed',
                    'order_code' => $order->order_code,
                    'bill_amount' => $order->bill_amount,
                    'received_amount' => $receivedAmount,
                    'customer_gave_amount' => $customerGaveAmount,
                    'change_returned' => $changeReturned,
                    'tip_amount' => $actualTip,
                    'parking_reimbursed' => $actualParkingReimbursed,
                    'payment_method' => $paymentMethod,
                    'settlement_account_id' => $settlementAccount->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );

            $order->update([
                'status' => 'delivered',
                'payment_method' => $paymentMethod,
                'customer_gave_amount' => $customerGaveAmount,
                'change_returned' => $changeReturned,
                'received_amount' => $receivedAmount,
                'tip_amount' => $actualTip,
                'parking_reimbursed' => $actualParkingReimbursed,
                'settlement_account_id' => $settlementAccount->id,
                'delivered_at' => $txTime,
                'delivered_latitude' => $latitude,
                'delivered_longitude' => $longitude,
                'completion_transaction_id' => $tx->id,
                'notes' => $notes ?: $order->notes,
            ]);

            AuditLog::create([
                'auditable_type' => ActiveCodOrder::class,
                'auditable_id' => $order->id,
                'action' => 'cod_order_delivered',
                'old_values' => ['status' => 'on_delivery'],
                'new_values' => [
                    'status' => 'delivered',
                    'received_amount' => $receivedAmount,
                    'tip_amount' => $actualTip,
                    'payment_method' => $paymentMethod,
                ],
                'reason' => "Pesanan COD #{$order->order_code} selesai diserahkan ke customer. Uang Rp " . number_format($receivedAmount, 0, ',', '.') . " masuk ke {$settlementAccount->name}.",
            ]);

            return $order;
        });
    }

    /**
     * Batalkan pesanan COD (misal resto tutup atau dicancel pelanggan di aplikasi).
     */
    public function cancelCodOrder(ActiveCodOrder $order, string $reason): ActiveCodOrder
    {
        if ($order->status !== 'on_delivery') {
            throw new InvalidArgumentException("Order COD #{$order->order_code} sudah berstatus {$order->status} sehingga tidak dapat dibatalkan.");
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan pembatalan pesanan COD wajib diisi minimal 5 karakter.");
        }

        return DB::transaction(function () use ($order, $reason) {
            // Reverse transaksi awal agar saldo ShopeePay dipulihkan
            if ($order->initialTransaction) {
                $this->reversalService->reverseTransaction(
                    originalTransaction: $order->initialTransaction,
                    reason: "Pembatalan Order COD #{$order->order_code}: {$reason}"
                );
            }

            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes ? "{$order->notes} | Batal: {$reason}" : "Batal: {$reason}",
            ]);

            AuditLog::create([
                'auditable_type' => ActiveCodOrder::class,
                'auditable_id' => $order->id,
                'action' => 'cod_order_cancelled',
                'old_values' => ['status' => 'on_delivery'],
                'new_values' => ['status' => 'cancelled', 'reason' => $reason],
                'reason' => "Pesanan COD #{$order->order_code} dibatalkan: {$reason}. Saldo ShopeePay dipulihkan.",
            ]);

            return $order;
        });
    }
}
