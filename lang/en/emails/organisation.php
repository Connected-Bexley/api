<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Organisation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during organisation sign up
    |
    */
    'sign_up_form' => [
        'received' => [
            'notify_global_admin' => [
                'subject' => 'Organisation Sign Up Form Submitted',
                'content' => '
Hello,

An organisation sign up form has been submitted for :ORGANISATION_NAME.

Please review the request below before approving/rejecting:
:REQUEST_URL

Many thanks,
The :APP_NAME Team
                ',
            ],
            'notify_submitter' => [
                'subject' => 'Organisation Sign Up Form Submitted',
                'content' => '
Hi :SUBMITTER_NAME,

Your request to register :ORGANISATION_NAME on :APP_NAME has been submitted and received. A member of the admin team will review it shortly.

If you have any questions, please get in touch at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
                ',
            ],
        ],
        'approved' => [
            'notify_submitter' => [
                'subject' => 'Organisation Sign Up Form Approved ',
                'content' => '
Hi :SUBMITTER_NAME,

Your request to register :ORGANISATION_NAME on :APP_NAME on :request_date has been approved.

Your service may not be visible on the site immediately due to the time it takes for our administration team to process new organisations.

You can now log on to the administration portal to update your page or add new services. You will find more options to customise your page than were available on the completed form. You can access the administration portal at: :APP_ADMIN_URL

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
                ',
            ],
        ],
        'rejected' => [
            'notify_submitter' => [
                'subject' => 'New Organisation not approved',
                'content' => '
Hi :SUBMITTER_NAME,

Thank you for submitting your request to have :ORGANISATION_NAME listed on :APP_NAME.

Unfortunately, your request to list :ORGANISATION_NAME on :APP_NAME on :request_date has been rejected. This is due to the organisation/service not meeting the terms and conditions of being listed on :APP_NAME.

You can read more about our terms and conditions: :app_tandc_url

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
                    ',
            ],
        ],
    ],
];
