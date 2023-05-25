<?php

namespace App\Models\Scopes;

use App\Models\Role;
use App\Models\StatusUpdate;
use App\Models\UpdateRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ReportScopes
{
    /**
     * User Export Report query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUserExportResults(): Collection
    {
        $sql = <<<'EOT'
CASE `id`
    WHEN ? THEN 1
    WHEN ? THEN 2
    WHEN ? THEN 3
    WHEN ? THEN 4
    WHEN ? THEN 5
    ELSE 6
END
EOT;

        $bindings = [
            Role::superAdmin()->id,
            Role::globalAdmin()->id,
            Role::organisationAdmin()->id,
            Role::serviceAdmin()->id,
            Role::serviceWorker()->id,
        ];

        $rolesQuery = DB::table('roles')
            ->select([
                'id',
                'name',
            ])
            ->selectRaw("$sql as value", $bindings);

        $query = DB::table('users')
            ->select([
                'users.id as id',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.email as email',
            ])
            ->selectRaw('substring_index(group_concat(distinct all_roles.name ORDER BY all_roles.value), ",", 1) max_role')
            ->selectRaw('trim(trailing "," from replace(replace(replace(replace(group_concat(distinct all_roles.name ORDER BY all_roles.value),?,""),?,""),?,""),",,",",")) all_permissions', [Role::NAME_SUPER_ADMIN, Role::NAME_GLOBAL_ADMIN, Role::NAME_SERVICE_WORKER])
            ->selectRaw('concat_ws(",",group_concat(distinct user_roles.organisation_id), group_concat(distinct user_roles.service_id)) org_service_ids')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->joinSub($rolesQuery, 'all_roles', function ($join) {
                $join->on('all_roles.id', '=', 'user_roles.role_id');
            })
            ->groupBy('users.id');

        return $query->get();
    }

    /**
     * Service Export Report query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getServiceExportResults(): Collection
    {
        $query = DB::table('services')
            ->select([
                'organisations.name as organisation_name',
                'organisations.id as organisation_id',
                'organisations.email as organisation_email',
                'organisations.phone as organisation_phone',
                'services.id as service_id',
                'services.name as service_name',
                'services.url as service_url',
                'services.contact_name as service_contact_name',
                'services.updated_at as service_updated_at',
                'services.referral_method as service_referral_method',
                'services.referral_email as service_referral_email',
                'services.status as service_status',
            ])
            ->selectRaw('group_concat(distinct trim(trailing ", " from replace(concat_ws(", ", locations.address_line_1, locations.address_line_2, locations.address_line_3, locations.city, locations.county, locations.postcode, locations.country), ", , ", ", ")) separator "|") as service_locations')
            ->join('organisations', 'services.organisation_id', '=', 'organisations.id')
            ->leftJoin('service_locations', 'service_locations.service_id', '=', 'services.id')
            ->leftJoin('locations', 'service_locations.location_id', '=', 'locations.id')
            ->groupBy('services.id');

        return $query->get();
    }

    /**
     * Organisation Export Report query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrganisationExportResults(): Collection
    {
        $serviceCountQuery = DB::table('services')
            ->selectRaw('organisation_id, count(*) as count')
            ->groupBy('organisation_id');

        $nonAdminUsersCountQuery = DB::table('user_roles')
            ->selectRaw('user_roles.organisation_id as organisation_id, count(user_roles.user_id) as count')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.name', [
                Role::NAME_SERVICE_WORKER,
                Role::NAME_ORGANISATION_ADMIN,
                Role::NAME_SERVICE_ADMIN,
            ])
            ->groupBy('user_roles.organisation_id', 'user_roles.user_id');

        $query = DB::table('organisations')
            ->select([
                'organisations.id as organisation_id',
                'organisations.name as organisation_name',
                'organisations.email as organisation_email',
                'organisations.phone as organisation_phone',
                'organisations.url as organisation_url',
            ])
            ->selectRaw('ifnull(service_counts.count, 0) as organisation_services_count')
            ->selectRaw('ifnull(non_admin_user_counts.count, 0) as non_admin_users_count')
            ->distinct()
            ->leftJoinSub($serviceCountQuery, 'service_counts', function ($join) {
                $join->on('service_counts.organisation_id', '=', 'organisations.id');
            })
            ->leftJoinSub($nonAdminUsersCountQuery, 'non_admin_user_counts', function ($join) {
                $join->on('non_admin_user_counts.organisation_id', '=', 'organisations.id');
            });

        return $query->get();
    }

    /**
     * Location Export Report query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLocationExportResults(): Collection
    {
        $serviceCountQuery = DB::table('service_locations')
            ->selectRaw('location_id, count(*) as count')
            ->groupBy('location_id');

        $query = DB::table('locations')
            ->select([
                'locations.address_line_1 as address_line_1',
                'locations.address_line_2 as address_line_2',
                'locations.address_line_3 as address_line_3',
                'locations.city as city',
                'locations.county as county',
                'locations.postcode as postcode',
                'locations.country as country',
            ])
            ->selectRaw('ifnull(service_counts.count, 0) as services_count')
            ->leftJoinSub($serviceCountQuery, 'service_counts', function ($join) {
                $join->on('service_counts.location_id', '=', 'locations.id');
            });

        return $query->get();
    }

    /**
     * Referral Export Report query.
     *
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \Illuminate\Support\Collection
     */
    public function getReferralExportResults(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): Collection
    {
        $statusUpdateQuery = DB::table('status_updates')
            ->selectRaw('referral_id, max(created_at) as last_update')
            ->where('to', StatusUpdate::TO_COMPLETED)
            ->groupBy('referral_id');

        $query = DB::table('referrals')
            ->select([
                'organisations.id as organisation_id',
                'organisations.name as organisation_name',
                'services.id as service_id',
                'services.name as service_name',
                'referrals.created_at as created_at',
                'status_updates.last_update as last_update',
                'referrals.status as status',
                'referrals.referee_name as referee_name',
                'referrals.organisation as organisation',
                'taxonomies.name as taxonomy_name',
                'referrals.referral_consented_at as consented_at',
            ])
            ->join('services', 'services.id', '=', 'referrals.service_id')
            ->join('organisations', 'organisations.id', '=', 'services.organisation_id')
            ->leftJoin('organisation_taxonomies', 'organisation_taxonomies.id', '=', 'referrals.organisation_taxonomy_id')
            ->leftJoin('taxonomies', 'taxonomies.id', '=', 'organisation_taxonomies.taxonomy_id')
            ->leftJoinSub($statusUpdateQuery, 'status_updates', function ($join) {
                $join->on('status_updates.referral_id', '=', 'referrals.id');
            })
            ->when($startsAt && $endsAt, function ($query) use ($startsAt, $endsAt) {
                // When date range provided, filter referrals which were created between the date range.
                $query->whereBetween('referrals.created_at', [$startsAt, $endsAt]);
            });

        return $query->get();
    }

    /**
     * Feedback Export Report query.
     *
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \Illuminate\Support\Collection
     */
    public function getFeedbackExportResults(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): Collection
    {
        $query = DB::table('page_feedbacks')
            ->select([
                'page_feedbacks.created_at as created_at',
                'page_feedbacks.feedback as feedback',
                'page_feedbacks.url as url',
            ])
            ->when($startsAt && $endsAt, function ($query) use ($startsAt, $endsAt) {
                // When date range provided, filter feedbacks which were created between the date range.
                $query->whereBetween('page_feedbacks.created_at', [$startsAt, $endsAt]);
            });

        return $query->get();
    }

    /**
     * Audit Export Report query.
     *
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \Illuminate\Support\Collection
     */
    public function getAuditExportResults(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): Collection
    {
        $query = DB::table('audits')
            ->select([
                'audits.action as action',
                'audits.description as description',
                'audits.created_at as created_at',
                'audits.ip_address as ip_address',
                'audits.user_agent as user_agent',
            ])
            ->selectRaw('concat(users.first_name," ",users.last_name) as full_name')
            ->leftJoin('users', 'users.id', '=', 'audits.user_id')
            ->when($startsAt && $endsAt, function ($query) use ($startsAt, $endsAt) {
                // When date range provided, filter audits which were created between the date range.
                $query->whereBetween('audits.created_at', [$startsAt, $endsAt]);
            });

        return $query->get();
    }

    /**
     * Search Histories Export Report query.
     *
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \Illuminate\Support\Collection
     */
    public function getSearchHistoriesExportResults(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): Collection
    {
        $query = DB::table('search_histories')
            ->select([
                'search_histories.count as count',
                'search_histories.created_at as created_at',
            ])
            ->selectRaw('ifnull(json_unquote(search_histories.query->"$.body.query.function_score.query.bool.should[0].match.name.query"),ifnull(json_unquote(search_histories.query->"$.body.query.bool.must.should[0].match.title.query"),json_unquote(search_histories.query->"$.body.query.function_score.query.bool.should[0].match.title.query"))) as query')
            ->selectRaw('json_unquote(search_histories.query->"$.body.sort[0]._geo_distance") as distance')
            ->whereRaw('json_contains_path(search_histories.query, "one", "$.body.query.function_score.query.bool.should[0].match.name.query", "$.body.query.bool.must.should[0].match.title.query", "$.body.query.function_score.query.bool.should[0].match.title.query") = 1')
            ->when($startsAt && $endsAt, function ($query) use ($startsAt, $endsAt) {
                // When date range provided, filter search histories which were created between the date range.
                $query->whereBetween('search_histories.created_at', [$startsAt, $endsAt]);
            });

        return $query->get();
    }

    /**
     * Update Request Export Report query.
     *
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \Illuminate\Support\Collection
     */
    public function getUpdateRequestExportResults(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): Collection
    {
        $entrySql = (new UpdateRequest())->getEntrySql();

        $query = DB::table('update_requests')
            ->select([
                'update_requests.updateable_type as updateable_type',
                'update_requests.created_at as created_at',
                'update_requests.approved_at as approved_at',
                'update_requests.deleted_at as deleted_at',
            ])
            ->selectRaw("({$entrySql}) as entry")
            ->selectRaw('concat(users.first_name," ",users.last_name) as user_full_name')
            ->selectRaw('concat(actioning_users.first_name," ",actioning_users.last_name) as actioning_user_full_name')
            ->leftJoin('users', 'users.id', '=', 'update_requests.user_id')
            ->leftJoin('users as actioning_users', 'actioning_users.id', '=', 'update_requests.actioning_user_id')
            ->where(function ($query) {
                $query->whereNotNull('update_requests.approved_at')
                    ->orWhereNotNull('update_requests.deleted_at');
            })
            ->when($startsAt && $endsAt, function ($query) use ($startsAt, $endsAt) {
                // When date range provided, filter update requests which were created between the date range.
                $query->whereBetween('update_requests.created_at', [$startsAt, $endsAt]);
            });

        return $query->get();
    }
}
