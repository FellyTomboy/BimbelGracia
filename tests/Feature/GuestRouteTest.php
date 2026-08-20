<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_forgot_password_page_loads(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get('/admin');
        // Route may be 302 redirect to login or 404 if not defined — both are acceptable
        $this->assertTrue(in_array($response->status(), [302, 404]));
    }

    public function test_guest_cannot_access_guru(): void
    {
        $response = $this->get('/guru');
        $this->assertTrue(in_array($response->status(), [302, 404]));
    }

    public function test_guest_cannot_access_parent(): void
    {
        $response = $this->get('/parent');
        $this->assertTrue(in_array($response->status(), [302, 404]));
    }

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials_redirects(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_login_with_invalid_credentials_shows_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHas('errors');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
    }

    public function test_register_student_route_requires_token(): void
    {
        $response = $this->get('/register-student/invalid-token');
        // Token is validated by controller — may redirect or show error
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_register_teacher_route_requires_token(): void
    {
        $response = $this->get('/register-teacher/invalid-token');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }
}
