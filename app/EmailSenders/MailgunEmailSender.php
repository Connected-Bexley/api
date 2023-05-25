<?php

namespace App\EmailSenders;

use App\Contracts\EmailSender;
use App\Emails\Email;
use Illuminate\Mail\Markdown;
use Mailgun\Mailgun;

class MailgunEmailSender implements EmailSender
{
    /**
     * Global values to be included in language files.
     *
     * @var array
     */
    protected $globalValues = [];

    public function __construct()
    {
        $this->globalValues['APP_NAME'] = config('app.name');
        $this->globalValues['APP_ADMIN_URL'] = config('local.backend_uri');
        $this->globalValues['CONTACT_EMAIL'] = config('local.global_admin.email');
    }

    /**
     * Create the email subject from the language file and the email values.
     *
     * @param Email $email
     * @return string
     */
    public function createSubject(Email $email)
    {
        return trans(
            $email->getSubject(),
            array_merge($this->globalValues, $email->values)
        );
    }

    /**
     * Create the email content from the language file and the email values.
     *
     * @param Email $email
     * @return string
     */
    public function createContent(Email $email)
    {
        return trans(
            $email->getContent(),
            array_merge($this->globalValues, $email->values)
        );
    }

    /**
     * Create the email content from the language file and the email values.
     *
     * @param Email $email
     * @return string
     */
    public function createHtmlContent(Email $email)
    {
        return Markdown::parse(trans(
            $email->getContent(),
            array_merge($this->globalValues, $email->values)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function send(Email $email)
    {
        /** @var \Mailgun\Mailgun $client */
        $client = resolve(Mailgun::class);

        $fromName = config('mail.from.name');
        $fromAddress = config('mail.from.address');

        $content = $this->createContent($email);
        $htmlContent = $this->createHtmlContent($email);

        $response = $client
            ->messages()
            ->send(config('services.mailgun.domain'), [
                'from' => "{$fromName} <{$fromAddress}>",
                'to' => $email->to,
                'subject' => $this->createSubject($email),
                'text' => $content,
                'html' => $htmlContent,
            ]);

        $email->notification->update(['message' => $content]);

        if (config('app.debug')) {
            logger()->debug('Email sent', (array)$response);
        }
    }
}
