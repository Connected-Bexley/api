<?php

namespace App\Contracts;

use App\Sms\Sms;

interface SmsSender
{
    public function send(Sms $sms);
}
