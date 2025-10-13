<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Força configuração de sessão para Sanctum durante testes
        config([
            'session.driver' => 'file',
            'session.same_site' => 'lax',
            'session.encrypt' => false,
            'session.http_only' => true,
            'sanctum.stateful' => [
                'localhost',
                'localhost:3000',
                '127.0.0.1',
                '127.0.0.1:8000'
            ]
        ]);
    }
}
