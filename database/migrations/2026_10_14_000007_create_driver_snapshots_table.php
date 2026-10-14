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
        Schema::create('driver_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_day_id')->constrained('work_days')->onDelete('cascade');
            $table->timestamp('recorded_at'); // Waktu snapshot disimpan
            $table->unsignedInteger('completed_orders')->default(0); // Jumlah pesanan selesai
            $table->bigInteger('hub_income')->default(0); // Pendapatan Hub (Rupiah)
            $table->bigInteger('hub_bonus')->default(0); // Estimasi / Nilai Bonus Hub (Rupiah)
            $table->bigInteger('customer_tips')->default(0); // Tip pelanggan di Shopee (Rupiah)
            $table->bigInteger('shopeepay_balance')->default(0); // Saldo rekening ShopeePay saat itu
            $table->bigInteger('gopay_balance')->default(0); // Saldo rekening GoPay saat itu
            $table->bigInteger('gopay_merchant_balance')->default(0); // Saldo QRIS GoPay Merchant saat itu
            $table->timestamps();

            // Indexes
            $table->index(['work_day_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_snapshots');
    }
};
