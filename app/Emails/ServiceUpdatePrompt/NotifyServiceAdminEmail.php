<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyServiceAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.service_update_prompt.notify_service_admin.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.service.updated.notify_service.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.service.updated.notify_service.subject';
    }
}
