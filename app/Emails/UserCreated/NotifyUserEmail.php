<?php

namespace App\Emails\UserCreated;

use App\Emails\Email;

class NotifyUserEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.user_created.notify_user.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.user.created.notify_user.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.user.created.notify_user.subject';
    }
}
