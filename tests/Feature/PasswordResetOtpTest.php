<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_after_verifying_email_otp(): void
    {
        $user = $this->user();
        Mail::fake();
        $otp = null;

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('password.reset.form'));

        $this->post(route('password.update'), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(password_verify('NewPassword123', $user->fresh()->password));
    }

    public function test_incorrect_otp_does_not_allow_password_reset(): void
    {
        $user = $this->user();
        Mail::fake();

        $this->post(route('password.email'), ['email' => $user->email]);
        $this->from(route('password.otp.form'))
            ->post(route('password.otp.verify'), ['otp' => '000000'])
            ->assertRedirect(route('password.otp.form'))
            ->assertSessionHasErrors('otp');

        $this->get(route('password.reset.form'))->assertRedirect(route('password.request'));
    }

    public function test_password_request_page_is_available_from_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('Send verification code');
    }

    private function user(): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'OTP Store',
            'owner_name' => 'OTP Owner',
            'mobile' => '+91 98765 43210',
            'email' => 'otp@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'OTP Road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'OTP Owner',
            'email' => 'otp@example.com',
            'company_name' => 'OTP Store',
            'phone' => '9876543210',
            'country_code' => '+91',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);
    }
}
