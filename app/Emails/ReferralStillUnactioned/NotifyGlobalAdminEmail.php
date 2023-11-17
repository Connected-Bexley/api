<?php

namespace App\Emails\ReferralStillUnactioned;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_still_unactioned.notify_global_admin.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.referral.unactioned_still.notify_global_admin.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.referral.unactioned_still.notify_global_admin.subject';
    }
}
