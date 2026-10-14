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
        Schema::create('active_cod_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_day_id')->constrained('work_days')->cascadeOnDelete();
            $table->string('order_code', 20); // 3-digit order code misal "633", "385"
            $table->string('merchant_name')->nullable();
            $table->unsignedInteger('bill_amount'); // Tagihan asli resto Shopee
            $table->unsignedInteger('rounded_bill_amount'); // Pembulatan lapangan misal 52.000
            $table->string('status')->default('on_delivery'); // on_delivery, delivered, cancelled
            
            // Audit Saat Order Diterima
            $table->timestamp('accepted_at');
            $table->decimal('accepted_latitude', 10, 7)->nullable();
            $table->decimal('accepted_longitude', 10, 7)->nullable();
            $table->foreignId('initial_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Audit Saat Penyelesaian di Lokasi Pelanggan
            $table->string('payment_method')->nullable(); // cash, qris
            $table->unsignedInteger('customer_gave_amount')->nullable(); // Nominal uang yang dikasih customer
            $table->unsignedInteger('change_returned')->nullable(); // Kembalian diserahkan ke customer
            $table->unsignedInteger('received_amount')->nullable(); // Uang bersih masuk kantong/rekening
            $table->integer('tip_amount')->default(0); // Surplus tip murni
            $table->unsignedInteger('parking_reimbursed')->default(0); // Uang parkir diganti customer jika ada
            $table->foreignId('settlement_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivered_latitude', 10, 7)->nullable();
            $table->decimal('delivered_longitude', 10, 7)->nullable();
            $table->foreignId('completion_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['work_day_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_cod_orders');
    }
};
