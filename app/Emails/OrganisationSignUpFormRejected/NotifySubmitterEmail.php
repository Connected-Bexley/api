<?php

namespace App\Emails\OrganisationSignUpFormRejected;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.organisation_sign_up_form_rejected.notify_submitter.email');
    }

    public function getSubject(): string
    {
        return 'emails.organisation.sign_up_form.rejected.notify_submitter.subject';
    }

    public function getContent(): string
    {
        return 'emails.organisation.sign_up_form.rejected.notify_submitter.content';
    }
}
