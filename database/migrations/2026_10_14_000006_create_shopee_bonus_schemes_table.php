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
        Schema::create('shopee_bonus_schemes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tier_level'); // 1, 2, 3, 4
            $table->unsignedInteger('min_orders'); // Contoh: 16
            $table->unsignedInteger('max_orders')->nullable(); // Contoh: 20 (null untuk tak hingga)
            $table->bigInteger('rate_per_order'); // Contoh: 1000 (Rupiah utuh presisi Rp1)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('tier_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopee_bonus_schemes');
    }
};
