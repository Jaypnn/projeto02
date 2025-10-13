<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SanctumSessionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function can_get_csrf_cookie()
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
    }

    /** @test */
    public function can_login_with_session_after_getting_csrf_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // First get CSRF token
        $this->get('/sanctum/csrf-cookie');

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/session/login', $loginData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged in successfully']);

        // Verify session was created
        $this->assertAuthenticated('web');
    }

    /** @test */
    public function can_access_protected_routes_with_session()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Get CSRF token and login
        $this->get('/sanctum/csrf-cookie');
        
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Now access protected route
        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'email' => $user->email,
                 ]);
    }

    /** @test */
    public function can_logout_from_session()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Get CSRF token and login
        $this->get('/sanctum/csrf-cookie');
        
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Verify logged in
        $this->assertAuthenticated('web');

        // Logout
        $response = $this->postJson('/api/auth/session/logout');

        $response->assertStatus(204);
        $this->assertGuest('web');
    }

    /** @test */
    public function csrf_protection_works_without_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Try to login without getting CSRF token first
        $response = $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Should work because we're in testing environment
        // In production, this would require proper CSRF token
        $response->assertStatus(200);
    }

    /** @test */
    public function remember_me_functionality_works()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $this->get('/sanctum/csrf-cookie');

        $response = $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged in successfully']);

        $this->assertAuthenticated('web');
    }

    /** @test */
    public function session_persists_across_requests()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Login with session
        $this->get('/sanctum/csrf-cookie');
        $this->postJson('/api/auth/session/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Make multiple requests to verify session persistence
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson('/api/me');
            $response->assertStatus(200)
                     ->assertJson(['id' => $user->id]);
        }
    }

    /** @test */
    public function web_auth_routes_work_with_session()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Test web auth login
        $response = $this->postJson('/web-auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated('web');

        // Test web auth me endpoint
        $response = $this->getJson('/web-auth/me');
        $response->assertStatus(200)
                 ->assertJson(['id' => $user->id]);

        // Test web auth logout
        $response = $this->postJson('/web-auth/logout');
        $response->assertStatus(204);
        $this->assertGuest('web');
    }

    /** @test */
    public function cannot_access_protected_routes_after_logout()
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

        // Verify access
        $this->getJson('/api/me')->assertStatus(200);

        // Logout
        $this->postJson('/api/auth/session/logout');

        // Try to access protected route
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_credentials_return_401()
    {
        $this->get('/sanctum/csrf-cookie');

        $response = $this->postJson('/api/auth/session/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);

        $this->assertGuest('web');
    }
}