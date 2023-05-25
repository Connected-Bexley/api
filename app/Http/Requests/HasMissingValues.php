<?php

namespace App\Http\Requests;

use App\Support\MissingValue;

trait HasMissingValues
{
    /**
     * @param string $key
     * @return mixed|\App\Support\MissingValue
     */
    public function missingValue(string $key)
    {
        return $this->missing($key) ? new MissingValue() : $this->input($key);
    }
}
