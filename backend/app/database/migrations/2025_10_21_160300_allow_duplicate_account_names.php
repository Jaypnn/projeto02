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
        Schema::table('accounts', function (Blueprint $table) {
            // Remover a restrição de nome único por usuário para permitir múltiplas contas com o mesmo apelido
            try {
                $table->dropUnique('accounts_user_id_name_unique');
            } catch (\Throwable $e) {
                // Ignorar se o índice tiver outro nome ou já tiver sido removido
            }
            // Opcional: manter um índice simples para buscas por usuário+nome
            try {
                $table->index(['user_id', 'name']);
            } catch (\Throwable $e) {
                // índice já existente
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remover o índice simples
            try {
                $table->dropIndex(['user_id', 'name']);
            } catch (\Throwable $e) {
                // ignorar
            }
            // Recriar a restrição única (como estava originalmente)
            try {
                $table->unique(['user_id', 'name']);
            } catch (\Throwable $e) {
                // ignorar
            }
        });
    }
};
