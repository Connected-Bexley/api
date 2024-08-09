<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mime_type' => $this->mime_type,
            'is_private' => $this->is_private,
            'alt_text' => $this->meta['alt_text'] ?? null,
            'max_dimension' => $this->meta['max_dimension'] ?? null,
            'src' => 'data:' . $this->mime_type . ';base64,' . base64_encode($this->getContent()),
            'url' => $this->url(),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
