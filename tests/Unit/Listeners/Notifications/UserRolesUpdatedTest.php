<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Emails\UserRolesUpdated\NotifyUserEmail;
use App\Events\UserRolesUpdated as UserRolesUpdatedEvent;
use App\Listeners\Notifications\UserRolesUpdated as BaseUserRolesUpdatedListener;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserRolesUpdatedTest extends TestCase
{
    public function test_emails_sent_out()
    {
        Queue::fake();

        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = new UserRolesUpdatedEvent($user, new Collection(), $user->userRoles);
        $listener = new UserRolesUpdatedListener();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyUserEmail::class);
        Queue::assertPushed(NotifyUserEmail::class, function (NotifyUserEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.user_roles_updated.notify_user.email'),
                $email->templateId
            );
            $this->assertEquals('emails.user.roles_updated.notify_user.subject', $email->getSubject());
            $this->assertEquals('emails.user.roles_updated.notify_user.content', $email->getContent());
            $this->assertArrayHasKey('NAME', $email->values);
            $this->assertArrayHasKey('OLD_PERMISSIONS', $email->values);
            $this->assertArrayHasKey('PERMISSIONS', $email->values);

            return true;
        });
    }

    public function test_revoked_roles_method_works_from_super_admin_to_global_admin()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $oldRoles = new Collection([
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::superAdmin()->id,
            ]),
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::globalAdmin()->id,
            ]),
        ]);

        $newRoles = new Collection([
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::globalAdmin()->id,
            ]),
        ]);

        $listener = new UserRolesUpdatedListener();
        $revokedRoles = $listener->getRevokedRoles($oldRoles, $newRoles);

        $this->assertEquals(1, $revokedRoles->count());
        $this->assertEquals($user->id, $revokedRoles->first()->user_id);
        $this->assertEquals(Role::superAdmin()->id, $revokedRoles->first()->role_id);
    }

    public function test_revoked_roles_method_works_from_global_admin_to_super_admin()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $oldRoles = new Collection([
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::globalAdmin()->id,
            ]),
        ]);

        $newRoles = new Collection([
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::superAdmin()->id,
            ]),
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::globalAdmin()->id,
            ]),
        ]);

        $listener = new UserRolesUpdatedListener();
        $revokedRoles = $listener->getRevokedRoles($oldRoles, $newRoles);

        $this->assertEquals(0, $revokedRoles->count());
    }

    public function test_revoked_roles_method_works_from_global_admin_to_organisation_admin()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Models\Organisation $organisationOne */
        $organisationOne = Organisation::factory()->create();

        /** @var \App\Models\Organisation $organisationOne */
        $organisationTwo = Organisation::factory()->create();

        $globalAdminUserRole = new UserRole([
            'id' => uuid(),
            'user_id' => $user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);

        $organisationOneAdminUserRole = new UserRole([
            'id' => uuid(),
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisationOne->id,
        ]);

        $organisationTwoAdminUserRole = new UserRole([
            'id' => uuid(),
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisationTwo->id,
        ]);

        $oldRoles = new Collection([
            $globalAdminUserRole,
            $organisationOneAdminUserRole,
            $organisationTwoAdminUserRole,
        ]);

        $newRoles = new Collection([
            $organisationOneAdminUserRole,
        ]);

        $listener = new UserRolesUpdatedListener();
        $revokedRoles = $listener->getRevokedRoles($oldRoles, $newRoles);

        $this->assertEquals(2, $revokedRoles->count());
        $this->assertNotNull($revokedRoles->firstWhere('id', '=', $globalAdminUserRole->id));
        $this->assertNotNull($revokedRoles->firstWhere('id', '=', $organisationTwoAdminUserRole->id));
    }

    public function test_added_roles_method_works_from_global_admin_to_super_admin()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $superAdmin = new UserRole([
            'id' => uuid(),
            'user_id' => $user->id,
            'role_id' => Role::superAdmin()->id,
        ]);

        $globalAdmin = new UserRole([
            'id' => uuid(),
            'user_id' => $user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);

        $oldRoles = new Collection([
            $globalAdmin,
        ]);

        $newRoles = new Collection([
            $superAdmin,
            $globalAdmin,
        ]);

        $listener = new UserRolesUpdatedListener();
        $addedRoles = $listener->getAddedRoles($oldRoles, $newRoles);

        $this->assertEquals(1, $addedRoles->count());
        $this->assertNotNull($addedRoles->firstWhere('id', '=', $superAdmin->id));
    }
}

class UserRolesUpdatedListener extends BaseUserRolesUpdatedListener
{
    public function getRevokedRoles(Collection $oldRoles, Collection $newRoles): Collection
    {
        return parent::getRevokedRoles($oldRoles, $newRoles);
    }

    public function getAddedRoles(Collection $oldRoles, Collection $newRoles): Collection
    {
        return parent::getAddedRoles($oldRoles, $newRoles);
    }
}
