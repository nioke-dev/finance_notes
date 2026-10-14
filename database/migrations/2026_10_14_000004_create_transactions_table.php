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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_day_id')->constrained('work_days')->onDelete('restrict');
            $table->string('type', 30); // income, expense, transfer, adjustment, reversal
            $table->string('description', 255); // Keterangan transaksi
            $table->timestamp('transaction_time'); // Waktu kejadian aktual
            $table->boolean('is_estimated_time')->default(false); // True jika placeholder 23:59
            $table->json('metadata')->nullable(); // GPS, resto, volume liter, dll
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('transaction_time');
            $table->index(['work_day_id', 'transaction_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
