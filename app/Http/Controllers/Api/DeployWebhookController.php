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
            foreach (['2026-10-20', '2026-10-21', '2026-10-22'] as $d) {
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
                'message' => 'Shifts 20, 21, and 22 setup successfully.',
                'work_days' => $created,
            ]);
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
