<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during service creation and updates
    |
    */
    'created' => [
        'notify_global_admin' => [
            'subject' => ' 	Service Created :SERVICE_NAME) - Ready to review',
            'content' => "
Hello,

A new service has been created by an organisation admin and requires a Global Administrator to review:

- :SERVICE_NAME
- :ORGANISATION_NAME
- :SERVICE_INTRO

You will need to:

- Check the content entered is acceptable, descriptive, plain English, and doesn't have any typos
- Add taxonomies to the service, based on the content
- Enable the service if it is acceptable

If the service is not ready to go live, please contact the user that made the request to rectify the problems.

The user that made the request was :ORGANISATION_ADMIN_NAME, and you can contact them via :ORGANISATION_ADMIN_EMAIL

To review the service, follow this link: :SERVICE_URL

Many thanks,
The :APP_NAME Team
            ",
        ],
    ],
    'updated' => [
        'notify_global_admin' => [
            'subject' => ':SERVICE_NAME page on :APP_NAME - Inactive for 1 year',
            'content' => '
:SERVICE_NAME on :APP_NAME has not been updated in over 12 months.

View the page on :APP_NAME:
:SERVICE_URL

Reminders have been sent monthly to the following:
:SERVICE_ADMIN_NAMES

## Page already up to date?

Reset the clock:
:SERVICE_STILL_UP_TO_DATE_URL

## Disable page?

You can disable the page in the backend:
:SERVICE_URL
            ',
        ],
        'notify_service_admin' => [
            'subject' => ':SERVICE_NAME page on :APP_NAME',
            'content' => "
Hello,

This is a reminder that your page, :SERVICE_NAME on :APP_NAME has not been updated in over 6 months.

View the page on :APP_NAME:
:SERVICE_URL

## Update Page

You can login to our backend portal to update the page by entering your details and clicking the 'Services' tab. If you can't remember your login, or need some additional support, feel free to contact the support team.

Access the :APP_NAME backend portal to update:
:APP_ADMIN_URL

## Page doesn't need updating?

Let us know:
:SERVICE_STILL_UP_TO_DATE_URL

We'll make a note that the page is up to date already.

## Service no longer running?

Please let us know if you'd like the page removed from the site. A member of our admin team will disable it for you.

Contact us by email: :CONTACT_EMAIL

## Don't think you should have received this?

You have received this because you are one of the admins for this page. If you believe this is incorrect, please let us know. We'll be happy to change your permissions.

Contact us by email: :CONTACT_EMAIL

Many thanks,
The :APP_NAME Team
            ",
        ],
    ],
    'disabled' => [
        'notify_global_admin' => [
            'subject' => 'Disabled :SERVICE_NAME page on :APP_NAME',
            'content' => '
:SERVICE_NAME on :APP_NAME has been marked as disabled after not being updated for over a year.
',
        ],
    ],
];
