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

        $message = $client->messages->create($this->ukToInternationalNumber($sms->to), [
            'from' => config('sms.twilio.from'),
            'body' => $content,
        ]);

        $sms->notification->update(['message' => $message->body]);

        if (config('app.debug')) {
            logger()->debug('SMS sent via Twilio', $message->toArray());
        }
    }

    /**
     * Convert UK internal mobile '07' numbers to international '+44' numbers.
     *
     * @param string $ukMobileNumber
     * @return string
     */
    public function ukToInternationalNumber(string $ukMobileNumber)
    {
        $matches = preg_match('/^(\+447[0-9]{9})$/', $ukMobileNumber);
        if ($matches === 1) {
            return $ukMobileNumber;
        }

        $matches = preg_match('/^(07[0-9]{9})$/', $ukMobileNumber);

        if ($matches === 1) {
            return preg_replace('/^(0)/', '+44', $ukMobileNumber, 1);
        }

        return false;
    }
}
