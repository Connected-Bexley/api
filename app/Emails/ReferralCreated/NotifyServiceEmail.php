<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyServiceEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_created.notify_service.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.referral.created.notify_service.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.referral.created.notify_service.subject';
    }
}
