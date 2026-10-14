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
        Schema::create('work_days', function (Blueprint $table) {
            $table->id();
            $table->date('work_date')->unique();
            $table->string('status', 30)->default('needs_reconciliation');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('online_hours', 4, 2)->nullable();
            $table->boolean('is_performance_eligible')->default(false);
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('work_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_days');
    }
};
