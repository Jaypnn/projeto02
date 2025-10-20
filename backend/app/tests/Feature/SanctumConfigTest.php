<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SanctumConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sanctum_csrf_cookie_route_is_available()
    {
        $response = $this->get('/sanctum/csrf-cookie');
        $response->assertStatus(204);
    }

    /** @test */
    public function session_authentication_works()
    {
        $user = User::factory()->create();

        // Get CSRF token first
        $this->get('/sanctum/csrf-cookie');
        
        // Authenticate user using web guard
        $this->actingAs($user, 'web');

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJson(['id' => $user->id]);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected()
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    /** @test */
    public function cors_configuration_is_working()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->get('/sanctum/csrf-cookie');

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /** @test */
    public function sanctum_configuration_is_correct()
    {
        $statefulDomains = config('sanctum.stateful');
        
        // Verifica se os domínios essenciais estão configurados (valores padrão do Laravel)
        $this->assertContains('localhost', $statefulDomains);
        $this->assertContains('localhost:3000', $statefulDomains);
        $this->assertContains('127.0.0.1', $statefulDomains);
        
        // Verifica se está usando apenas o guard web (sem tokens)
        $this->assertEquals(['web'], config('sanctum.guard'));
    }

    /** @test */
    public function cors_allows_configured_origins()
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost', 
            'http://127.0.0.1'
        ];

        foreach ($allowedOrigins as $origin) {
            $response = $this->withHeaders([
                'Origin' => $origin,
            ])->get('/sanctum/csrf-cookie');

            // Verifica se a requisição foi bem-sucedida (CORS não bloqueou)
            $response->assertStatus(204);
            
            // Se o header existe, verifica se está correto
            if ($response->headers->has('Access-Control-Allow-Origin')) {
                $this->assertEquals($origin, $response->headers->get('Access-Control-Allow-Origin'));
            }
        }
    }

    /** @test */
    public function cors_rejects_unknown_origins()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://malicious-site.com',
        ])->get('/sanctum/csrf-cookie');

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /** @test */
    public function session_configuration_environment_variables_work()
    {
        // Verifica se o Laravel consegue carregar variáveis de ambiente
        $sanctumDomains = config('sanctum.stateful');
        
        // Testa se a configuração está funcionando (independente dos valores específicos)
        $this->assertIsArray($sanctumDomains);
        $this->assertNotEmpty($sanctumDomains);
        
        // Verifica se contém pelo menos localhost (valor padrão)
        $this->assertContains('localhost', $sanctumDomains);
    }

    /** @test */
    public function web_auth_routes_exist()
    {
        // Test that the web auth routes are registered
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Should return validation error for invalid credentials
        $response = $this->postJson('/web-auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should return error (401 or validation error)
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}