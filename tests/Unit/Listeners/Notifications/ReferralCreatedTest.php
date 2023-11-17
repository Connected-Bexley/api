<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Emails\ReferralCreated\NotifyClientEmail;
use App\Emails\ReferralCreated\NotifyRefereeEmail;
use App\Emails\ReferralCreated\NotifyServiceEmail;
use App\Events\EndpointHit;
use App\Listeners\Notifications\ReferralCreated;
use App\Models\Referral;
use App\Models\Service;
use App\Models\User;
use App\Sms\ReferralCreated\NotifyClientSms;
use App\Sms\ReferralCreated\NotifyRefereeSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReferralCreatedTest extends TestCase
{
    public function test_emails_sent_out(): void
    {
        Queue::fake();

        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_NEW,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onCreate($request, '', $referral);
        $listener = new ReferralCreated();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeEmail::class);
        Queue::assertPushed(NotifyRefereeEmail::class, function (NotifyRefereeEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_created.notify_referee.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.created.notify_referee.subject', $email->getSubject());
            $this->assertEquals('emails.referral.created.notify_referee.content', $email->getContent());
            $this->assertArrayHasKey('REFEREE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_CONTACT_METHOD', $email->values);
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_created.notify_client.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.created.notify_client.subject', $email->getSubject());
            $this->assertEquals('emails.referral.created.notify_client.content', $email->getContent());
            $this->assertArrayHasKey('REFERRAL_SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_CONTACT_METHOD', $email->values);
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyServiceEmail::class);
        Queue::assertPushed(NotifyServiceEmail::class, function (NotifyServiceEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_created.notify_service.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.created.notify_service.subject', $email->getSubject());
            $this->assertEquals('emails.referral.created.notify_service.content', $email->getContent());
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('REFERRAL_SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_INITIALS', $email->values);
            $this->assertArrayHasKey('CONTACT_INFO', $email->values);
            $this->assertArrayHasKey('REFERRAL_TYPE', $email->values);
            $this->assertArrayHasKey('REFERRAL_CONTACT_METHOD', $email->values);

            return true;
        });
    }

    public function test_sms_sent_out(): void
    {
        Queue::fake();

        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'phone' => '07700000000',
            'referee_phone' => '07700000000',
            'status' => Referral::STATUS_NEW,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onCreate($request, '', $referral);
        $listener = new ReferralCreated();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeSms::class);
        Queue::assertPushed(NotifyRefereeSms::class, function (NotifyRefereeSms $sms) {
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });
    }

    public function test_both_email_and_sms_sent_out_to_client(): void
    {
        Queue::fake();

        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'email' => 'test@example.com',
            'phone' => '07700000000',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_NEW,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onCreate($request, '', $referral);
        $listener = new ReferralCreated();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertArrayHasKey('REFERRAL_SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_CONTACT_METHOD', $email->values);
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });
    }
}
