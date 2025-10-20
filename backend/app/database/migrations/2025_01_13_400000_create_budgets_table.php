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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('period_type', ['weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
            $table->boolean('is_active')->default(true);
            $table->decimal('spent_amount', 15, 2)->default(0); // calculado automaticamente
            $table->decimal('remaining_amount', 15, 2)->default(0); // calculado automaticamente
            $table->json('settings')->nullable(); // configurações adicionais
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};