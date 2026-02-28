<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        $password = $overrides['password'] ?? 'password123';

        return array_merge([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => $password,
            'password_confirmation' => $password,
        ], $overrides);
    }

    public function test_user_can_register(): void
    {
        $data = $this->validData();

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at'],
                'token' => ['token', 'type'],
            ])
            ->assertJsonPath('user.name', $data['name'])
            ->assertJsonPath('user.email', $data['email'])
            ->assertJsonPath('token.type', 'bearer');

        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_register_creates_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData());

        $response->assertStatus(201);

        $this->assertNotEmpty($response->json('token.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_requires_name(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData(['name' => '']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_register_requires_email(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData(['email' => '']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_requires_valid_email(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData(['email' => 'not-an-email']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_requires_unique_email(): void
    {
        $existing = User::factory()->create();

        $response = $this->postJson('/api/auth/register', $this->validData(['email' => $existing->email]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_requires_password_min_8_chars(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_name_max_255(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validData([
            'name' => str_repeat('a', 256),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
