<?php

namespace App\SmsSenders;

use App\Contracts\SmsSender;
use App\Sms\Sms;
use Twilio\Rest\Client;

class TwilioSmsSender implements SmsSender
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
     * {@inheritDoc}
     */
    public function send(Sms $sms)
    {
        $content = trans(
            $sms->getContent(),
            array_merge($this->globalValues, $sms->values)
        );

        /** @var \Twilio\Rest\Client $client */
        $client = resolve(Client::class);

        $message = $client->messages->create($sms->to, [
            'from' => config('tlr.twilio.from'),
            'body' => $content,
        ]);

        $sms->notification->update(['message' => $content]);

        if (config('app.debug')) {
            logger()->debug('SMS sent', $message->toArray());
        }
    }
}
