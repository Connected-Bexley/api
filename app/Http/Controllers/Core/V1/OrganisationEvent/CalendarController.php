<?php

namespace App\Http\Controllers\Core\V1\OrganisationEvent;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganisationEvent\Calendar\ShowRequest;
use App\Models\OrganisationEvent;
use Carbon\Carbon;
use DateTime;

class CalendarController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, OrganisationEvent $organisationEvent)
    {
        $now = new DateTime();
        $start = new Carbon($organisationEvent->start_date);
        [$startHour, $startMinute, $startSecond] = explode(':', $organisationEvent->start_time);
        $start->setTime($startHour, $startMinute, $startSecond);
        $end = new Carbon($organisationEvent->end_date);
        [$endHour, $endMinute, $endSecond] = explode(':', $organisationEvent->end_time);
        $end->setTime($endHour, $endMinute, $endSecond);

        $vEvent = [
            'VERSION' => '2.0',
            'PRODID' => '-//hacksw/handcal//NONSGML v1.0//EN',
            'BEGIN' => 'VEVENT',
            'UID' => $organisationEvent->id,
            'DTSTAMP' => $now->format('Ymd\\THis\\Z'),
            'ORGANIZER' => null,
            'DTSTART' => $start->format('Ymd\\THis\\Z'),
            'DTEND' => $end->format('Ymd\\THis\\Z'),
            'SUMMARY' => $organisationEvent->title,
            'DESCRIPTION' => $organisationEvent->intro,
            'GEO' => null,
            'LOCATION' => null,
            'END' => 'VEVENT',
        ];

        if (!$organisationEvent->is_virtual) {
            $vEvent['GEO'] = $organisationEvent->location->lat . ';' . $organisationEvent->location->lon;
            $vEvent['LOCATION'] = str_ireplace(',', '\,', $organisationEvent->location->toAddress()->__toString());
        }

        if ($organisationEvent->organiser_name) {
            $vEvent['ORGANIZER'] = 'CN=' . $organisationEvent->organiser_name;

            if ($organisationEvent->organiser_email) {
                $vEvent['ORGANIZER'] .= ':MAILTO:' . $organisationEvent->organiser_email;
            }
        }

        // Remove any empty rows
        $vEvent = array_filter($vEvent, function ($value) {
            return (bool)$value;
        });

        $vEvent = array_map(
            function ($key, $value) {
                return $key === 'ORGANIZER' ? $key . ';' . $value : $key . ':' . $value;
            },
            array_keys($vEvent),
            $vEvent
        );

        // Wrap the vEvent in a vCalendar
        array_unshift($vEvent, 'BEGIN:VCALENDAR');
        array_push($vEvent, 'END:VCALENDAR');

        return response(implode(
            "\r\n",
            $vEvent
        ))->header('Content-Type', 'text/calendar');
    }
}
