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
        Schema::table('cash_denominations', function (Blueprint $table) {
            $table->unsignedInteger('damaged_notes_amount')->default(0)->after('total_physical_amount');
            $table->string('damaged_notes_note')->nullable()->after('damaged_notes_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_denominations', function (Blueprint $table) {
            $table->dropColumn(['damaged_notes_amount', 'damaged_notes_note']);
        });
    }
};
