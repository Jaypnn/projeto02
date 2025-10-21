<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'bank_id')) {
                $table->string('bank_id', 50)->nullable()->after('description');
            }
            // Harmonizar tamanho da coluna color com validação (até 20)
            if (Schema::hasColumn('accounts', 'color')) {
                // Algumas instalações podem ter tamanho 7; manter compatível se já criado
                try {
                    $table->string('color', 20)->change();
                } catch (\Throwable $e) {
                    // Ignora se o driver não suportar change() sem doctrine/dbal
                }
            }
        });

        // Atualiza ENUM de 'type' para incluir 'wallet' quando o banco suportar
        try {
            DB::statement("ALTER TABLE accounts MODIFY COLUMN type ENUM('checking','savings','credit_card','investment','cash','wallet','other') NOT NULL");
        } catch (\Throwable $e) {
            // Ignora em drivers que não usam ENUM ou quando já está atualizado
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'bank_id')) {
                $table->dropColumn('bank_id');
            }
            // Não desfaz alteração de tamanho da coluna 'color' por compatibilidade
        });
    }
};
