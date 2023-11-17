<?php

namespace App\Emails\OrganisationSignUpFormReceived;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.organisation_sign_up_form_received.notify_submitter.email');
    }

    public function getContent(): string
    {
        return 'emails.organisation.sign_up_form.received.notify_submitter.content';
    }

    public function getSubject(): string
    {
        return 'emails.organisation.sign_up_form.received.notify_submitter.subject';
    }
}
