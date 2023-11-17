<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'role' => $this->role->name,
            'organisation_id' => $this->when($this->isOrganisationAdmin(), $this->organisation_id),
            'service_id' => $this->when($this->isServiceWorker() || $this->isServiceAdmin(), $this->service_id),

            // Relationships.
            'organisation' => $this->when(
                $this->organisation_id !== null,
                new OrganisationResource($this->whenLoaded('organisation'))
            ),
            'service' => $this->when(
                $this->service_id !== null,
                new ServiceResource($this->whenLoaded('service'))
            ),
        ];
    }
}
