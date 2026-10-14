<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SetInitialBalanceRequest;
use App\Models\Account;
use App\Services\InitialBalanceService;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    protected InitialBalanceService $initialBalanceService;

    public function __construct(InitialBalanceService $initialBalanceService)
    {
        $this->initialBalanceService = $initialBalanceService;
    }

    /**
     * Endpoint untuk inisialisasi / koreksi saldo modal awal akun.
     */
    public function setInitialBalance(SetInitialBalanceRequest $request, Account $account): JsonResponse
    {
        $amount = (int) $request->input('initial_balance');
        $reason = $request->input('reason');

        if ($account->initial_balance > 0 && $reason) {
            $updated = $this->initialBalanceService->correctInitialBalance($account, $amount, $reason);
            $message = 'Saldo awal berhasil dikoreksi.';
        } else {
            $updated = $this->initialBalanceService->setInitialBalance($account, $amount);
            $message = 'Saldo awal berhasil diatur.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'account_id' => $updated->id,
                'name' => $updated->name,
                'current_balance' => $updated->current_balance,
                'initial_balance' => $updated->initial_balance,
            ],
        ], 200);
    }
}
