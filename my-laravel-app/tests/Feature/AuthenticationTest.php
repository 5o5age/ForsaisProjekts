<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_sign_in_with_email(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jānis',
            'surname' => 'Ozols',
            'email' => 'janis@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'name' => 'Jānis',
            'surname' => 'Ozols',
            'email' => 'janis@example.com',
        ]);

        $user = User::where('email', 'janis@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(password_verify('password123', $user->password));

        $this->post(route('login.store'), [
            'email' => 'janis@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->get(route('dashboard'))->assertOk()->assertSee('Sveiks, Jānis Ozols!');
    }

    public function test_guest_can_open_auth_pages_and_unknown_page_shows_home_link(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Reģistrēties')
            ->assertSee('Pieslēgties');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Atpakaļ uz sākumlapu')
            ->assertSee(route('home'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Atpakaļ uz sākumlapu')
            ->assertSee(route('home'));

        $this->get('/neeksistejosha-lapa')
            ->assertNotFound()
            ->assertSee('Lapa nav atrasta')
            ->assertSee(route('home'));
    }

    public function test_home_about_and_contact_navigation_pages_are_reachable(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('about'))
            ->assertSee(route('contact'));

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Par mums')
            ->assertSee(route('contact'))
            ->assertSee(route('home'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Kontakti')
            ->assertSee(route('about'))
            ->assertSee(route('home'));
    }

    public function test_registration_requires_valid_fields_and_password_policy(): void
    {
        $this->post(route('register.store'), [])->assertSessionHasErrors([
            'name', 'surname', 'email', 'password',
        ]);

        $this->post(route('register.store'), [
            'name' => str_repeat('a', 31),
            'surname' => str_repeat('b', 31),
            'email' => str_repeat('a', 40).'@example.com',
            'password' => 'abcdefgh',
        ])->assertSessionHasErrors(['name', 'surname', 'email', 'password']);
    }

    public function test_email_requires_a_domain_suffix(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jānis',
            'surname' => 'Ozols',
            'email' => 'janis@localhost',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);

        $this->post(route('login.store'), [
            'email' => 'janis@localhost',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_registration_rejects_duplicate_email_and_login_rejects_bad_credentials(): void
    {
        User::factory()->create([
            'email' => 'janis@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('register.store'), [
            'name' => 'Jānis',
            'surname' => 'Ozols',
            'email' => 'janis@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);

        $this->post(route('login.store'), [
            'email' => 'janis@example.com',
            'password' => 'wrongpass123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_guest_cannot_view_dashboard_and_user_can_log_out(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}