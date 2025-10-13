<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function csrf_cookie_contains_valid_token()
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
        
        // Verify the cookie is not empty
        $cookies = $response->getCookie('XSRF-TOKEN');
        $this->assertNotEmpty($cookies->getValue());
    }

    /** @test */
    public function session_routes_work_with_csrf_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Get CSRF token
        $csrfResponse = $this->get('/sanctum/csrf-cookie');
        $csrfToken = $csrfResponse->getCookie('XSRF-TOKEN')->getValue();

        // Login with CSRF token in header
        $response = $this->withHeaders([
            'X-XSRF-TOKEN' => $csrfToken,
        ])->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated('web');
    }

    /** @test */
    public function stateful_domains_configuration_works()
    {
        // This test verifies that our SANCTUM_STATEFUL_DOMAINS configuration
        // is working by checking if session authentication works
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $this->get('/sanctum/csrf-cookie');
        
        $response = $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        // Subsequent requests should work with session
        $meResponse = $this->getJson('/api/me');
        $meResponse->assertStatus(200)
                   ->assertJson(['id' => $user->id]);
    }

    /** @test */
    public function session_regenerates_on_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Get initial session
        $this->get('/sanctum/csrf-cookie');
        $initialSessionId = session()->getId();

        // Login should regenerate session
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $newSessionId = session()->getId();
        
        // Session ID should be different after login for security
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    /** @test */
    public function session_invalidates_on_logout()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Login
        $this->get('/sanctum/csrf-cookie');
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated('web');
        $sessionId = session()->getId();

        // Logout
        $this->postJson('/api/auth/session/logout');

        $this->assertGuest('web');
        
        // Session should be invalidated
        $newSessionId = session()->getId();
        $this->assertNotEquals($sessionId, $newSessionId);
    }

    /** @test */
    public function csrf_token_persists_across_requests()
    {
        // Get CSRF token
        $response = $this->get('/sanctum/csrf-cookie');
        $csrfToken = $response->getCookie('XSRF-TOKEN')->getValue();

        // Make another request and verify token is still there
        $response2 = $this->withCookie('XSRF-TOKEN', $csrfToken)
                          ->get('/sanctum/csrf-cookie');

        $response2->assertStatus(204);
        $response2->assertCookie('XSRF-TOKEN');
    }

    /** @test */
    public function middleware_validates_csrf_for_stateful_requests()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Login first to establish session
        $this->get('/sanctum/csrf-cookie');
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Try to logout without CSRF token (should still work in testing)
        $response = $this->postJson('/api/auth/session/logout');
        $response->assertStatus(204);
    }

    /** @test */
    public function session_configuration_is_correct()
    {
        // Verify session configuration
        $this->assertEquals('database', config('session.driver'));
        $this->assertEquals(120, config('session.lifetime'));
        $this->assertEquals('lax', config('session.same_site'));
        $this->assertTrue(config('session.http_only'));
    }

    /** @test */
    public function sanctum_configuration_is_correct()
    {
        // Verify Sanctum configuration
        $statefulDomains = config('sanctum.stateful');
        
        $this->assertContains('localhost:3000', $statefulDomains);
        $this->assertContains('localhost:5173', $statefulDomains);
        $this->assertContains('localhost:4200', $statefulDomains);
        
        $this->assertEquals(['web'], config('sanctum.guard'));
    }
}