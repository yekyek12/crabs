<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_logs_user_in_redirects_to_dashboard_and_flashes_success_message(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Registration successful. Welcome to your dashboard.');

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'email' => 'test@example.com',
            'role' => 'user',
            'account_status' => 'active',
        ]);
    }
}
