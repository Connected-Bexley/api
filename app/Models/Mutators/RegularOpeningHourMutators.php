<?php

namespace App\Models\Mutators;

use App\Support\Time;

trait RegularOpeningHourMutators
{
    public function getOpensAtAttribute(string $opensAt): Time
    {
        return Time::create($opensAt);
    }

    /**
     * @param Time|string $opensAt
     */
    public function setOpensAtAttribute($opensAt)
    {
        $opensAt = $opensAt instanceof Time ? $opensAt : Time::create($opensAt);

        $this->attributes['opens_at'] = $opensAt->toString();
    }

    public function getClosesAtAttribute(string $closesAt): Time
    {
        return Time::create($closesAt);
    }

    /**
     * @param Time|string $closesAt
     */
    public function setClosesAtAttribute($closesAt)
    {
        $closesAt = $closesAt instanceof Time ? $closesAt : Time::create($closesAt);

        $this->attributes['closes_at'] = $closesAt->toString();
    }
}
