<?php

namespace App\Generators;

use App\Models\Model;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class UniqueSlugGenerator
{
    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * UniqueSlugGenerator constructor.
     */
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $string The string to slugify
     */
    /**
     * Find the first unique slug for the model using the provided slug
     * Unique slugs are created with a '-n' suffix.
     *
     * @param string $string
     * @param Model $model
     * @param string $column
     * @param int $index
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @return string
     */
    public function generate(string $string, Model $model, string $column = 'slug', int $index = 0): string
    {
        $slug = $this->slugify($string);
        $baseSlug = preg_replace('|\-\d$|', '', $slug);
        $slug = $index === 0 ? $baseSlug : "$baseSlug-{$index}";

        $slugAlreadyUsed = $this->db->table($model->getTable())
            ->where($column, '=', $slug)
            ->when($model->id, function ($query, $modelId) {
                $query->where('id', '!=', $modelId);
            })
            ->exists();

        if ($slugAlreadyUsed) {
            return $this->generate($string, $model, $column, $index + 1);
        }

        return $slug;
    }

    /**
     * @param string $string The string to slugify
     * @param string $slug The existing slug to compare against
     * @return bool Check whether the input string would slugify into the provided slug
     */
    public function compareEquals(string $string, string $slug): bool
    {
        $stringSlugRegex = preg_quote($this->slugify($string));

        return preg_match("/^{$stringSlugRegex}(-[0-9]+)?$/", $slug) === 1;
    }

    protected function slugify(string $string): string
    {
        return Str::slug($string);
    }
}
