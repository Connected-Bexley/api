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
            'content' => "
            Hi

            You've made a connection on :APP_NAME :REFERRAL_ID. The service should contact you within 10 working days. Any feedback contact :CONTACT_EMAIL

            The :APP_NAME Team
            ",
        ],
        'notify_referee' => [
            'content' => "
            Hi

            You've made a connection for a client on :APP_NAME :REFERRAL_ID. The service should contact them within 10 working days. Any feedback contact :CONTACT_EMAIL

            The :APP_NAME Team
            ",
        ],
    ],
    'completed' => [
        'notify_client' => [
            'content' => "
            Hi

            You've made a connection on :APP_NAME ((REFERRAL_ID)). The service should contact you within 10 working days. Any feedback contact :CONTACT_EMAIL

            The :APP_NAME Team
            ",
        ],
        'notify_referee' => [
            'content' => "
            Hi :REFEREE_NAME

            The referral you made to :SERVICE_NAME has been marked as complete. ID: :REFERRAL_ID

            Your client should have been contacted by now, but if they haven't then please contact them on :SERVICE_PHONE or by email at :SERVICE_EMAIL.

            The :APP_NAME Team
            ",
        ],
    ],
    'incompleted' => [
        'notify_client' => [
            'content' => "
            Hi :CLIENT_INITIALS

            Your referral (ID: :REFERRAL_ID) has been marked as incomplete. This means the service tried to contact you but couldn't.

            For details: :CONTACT_EMAIL

            The :APP_NAME Team.
            ",
        ],
        'notify_referee' => [
            'content' => "
            Hi :REFEREE_NAME,

            Your referral (ID: :REFERRAL_ID) has been marked as incomplete. This means the service tried to contact the client but couldn't.

            For details: :CONTACT_EMAIL

            Many thanks,
            The :APP_NAME Team.
            ",
        ],
    ],
];
