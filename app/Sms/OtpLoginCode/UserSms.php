<?php

namespace App\Sms\OtpLoginCode;

use App\Sms\Sms;

class UserSms extends Sms
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.otp_login_code.sms');
    }

    public function getContent(): string
    {
        return 'sms.otp.user.content';
    }
}
