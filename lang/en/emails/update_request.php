<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Update Request Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during update requests
    |
     */
    'received' => [
        'notify_global_admin' => [
            'subject' => 'Update Request Submitted',
            'content' => '
Hello,

An update request has been created for the :RESOURCE_TYPE with the ID: :RESOURCE_ID.

Please review the request below before approving/rejecting:
:REQUEST_URL

Regards,
The :APP_NAME Team
            ',
        ],
        'notify_submitter' => [
            'subject' => ' 	Update Request Submitted ',
            'content' => '
Hi :SUBMITTER_NAME,

Your update to :RESOURCE_NAME (:RESOURCE_TYPE) has been submitted and received. A member of the admin team will review it shortly.

If you have any questions, please get in touch at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ',
        ],
    ],
    'approved' => [
        'notify_submitter' => [
            'subject' => 'Update Request Approved',
            'content' => '
Hi :SUBMITTER_NAME,

Your update request for the :RESOURCE_NAME (:RESOURCE_TYPE) on :REQUEST_DATE has been approved.

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ',
        ],
    ],
    'rejected' => [
        'notify_submitter' => [
            'subject' => 'Update Request Rejected',
            'content' => '
Hi :SUBMITTER_NAME,

Your update request for the :RESOURCE_NAME :(RESOURCE_TYPE) on :REQUEST_DATE has been rejected for the following reason(s):

:REJECTION_MESSAGE

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ',
        ],
    ],
];
