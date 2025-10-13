<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No Laravel 11, as rotas do Sanctum são registradas automaticamente
        // A rota /sanctum/csrf-cookie já está disponível por padrão
    }
}
