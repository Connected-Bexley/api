<?php

namespace Tests\Unit\Console\Commands\Ck\Notify;

use App\Console\Commands\Ck\Notify\StaleServicesCommand;
use App\Emails\ServiceUpdatePrompt\NotifyGlobalAdminEmail;
use App\Emails\ServiceUpdatePrompt\NotifyServiceAdminEmail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StaleServicesCommandTest extends TestCase
{
    /*
     * 6 to 12 months.
     */
    public function test_6_to_12_months_emails_not_sent_after_5_months()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(5),
        ]);

        User::factory()->create()->makeServiceAdmin($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyServiceAdminEmail::class);
    }

    public function test_6_to_12_months_emails_not_sent_after_13_months()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(13),
        ]);

        User::factory()->create()->makeServiceAdmin($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyServiceAdminEmail::class);
    }

    public function test_6_to_12_months_emails_sent_after_6_months()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(6),
        ]);

        User::factory()->create()->makeServiceAdmin($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertPushedOn('notifications', NotifyServiceAdminEmail::class);
        Queue::assertPushed(NotifyServiceAdminEmail::class, function (NotifyServiceAdminEmail $email): bool {
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_URL', $email->values);
            $this->assertArrayHasKey('SERVICE_STILL_UP_TO_DATE_URL', $email->values);

            return true;
        });
    }

    public function test_6_to_12_months_emails_sent_after_12_months()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(12),
        ]);

        User::factory()->create()->makeServiceAdmin($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertPushedOn('notifications', NotifyServiceAdminEmail::class);
        Queue::assertPushed(NotifyServiceAdminEmail::class, function (NotifyServiceAdminEmail $email): bool {
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_URL', $email->values);
            $this->assertArrayHasKey('SERVICE_STILL_UP_TO_DATE_URL', $email->values);

            return true;
        });
    }

    public function test_6_to_12_months_emails_sent_after_9_months()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(9),
        ]);

        User::factory()->create()->makeServiceAdmin($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertPushedOn('notifications', NotifyServiceAdminEmail::class);
        Queue::assertPushed(NotifyServiceAdminEmail::class, function (NotifyServiceAdminEmail $email): bool {
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_URL', $email->values);
            $this->assertArrayHasKey('SERVICE_STILL_UP_TO_DATE_URL', $email->values);

            return true;
        });
    }

    public function test_6_to_12_months_emails_not_sent_to_service_workers()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(9),
        ]);

        User::factory()->create()->makeServiceWorker($service);

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyServiceAdminEmail::class);
    }

    public function test_6_to_12_months_emails_not_sent_to_global_admins()
    {
        Queue::fake();

        Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(9),
        ]);

        User::factory()->create()->makeGlobalAdmin();

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyServiceAdminEmail::class);
    }

    /*
     * After 12 months.
     */

    public function test_after_12_months_emails_not_sent_after_11_months()
    {
        Queue::fake();

        Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(11),
        ]);

        User::factory()->create()->makeSuperAdmin();

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyGlobalAdminEmail::class);
    }

    public function test_after_12_months_emails_not_sent_after_13_months()
    {
        Queue::fake();

        Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(13),
        ]);

        User::factory()->create()->makeSuperAdmin();

        Artisan::call(StaleServicesCommand::class);

        Queue::assertNotPushed(NotifyGlobalAdminEmail::class);
    }

    public function test_after_12_months_emails_sent_after_12_months()
    {
        Queue::fake();

        Service::factory()->create([
            'last_modified_at' => Date::now()->subMonths(12),
        ]);

        User::factory()->create()->makeSuperAdmin();

        Artisan::call(StaleServicesCommand::class);

        Queue::assertPushedOn('notifications', NotifyGlobalAdminEmail::class);
        Queue::assertPushed(NotifyGlobalAdminEmail::class, function (NotifyGlobalAdminEmail $email): bool {
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_URL', $email->values);
            $this->assertArrayHasKey('SERVICE_ADMIN_NAMES', $email->values);
            $this->assertArrayHasKey('SERVICE_STILL_UP_TO_DATE_URL', $email->values);

            return true;
        });
    }
}
