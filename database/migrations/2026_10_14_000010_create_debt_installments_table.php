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
        Schema::create('debt_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->onDelete('cascade');
            $table->unsignedInteger('installment_number'); // Cicilan ke-1, 2, dst
            $table->date('due_date'); // Tanggal jatuh tempo
            $table->bigInteger('amount_due'); // Tagihan periode ini (Rupiah)
            $table->bigInteger('amount_paid')->default(0); // Riil dibayar
            $table->string('status', 20)->default('unpaid'); // unpaid, paid
            $table->timestamp('paid_at')->nullable(); // Waktu lunas
            $table->timestamps();

            // Indexes
            $table->index(['debt_id', 'installment_number']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_installments');
    }
};
