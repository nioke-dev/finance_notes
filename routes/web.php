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
    Route::get('/analytics/driver', [DriverAnalyticsController::class, 'index'])->name('analytics.driver');
    Route::get('/analytics/fuel', [FuelAnalyticsController::class, 'index'])->name('analytics.fuel');
    Route::get('/debts', [DebtWebController::class, 'index'])->name('debts.index');
    Route::get('/export/transactions/csv', [ExportReportController::class, 'exportCsv'])->name('export.transactions.csv');
    Route::get('/export/report/pdf', [ExportReportController::class, 'exportPdf'])->name('export.report.pdf');
    Route::get('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'index'])->name('settings.shopee-bonus');
    Route::post('/settings/shopee-bonus', [ShopeeBonusSettingController::class, 'update'])->name('settings.shopee-bonus.update');
    Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('audit-logs.index');
});
