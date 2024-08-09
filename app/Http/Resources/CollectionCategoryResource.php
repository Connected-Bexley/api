<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'intro' => $this->meta['intro'],
            'image_file_id' => $this->meta['image_file_id'] ?? null,
            'image' => $this->image ? [
                'id' => $this->image->id,
                'url' => $this->image->url(),
                'mime_type' => $this->image->mime_type,
                'alt_text' => $this->image->meta['alt_text'] ?? null,
            ] : null,
            'order' => $this->order,
            'enabled' => $this->enabled,
            'homepage' => $this->homepage,
            'sideboxes' => $this->meta['sideboxes'],
            'category_taxonomies' => TaxonomyResource::collection($this->taxonomies),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
