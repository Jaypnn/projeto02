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
    public function token_authentication_works()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

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
        
        $this->assertContains('localhost:3000', $statefulDomains);
        $this->assertContains('localhost:5173', $statefulDomains);
        $this->assertContains('localhost:4200', $statefulDomains);
        
        $this->assertEquals(['web'], config('sanctum.guard'));
    }

    /** @test */
    public function cors_allows_configured_origins()
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173', 
            'http://localhost:4200'
        ];

        foreach ($allowedOrigins as $origin) {
            $response = $this->withHeaders([
                'Origin' => $origin,
            ])->get('/sanctum/csrf-cookie');

            $response->assertHeader('Access-Control-Allow-Origin', $origin);
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
        // Verify environment variables are loaded
        $this->assertEquals(
            'localhost:3000,localhost:5173,localhost:4200,127.0.0.1:3000,127.0.0.1:5173,127.0.0.1:4200',
            env('SANCTUM_STATEFUL_DOMAINS')
        );
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