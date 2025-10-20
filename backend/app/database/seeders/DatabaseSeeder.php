<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seed do sistema financeiro...');

        // Usar apenas o seeder simples que funciona
        $this->call([
            SimpleFinancialSeeder::class,
        ]);

        // Criar usuário de teste adicional se não existir
        if (!User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            $this->command->info('Usuário de teste adicional criado: test@example.com');
        }

        $this->command->info('✅ Seed concluído com sucesso!');
    }
}
