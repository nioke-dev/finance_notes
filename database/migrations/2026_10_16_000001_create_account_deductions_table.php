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
        Schema::create('account_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name', 100);
            $table->bigInteger('amount');
            $table->unsignedTinyInteger('deduction_day')->default(1);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_deducted_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'is_active']);
            $table->index('deduction_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_deductions');
    }
};
