<?php

namespace App\Emails\OrganisationSignUpFormReceived;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.organisation_sign_up_form_received.notify_global_admin.email');
    }

    public function getContent(): string
    {
        return 'emails.organisation.sign_up_form.received.notify_global_admin.content';
    }

    public function getSubject(): string
    {
        return 'emails.organisation.sign_up_form.received.notify_global_admin.subject';
    }
}
