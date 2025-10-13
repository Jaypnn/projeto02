<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cors_headers_are_present_for_api_routes()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type, Authorization',
        ])->options('/api/auth/register');

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    /** @test */
    public function cors_headers_are_present_for_sanctum_csrf_cookie()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->get('/sanctum/csrf-cookie');

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /** @test */
    public function cors_allows_credentials_for_authenticated_requests()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Login first
        $this->get('/sanctum/csrf-cookie');
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Make authenticated request with Origin header
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /** @test */
    public function cors_rejects_unknown_origins()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://malicious-site.com',
        ])->get('/sanctum/csrf-cookie');

        // Should not have Access-Control-Allow-Origin header for unknown origins
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /** @test */
    public function cors_allows_multiple_configured_origins()
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:4200',
            'http://127.0.0.1:3000',
        ];

        foreach ($allowedOrigins as $origin) {
            $response = $this->withHeaders([
                'Origin' => $origin,
            ])->get('/sanctum/csrf-cookie');

            $response->assertHeader('Access-Control-Allow-Origin', $origin);
            $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    /** @test */
    public function preflight_requests_work_correctly()
    {
        $response = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type, X-Requested-With',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}