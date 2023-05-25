<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Report Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during creation of reports
    |
    */
    'scheduled' => [
        'notify_global_admin' => [
            'subject' => ' Scheduled report generated ',
            'content' => '
Hello,

A :REPORT_FREQUENCY :REPORT_TYPE report has been generated.

Please login to the admin system to view the report.
            ',
        ],
    ],
];
