<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('cashier');
            $table->string('status')->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_owner_can_sign_in_and_is_redirected_to_the_dashboard(): void
    {
        $user = $this->createUser('owner');

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_and_use_a_generic_error(): void
    {
        $user = $this->createUser('owner');

        $this->from(route('login'))
            ->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $user = $this->createUser('owner', 'inactive');

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cashiers_cannot_open_owner_only_pages(): void
    {
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)->get(route('dashboard'))->assertForbidden();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.post'), [
                'email' => 'unknown@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login.post'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_public_auth_diagnostic_endpoint_is_removed(): void
    {
        $this->get('/test-auth')->assertNotFound();
    }

    public function test_login_page_does_not_expose_demo_credentials(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertDontSee('owner@FreshTrack.ph')
            ->assertDontSee('manager@FreshTrack.ph')
            ->assertDontSee('cashier@FreshTrack.ph')
            ->assertDontSee('Demo credentials')
            ->assertDontSee('All accounts use password')
            ->assertDontSee('value="password"', false);
    }

    private function createUser(string $role, string $status = 'active'): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'@example.test',
            'password' => Hash::make('correct-password'),
            'role' => $role,
            'status' => $status,
        ]);
    }
}
