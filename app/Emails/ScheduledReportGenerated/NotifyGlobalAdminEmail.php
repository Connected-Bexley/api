<?php

namespace App\Emails\ScheduledReportGenerated;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    protected function getTemplateId(): string
    {
        return config('gov_uk_notify.notifications_template_ids.scheduled_report_generated.notify_global_admin.email');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return 'email.report.scheduled.notify_global_admin.content';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): string
    {
        return 'email.report.scheduled.notify_global_admin.subject';
    }
}
