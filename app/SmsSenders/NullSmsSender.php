<?php

namespace App\SmsSenders;

use App\Contracts\SmsSender;
use App\Sms\Sms;

class NullSmsSender implements SmsSender
{
    public function send(Sms $sms)
    {
        $sms->notification->update(['message' => 'Sent by null sender - no message content provided']);
    }
}
