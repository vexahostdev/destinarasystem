<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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
        Event::fake([Registered::class, UserRegistered::class]);

        $response = $this->post('/daftar', [
            'name' => 'Budi Santoso',
            'org_name' => 'SMA Negeri 1 Yogyakarta',
            'email' => 'budi@sman1jogja.sch.id',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'buyer',
            'terms' => '1',
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
        Event::assertDispatched(UserRegistered::class);
    }

    public function test_partner_registration(): void
    {
        Event::fake([Registered::class, UserRegistered::class]);

        $response = $this->post('/daftar', [
            'name' => 'Ketut Suardana',
            'org_name' => 'Pokdarwis Desa Karangrejo',
            'email' => 'ketut@karangrejo.id',
            'phone' => '081987654321',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'partner',
            'terms' => '1',
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
        Event::assertDispatched(UserRegistered::class);
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

    public function test_registration_requires_accepted_terms_and_matching_password_confirmation(): void
    {
        $response = $this->post('/daftar', [
            'name' => 'Budi Santoso',
            'org_name' => 'SMA Negeri 1 Yogyakarta',
            'email' => 'budi_err@sman1jogja.sch.id',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
            'account_type' => 'buyer',
            // terms missing
        ]);

        $response->assertSessionHasErrors(['password', 'terms']);
        $this->assertGuest();
    }

    public function test_login_screen_has_correct_password_request_link_and_no_mailto(): void
    {
        $response = $this->get('/masuk');
        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
        $response->assertDontSee('mailto:admin@destinara.id');
    }

    public function test_user_verification_and_password_reset_sends_indonesian_database_template_notification(): void
    {
        $user = User::factory()->create([
            'email' => 'user_notify@destinara.test',
            'account_type' => AccountType::BUYER,
        ]);

        $user->sendEmailVerificationNotification();
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.verify_email',
            'recipient' => 'user_notify@destinara.test',
        ]);

        $user->sendPasswordResetNotification('dummy-reset-token');
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.reset_password',
            'recipient' => 'user_notify@destinara.test',
        ]);
    }

    public function test_partner_registration_via_form_payload_and_password_reset_creates_notification_logs(): void
    {
        $payload = [
            'name' => 'Pak Wayan Pengelola',
            'org_name' => 'Koperasi Wisata Penglipuran',
            'email' => 'wayan@penglipuran.test',
            'phone' => '081234567899',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'partner',
            'terms' => '1',
        ];

        $response = $this->post('/daftar', $payload);
        $response->assertRedirect(route('verification.notice'));

        // Notification log for auth.verify_email to partner email
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.verify_email',
            'channel' => 'mail',
            'recipient' => 'wayan@penglipuran.test',
        ]);

        // Notification log for auth.welcome to partner
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.welcome',
            'recipient' => 'wayan@penglipuran.test',
        ]);

        $partner = User::where('email', 'wayan@penglipuran.test')->firstOrFail();
        $this->assertTrue($partner->isPartner());

        // Password reset for partner
        $this->post(route('password.email'), [
            'email' => 'wayan@penglipuran.test',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.reset_password',
            'channel' => 'mail',
            'recipient' => 'wayan@penglipuran.test',
        ]);
    }

    public function test_forgot_password_response_does_not_reveal_whether_email_is_registered_k5(): void
    {
        User::factory()->create(['email' => 'terdaftar@sekolah.test', 'account_type' => AccountType::BUYER]);

        $registered = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'terdaftar@sekolah.test']);
        $unregistered = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidak-ada@sekolah.test']);

        foreach ([$registered, $unregistered] as $response) {
            $response->assertRedirect(route('password.request'));
            $response->assertSessionHasNoErrors();
            $response->assertSessionHas('status', 'Jika email tersebut terdaftar, tautan reset password telah dikirim. Silakan periksa kotak masuk Anda.');
        }
    }
}
