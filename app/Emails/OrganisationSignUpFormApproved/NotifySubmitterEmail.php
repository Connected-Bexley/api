<?php

namespace App\Emails\OrganisationSignUpFormApproved;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.organisation_sign_up_form_approved.notify_submitter.email');
    }

    public function getSubject(): string
    {
        return 'emails.organisation.sign_up_form.approved.notify_submitter.subject';
    }

    public function getContent(): string
    {
        return 'emails.organisation.sign_up_form.approved.notify_submitter.content';
    }
}
