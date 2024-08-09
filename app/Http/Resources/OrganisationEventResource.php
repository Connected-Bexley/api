<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'has_image' => $this->hasImage(),
            'title' => $this->title,
            'intro' => $this->intro,
            'description' => $this->description,
            'image' => $this->imageFile ? [
                'id' => $this->imageFile->id,
                'url' => $this->imageFile->url(),
                'mime_type' => $this->imageFile->mime_type,
                'alt_text' => $this->imageFile->altText,
            ] : null,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_free' => $this->is_free,
            'fees_text' => $this->fees_text,
            'fees_url' => $this->fees_url,
            'organiser_name' => $this->organiser_name,
            'organiser_phone' => $this->organiser_phone,
            'organiser_email' => $this->organiser_email,
            'organiser_url' => $this->organiser_url,
            'booking_title' => $this->booking_title,
            'booking_summary' => $this->booking_summary,
            'booking_url' => $this->booking_url,
            'booking_cta' => $this->booking_cta,
            'homepage' => $this->homepage,
            'is_virtual' => $this->is_virtual,
            'google_calendar_link' => $this->googleCalendarLink,
            'microsoft_calendar_link' => $this->microsoftCalendarLink,
            'apple_calendar_link' => $this->appleCalendarLink,
            'location_id' => $this->location_id,
            'organisation_id' => $this->organisation_id,
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),

            // Relationships.
            'organisation' => new OrganisationResource($this->whenLoaded('organisation')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'category_taxonomies' => TaxonomyResource::collection($this->taxonomies),
        ];
    }
}
