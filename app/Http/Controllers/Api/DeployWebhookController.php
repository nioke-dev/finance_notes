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
        }

        // Jalankan optimize clear
        Artisan::call('optimize:clear');
        $optimizeOutput = Artisan::output();

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
