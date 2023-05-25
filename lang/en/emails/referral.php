<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during referrals
    |
    */

    'created' => [
        'notify_client' => [
            'subject' => 'Confirmation of referral',
            'content' => "
Hello,

You\'ve successfully connected to :REFERRAL_SERVICE_NAME!

They should be in touch with you via :REFERRAL_CONTACT_METHOD within 10 working days.

Your referral ID is :REFERRAL_ID.

If you have any feedback regarding this connection, or have not heard back within 10 working days, please contact the admin team via :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
        'notify_referee' => [
            'subject' => 'Confirmation of referral',
            'content' => "
Hello :REFEREE_NAME,

You\'ve successfully made a referral to :REFERRAL_SERVICE_NAME!

They should be in touch with the client by :REFERRAL_CONTACT_METHOD to speak to them about accessing the service within 10 working days.

The referral ID is :REFERRAL_ID. If you have any feedback regarding this connection, please contact the admin team via :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
        'notify_service' => [
            'subject' => 'New Referral Received',
            'content' => "
Hello,

You\'ve received a referral to your service!

Referral ID: :REFERRAL_ID
Service: :REFERRAL_SERVICE_NAME
Client initials: :REFERRAL_INITIALS
Contact via: :CONTACT_INFO

This is a :REFERRAL_TYPE

Please contact the client via :REFERRAL_CONTACT_METHOD within the next 10 working days.

You can see further details of the referral, and mark as completed:
:APP_ADMIN_REFERRAL_URL

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
    ],
    'completed' => [
        'notify_client' => [
            'subject' => 'Confirmation of referral',
            'content' => '
Hello,

Your referral ID is :REFERRAL_ID.

Your connection to :SERVICE_NAME has been marked as completed by the service.

This means that they have been in touch with you about accessing their service.

If you have any feedback regarding this connection or believe the service did not try to contact you, please contact the admin team via :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ',
        ],
        'notify_referee' => [
            'subject' => 'Referral Completed',
            'content' => "
Hi :REFEREE_NAME,

The referral you made to :SERVICE_NAME has been marked as complete. Referral ID: :REFERRAL_ID.

Your client should have been contacted by now, but if they haven't then please contact them on :SERVICE_PHONE or by email at :SERVICE_EMAIL.

If you would like to leave any feedback on the referral or get in touch with us, you can contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
    ],
    'incompleted' => [
        'notify_client' => [
            'subject' => 'Referral Incompleted',
            'content' => '
Hello :Customer name,

Referral ID: :REFERRAL_ID

Your referral to :SERVICE_NAME has been marked as incomplete with the following message:

":REFERRAL_STATUS".

If you believe the service did not try to contact you, or you have any other feedback regarding the connection, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team.
            ',
        ],
        'notify_referee' => [
            'subject' => 'Referral Incompleted',
            'content' => '
Hi :REFEREE_NAME,

The referral you made to :SERVICE_NAME has been marked as incomplete with the following message:

":REFERRAL_STATUS".

If you believe the service did not try to contact the client, or you have any other feedback regarding the connection, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team.
            ',
        ],
    ],
    'unactioned' => [
        'notify_service' => [
            'subject' => 'Referral awaiting action',
            'content' => "
Hello,

You received a referral to your service :REFERRAL_SERVICE_NAME for :REFERRAL_INITIALS and :REFERRAL_ID :REFERRAL_DAYS_AGO working days ago.

This is a :REFERRAL_TYPE.

Please contact the client via :REFERRAL_CONTACT_METHOD within the next :REFERRAL_DAYS_LEFT working days.

If you are unable to get in contact with the client, you can mark the referral is \'Incomplete\'.

You can update the status of the referral in the admin portal:
:APP_ADMIN_REFERRAL_URL

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
    ],
    'unactioned_still' => [
        'notify_global_admin' => [
            'subject' => ':REFERRAL_SERVICE_NAME has a referral about to expire',
            'content' => '
:REFERRAL_SERVICE_NAME has a referral about to expire. The details are as follows:

- Referral made: :REFERRAL_CREATED_AT
- :REFERRAL_TYPE
- Client initials: :REFERRAL_INITIALS
- Referral ID: :REFERRAL_ID
- Referral email address: :SERVICE_REFERRAL_EMAIL

Users attached to this service are as follows:

Service Worker(s):
:SERVICE_WORKERS

Service Admin(s):
:SERVICE_ADMINS

Organisation Admin(s):
:ORGANISATION_ADMINS
            ',
        ],
    ],
];
