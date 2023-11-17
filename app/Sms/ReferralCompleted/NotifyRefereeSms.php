<?php

namespace App\Sms\ReferralCompleted;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.referral_completed.notify_referee.sms');
    }

    public function getContent(): string
    {
        return 'sms.referral.completed.notify_referee';
    }
}
