<?php

namespace App\Contracts;

use App\Models\UpdateRequest;
use Illuminate\Contracts\Validation\Validator;

interface AppliesUpdateRequests
{
    /**
     * Check if the update request is valid.
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator;

    /**
     * Apply the update request.
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest;

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     */
    public function getData(array $data): array;
}
