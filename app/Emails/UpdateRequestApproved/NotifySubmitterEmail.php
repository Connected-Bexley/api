<?php

namespace App\Emails\UpdateRequestApproved;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.update_request_approved.notify_submitter.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.update_request.approved.notify_submitter.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.update_request.approved.notify_submitter.subject';
    }
}
