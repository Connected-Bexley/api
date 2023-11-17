<?php

namespace App\Sms\ReferralIncompleted;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_incompleted.notify_referee.sms');
    }

    public function getContent(): string
    {
        return 'sms.referral.incompleted.notify_referee';
    }
}
