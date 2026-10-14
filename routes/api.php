<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::post('/accounts/{account}/initial-balance', [\App\Http\Controllers\Api\AccountController::class, 'setInitialBalance']);
    Route::post('/transfers', [\App\Http\Controllers\Api\TransferController::class, 'store']);
    Route::post('/driver/evaluate-performance', [\App\Http\Controllers\Api\DriverPerformanceController::class, 'evaluate']);
    Route::post('/driver/snapshot', [\App\Http\Controllers\Api\DriverSnapshotController::class, 'store']);
    Route::post('/driver/confirm-bonus-disbursement', [\App\Http\Controllers\Api\BonusDisbursementController::class, 'confirm']);
    Route::post('/quick/parking', [\App\Http\Controllers\Api\QuickParkingController::class, 'store']);
    Route::post('/quick/fuel', [\App\Http\Controllers\Api\QuickFuelController::class, 'store']);
    Route::post('/quick/voice-memo', [\App\Http\Controllers\Api\VoiceMemoController::class, 'upload']);
    Route::post('/voice-memos', [\App\Http\Controllers\Api\VoiceMemoController::class, 'upload']);
    Route::get('/voice-memos', [\App\Http\Controllers\Api\VoiceMemoController::class, 'index']);
    Route::post('/quick/cod', [\App\Http\Controllers\Api\CodOrderController::class, 'store']);
    Route::post('/quick/personal-expense', [\App\Http\Controllers\Api\PersonalExpenseController::class, 'store']);
    Route::post('/debts/friend-payment', [\App\Http\Controllers\Api\FriendDebtController::class, 'pay']);
    Route::get('/debts/upcoming', [\App\Http\Controllers\Api\DebtScheduleController::class, 'upcoming']);
    Route::post('/reconciliation/finalize-day', [\App\Http\Controllers\Api\ReconciliationController::class, 'finalizeDay']);
    Route::get('/reconciliation/unfinalized-dates', [\App\Http\Controllers\Api\UnfinalizedDatesBannerController::class, 'index']);

    // In-App OTA Auto-Update Endpoint
    Route::get('/app/check-update', [\App\Http\Controllers\Api\AppUpdateController::class, 'checkUpdate']);
});

Route::match(['get', 'post'], '/deploy', [\App\Http\Controllers\Api\DeployWebhookController::class, 'handle']);
