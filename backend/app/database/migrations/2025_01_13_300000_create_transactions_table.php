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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            
            // Para transferências
            $table->foreignId('transfer_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('transfer_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            // Campos adicionais
            $table->string('reference')->nullable(); // referência externa
            $table->json('tags')->nullable(); // tags para organização
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_type')->nullable(); // daily, weekly, monthly, yearly
            $table->integer('recurring_interval')->nullable(); // intervalo da recorrência
            $table->date('recurring_until')->nullable(); // até quando repetir
            
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'type']);
            $table->index(['category_id', 'type']);
            $table->index(['status', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};