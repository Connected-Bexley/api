<?php

namespace App\Services\DataPersistence;

use App\Models\File;

trait ResizesImages
{
    /**
     * Resize the image.
     *
     * @param string $imageFileId
     */
    public function resizeImageFile($imageFileId)
    {
        /** @var \App\Models\File $file */
        $file = File::findOrFail($imageFileId)->assigned();

        // Create resized version for common dimensions.
        foreach (config('local.cached_image_dimensions') as $maxDimension) {
            $file->resizedVersion($maxDimension);
        }
    }
}
