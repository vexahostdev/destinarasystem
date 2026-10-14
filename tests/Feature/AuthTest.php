<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/masuk');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@destinara.test',
            'password' => Hash::make('password123'),
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $response = $this->post('/masuk', [
            'email' => 'buyer@destinara.test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('buyer.dashboard'));
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@destinara.test',
            'password' => Hash::make('password123'),
            'account_type' => AccountType::BUYER,
            'status' => 'suspended',
            'suspended_reason' => 'Melanggar aturan platform',
        ]);

        $response = $this->post('/masuk', [
            'email' => 'suspended@destinara.test',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_buyer_registration(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/daftar', [
            'name' => 'Budi Santoso',
            'org_name' => 'SMA Negeri 1 Yogyakarta',
            'email' => 'budi@sman1jogja.sch.id',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'buyer',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@sman1jogja.sch.id',
            'account_type' => 'buyer',
            'phone' => '6281234567890',
        ]);

        $this->assertDatabaseHas('institutions', [
            'institution_name' => 'SMA Negeri 1 Yogyakarta',
            'pic_name' => 'Budi Santoso',
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_partner_registration(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/daftar', [
            'name' => 'Ketut Suardana',
            'org_name' => 'Pokdarwis Desa Karangrejo',
            'email' => 'ketut@karangrejo.id',
            'phone' => '081987654321',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'partner',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ketut@karangrejo.id',
            'account_type' => 'partner',
            'phone' => '6281987654321',
        ]);

        $this->assertDatabaseHas('partner_profiles', [
            'organization_name' => 'Pokdarwis Desa Karangrejo',
            'verification_status' => 'unverified',
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_logout_requires_post_and_invalidates_session(): void
    {
        $user = User::factory()->create([
            'account_type' => AccountType::BUYER,
        ]);

        $this->actingAs($user);

        // GET logout must fail or redirect without logging out
        $this->get('/keluar')->assertStatus(405);
        $this->assertAuthenticated();

        // POST logout succeeds
        $response = $this->post('/keluar');
        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
