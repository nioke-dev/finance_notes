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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120); // Contoh: "Paket Data Narik 50GB", "Kamar Kos Bulanan", "WiFi Kos"
            $table->string('category', 40)->default('work_essential'); // work_essential, living_expense, entertainment, other
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, weekly, yearly
            $table->bigInteger('amount'); // Nominal Rupiah utuh
            $table->unsignedTinyInteger('due_day_of_month')->default(1); // Tanggal 1 s/d 31
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'due_day_of_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
