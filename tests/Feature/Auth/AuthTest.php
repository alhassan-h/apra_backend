<?php

use App\Models\User;

describe('Registration', function () {
    it('registers a new pharmacist', function () {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'Jane Pharmacist',
            'email'                 => 'jane@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'access_token', 'token_type']);

        expect($response->json('user.role'))->toBe('PHARMACIST');
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'PHARMACIST']);
    });

    it('registers an admin when role is provided', function () {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'Super Admin',
            'email'                 => 'admin@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'ADMIN',
        ]);

        $response->assertCreated();
        expect($response->json('user.role'))->toBe('ADMIN');
    });

    it('rejects duplicate emails', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'full_name'             => 'Another User',
            'email'                 => 'taken@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects weak passwords', function () {
        $this->postJson('/api/auth/register', [
            'full_name'             => 'Weak',
            'email'                 => 'weak@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('Login', function () {
    it('logs in with correct credentials', function () {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'access_token', 'token_type']);
    });

    it('rejects wrong credentials', function () {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('Me & Logout', function () {
    it('returns the authenticated user', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    });

    it('logs out and invalidates the token', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        expect($user->fresh()->tokens()->count())->toBe(0);
    });

    it('returns 401 for unauthenticated requests', function () {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    });
});
