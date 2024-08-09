<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'has_image' => $this->hasImage(),
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'address_line_3' => $this->address_line_3,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'image' => $this->imageFile ? [
                'id' => $this->imageFile->id,
                'url' => $this->imageFile->url(),
                'mime_type' => $this->imageFile->mime_type,
                'alt_text' => $this->imageFile->altText,
            ] : null,
            'accessibility_info' => $this->accessibility_info,
            'has_wheelchair_access' => $this->has_wheelchair_access,
            'has_induction_loop' => $this->has_induction_loop,
            'has_accessible_toilet' => $this->has_accessible_toilet,
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
