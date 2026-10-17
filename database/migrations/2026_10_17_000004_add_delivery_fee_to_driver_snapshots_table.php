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
            if (!Schema::hasColumn('driver_snapshots', 'delivery_fee')) {
                $table->bigInteger('delivery_fee')->default(0)->after('completed_orders');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('driver_snapshots', 'delivery_fee')) {
                $table->dropColumn('delivery_fee');
            }
        });
    }
};
