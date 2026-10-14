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
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('work_essential'); // pangan, hygiene, work_essential, maintenance, wants
            $table->unsignedTinyInteger('priority_level')->default(3); // 1: Pangan, 2: Mandi, 3: Nyawa Narik/Oli, 4: Servis, 5: Wants
            $table->unsignedInteger('target_amount');
            $table->unsignedInteger('allocated_amount')->default(0);
            $table->date('target_date')->nullable();
            $table->string('status')->default('active'); // active, achieved, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
