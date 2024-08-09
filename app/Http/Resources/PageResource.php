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
            'content' => $this->when(request()->routeIs('core.v1.pages.store', 'core.v1.pages.show', 'core.v1.pages.update'), $this->content),
            'order' => $this->order,
            'enabled' => $this->enabled,
            'page_type' => $this->page_type,
            'image' => $this->image ? [
                'id' => $this->image->id,
                'url' => $this->image->url(),
                'mime_type' => $this->image->mime_type,
                'alt_text' => $this->image->altText,
            ] : null,
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? [
                    'id' => $this->parent->id,
                    'title' => $this->parent->title,
                    'slug' => $this->parent->slug,
                    'excerpt' => $this->parent->excerpt,
                    'order' => $this->parent->order,
                    'enabled' => $this->parent->enabled,
                    'page_type' => $this->parent->page_type,
                    'created_at' => $this->parent->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => $this->parent->updated_at->format(CarbonImmutable::ISO8601),
                ] : null;
            }),
            'children' => $this->whenLoaded('children', function () {
                return $this->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->title,
                        'slug' => $child->slug,
                        'excerpt' => $child->excerpt,
                        'order' => $child->order,
                        'enabled' => $child->enabled,
                        'page_type' => $child->page_type,
                        'created_at' => $child->created_at->format(CarbonImmutable::ISO8601),
                        'updated_at' => $child->updated_at->format(CarbonImmutable::ISO8601),
                    ];
                });
            }),
            'ancestors' => $this->whenLoaded('ancestors', function () {
                return static::defaultOrder()->ancestorsOf($this->id)->map(function ($ancestor) {
                    return [
                        'id' => $ancestor->id,
                        'title' => $ancestor->title,
                        'slug' => $ancestor->slug,
                        'excerpt' => $ancestor->excerpt,
                        'order' => $ancestor->order,
                        'enabled' => $ancestor->enabled,
                        'page_type' => $ancestor->page_type,
                        'created_at' => $ancestor->created_at->format(CarbonImmutable::ISO8601),
                        'updated_at' => $ancestor->updated_at->format(CarbonImmutable::ISO8601),
                    ];
                });
            }),
            'landing_page' => $this->whenLoaded('landingPageAncestors', function () {
                return $this->landingPage ? [
                    'id' => $this->landingPage->id,
                    'title' => $this->landingPage->title,
                    'slug' => $this->landingPage->slug,
                    'excerpt' => $this->landingPage->excerpt,
                    'order' => $this->landingPage->order,
                    'enabled' => $this->landingPage->enabled,
                    'page_type' => $this->landingPage->page_type,
                    'created_at' => $this->landingPage->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => $this->landingPage->updated_at->format(CarbonImmutable::ISO8601),
                ] : null;
            }),
            'collection_categories' => CollectionCategoryResource::collection($this->whenLoaded('collectionCategories')),
            'collection_personas' => CollectionPersonaResource::collection($this->whenLoaded('collectionPersonas')),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
