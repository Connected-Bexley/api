<?php

namespace App\Listeners\Notifications;

use App\Emails\ReferralCreated\NotifyClientEmail;
use App\Emails\ReferralCreated\NotifyRefereeEmail;
use App\Emails\ReferralCreated\NotifyServiceEmail;
use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Referral;
use App\Models\Service;
use App\Sms\ReferralCreated\NotifyClientSms;
use App\Sms\ReferralCreated\NotifyRefereeSms;

class ReferralCreated
{
    /**
     * Handle the event.
     */
    public function handle(EndpointHit $event): void
    {
        // Only handle specific endpoint events.
        if ($event->isntFor(Referral::class, Audit::ACTION_CREATE)) {
            return;
        }

        $referral = $event->getModel();
        $this->notifyClient($referral);
        $this->notifyReferee($referral);
        if ($referral->service->referral_method === Service::REFERRAL_METHOD_INTERNAL) {
            $this->notifyService($referral);
        }
    }

    protected function notifyClient(Referral $referral)
    {
        // Only send an email if email address was provided.
        if ($referral->email) {
            $referral->sendEmailToClient(new NotifyClientEmail($referral->email, [
                'REFERRAL_SERVICE_NAME' => $referral->service->name,
                'REFERRAL_CONTACT_METHOD' => 'email',
                'REFERRAL_ID' => $referral->reference,
            ]));
        }

        // Only send an SMS if phone number was provided.
        if ($referral->phone) {
            $referral->sendSmsToClient(new NotifyClientSms($referral->phone, [
                'REFERRAL_ID' => $referral->reference,
            ]));
        }
    }

    protected function notifyReferee(Referral $referral)
    {
        if ($referral->referee_email) {
            // Only send an email if email address was provided.
            $referral->sendEmailToReferee(new NotifyRefereeEmail($referral->referee_email, [
                'REFEREE_NAME' => $referral->referee_name,
                'REFERRAL_SERVICE_NAME' => $referral->service->name,
                'REFERRAL_CONTACT_METHOD' => 'email',
                'REFERRAL_ID' => $referral->reference,
            ]));
        } elseif ($referral->referee_phone) {
            // Resort to SMS, but only if phone number address was provided.
            $referral->sendSmsToReferee(new NotifyRefereeSms($referral->referee_phone, [
                'REFERRAL_ID' => $referral->reference,
            ]));
        }
    }

    protected function notifyService(Referral $referral)
    {
        $contactInfo = $referral->email ?? $referral->phone ?? $referral->other_contact ?? 'N/A';
        if ($referral->email !== null) {
            $contactMethod = 'email';
        } elseif ($referral->phone !== null) {
            $contactMethod = 'phone';
        } elseif ($referral->other_contact) {
            $contactMethod = 'other';
        } else {
            $contactMethod = 'N/A';
        }

        $referral->service->sendEmailToContact(new NotifyServiceEmail($referral->service->referral_email, [
            'REFERRAL_ID' => $referral->reference,
            'REFERRAL_SERVICE_NAME' => $referral->service->name,
            'REFERRAL_INITIALS' => $referral->initials(),
            'CONTACT_INFO' => $contactInfo,
            'REFERRAL_TYPE' => $referral->isSelfReferral() ? 'self referral' : 'champion referral',
            'REFERRAL_CONTACT_METHOD' => $contactMethod,
            'APP_ADMIN_REFERRAL_URL' => config('local.backend_uri') . '/referrals',
        ]));
    }
}
