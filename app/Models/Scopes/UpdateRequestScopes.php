<?php

namespace App\Models\Scopes;

use App\Models\UpdateRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait UpdateRequestScopes
{
    public function scopeNew(Builder $query): Builder
    {
        return $query->whereNull('updateable_id');
    }

    public function scopeExisting(Builder $query): Builder
    {
        return $query->whereNotNull('updateable_id');
    }

    public function scopeServiceId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_SERVICE)
            ->whereIn('updateable_id', $ids);
    }

    public function scopeServiceLocationId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_SERVICE_LOCATION)
            ->whereIn('updateable_id', $ids);
    }

    public function scopeLocationId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_LOCATION)
            ->whereIn('updateable_id', $ids);
    }

    public function scopeOrganisationId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->whereIn('updateable_id', $ids);
    }

    public function scopeOrganisationEventId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION_EVENT)
            ->whereIn('updateable_id', $ids);
    }

    public function scopePageId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_PAGE)
            ->whereIn('updateable_id', $ids);
    }

    public function scopeWithEntry(Builder $query, string $alias = 'entry'): Builder
    {
        return $query->addSelect(DB::raw("({$this->getEntrySql()}) AS `{$alias}`"));
    }

    /**
     * This SQL query is placed into its own method as it is referenced
     * in multiple places.
     */
    public function getEntrySql(): string
    {
        $services = UpdateRequest::EXISTING_TYPE_SERVICE;
        $newServicesOrgAdmin = UpdateRequest::NEW_TYPE_SERVICE_ORG_ADMIN;
        $newServicesGlobalAdmin = UpdateRequest::NEW_TYPE_SERVICE_GLOBAL_ADMIN;
        $locations = UpdateRequest::EXISTING_TYPE_LOCATION;
        $serviceLocations = UpdateRequest::EXISTING_TYPE_SERVICE_LOCATION;
        $organisations = UpdateRequest::EXISTING_TYPE_ORGANISATION;
        $organisationSignUpForm = UpdateRequest::NEW_TYPE_ORGANISATION_SIGN_UP_FORM;
        $newOrganisationGlobalAdmin = UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN;
        $organisationEvents = UpdateRequest::EXISTING_TYPE_ORGANISATION_EVENT;
        $newOrganisationEventOrgAdmin = UpdateRequest::NEW_TYPE_ORGANISATION_EVENT;
        $pages = UpdateRequest::EXISTING_TYPE_PAGE;
        $newPage = UpdateRequest::NEW_TYPE_PAGE;

        return <<<EOT
CASE `update_requests`.`updateable_type`
    WHEN "{$services}" THEN (
        SELECT `services`.`name`
        FROM `services`
        WHERE `update_requests`.`updateable_id` = `services`.`id`
        LIMIT 1
    )
    WHEN "{$newServicesOrgAdmin}" THEN (
        `update_requests`.`data`->>"$.name"
    )
    WHEN "{$newServicesGlobalAdmin}" THEN (
        `update_requests`.`data`->>"$.name"
    )
    WHEN "{$locations}" THEN (
        SELECT `locations`.`address_line_1`
        FROM `locations`
        WHERE `update_requests`.`updateable_id` = `locations`.`id`
        LIMIT 1
    )
    WHEN "{$serviceLocations}" THEN (
        SELECT IFNULL(`service_locations`.`name`, `locations`.`address_line_1`)
        FROM `service_locations`
        LEFT JOIN `locations` ON `service_locations`.`location_id` = `locations`.`id`
        WHERE `update_requests`.`updateable_id` = `service_locations`.`id`
        LIMIT 1
    )
    WHEN "{$organisations}" THEN (
        SELECT `organisations`.`name`
        FROM `organisations`
        WHERE `update_requests`.`updateable_id` = `organisations`.`id`
        LIMIT 1
    )
    WHEN "{$organisationSignUpForm}" THEN (
        IF(`update_requests`.`data`->>"$.organisation.id", (
            SELECT `organisations`.`name`
            FROM `organisations`
            WHERE `update_requests`.`data`->>"$.organisation.id" = `organisations`.`id`
            LIMIT 1
        ), `update_requests`.`data`->>"$.organisation.name")
    )
    WHEN "{$newOrganisationGlobalAdmin}" THEN (
        IF(`update_requests`.`data`->>"$.organisation.id", (
            SELECT `organisations`.`name`
            FROM `organisations`
            WHERE `update_requests`.`data`->>"$.organisation.id" = `organisations`.`id`
            LIMIT 1
        ), `update_requests`.`data`->>"$.organisation.name")
    )
    WHEN "{$organisationEvents}" THEN (
        SELECT `organisation_events`.`title`
        FROM `organisation_events`
        WHERE `update_requests`.`updateable_id` = `organisation_events`.`id`
        LIMIT 1
    )
    WHEN "{$newOrganisationEventOrgAdmin}" THEN (
        `update_requests`.`data`->>"$.title"
    )
    WHEN "{$pages}" THEN (
        SELECT `pages`.`title`
        FROM `pages`
        WHERE `update_requests`.`updateable_id` = `pages`.`id`
        LIMIT 1
    )
    WHEN "{$newPage}" THEN (
        `update_requests`.`data`->>"$.title"
    )
END
EOT;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('approved_at')
            ->whereNull('deleted_at');
    }
}
