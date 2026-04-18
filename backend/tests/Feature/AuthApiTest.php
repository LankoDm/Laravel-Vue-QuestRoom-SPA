<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for user authentication and registration API endpoints.
 */
class AuthApiTest extends TestCase
{
    // Resets the in-memory database state after each test method is executed
    use RefreshDatabase;

    /**
     * Test that a new user can successfully register an account.
     */
    public function test_user_can_register_successfully(): void
    {
        // Define the registration payload
        $payload = [
            'name' => 'Іван Франко',
            'email' => 'ivan@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234'
        ];

        // Send a POST request to the register endpoint
        $response = $this->postJson('/api/register', $payload);

        // Verify the response status and structure returns expected user data and token
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token'
            ]);

        // Verify the user was created in the database with the default 'user' role
        $this->assertDatabaseHas('users', [
            'email' => 'ivan@example.com',
            'role' => 'user'
        ]);
    }

    /**
     * Test that an existing user can log in with correct credentials.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        // Create a user in the database
        $user = User::factory()->create([
            'email' => 'testlogin@example.com',
            'password' => Hash::make('password123')
        ]);

        // Send a POST request to the login endpoint
        $response = $this->postJson('/api/login', [
            'email' => 'testlogin@example.com',
            'password' => 'password123'
        ]);

        // Verify the response indicates successful login and contains a token
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token'
            ]);
    }

    /**
     * Test that login fails when providing an incorrect password.
     */
    public function test_login_fails_with_wrong_password(): void
    {
        // Create a user in the database
        $user = User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('password123')
        ]);

        // Send a POST request with the wrong password
        $response = $this->postJson('/api/login', [
            'email' => 'wrongpass@example.com',
            'password' => 'wrong123'
        ]);

        // Verify the request is rejected with a correct status
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Невірний email або пароль'
            ]);
    }
}
