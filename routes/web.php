<?php

use App\Http\Controllers\Web\AuditLogWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\ClosingWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DebtWebController;
use App\Http\Controllers\Web\DriverAnalyticsController;
use App\Http\Controllers\Web\ExportReportController;
use App\Http\Controllers\Web\FuelAnalyticsController;
use App\Http\Controllers\Web\ShopeeBonusSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login']);
});

// Authenticated Super App Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect('/dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/closing/{id}', [ClosingWebController::class, 'show'])->name('closing.show');
    Route::post('/closing/{id}/denominations', [ClosingWebController::class, 'saveDenominations'])->name('closing.denominations');
    Route::post('/closing/{id}/finalize', [ClosingWebController::class, 'finalize'])->name('closing.finalize');
    Route::post('/closing/{id}/adjust-discrepancy', [ClosingWebController::class, 'adjustDiscrepancy'])->name('closing.adjust-discrepancy');
    Route::post('/closing/{id}/transactions/{transactionId}/correct', [ClosingWebController::class, 'correctTransaction'])->name('closing.transactions.correct');

    // Operational Actions Form Submissions
    Route::post('/operational/open-day', [\App\Http\Controllers\Web\OperationalWebController::class, 'openWorkDay'])->name('operational.open-day');
    Route::post('/operational/account', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeAccount'])->name('operational.account');
    Route::post('/operational/account/{id}/toggle-active', [\App\Http\Controllers\Web\OperationalWebController::class, 'toggleAccountActive'])->name('operational.account.toggle-active');
    Route::delete('/operational/account/{id}', [\App\Http\Controllers\Web\OperationalWebController::class, 'destroyAccount'])->name('operational.account.destroy');
    Route::post('/operational/paylater-providers', [\App\Http\Controllers\Web\OperationalWebController::class, 'storePaylaterProvider'])->name('operational.paylater-providers.store');
    Route::post('/operational/paylater-providers/{id}/limit', [\App\Http\Controllers\Web\OperationalWebController::class, 'updatePaylaterProviderLimit'])->name('operational.paylater-providers.limit');
    Route::post('/operational/paylater-providers/{id}/toggle-active', [\App\Http\Controllers\Web\OperationalWebController::class, 'togglePaylaterProviderActive'])->name('operational.paylater-providers.toggle-active');
    Route::delete('/operational/paylater-providers/{id}', [\App\Http\Controllers\Web\OperationalWebController::class, 'destroyPaylaterProvider'])->name('operational.paylater-providers.destroy');
    Route::post('/operational/set-initial-balance', [\App\Http\Controllers\Web\OperationalWebController::class, 'setAccountInitialBalance'])->name('operational.set-initial-balance');
    Route::post('/operational/cash-denominations', [\App\Http\Controllers\Web\OperationalWebController::class, 'updateCashDenominations'])->name('operational.cash-denominations');
    Route::post('/operational/fuel', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeFuel'])->name('operational.fuel');
    Route::post('/operational/parking', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeParking'])->name('operational.parking');
    Route::post('/operational/quick-tip', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeQuickTip'])->name('operational.quick-tip');
    Route::post('/operational/batch-transactions', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeBatchTransactions'])->name('operational.batch-transactions');
    Route::post('/operational/update-hub-bonus', [\App\Http\Controllers\Web\OperationalWebController::class, 'updateHubBonus'])->name('operational.update-hub-bonus');
    Route::post('/operational/claim-hub-bonus', [\App\Http\Controllers\Web\OperationalWebController::class, 'claimHubBonus'])->name('operational.claim-hub-bonus');
    Route::post('/operational/cod', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeCod'])->name('operational.cod');
    Route::post('/operational/cod-orders/accept', [\App\Http\Controllers\Web\OperationalWebController::class, 'acceptCodOrder'])->name('operational.cod-orders.accept');
    Route::post('/operational/cod-orders/{id}/complete', [\App\Http\Controllers\Web\OperationalWebController::class, 'completeCodOrder'])->name('operational.cod-orders.complete');
    Route::post('/operational/cod-orders/{id}/cancel', [\App\Http\Controllers\Web\OperationalWebController::class, 'cancelCodOrder'])->name('operational.cod-orders.cancel');
    Route::post('/operational/transfer', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeTransfer'])->name('operational.transfer');
    Route::post('/operational/personal-expense', [\App\Http\Controllers\Web\OperationalWebController::class, 'storePersonalExpense'])->name('operational.personal-expense');
    Route::post('/operational/personal-income', [\App\Http\Controllers\Web\OperationalWebController::class, 'storePersonalIncome'])->name('operational.personal-income');
    Route::post('/operational/field-incidental-expense', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeFieldIncidentalExpense'])->name('operational.field-incidental-expense');
    Route::post('/operational/debt', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeDebt'])->name('operational.debt');
    Route::post('/operational/existing-debt', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeExistingDebt'])->name('operational.existing-debt');
    Route::delete('/operational/debts/{id}', [\App\Http\Controllers\Web\OperationalWebController::class, 'destroyDebt'])->name('operational.debts.destroy');
    Route::post('/operational/spaylater-purchase', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeSPayLaterPurchase'])->name('operational.spaylater-purchase');
    Route::post('/operational/pay-installment', [\App\Http\Controllers\Web\OperationalWebController::class, 'payInstallment'])->name('operational.pay-installment');
    Route::post('/operational/settle-friend-debt', [\App\Http\Controllers\Web\OperationalWebController::class, 'settleFriendDebt'])->name('operational.settle-friend-debt');
    Route::post('/operational/lend-friend-cash', [\App\Http\Controllers\Web\OperationalWebController::class, 'lendFriendCash'])->name('operational.lend-friend-cash');
    Route::post('/operational/digital-reconciliation', [\App\Http\Controllers\Web\OperationalWebController::class, 'reconcileDigital'])->name('operational.digital-reconciliation');
    Route::post('/operational/goals', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeGoal'])->name('operational.goals');
    Route::post('/operational/goals/{id}/allocate', [\App\Http\Controllers\Web\OperationalWebController::class, 'allocateGoalFunds'])->name('operational.goals.allocate');
    Route::post('/operational/goals/{id}/achieve', [\App\Http\Controllers\Web\OperationalWebController::class, 'achieveGoal'])->name('operational.goals.achieve');
    Route::post('/operational/subscriptions', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeSubscription'])->name('operational.subscriptions');
    Route::post('/operational/subscriptions/{id}/pay', [\App\Http\Controllers\Web\OperationalWebController::class, 'paySubscription'])->name('operational.subscriptions.pay');
    Route::post('/operational/subscriptions/{id}/toggle', [\App\Http\Controllers\Web\OperationalWebController::class, 'toggleSubscription'])->name('operational.subscriptions.toggle');
    Route::post('/operational/voice-memo', [\App\Http\Controllers\Api\VoiceMemoController::class, 'upload'])->name('operational.voice-memo');
    Route::post('/operational/account-deduct-balance', [\App\Http\Controllers\Web\OperationalWebController::class, 'deductAccountBalance'])->name('operational.account-deduct-balance');
    Route::post('/operational/account-deduction', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeAccountDeduction'])->name('operational.account-deduction.store');
    Route::post('/operational/account-deduction/{id}/execute', [\App\Http\Controllers\Web\OperationalWebController::class, 'executeAccountDeduction'])->name('operational.account-deduction.execute');
    Route::delete('/operational/account-deduction/{id}', [\App\Http\Controllers\Web\OperationalWebController::class, 'destroyAccountDeduction'])->name('operational.account-deduction.destroy');
    Route::post('/operational/transactions/{id}/edit-description', [\App\Http\Controllers\Web\OperationalWebController::class, 'updateTransactionDescription'])->name('operational.transactions.edit-description');
    Route::post('/operational/reset-trial-data', [\App\Http\Controllers\Web\OperationalWebController::class, 'resetTrialData'])->name('operational.reset-trial-data');

    Route::get('/analytics/driver', [DriverAnalyticsController::class, 'index'])->name('analytics.driver');
    Route::get('/analytics/fuel', [FuelAnalyticsController::class, 'index'])->name('analytics.fuel');
    Route::get('/debts', [DebtWebController::class, 'index'])->name('debts.index');
    Route::get('/wallets', [\App\Http\Controllers\Web\WalletWebController::class, 'index'])->name('wallets.index');
    Route::get('/export/transactions/csv', [ExportReportController::class, 'exportCsv'])->name('export.transactions.csv');
    Route::get('/export/report/pdf', [ExportReportController::class, 'exportPdf'])->name('export.report.pdf');
    Route::get('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'index'])->name('settings.shopee-bonus');
    Route::post('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'update'])->name('settings.shopee-bonus.update');
    Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('audit-logs.index');
});

// Streaming File Audio Voice Memo Helm (Bypass Symlink / APP_URL issues)
Route::get('/operational/voice-memo/{voiceMemo}/stream', [\App\Http\Controllers\Api\VoiceMemoController::class, 'stream'])->name('operational.voice-memo.stream');

// Download Langsung APK Rilis Android
Route::get('/download/driver-finance-latest.apk', [\App\Http\Controllers\Api\AppUpdateController::class, 'downloadApk'])->name('app.download');
Route::get('/ci/log', [\App\Http\Controllers\Api\AppUpdateController::class, 'viewCiLog'])->name('ci.log');

// PWA Progressive Web App Endpoints
Route::get('/manifest.json', function () {
    return response(file_get_contents(public_path('manifest.json')), 200, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, public, max-age=3600',
    ]);
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Service-Worker-Allowed' => '/',
    ]);
})->name('pwa.sw');

