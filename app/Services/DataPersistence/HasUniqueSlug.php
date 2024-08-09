<?php

namespace App\Services\DataPersistence;

use App\Generators\UniqueSlugGenerator;
use App\Models\Model;
use Illuminate\Contracts\Container\BindingResolutionException;

trait HasUniqueSlug
{
    /**
     * Return a unique version of the proposed slug.
     *
     * @param string $slug
     * @param App\Models\Model $table
     * @param string $column
     * @param int $index
     * @throws BindingResolutionException
     * @return string
     */
    public function uniqueSlug(string $slug, Model $model, string $column = 'slug', int $index = 0): string
    {
        $slugGenerator = app(UniqueSlugGenerator::class);

        return $slugGenerator->generate($slug, $model, $column, $index);
    }
}
