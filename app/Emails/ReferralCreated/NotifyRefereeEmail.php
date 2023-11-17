<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_created.notify_referee.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.referral.created.notify_referee.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.referral.created.notify_referee.subject';
    }
}
