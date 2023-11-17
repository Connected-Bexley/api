<?php

namespace App\Listeners\Notifications;

use App\Emails\PageFeedbackReceived\NotifyGlobalAdminEmail;
use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Notification;
use App\Models\PageFeedback;

class PageFeedbackReceived
{
    /**
     * Handle the event.
     */
    public function handle(EndpointHit $event): void
    {
        // Only handle specific endpoint events.
        if ($event->isntFor(PageFeedback::class, Audit::ACTION_CREATE)) {
            return;
        }

        $this->notifyGlobalAdmins($event->getModel());
    }

    protected function notifyGlobalAdmins(PageFeedback $pageFeedback)
    {
        Notification::sendEmail(
            new NotifyGlobalAdminEmail(config('local.global_admin.email'), [
                'FEEDBACK_URL' => $pageFeedback->url,
                'FEEDBACK_CONTENT' => $pageFeedback->feedback,
            ])
        );
    }
}
