<?php

namespace App\Sms\ReferralCreated;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_created.notify_referee.sms');
    }

    public function getContent(): string
    {
        return 'sms.referral.created.notify_referee';
    }
}
