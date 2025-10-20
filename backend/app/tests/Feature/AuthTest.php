<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Registered successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    /** @test */
    public function user_cannot_register_with_invalid_data()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        // Get CSRF token first
        $this->get('/sanctum/csrf-cookie');

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logged in successfully'
                 ]);

        // Verify user is authenticated
        $this->assertAuthenticated('web');
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid credentials'
                 ]);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        // Get CSRF token first
        $this->get('/sanctum/csrf-cookie');
        
        // Authenticate user using web guard
        $this->actingAs($user, 'web');

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(204);
        
        // Verify user is no longer authenticated
        $this->assertGuest('web');
    }

    /** @test */
    public function authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();

        // Get CSRF token first
        $this->get('/sanctum/csrf-cookie');
        
        // Authenticate user using web guard
        $this->actingAs($user, 'web');

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'name',
                     'email',
                     'created_at',
                     'updated_at'
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_route()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }


}