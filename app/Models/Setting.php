<?php

namespace App\Models;

use App\Models\Mutators\SettingMutators;
use App\Models\Relationships\SettingRelationships;
use App\Models\Scopes\SettingScopes;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class Setting extends Model implements Responsable
{
    use SettingMutators;
    use SettingRelationships;
    use SettingScopes;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * Determines if the primary key is a UUID.
     *
     * @var bool
     */
    protected $keyIsUuid = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function toResponse($request): JsonResponse
    {
        $data = static::all()
            ->mapWithKeys(function (self $setting) {
                return [$setting->key => $setting->value];
            })
            ->all();

        $data = $this->transform($data);

        return response()->json(['data' => $data]);
    }

    /**
     * Transform the response.
     */
    protected function transform(array $value): array
    {
        $buttonImageFileId = Arr::get(
            $value,
            'cms.frontend.banner.image_file_id'
        );
        $cmsFrontendBannerHasImage = $buttonImageFileId !== null;

        Arr::set(
            $value,
            'cms.frontend.banner.has_image',
            $cmsFrontendBannerHasImage
        );
        Arr::forget($value, 'cms.frontend.banner.image_file_id');

        return $value;
    }

    public static function cms(): self
    {
        return static::findOrFail('cms');
    }
}
