<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('employer can register and reach the dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'Alicia Employer',
        'email' => 'employer@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'employer',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'employer@example.com',
        'role' => 'employer',
    ]);

    $this->get('/dashboard')->assertStatus(200)->assertSee('Employer Dashboard');
});

test('candidate can login and reach the dashboard', function () {
    $user = User::factory()->create([
        'name' => 'Casey Candidate',
        'email' => 'candidate@example.com',
        'password' => Hash::make('password123'),
        'role' => 'candidate',
    ]);

    $response = $this->post('/login', [
        'email' => 'candidate@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    $this->get('/dashboard')->assertStatus(200)->assertSee('Candidate Dashboard');
});
