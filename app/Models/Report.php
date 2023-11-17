<?php

namespace App\Models;

use App\Models\Mutators\ReportMutators;
use App\Models\Relationships\ReportRelationships;
use App\Models\Scopes\ReportScopes;
use Carbon\CarbonImmutable;
use Closure;
use Exception;
use Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;
    use ReportMutators;
    use ReportRelationships;
    use ReportScopes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Created a report record and a file record.
     * Then delegates the physical file creation to a `generateReportName` method.
     *
     * @throws \Exception
     */
    public static function generate(
        ReportType $type,
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Generate the file name.
        $filename = sprintf(
            '%s_%s_%s.csv',
            Date::now()->format('Y-m-d_H-i'),
            Str::slug(config('app.name')),
            Str::slug($type->name)
        );

        // Create the file record.
        $file = File::create([
            'filename' => $filename,
            'mime_type' => 'text/csv',
            'is_private' => true,
        ]);

        // Create the report record.
        $report = static::create([
            'report_type_id' => $type->id,
            'file_id' => $file->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // Get the name for the report generation method.
        $methodName = 'generate' . ucfirst(Str::camel($type->name));

        // Throw exception if the report type does not have a generate method.
        if (!method_exists($report, $methodName)) {
            throw new Exception("The report type [{$type->name}] does not have a corresponding generate method");
        }

        return $report->$methodName($startsAt, $endsAt);
    }

    public function generateUsersExport(): self
    {
        $headings = [
            'User Reference ID',
            'User First Name',
            'User Last Name',
            'Email address',
            'Highest Permission Level',
            'Organisation/Service Permission Levels',
            'Organisation/Service IDs',
        ];

        $data = $this->getUserExportResults()->map(function ($row) {
            return [
                $row->id,
                $row->first_name,
                $row->last_name,
                $row->email,
                $row->max_role,
                $row->all_permissions,
                $row->org_service_ids,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateServicesExport(): self
    {
        $headings = [
            'Organisation',
            'Org Reference ID',
            'Org Email',
            'Org Phone',
            'Service Reference ID',
            'Service Name',
            'Service Web Address',
            'Service Contact Name',
            'Last Updated',
            'Referral Type',
            'Referral Contact',
            'Status',
            'Locations Delivered At',
        ];

        $data = $this->getServiceExportResults()->map(function ($row) {
            return [
                $row->organisation_name,
                $row->organisation_id,
                $row->organisation_email,
                $row->organisation_phone,
                $row->service_id,
                $row->service_name,
                $row->service_url,
                $row->service_contact_name,
                (new CarbonImmutable($row->service_updated_at))->format(CarbonImmutable::ISO8601),
                $row->service_referral_method,
                $row->service_referral_email,
                $row->service_status,
                $row->service_locations,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateOrganisationsExport(): self
    {
        $headings = [
            'Organisation Reference ID',
            'Organisation Name',
            'Number of Services',
            'Organisation Email',
            'Organisation Phone',
            'Organisation URL',
            'Number of Accounts Attributed',
        ];

        $data = $this->getOrganisationExportResults()->map(function ($row) {
            return [
                $row->organisation_id,
                $row->organisation_name,
                $row->organisation_services_count,
                $row->organisation_email,
                $row->organisation_phone,
                $row->organisation_url,
                $row->non_admin_users_count,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateLocationsExport(): self
    {
        $headings = [
            'Address Line 1',
            'Address Line 2',
            'Address Line 3',
            'City',
            'County',
            'Postcode',
            'Country',
            'Number of Services Delivered at The Location',
        ];

        $data = $this->getLocationExportResults()->map(function ($row) {
            return [
                $row->address_line_1,
                $row->address_line_2,
                $row->address_line_3,
                $row->city,
                $row->county,
                $row->postcode,
                $row->country,
                $row->services_count,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateReferralsExport(
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Referred to Organisation ID',
            'Referred to Organisation',
            'Referred to Service ID',
            'Referred to Service Name',
            'Date Made',
            'Date Complete',
            'Self/Champion',
            'Refer from organisation',
            'Date Consent Provided',
        ];

        $data = $this->getReferralExportResults($startsAt, $endsAt)->map(function ($row) {
            return [
                $row->organisation_id,
                $row->organisation_name,
                $row->service_id,
                $row->service_name,
                (new CarbonImmutable($row->created_at))->format(CarbonImmutable::ISO8601),
                $row->status === Referral::STATUS_COMPLETED ? (new CarbonImmutable($row->last_update))->format(CarbonImmutable::ISO8601) : '',
                $row->referee_name === null ? 'Self' : 'Champion',
                $row->organisation ?? $row->taxonomy_name,
                $row->consented_at ? (new CarbonImmutable($row->consented_at))->format(CarbonImmutable::ISO8601) : null,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateFeedbackExport(
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Date Submitted',
            'Feedback Content',
            'Page URL',
        ];

        $data = $this->getFeedbackExportResults($startsAt, $endsAt)->map(function ($row) {
            return [
                (new CarbonImmutable($row->created_at))->toDateString(),
                $row->feedback,
                $row->url,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateAuditLogsExport(
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Action',
            'Description',
            'User',
            'Date/Time',
            'IP Address',
            'User Agent',
        ];

        $data = $this->getAuditExportResults($startsAt, $endsAt)->map(function ($row) {
            return [
                $row->action,
                $row->description,
                $row->full_name,
                $row->created_at ? (new CarbonImmutable($row->created_at))->format(CarbonImmutable::ISO8601) : null,
                $row->ip_address,
                $row->user_agent,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateSearchHistoriesExport(
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Date made',
            'Search Text',
            'Number Services Returned',
            'Coordinates (Latitude,Longitude)',
        ];

        $data = $this->getSearchHistoriesExportResults($startsAt, $endsAt)->map(function ($row) {
            $coordinate = null;

            if ($row->distance) {
                $distance = json_decode($row->distance);
                $location = $distance->{'service_locations.location'} ?? $distance->{'event_location.location'};
                $coordinate = empty($location) ? null : implode(',', [$location->lat, $location->lon]);
            }

            return [
                $row->created_at ? (new CarbonImmutable($row->created_at))->toDateString() : null,
                $row->query,
                $row->count,
                $coordinate,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    public function generateHistoricUpdateRequestsExport(
        CarbonImmutable $startsAt = null,
        CarbonImmutable $endsAt = null
    ): self {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'User Submitted',
            'Type',
            'Entry',
            'Date/Time Request Made',
            'Approved/Declined',
            'Date Actioned',
            'Admin who Actioned',
        ];

        $data = $this->getUpdateRequestExportResults($startsAt, $endsAt)->map(function ($row) {
            return [
                $row->user_full_name ?? null,
                $row->updateable_type,
                $row->entry,
                (new CarbonImmutable($row->created_at))->format(CarbonImmutable::ISO8601),
                $row->approved_at !== null ? 'Approved' : 'Declined',
                $row->approved_at !== null
                ? (new CarbonImmutable($row->approved_at))->format(CarbonImmutable::ISO8601)
                : (new CarbonImmutable($row->deleted_at))->format(CarbonImmutable::ISO8601),
                $row->actioning_user_full_name ?? null,
            ];
        })->all();

        array_unshift($data, $headings);

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * Report Row Generator.
     */
    public function reportRowGenerator(Collection $data, Closure $callback): Generator
    {
        foreach ($data as $dataItem) {
            yield $callback($dataItem);
        }
    }
}
