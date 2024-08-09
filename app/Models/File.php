<?php

namespace App\Models;

use App\ImageTools\Resizer;
use App\Models\Mutators\FileMutators;
use App\Models\Relationships\FileRelationships;
use App\Models\Scopes\FileScopes;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class File extends Model implements Responsable
{
    use HasFactory;
    use FileMutators;
    use FileRelationships;
    use FileScopes;

    const MIME_TYPE_PNG = 'image/png';

    const MIME_TYPE_JPG = 'image/jpg';

    const MIME_TYPE_JPEG = 'image/jpeg';

    const MIME_TYPE_SVG = 'image/svg+xml';

    const MIME_TYPE_TXT = 'text/plain';

    const META_TYPE_RESIZED_IMAGE = 'resized_image';

    const META_TYPE_PENDING_ASSIGNMENT = 'pending_assignment';

    const META_TYPE_ASSIGNED = 'assigned';

    const META_PLACEHOLDER_FOR_ORGANISATION = 'organisation';

    const META_PLACEHOLDER_FOR_ORGANISATION_EVENT = 'organisation_event';

    const META_PLACEHOLDER_FOR_SERVICE = 'service';

    const META_PLACEHOLDER_FOR_COLLECTION_CATEGORY = 'collection_category';

    const META_PLACEHOLDER_FOR_COLLECTION_PERSONA = 'collection_persona';

    const META_PLACEHOLDER_FOR_LOCATION = 'location';

    const META_PLACEHOLDER_FOR_SERVICE_LOCATION = 'service_location';

    const PEDNING_ASSIGNMENT_AUTO_DELETE_DAYS = 1;

    const WITH_PERIOD = true;

    const WITHOUT_PERIOD = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Create an HTTP response that represents the object.
     * @param mixed $request
     */
    public function toResponse($request): Response
    {
        return response()->make($this->getContent(), Response::HTTP_OK, [
            'Access-Control-Expose-Headers' => ['Content-Type', 'Content-Disposition'],
            'Content-Type' => $this->mime_type,
            'Content-Disposition' => sprintf('inline; filename="%s"', $this->filename),
        ]);
    }

    public function getContent(): string
    {
        return Storage::disk(config('filesystems.cloud'))->get($this->path());
    }

    public function path(): string
    {
        $directory = $this->is_private ? 'files/private' : 'files/public';

        return "/{$directory}/{$this->id}-{$this->filename}";
    }

    protected function visibility(): string
    {
        // S3 requires private visibility
        return config('filesystems.cloud') === 's3' ? 'private' : ($this->is_private ? 'private' : 'public');
    }

    public function upload(string $content): File
    {
        Storage::disk(config('filesystems.cloud'))->put($this->path(), $content, $this->visibility());

        return $this;
    }

    public function url(): string
    {
        return route('core.v1.image.display', ['filename' => $this->filename]);
    }

    /**
     * Deletes the file from disk.
     */
    public function deleteFromDisk()
    {
        Storage::disk(config('filesystems.cloud'))->delete($this->path());
    }

    public function uploadBase64EncodedFile(string $content): File
    {
        $data = explode(',', $content);
        $data = base64_decode(end($data));

        return $this->upload($data);
    }

    /**
     * @deprecated you should now use the uploadBase64EncodedFile() method instead
     */
    public function uploadBase64EncodedPng(string $content): File
    {
        return $this->uploadBase64EncodedFile($content);
    }

    /**
     * Get a file record which is a resized version of the current instance.
     */
    public function resizedVersion(int $maxDimension = null): self
    {
        // If no resize or format is SVG then return current instance.
        if ($maxDimension === null || $this->mime_type === self::MIME_TYPE_SVG) {
            return $this;
        }

        // Parameter validation.
        if ($maxDimension < 1 || $maxDimension > 1000) {
            throw new \InvalidArgumentException("Max dimension in not withing range [$maxDimension]");
        }

        $file = static::query()
            ->whereRaw('`meta`->>"$.type" = ?', [static::META_TYPE_RESIZED_IMAGE])
            ->whereRaw('`meta`->>"$.data.file_id" = ?', [$this->id])
            ->whereRaw('`meta`->>"$.data.max_dimension" = ?', [$maxDimension])
            ->first();

        // Create the resized version if it doesn't exist.
        if ($file === null) {
            /** @var Resizer $resizer */
            $resizer = resolve(Resizer::class);

            /** @var File $file */
            $file = static::create([
                'filename' => $this->filename,
                'mime_type' => $this->mime_type,
                'meta' => [
                    'type' => static::META_TYPE_RESIZED_IMAGE,
                    'data' => [
                        'file_id' => $this->id,
                        'max_dimension' => $maxDimension,
                    ],
                ],
                'is_private' => $this->is_private,
            ]);

            $file->upload(
                $resizer->resize($this->getContent(), $maxDimension)
            );
        }

        return $file;
    }

    /**
     * Get a file record which is a resized version of the specified placeholder.
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function resizedPlaceholder(int $maxDimension, string $placeholderFor): self
    {
        // Parameter validation.
        $validPlaceholdersFor = [
            static::META_PLACEHOLDER_FOR_ORGANISATION,
            static::META_PLACEHOLDER_FOR_ORGANISATION_EVENT,
            static::META_PLACEHOLDER_FOR_SERVICE,
            static::META_PLACEHOLDER_FOR_COLLECTION_PERSONA,
            static::META_PLACEHOLDER_FOR_COLLECTION_CATEGORY,
            static::META_PLACEHOLDER_FOR_LOCATION,
            static::META_PLACEHOLDER_FOR_SERVICE_LOCATION,
        ];

        if (!in_array($placeholderFor, $validPlaceholdersFor)) {
            throw new \InvalidArgumentException("Invalid placeholder name [$placeholderFor]");
        }

        $file = static::query()
            ->whereRaw('`meta`->>"$.type" = ?', [static::META_TYPE_RESIZED_IMAGE])
            ->whereRaw('`meta`->>"$.data.placeholder_for" = ?', [$placeholderFor])
            ->whereRaw('`meta`->>"$.data.max_dimension" = ?', [$maxDimension])
            ->first();

        // Create the resized version if it doesn't exist.
        if ($file === null) {
            /** @var Resizer $resizer */
            $resizer = resolve(Resizer::class);

            /** @var File $file */
            $file = static::create([
                'filename' => "$placeholderFor.png",
                'mime_type' => static::MIME_TYPE_PNG,
                'meta' => [
                    'type' => static::META_TYPE_RESIZED_IMAGE,
                    'data' => [
                        'placeholder_for' => $placeholderFor,
                        'max_dimension' => $maxDimension,
                    ],
                ],
                'is_private' => false,
            ]);

            $srcImageContent = Storage::disk('local')->get("/placeholders/$placeholderFor.png");
            $file->upload(
                $resizer->resize($srcImageContent, $maxDimension)
            );
        }

        return $file;
    }

    public static function extensionFromMime(string $mimeType, bool $withPeriod = true): string
    {
        $map = [
            static::MIME_TYPE_PNG => '.png',
            static::MIME_TYPE_SVG => '.svg',
            static::MIME_TYPE_JPG => '.jpg',
            static::MIME_TYPE_JPEG => '.jpg',
            static::MIME_TYPE_TXT => '.txt',
        ];

        if (!array_key_exists($mimeType, $map)) {
            throw new \InvalidArgumentException("The mime type [$mimeType] is not supported.");
        }

        return $withPeriod ? $map[$mimeType] : trim($map[$mimeType], '.');
    }

    /**
     * Set the pending_assignment / assigned meta value.
     *
     * @throws MassAssignmentException
     * @return File
     */
    public function assigned(): self
    {
        $meta = $this->meta;
        $meta['type'] = self::META_TYPE_ASSIGNED;
        $this->update(['meta' => $meta]);

        return $this;
    }
}
