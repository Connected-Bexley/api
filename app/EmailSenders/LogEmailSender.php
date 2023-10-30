<?php

namespace App\EmailSenders;

use App\Contracts\EmailSender;
use App\Emails\Email;
use Illuminate\Support\Facades\Date;

class LogEmailSender implements EmailSender
{
    /**
     * Global values to be included in language files.
     *
     * @var array
     */
    protected $globalValues = [];

    public function __construct()
    {
        $globalValues['APP_NAME'] = config('app.name');
        $globalValues['APP_ADMIN_URL'] = config('local.backend_uri');
        $globalValues['CONTACT_EMAIL'] = config('local.global_admin.email');
    }

    /**
     * @param \App\Emails\Email $email
     */
    public function send(Email $email)
    {
        logger()->debug('Email sent via Log at [' . Date::now()->toDateTimeString() . ']', [
            'to' => $email->to,
            'templateId' => $email->templateId,
            'values' => array_merge($this->globalValues, $email->values),
            'reference' => $email->reference,
            'replyTo' => $email->replyTo,
        ]);

        $email->notification->update(['message' => 'Sent by log sender - no message content provided']);
    }
}
