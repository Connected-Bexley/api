<?php

namespace App\Models;

use App\Models\Relationships\ServiceEligibilityRelationships;

class ServiceEligibility extends Model
{
    use ServiceEligibilityRelationships;

    public function touchService(): ServiceEligibility
    {
        $this->service->save();

        return $this;
    }
}
