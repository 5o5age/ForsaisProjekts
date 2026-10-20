<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_supervisor_and_is_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Anna Kalniņa', 'email' => 'anna@example.test', 'phone' => '',
            'role' => 'supervisor', 'stable_mode' => 'create', 'stable_name' => 'Anna stallis',
            'password' => 'StableCare12345', 'password_confirmation' => 'StableCare12345', 'terms' => '1',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'anna@example.test', 'role' => 'admin']);
        $this->assertNotNull(User::where('email', 'anna@example.test')->firstOrFail()->stable_id);
        $this->assertDatabaseCount('stables', 1);
    }

    public function test_existing_stable_invite_cannot_be_used_to_self_assign_administrator(): void
    {
        $stable = Stable::create(['name' => 'Esošais stallis', 'invite_code' => 'VALID-INVITE']);
        $response = $this->from('/register')->post('/register', [
            'name' => 'Admin User', 'email' => 'admin@example.test', 'role' => 'admin', 'stable_mode' => 'join',
            'stable_invite_code' => $stable->invite_code,
            'password' => 'StableCare12345', 'password_confirmation' => 'StableCare12345', 'terms' => '1',
        ]);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_user_can_join_existing_stable_with_a_non_admin_role(): void
    {
        $stable = Stable::create(['name' => 'Esošais stallis', 'invite_code' => 'JOIN-INVITE']);
        $this->post('/register', [
            'name' => 'Anna Kalniņa', 'email' => 'join@example.test', 'role' => 'supervisor', 'stable_mode' => 'join',
            'stable_invite_code' => $stable->invite_code,
            'password' => 'StableCare12345', 'password_confirmation' => 'StableCare12345', 'terms' => '1',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'join@example.test', 'stable_id' => $stable->id, 'role' => 'supervisor']);
        $this->assertDatabaseCount('stables', 1);
    }

    public function test_supervisor_cannot_open_administrator_user_management(): void
    {
        $user = User::create(['name' => 'Supervisors', 'email' => 'supervisor@example.test', 'password' => 'StableCare12345', 'role' => 'supervisor', 'is_active' => true]);
        $this->actingAs($user)->get('/lietotaji')->assertForbidden();
    }
}
