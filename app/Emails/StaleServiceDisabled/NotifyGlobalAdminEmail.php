<?php

namespace App\Emails\StaleServiceDisabled;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.stale_service_disabled.notify_global_admin.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.service.disabled.notify_global_admin.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.service.disabled.notify_global_admin.subject';
    }
}
