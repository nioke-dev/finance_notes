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
    Route::post('/operational/set-initial-balance', [\App\Http\Controllers\Web\OperationalWebController::class, 'setAccountInitialBalance'])->name('operational.set-initial-balance');
    Route::post('/operational/fuel', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeFuel'])->name('operational.fuel');
    Route::post('/operational/parking', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeParking'])->name('operational.parking');
    Route::post('/operational/cod', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeCod'])->name('operational.cod');
    Route::post('/operational/transfer', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeTransfer'])->name('operational.transfer');
    Route::post('/operational/personal-expense', [\App\Http\Controllers\Web\OperationalWebController::class, 'storePersonalExpense'])->name('operational.personal-expense');
    Route::post('/operational/personal-income', [\App\Http\Controllers\Web\OperationalWebController::class, 'storePersonalIncome'])->name('operational.personal-income');
    Route::post('/operational/field-incidental-expense', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeFieldIncidentalExpense'])->name('operational.field-incidental-expense');
    Route::post('/operational/debt', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeDebt'])->name('operational.debt');
    Route::post('/operational/spaylater-purchase', [\App\Http\Controllers\Web\OperationalWebController::class, 'storeSPayLaterPurchase'])->name('operational.spaylater-purchase');
    Route::post('/operational/pay-installment', [\App\Http\Controllers\Web\OperationalWebController::class, 'payInstallment'])->name('operational.pay-installment');
    Route::post('/operational/settle-friend-debt', [\App\Http\Controllers\Web\OperationalWebController::class, 'settleFriendDebt'])->name('operational.settle-friend-debt');
    Route::post('/operational/lend-friend-cash', [\App\Http\Controllers\Web\OperationalWebController::class, 'lendFriendCash'])->name('operational.lend-friend-cash');

    Route::get('/analytics/driver', [DriverAnalyticsController::class, 'index'])->name('analytics.driver');
    Route::get('/analytics/fuel', [FuelAnalyticsController::class, 'index'])->name('analytics.fuel');
    Route::get('/debts', [DebtWebController::class, 'index'])->name('debts.index');
    Route::get('/export/transactions/csv', [ExportReportController::class, 'exportCsv'])->name('export.transactions.csv');
    Route::get('/export/report/pdf', [ExportReportController::class, 'exportPdf'])->name('export.report.pdf');
    Route::get('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'index'])->name('settings.shopee-bonus');
    Route::post('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'update'])->name('settings.shopee-bonus.update');
    Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('audit-logs.index');
});
