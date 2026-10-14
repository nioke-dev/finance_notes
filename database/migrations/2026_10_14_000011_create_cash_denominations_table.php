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
        Schema::create('cash_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_day_id')->unique()->constrained('work_days')->onDelete('cascade');
            
            // Koin
            $table->unsignedInteger('coin_100')->default(0);
            $table->unsignedInteger('coin_200')->default(0);
            $table->unsignedInteger('coin_500')->default(0);
            $table->unsignedInteger('coin_1000')->default(0);

            // Kertas
            $table->unsignedInteger('bill_1000')->default(0);
            $table->unsignedInteger('bill_2000')->default(0);
            $table->unsignedInteger('bill_5000')->default(0);
            $table->unsignedInteger('bill_10000')->default(0);
            $table->unsignedInteger('bill_20000')->default(0);
            $table->unsignedInteger('bill_50000')->default(0);
            $table->unsignedInteger('bill_100000')->default(0);

            // Total Akumulasi
            $table->bigInteger('total_physical_amount')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('work_day_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_denominations');
    }
};
