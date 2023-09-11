<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Emails\ReferralCompleted\NotifyClientEmail;
use App\Emails\ReferralCompleted\NotifyRefereeEmail;
use App\Events\EndpointHit;
use App\Listeners\Notifications\ReferralCompleted;
use App\Models\Referral;
use App\Models\User;
use App\Sms\ReferralCompleted\NotifyClientSms;
use App\Sms\ReferralCompleted\NotifyRefereeSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReferralCompletedTest extends TestCase
{
    public function test_emails_sent_out()
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_COMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_COMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralCompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeEmail::class);
        Queue::assertPushed(NotifyRefereeEmail::class, function (NotifyRefereeEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_completed.notify_referee.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.completed.notify_referee.subject', $email->getSubject());
            $this->assertEquals('emails.referral.completed.notify_referee.content', $email->getContent());
            $this->assertArrayHasKey('REFEREE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('SERVICE_PHONE', $email->values);
            $this->assertArrayHasKey('SERVICE_EMAIL', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_completed.notify_client.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.completed.notify_client.subject', $email->getSubject());
            $this->assertEquals('emails.referral.completed.notify_client.content', $email->getContent());
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);

            return true;
        });
    }

    public function test_sms_sent_out()
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'phone' => 'test@example.com',
            'referee_phone' => '07700000000',
            'status' => Referral::STATUS_COMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_COMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralCompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeSms::class);
        Queue::assertPushed(NotifyRefereeSms::class, function (NotifyRefereeSms $sms) {
            $this->assertArrayHasKey('REFEREE_NAME', $sms->values);
            $this->assertArrayHasKey('SERVICE_NAME', $sms->values);
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);
            $this->assertArrayHasKey('SERVICE_PHONE', $sms->values);
            $this->assertArrayHasKey('SERVICE_EMAIL', $sms->values);

            return true;
        });
    }

    public function test_both_email_and_sms_sent_out_to_client()
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'email' => 'test@example.com',
            'phone' => '07700000000',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_COMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_COMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralCompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });
    }
}
