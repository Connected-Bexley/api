<?php

namespace App\Models;

use App\Contracts\AppliesUpdateRequests;
use App\Http\Requests\ServiceLocation\UpdateRequest as Request;
use App\Models\Mutators\ServiceLocationMutators;
use App\Models\Relationships\ServiceLocationRelationships;
use App\Models\Scopes\ServiceLocationScopes;
use App\Rules\FileIsMimeType;
use App\Support\Time;
use App\UpdateRequest\UpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class ServiceLocation extends Model implements AppliesUpdateRequests
{
    use HasFactory;
    use ServiceLocationMutators;
    use ServiceLocationRelationships;
    use ServiceLocationScopes;
    use UpdateRequests;

    /**
     * Determine if the service location is open at this point in time.
     */
    public function isOpenNow(): bool
    {
        // First check if any holiday opening hours have been specified.
        $hasHolidayHoursOpenNow = $this->hasHolidayHoursOpenNow();

        // If holiday opening hours found, then return them.
        if ($hasHolidayHoursOpenNow !== null) {
            return (bool)$hasHolidayHoursOpenNow;
        }

        // If no holiday hours found, then resort to regular opening hours.
        return (bool)$this->hasRegularHoursOpenNow();
    }

    /**
     * Returns HolidayOpeningHour::class if open, false if closed, or null if not specified.
     */
    protected function hasHolidayHoursOpenNow(): HolidayOpeningHour|bool|null
    {
        // Get the holiday opening hours that today falls within.
        $holidayOpeningHour = $this->holidayOpeningHours()
            ->where('starts_at', '<=', Date::today())
            ->where('ends_at', '>=', Date::today())
            ->first();

        // If none found, return null.
        if ($holidayOpeningHour === null) {
            return null;
        }

        return $holidayOpeningHour->isOpenNow() ? $holidayOpeningHour : false;
    }

    /**
     * Get the RegularOpeningHour that is open now.
     *
     * @return RegularOpeningHour
     */
    protected function hasRegularHoursOpenNow(): RegularOpeningHour|bool
    {
        // Loop through each opening hour.
        foreach ($this->regularOpeningHours as $regularOpeningHour) {
            if ($regularOpeningHour->isOpenNow()) {
                return $regularOpeningHour;
            }
        }

        return false;
    }

    /**
     * Get the next opening time of all the regular opening hours.
     *
     * @return Illumiinate\Suppport\Collection
     */
    public function nextOccurs()
    {
        $dates = collect([]);
        foreach ($this->regularOpeningHours as $regularOpeningHour) {
            $nextOpenDate = $regularOpeningHour->nextOpenDate();
            $holidayOpeningHour = $this->holidayOpeningHours()
                ->where('starts_at', '<=', $nextOpenDate)
                ->where('ends_at', '>=', $nextOpenDate)
                ->first();
            if ($holidayOpeningHour) {
                if ($holidayOpeningHour->is_closed) {
                    $nextOpenDate = $regularOpeningHour->nextOpenDate($holidayOpeningHour->ends_at);
                    $dates->push([
                        'date' => $nextOpenDate->toDateString(),
                        'start_time' => $regularOpeningHour->opens_at->toString(),
                        'end_time' => $regularOpeningHour->closes_at->toString(),
                    ]);
                } else {
                    $dates->push([
                        'date' => $nextOpenDate->toDateString(),
                        'start_time' => $holidayOpeningHour->opens_at->toString(),
                        'end_time' => $holidayOpeningHour->closes_at->toString(),
                    ]);
                }
            } else {
                $dates->push([
                    'date' => $nextOpenDate->toDateString(),
                    'start_time' => $regularOpeningHour->opens_at->toString(),
                    'end_time' => $regularOpeningHour->closes_at->toString(),
                ]);
            }
        }

        return $dates->sortBy('date')->first();
    }

    /**
     * Check if the update request is valid.
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new Request())->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['image_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_SVG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG),
        ];

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = $updateRequest->data;

        // Update the service location.
        $this->update([
            'name' => Arr::get($data, 'name', $this->name),
            'image_file_id' => Arr::get($data, 'image_file_id', $this->image_file_id),
        ]);

        // Attach the regular opening hours.
        if (array_key_exists('regular_opening_hours', $data)) {
            $this->regularOpeningHours()->delete();
            foreach ($data['regular_opening_hours'] as $regularOpeningHour) {
                $this->regularOpeningHours()->create([
                    'frequency' => $regularOpeningHour['frequency'],
                    'weekday' => in_array(
                        $regularOpeningHour['frequency'],
                        [RegularOpeningHour::FREQUENCY_WEEKLY, RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH]
                    )
                    ? $regularOpeningHour['weekday']
                    : null,
                    'day_of_month' => ($regularOpeningHour['frequency'] === RegularOpeningHour::FREQUENCY_MONTHLY)
                    ? $regularOpeningHour['day_of_month']
                    : null,
                    'occurrence_of_month' => ($regularOpeningHour['frequency'] === RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH)
                    ? $regularOpeningHour['occurrence_of_month']
                    : null,
                    'starts_at' => ($regularOpeningHour['frequency'] === RegularOpeningHour::FREQUENCY_FORTNIGHTLY)
                    ? $regularOpeningHour['starts_at']
                    : null,
                    'opens_at' => $regularOpeningHour['opens_at'],
                    'closes_at' => $regularOpeningHour['closes_at'],
                ]);
            }
        }

        // Attach the holiday opening hours.
        if (array_key_exists('holiday_opening_hours', $data)) {
            $this->holidayOpeningHours()->delete();
            foreach ($data['holiday_opening_hours'] as $holidayOpeningHour) {
                $this->holidayOpeningHours()->create([
                    'is_closed' => $holidayOpeningHour['is_closed'],
                    'starts_at' => $holidayOpeningHour['starts_at'],
                    'ends_at' => $holidayOpeningHour['ends_at'],
                    'opens_at' => $holidayOpeningHour['opens_at'],
                    'closes_at' => $holidayOpeningHour['closes_at'],
                ]);
            }
        }

        return $updateRequest;
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     */
    public function getData(array $data): array
    {
        return $data;
    }

    public function touchService(): ServiceLocation
    {
        $this->service->save();

        return $this;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return File|Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderImage(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_SERVICE_LOCATION);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/service_location.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }

    public function hasImage(): bool
    {
        return $this->image_file_id !== null;
    }
}
