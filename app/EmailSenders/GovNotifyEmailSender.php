<?php

namespace App\EmailSenders;

use Alphagov\Notifications\Client;
use App\Contracts\EmailSender;
use App\Emails\Email;

class GovNotifyEmailSender implements EmailSender
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
        /** @var \Alphagov\Notifications\Client $client */
        $client = resolve(Client::class);

        $response = $client->sendEmail(
            $email->to,
            $email->templateId,
            array_merge($this->globalValues, $email->values),
            $email->reference,
            $email->replyTo
        );

        $email->notification->update(['message' => $response['content']['body']]);

        if (config('app.debug')) {
            logger()->debug('Email sent via Gov Notify', $response);
        }
    }
}
