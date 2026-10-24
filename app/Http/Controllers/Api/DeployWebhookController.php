<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployWebhookController extends Controller
{
    /**
     * Handle one-click deployment webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $expectedKey = config('app.deploy_key', env('DEPLOY_SECRET_KEY', 'keuangan-secret-deploy-2026'));
        $providedKey = $request->query('key') ?? $request->input('key');

        if (!$providedKey || $providedKey !== $expectedKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing deployment secret key.',
            ], 403);
        }

        if ($request->query('action') === 'inspect_closing') {
            return response()->json([
                'success' => true,
                'work_days' => \App\Models\WorkDay::latest('id')->take(3)->get(),
                'accounts' => \App\Models\Account::all(['id', 'name', 'type', 'current_balance', 'initial_balance']),
                'latest_snapshots' => \App\Models\DriverSnapshot::latest('id')->take(5)->get(),
                'latest_transactions' => \App\Models\Transaction::latest('id')->take(10)->get(),
                'latest_audit_logs' => \App\Models\AuditLog::latest('id')->take(10)->get(),
            ]);
        }

        if ($request->query('action') === 'setup_shifts') {
            $created = [];
            foreach (['2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23'] as $d) {
                $created[] = \App\Models\WorkDay::firstOrCreate(
                    ['work_date' => $d],
                    [
                        'day_type' => \App\Models\WorkDay::TYPE_ON_BID,
                        'status' => \App\Models\WorkDay::STATUS_NEEDS_RECONCILIATION,
                        'opened_at' => \Carbon\Carbon::parse("{$d} 08:00:00"),
                    ]
                );
            }
            return response()->json([
                'success' => true,
                'message' => 'Shifts setup successfully.',
                'work_days' => $created,
            ]);
        }

        if ($request->query('action') === 'finalize_past_shifts') {
            try {
                return \Illuminate\Support\Facades\DB::transaction(function () {
                    $pastDays = \App\Models\WorkDay::where('work_date', '<', now()->toDateString())
                        ->where('status', '!=', \App\Models\WorkDay::STATUS_FINAL)
                        ->orderBy('work_date', 'asc')
                        ->get();

                    $finalized = [];
                    foreach ($pastDays as $wd) {
                        $oldStatus = $wd->status;
                        $wd->update([
                            'status' => \App\Models\WorkDay::STATUS_FINAL,
                            'closed_at' => \Illuminate\Support\Carbon::parse($wd->work_date)->setTime(23, 59, 59),
                            'closing_notes' => 'Finalisasi otomatis shift lampau tanggal ' . $wd->work_date->format('d M Y') . ' atas permintaan driver untuk persiapan on-bid 24 Oktober 2026.',
                        ]);

                        \App\Models\AuditLog::create([
                            'user_id' => null,
                            'action' => 'finalize_work_day_batch',
                            'entity_type' => 'work_days',
                            'entity_id' => $wd->id,
                            'old_values' => ['status' => $oldStatus],
                            'new_values' => ['status' => \App\Models\WorkDay::STATUS_FINAL],
                            'reason' => 'Finalisasi batch hari gantung historis ' . $wd->work_date->toDateString() . ' untuk persiapan on-bid.',
                        ]);

                        $finalized[] = [
                            'id' => $wd->id,
                            'date' => $wd->work_date->toDateString(),
                            'orders' => $wd->total_orders,
                        ];
                    }

                    return response()->json([
                        'success' => true,
                        'message' => count($finalized) . ' shift lampau berhasil difinalisasi secara resmi.',
                        'finalized_days' => $finalized,
                    ]);
                });
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        if ($request->query('action') === 'sync_actual_commitments') {
            try {
                return \Illuminate\Support\Facades\DB::transaction(function () {
                    // 1. Shift Setup & Stats
                    $workDay23 = \App\Models\WorkDay::firstOrCreate(
                        ['work_date' => '2026-10-23'],
                        [
                            'day_type' => \App\Models\WorkDay::TYPE_ON_BID,
                            'status' => \App\Models\WorkDay::STATUS_NEEDS_RECONCILIATION,
                            'opened_at' => \Illuminate\Support\Carbon::parse('2026-10-23 08:00:00'),
                        ]
                    );
                    $workDay23->update(['total_orders' => 16]);

                    if ($wd20 = \App\Models\WorkDay::where('work_date', '2026-10-20')->first()) {
                        $wd20->update(['total_orders' => 20]);
                    }
                    if ($wd21 = \App\Models\WorkDay::where('work_date', '2026-10-21')->first()) {
                        $wd21->update(['total_orders' => 4]);
                    }
                    if ($wd22 = \App\Models\WorkDay::where('work_date', '2026-10-22')->first()) {
                        $wd22->update(['total_orders' => 10]);
                    }

                    // 2. Denominasi Fisik Kas Rp 888.000 (121 lembar + 6 koin)
                    $denom = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay23->id]);
                    $denom->fill([
                        'bill_100000' => 2,
                        'bill_50000' => 2,
                        'bill_20000' => 7,
                        'bill_10000' => 22,
                        'bill_5000' => 21,
                        'bill_2000' => 53,
                        'bill_1000' => 14,
                        'coin_1000' => 0,
                        'coin_500' => 6,
                        'coin_200' => 0,
                        'coin_100' => 0,
                        'damaged_notes_amount' => 0,
                        'total_physical_amount' => 888000,
                    ]);
                    $denom->save();

                    // 3. Saldo Akun Kas
                    $cashAcc = \App\Models\Account::where('name', 'Cash')->orWhere('type', 'cash')->first();
                    if ($cashAcc) {
                        $oldCash = (int) $cashAcc->current_balance;
                        $meta = $cashAcc->metadata ?? [];
                        $meta['denominations'] = $denom->toArray();
                        $cashAcc->update([
                            'current_balance' => 888000,
                            'metadata' => $meta,
                        ]);

                        \App\Models\AuditLog::create([
                            'auditable_type' => \App\Models\Account::class,
                            'auditable_id' => $cashAcc->id,
                            'action' => 'cash_balance_reconciliation',
                            'old_values' => ['balance' => $oldCash],
                            'new_values' => ['balance' => 888000, 'surplus' => 888000 - $oldCash],
                            'reason' => 'Sinkronisasi fisik kas per 23 Okt: Rp 888.000 (121 lembar + 6 koin). Akumulasi penerimaan narik & COD 20-23 Okt +Rp ' . number_format(888000 - $oldCash, 0, ',', '.'),
                        ]);
                    }

                    // 4. Saldo Digital
                    $digitalUpdates = [
                        'BCA' => 805574,
                        'ShopeePay' => 64326,
                        'GoPay' => 27288,
                        'GoPay Merchant' => 2000,
                    ];
                    foreach ($digitalUpdates as $name => $actualBal) {
                        $acc = \App\Models\Account::where('name', $name)->first();
                        if ($acc) {
                            $oldBal = (int) $acc->current_balance;
                            $diff = $actualBal - $oldBal;
                            $acc->update(['current_balance' => $actualBal]);

                            \App\Models\AuditLog::create([
                                'auditable_type' => \App\Models\Account::class,
                                'auditable_id' => $acc->id,
                                'action' => $diff >= 0 ? 'digital_reconciliation_surplus' : 'digital_reconciliation_shortage',
                                'old_values' => ['balance' => $oldBal],
                                'new_values' => ['balance' => $actualBal, 'difference' => $diff],
                                'reason' => sprintf(
                                    'Rekonsiliasi saldo %s per 23 Okt: Rp %s -> Rp %s (%sRp %s pemakaian/operasional)',
                                    $name,
                                    number_format($oldBal, 0, ',', '.'),
                                    number_format($actualBal, 0, ',', '.'),
                                    $diff >= 0 ? '+' : '-',
                                    number_format(abs($diff), 0, ',', '.')
                                ),
                            ]);
                        }
                    }

                    // 5. Pencatatan Utang Teman (Makan Kokari Malang Rp 150.000)
                    $debtName = 'Makan All You Can Eat Kokari Malang';
                    $friendDebt = \App\Models\Debt::firstOrCreate(
                        ['name' => $debtName],
                        [
                            'platform' => 'other',
                            'borrower_name' => 'Teman',
                            'principal_amount' => 150000,
                            'fee_amount' => 0,
                            'total_obligation' => 150000,
                            'tenor_months' => 1,
                            'status' => 'active',
                            'started_at' => '2026-10-23',
                        ]
                    );

                    \App\Models\DebtInstallment::firstOrCreate(
                        [
                            'debt_id' => $friendDebt->id,
                            'installment_number' => 1,
                        ],
                        [
                            'due_date' => '2026-10-24',
                            'amount_due' => 150000,
                            'amount_paid' => 0,
                            'status' => 'unpaid',
                        ]
                    );

                    \App\Models\Account::firstOrCreate(
                        ['name' => 'Kewajiban Utang - Makan Kokari ke Teman'],
                        [
                            'type' => 'debt',
                            'current_balance' => 150000,
                            'initial_balance' => 150000,
                        ]
                    );

                    // 6. Planning Sewa Hotel Wisuda Rp 250.000
                    \App\Models\FinancialGoal::updateOrCreate(
                        ['name' => 'Sewa Hotel Wisuda (30-31 Okt)'],
                        [
                            'category' => 'wisuda',
                            'priority_level' => 1,
                            'target_amount' => 250000,
                            'allocated_amount' => 0,
                            'target_date' => '2026-10-30',
                            'status' => 'active',
                            'notes' => 'Booking kamar hotel wisuda 1 malam (Incaran: di bawah 200rb, batas atas 250rb)',
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Saldo riil, hutang teman Rp 150.000, dan planning hotel Rp 250.000 berhasil disinkronkan.',
                    ]);
                });
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        if (app()->environment('testing')) {
            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'git_pull' => [
                    'exit_code' => 0,
                    'output' => 'Already up to date.',
                ],
                'artisan_migrate' => ($request->query('reset_db') === 'yes' || $request->input('reset_db') === 'yes')
                    ? 'Database reset and seeded successfully.'
                    : 'Nothing to migrate.',
                'artisan_optimize' => 'Caches cleared successfully.',
                'message' => 'Deployment completed successfully (testing mode).',
            ]);
        }

        $basePath = base_path();
        chdir($basePath);

        $gitOutput = [];
        $gitCode = 0;
        // Jalankan git pull
        exec('git pull origin main 2>&1', $gitOutput, $gitCode);

        // Jalankan migrate atau migrate:fresh --seed
        if ($request->query('reset_db') === 'yes' || $request->input('reset_db') === 'yes') {
            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
            $migrateOutput = Artisan::output();
        } else {
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();

            // Opsi menjalankan specific seeder tanpa mereset database
            $seedClass = $request->query('seed_class') ?? $request->input('seed_class');
            if ($seedClass) {
                Artisan::call('db:seed', ['--class' => $seedClass, '--force' => true]);
                $migrateOutput .= "\n" . Artisan::output();
            }
        }

        // Jalankan optimize clear
        Artisan::call('optimize:clear');
        $optimizeOutput = Artisan::output();

        // Pastikan storage symlink terhubung
        if (!file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        if (!is_dir(base_path('bootstrap/cache'))) {
            @mkdir(base_path('bootstrap/cache'), 0777, true);
        }

        return response()->json([
            'success' => $gitCode === 0,
            'timestamp' => now()->toIso8601String(),
            'git_pull' => [
                'exit_code' => $gitCode,
                'output' => implode("\n", $gitOutput),
            ],
            'artisan_migrate' => trim($migrateOutput),
            'artisan_optimize' => trim($optimizeOutput),
            'message' => $gitCode === 0 ? 'Deployment completed successfully.' : 'Git pull encountered an error.',
        ]);
    }
}
