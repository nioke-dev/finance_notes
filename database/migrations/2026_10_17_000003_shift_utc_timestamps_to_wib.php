<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Geser seluruh timestamp transaksi & snapshot lama yang terekam pada era UTC (+7 jam / +420 menit ke WIB).
     */
    public function up(): void
    {
        // Cutoff: waktu ketika perbaikan timezone Asia/Jakarta diterapkan di server (2026-10-17 05:25:00 WIB)
        $cutoffTime = Carbon::parse('2026-10-17 05:25:00', 'Asia/Jakarta');

        // 1. Geser tabel transactions
        if (Schema::hasTable('transactions')) {
            $txs = DB::table('transactions')->get();
            foreach ($txs as $tx) {
                $meta = json_decode($tx->metadata ?? '{}', true) ?: [];
                if (!empty($meta['timezone_adjusted_wib'])) {
                    continue;
                }

                $txTime = Carbon::parse($tx->transaction_time);
                $createdAt = Carbon::parse($tx->created_at);

                if ($createdAt->lte($cutoffTime)) {
                    $newTxTime = $txTime->copy()->addHours(7);
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    $newUpdatedAt = $tx->updated_at ? Carbon::parse($tx->updated_at)->addHours(7) : $newCreatedAt;

                    $meta['timezone_adjusted_wib'] = true;
                    $meta['original_utc_time'] = $tx->transaction_time;

                    DB::table('transactions')->where('id', $tx->id)->update([
                        'transaction_time' => $newTxTime->toDateTimeString(),
                        'created_at' => $newCreatedAt->toDateTimeString(),
                        'updated_at' => $newUpdatedAt->toDateTimeString(),
                        'metadata' => json_encode($meta),
                    ]);
                }
            }
        }

        // 2. Geser tabel driver_snapshots
        if (Schema::hasTable('driver_snapshots')) {
            $snaps = DB::table('driver_snapshots')->get();
            foreach ($snaps as $snap) {
                $createdAt = Carbon::parse($snap->created_at);
                if ($createdAt->lte($cutoffTime)) {
                    $newRecTime = Carbon::parse($snap->recorded_at)->addHours(7);
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    $newUpdatedAt = $snap->updated_at ? Carbon::parse($snap->updated_at)->addHours(7) : $newCreatedAt;

                    DB::table('driver_snapshots')->where('id', $snap->id)->update([
                        'recorded_at' => $newRecTime->toDateTimeString(),
                        'created_at' => $newCreatedAt->toDateTimeString(),
                        'updated_at' => $newUpdatedAt->toDateTimeString(),
                    ]);
                }
            }
        }

        // 3. Geser tabel voice_memos
        if (Schema::hasTable('voice_memos')) {
            $memos = DB::table('voice_memos')->get();
            foreach ($memos as $memo) {
                $createdAt = Carbon::parse($memo->created_at);
                if ($createdAt->lte($cutoffTime)) {
                    $newRecTime = Carbon::parse($memo->recorded_at)->addHours(7);
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    $newUpdatedAt = $memo->updated_at ? Carbon::parse($memo->updated_at)->addHours(7) : $newCreatedAt;

                    DB::table('voice_memos')->where('id', $memo->id)->update([
                        'recorded_at' => $newRecTime->toDateTimeString(),
                        'created_at' => $newCreatedAt->toDateTimeString(),
                        'updated_at' => $newUpdatedAt->toDateTimeString(),
                    ]);
                }
            }
        }

        // 4. Geser tabel active_cod_orders
        if (Schema::hasTable('active_cod_orders')) {
            $orders = DB::table('active_cod_orders')->get();
            foreach ($orders as $order) {
                $createdAt = Carbon::parse($order->created_at);
                if ($createdAt->lte($cutoffTime)) {
                    $newAccepted = Carbon::parse($order->accepted_at)->addHours(7);
                    $newCompleted = $order->completed_at ? Carbon::parse($order->completed_at)->addHours(7) : null;
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    $newUpdatedAt = $order->updated_at ? Carbon::parse($order->updated_at)->addHours(7) : $newCreatedAt;

                    DB::table('active_cod_orders')->where('id', $order->id)->update([
                        'accepted_at' => $newAccepted->toDateTimeString(),
                        'completed_at' => $newCompleted ? $newCompleted->toDateTimeString() : null,
                        'created_at' => $newCreatedAt->toDateTimeString(),
                        'updated_at' => $newUpdatedAt->toDateTimeString(),
                    ]);
                }
            }
        }

        // 5. Geser tabel audit_logs
        if (Schema::hasTable('audit_logs')) {
            $logs = DB::table('audit_logs')->get();
            foreach ($logs as $log) {
                $createdAt = Carbon::parse($log->created_at);
                if ($createdAt->lte($cutoffTime)) {
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    DB::table('audit_logs')->where('id', $log->id)->update([
                        'created_at' => $newCreatedAt->toDateTimeString(),
                    ]);
                }
            }
        }

        // 6. Geser tabel work_days
        if (Schema::hasTable('work_days')) {
            $days = DB::table('work_days')->get();
            foreach ($days as $day) {
                $createdAt = Carbon::parse($day->created_at);
                if ($createdAt->lte($cutoffTime)) {
                    $newOpenedAt = $day->opened_at ? Carbon::parse($day->opened_at)->addHours(7) : null;
                    $newClosedAt = $day->closed_at ? Carbon::parse($day->closed_at)->addHours(7) : null;
                    $newCreatedAt = $createdAt->copy()->addHours(7);
                    $newUpdatedAt = $day->updated_at ? Carbon::parse($day->updated_at)->addHours(7) : $newCreatedAt;

                    $updateArr = [
                        'created_at' => $newCreatedAt->toDateTimeString(),
                        'updated_at' => $newUpdatedAt->toDateTimeString(),
                    ];

                    if ($newOpenedAt) {
                        $updateArr['opened_at'] = $newOpenedAt->toDateTimeString();
                        $updateArr['work_date'] = $newOpenedAt->toDateString();
                    }

                    if ($newClosedAt) {
                        $updateArr['closed_at'] = $newClosedAt->toDateTimeString();
                    }

                    DB::table('work_days')->where('id', $day->id)->update($updateArr);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback to prevent accidental double-shifting
    }
};
