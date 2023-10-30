<?php

namespace App\Contracts;

use App\Models\Model;
use Illuminate\Foundation\Http\FormRequest;

interface DataPersistenceService
{
    public function update(FormRequest $request, Model $model);

    public function store(FormRequest $request);
}
