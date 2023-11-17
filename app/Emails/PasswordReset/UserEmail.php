<?php

namespace App\Emails\PasswordReset;

use App\Emails\Email;

class UserEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.password_reset.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'emails.password_reset.user.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'emails.password_reset.user.subject';
    }
}
