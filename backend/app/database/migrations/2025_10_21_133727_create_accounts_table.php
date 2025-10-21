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
        // Evita conflito com a migration principal de contas.
        // Só cria a tabela caso ela ainda não exista (ex.: ambientes vazios).
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Apenas remove se a tabela foi criada por esta migration
        if (Schema::hasTable('accounts')) {
            // Não derruba a tabela se já existe estrutura completa criada pela outra migration
            // Por segurança, não executamos drop aqui.
        }
    }
};
