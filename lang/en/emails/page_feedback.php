<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Feedback Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during page feedback
    |
    */

    'received' => [
        'notify_global_admin' => [
            'subject' => 'Feedback received on the site',
            'content' => '
Hello,

A site feedback form has been submitted for the page:
:FEEDBACK_URL

Here are the details:

":FEEDBACK_CONTENT"
',
        ],
    ],
    'received_contact' => [
        'notify_global_admin' => [
            'content' => '
Hello,

A site feedback form has been submitted for the page:
:FEEDBACK_URL

Here are the details:

":FEEDBACK_CONTENT"

The user has left contact details if you wish to contact them back. You can view them on the admin system.
',
        ],
    ],
];
