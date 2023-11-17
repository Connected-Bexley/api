<?php

namespace App\Sms\ReferralIncompleted;

use App\Sms\Sms;

class NotifyClientSms extends Sms
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_incompleted.notify_client.sms');
    }

    public function getContent(): string
    {
        return 'sms.referral.incompleted.notify_client';
    }
}
