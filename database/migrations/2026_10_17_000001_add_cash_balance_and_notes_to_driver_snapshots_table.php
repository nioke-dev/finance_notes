<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('driver_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_snapshots', 'cash_balance')) {
                $table->bigInteger('cash_balance')->default(0)->after('gopay_merchant_balance');
            }
            if (!Schema::hasColumn('driver_snapshots', 'notes')) {
                $table->text('notes')->nullable()->after('cash_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_snapshots', function (Blueprint $table) {
            $table->dropColumn(['cash_balance', 'notes']);
        });
    }
};
