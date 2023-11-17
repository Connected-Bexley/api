<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'order' => $this->order,
            'enabled' => $this->enabled,
            'page_type' => $this->page_type,
            'image' => new FileResource($this->image),
            'landing_page' => new static($this->whenLoaded('landingPageAncestors', function () {
                return $this->landingPage;
            })),
            'parent' => new static($this->whenLoaded('parent')),
            'children' => static::collection($this->whenLoaded('children')),
            'collection_categories' => CollectionCategoryResource::collection($this->whenLoaded('collectionCategories')),
            'collection_personas' => CollectionPersonaResource::collection($this->whenLoaded('collectionPersonas')),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
