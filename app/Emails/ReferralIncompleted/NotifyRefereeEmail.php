<?php

namespace App\Emails\ReferralIncompleted;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_incompleted.notify_referee.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.referral.incompleted.notify_referee.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.referral.incompleted.notify_referee.subject';
    }
}
