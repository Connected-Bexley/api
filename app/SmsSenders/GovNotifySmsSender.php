<?php

namespace App\SmsSenders;

use Alphagov\Notifications\Client;
use App\Contracts\SmsSender;
use App\Sms\Sms;

class GovNotifySmsSender implements SmsSender
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

    public function send(Sms $sms)
    {
        /** @var \Alphagov\Notifications\Client $client */
        $client = resolve(Client::class);

        $response = $client->sendSms(
            $sms->to,
            $sms->templateId,
            array_merge($this->globalValues, $sms->values),
            $sms->reference,
            $sms->senderId
        );

        $sms->notification->update(['message' => $response['content']['body']]);

        if (config('app.debug')) {
            logger()->debug('SMS sent via Gov notify', $response);
        }
    }
}
