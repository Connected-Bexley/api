<?php

namespace Tests\Feature\Auth;

use App\Emails\PasswordReset\UserEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_email_sent_to_user()
    {
        Queue::fake();

        $user = User::factory()->create()->makeSuperAdmin();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), UserEmail::class);
        Queue::assertPushed(UserEmail::class, function (UserEmail $email) use ($user) {
            $this->assertEquals($user->email, $email->to);
            $this->assertEquals(config('gov_uk_notify.notifications_template_ids.password_reset.email'), $email->templateId);
            $this->assertArrayHasKey('PASSWORD_RESET_LINK', $email->values);

            return true;
        });
    }
}
