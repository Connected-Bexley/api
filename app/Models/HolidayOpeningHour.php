<?php

namespace App\Models;

use App\Models\Mutators\HolidayOpeningHourMutators;
use App\Models\Relationships\HolidayOpeningHourRelationships;
use App\Models\Scopes\HolidayOpeningHourScopes;
use App\Support\Time;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HolidayOpeningHour extends Model
{
    use HasFactory;
    use HolidayOpeningHourMutators;
    use HolidayOpeningHourRelationships;
    use HolidayOpeningHourScopes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_closed' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Is the current time within these holiday opening hours.
     *
     * @return bool
     */
    public function isOpenNow()
    {
        // If closed, opening and closing time are redundant, so just return false.
        if ($this->is_closed) {
            return false;
        }

        // Return if the current time falls within the opening and closing time.
        return Time::now()->between($this->opens_at, $this->closes_at);
    }
}
