<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.service_update_prompt.notify_global_admin.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.service.updated.notify_global_admin.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.service.updated.notify_global_admin.subject';
    }
}
