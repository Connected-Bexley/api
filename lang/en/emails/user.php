<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during creating and updating users
    |
    */
    'created' => [
        'notify_user' => [
            'subject' => 'Account Created',
            'content' => '
Hi :NAME,

An account has been created for you using this email address. You can log in to the :APP_NAME  admin portal at:
:APP_ADMIN_URL

Permissions:
:PERMISSIONS

If you have any questions, you can email us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME  Team
            ',
        ],
    ],
    'roles_updated' => [
        'notify_user' => [
            'subject' => 'Permissions Updated',
            'content' => '
Hi :NAME,

Your account has had its permissions updated.

Old permissions:
:OLD_PERMISSIONS

New permissions:
:PERMISSIONS

If you have any questions, please contact us at :CONTACT_EMAIL

Many thanks,
The :APP_NAME  Team
            ',
        ],
    ],
];
