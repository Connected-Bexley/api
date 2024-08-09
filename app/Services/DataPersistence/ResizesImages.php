<?php

namespace App\Services\DataPersistence;

use App\Models\File;

trait ResizesImages
{
    /**
     * Resize the image.
     */
    public function resizeImageFile(string $imageFileId)
    {
        /** @var File $file */
        $file = File::findOrFail($imageFileId)->assigned();

        // Create resized version for common dimensions.
        foreach (config('local.cached_image_dimensions') as $maxDimension) {
            $file->resizedVersion($maxDimension);
        }
    }
}
