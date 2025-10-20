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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', ['checking', 'savings', 'credit_card', 'investment', 'cash', 'other']);
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->string('color', 7)->default('#000000');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_total')->default(true); // incluir no saldo total
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};