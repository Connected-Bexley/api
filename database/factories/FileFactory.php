<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'filename' => Str::random() . '.dat',
            'mime_type' => 'text/plain',
            'is_private' => false,
        ];
    }

    public function pendingAssignment()
    {
        return $this->state(['meta' => ['type' => File::META_TYPE_PENDING_ASSIGNMENT]]);
    }

    public function imagePng()
    {
        return $this->afterCreating(function (File $file) {
            $image = Storage::disk('local')->get('/test-data/image.png');
            $file->uploadBase64EncodedFile('data:image/png;base64,' . base64_encode($image));
        })->state(['filename' => Str::random() . '.png', 'mime_type' => 'image/png']);
    }

    public function imageJpg()
    {
        return $this->afterCreating(function (File $file) {
            $image = Storage::disk('local')->get('/test-data/image.jpg');
            $file->uploadBase64EncodedFile('data:image/jpeg;base64,' . base64_encode($image));
        })->state(['filename' => Str::random() . '.jpg', 'mime_type' => 'image/jpeg']);
    }

    public function imageSvg()
    {
        return $this->afterCreating(function (File $file) {
            $image = Storage::disk('local')->get('/test-data/image.svg');
            $file->uploadBase64EncodedFile('data:image/svg+xml;base64,' . base64_encode($image));
        })->state(['filename' => Str::random() . '.svg', 'mime_type' => 'image/svg+xml']);
    }
}
