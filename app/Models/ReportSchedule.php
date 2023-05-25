<?php

namespace App\Models;

use App\Models\Mutators\ReportScheduleMutators;
use App\Models\Relationships\ReportScheduleRelationships;
use App\Models\Scopes\ReportScheduleScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportSchedule extends Model
{
    use HasFactory;
    use ReportScheduleMutators;
    use ReportScheduleRelationships;
    use ReportScheduleScopes;

    const REPEAT_TYPE_WEEKLY = 'weekly';

    const REPEAT_TYPE_MONTHLY = 'monthly';
}
