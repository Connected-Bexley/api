<?php

namespace App\Models;

use App\Models\Mutators\RegularOpeningHourMutators;
use App\Models\Relationships\RegularOpeningHourRelationships;
use App\Models\Scopes\RegularOpeningHourScopes;
use App\Support\Time;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Date;

class RegularOpeningHour extends Model
{
    use HasFactory;
    use RegularOpeningHourMutators;
    use RegularOpeningHourRelationships;
    use RegularOpeningHourScopes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_FORTNIGHTLY = 'fortnightly';

    const FREQUENCY_NTH_OCCURRENCE_OF_MONTH = 'nth_occurrence_of_month';

    const WEEKDAY_MONDAY = 1;

    const WEEKDAY_TUESDAY = 2;

    const WEEKDAY_WEDNESDAY = 3;

    const WEEKDAY_THURSDAY = 4;

    const WEEKDAY_FRIDAY = 5;

    const WEEKDAY_SATURDAY = 6;

    const WEEKDAY_SUNDAY = 7;

    /**
     * Is the current time within this regular opening hours.
     *
     * @return bool
     */
    public function isOpenNow()
    {
        // Check if the current time falls within the opening hours.
        $isOpenNow = Time::now()->between($this->opens_at, $this->closes_at);

        // If not, then no further checks needed.
        if (!$isOpenNow) {
            return false;
        }

        // Use a different algorithm for each frequency type.
        switch ($this->frequency) {
            // If weekly then check that the weekday is the same as today.
            case static::FREQUENCY_WEEKLY:
                if (Date::today()->dayOfWeek === $this->weekday) {
                    return true;
                }
                break;
                // If monthly then check that the day of the month is the same as today.
            case static::FREQUENCY_MONTHLY:
                if (Date::today()->day === $this->day_of_month) {
                    return true;
                }
                break;
                // If fortnightly then check that today falls directly on a multiple of 2 weeks.
            case static::FREQUENCY_FORTNIGHTLY:
                if (fmod(Date::today()->diffInDays($this->starts_at) / CarbonImmutable::DAYS_PER_WEEK, 2) === 0.0) {
                    return true;
                }
                break;
                // If nth occurrence of month then check today is the same occurrence.
            case static::FREQUENCY_NTH_OCCURRENCE_OF_MONTH:
                $occurrence = occurrence($this->occurrence_of_month);
                $weekday = weekday($this->weekday);
                $month = month(Date::today()->month);
                $year = Date::today()->year;
                $dateString = "$occurrence $weekday of $month $year";
                $date = Date::createFromTimestamp(strtotime($dateString));
                if (Date::today()->equalTo($date)) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Get the next open times.
     *
     * @param DateTimeInterface $from
     * @return DateTime
     */
    public function nextOpenDate(?DateTimeInterface $from = null): DateTime
    {
        $fromDate = $from ? (new Carbon($from)) : Carbon::now();
        $fromDate->settings(['monthOverflow' => false]);

        switch ($this->frequency) {
            case static::FREQUENCY_WEEKLY:
                $nextOccursDaysDiff = $fromDate->dayOfWeekIso > $this->weekday ? CarbonImmutable::DAYS_PER_WEEK - ($fromDate->dayOfWeekIso - $this->weekday) : $this->weekday - $fromDate->dayOfWeekIso;

                return $fromDate->addDays($nextOccursDaysDiff);
                break;
            case static::FREQUENCY_MONTHLY:
                if ($fromDate->day > $this->day_of_month) {
                    $fromDate->addMonth();
                }
                if ($this->day_of_month > $fromDate->daysInMonth) {
                    $fromDate->day = $fromDate->daysInMonth;
                } else {
                    $fromDate->day = $this->day_of_month;
                }

                return $fromDate;
                break;
            case static::FREQUENCY_FORTNIGHTLY:
                $fortnightDiff = ceil($fromDate->diffInDays($this->starts_at) / (CarbonImmutable::DAYS_PER_WEEK * 2));

                return (new Carbon($this->starts_at))->addWeeks($fortnightDiff * 2);
                break;
            case static::FREQUENCY_NTH_OCCURRENCE_OF_MONTH:
                $occurrence = occurrence($this->occurrence_of_month);
                $weekday = weekday($this->weekday);
                $month = month($fromDate->month);
                $year = $fromDate->year;
                $dateString = "$occurrence $weekday of $month $year";
                $openDate = Carbon::createFromTimestamp(strtotime($dateString));
                if ($openDate->lessThan($fromDate)) {
                    $fromDate->addMonth();
                    $month = month($fromDate->month);
                    $year = $fromDate->year;
                    $dateString = "$occurrence $weekday of $month $year";
                    $openDate = Carbon::createFromTimestamp(strtotime($dateString));
                }

                return $openDate;
                break;
        }
    }
}
