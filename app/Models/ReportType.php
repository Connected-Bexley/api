<?php

namespace App\Models;

use App\Models\Mutators\ReportTypeMutators;
use App\Models\Relationships\ReportTypeRelationships;
use App\Models\Scopes\ReportTypeScopes;

class ReportType extends Model
{
    use ReportTypeMutators;
    use ReportTypeRelationships;
    use ReportTypeScopes;

    public static function usersExport(): self
    {
        return static::where('name', 'Users Export')->firstOrFail();
    }

    public static function servicesExport(): self
    {
        return static::where('name', 'Services Export')->firstOrFail();
    }

    public static function organisationsExport(): self
    {
        return static::where('name', 'Organisations Export')->firstOrFail();
    }

    public static function locationsExport(): self
    {
        return static::where('name', 'Locations Export')->firstOrFail();
    }

    public static function referralsExport(): self
    {
        return static::where('name', 'Referrals Export')->firstOrFail();
    }

    public static function feedbackExport(): self
    {
        return static::where('name', 'Feedback Export')->firstOrFail();
    }

    public static function auditLogsExport(): self
    {
        return static::where('name', 'Audit Logs Export')->firstOrFail();
    }

    public static function searchHistoriesExport(): self
    {
        return static::where('name', 'Search Histories Export')->firstOrFail();
    }

    public static function historicUpdateRequestsExport(): self
    {
        return static::where('name', 'Historic Update Requests Export')->firstOrFail();
    }
}
