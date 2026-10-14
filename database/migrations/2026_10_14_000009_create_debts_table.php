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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120); // Contoh: "SPayLater - Beli HP Rofik", "SeaBank Pinjam 3x"
            $table->string('platform', 30); // spaylater, seabank_pinjam, gopay_pinjam, person, other
            $table->string('borrower_name', 100)->nullable(); // Jika talangan teman, contoh: "Rofik"
            $table->bigInteger('principal_amount'); // Pokok pinjaman (Rupiah utuh)
            $table->bigInteger('fee_amount')->default(0); // Bunga / biaya pinjaman
            $table->bigInteger('total_obligation'); // Pokok + Biaya
            $table->unsignedInteger('tenor_months')->default(1); // Jumlah bulan cicilan
            $table->string('status', 20)->default('active'); // active, paid_off
            $table->date('started_at'); // Tanggal mulai transaksi pinjaman
            $table->timestamps();

            // Indexes
            $table->index('platform');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
