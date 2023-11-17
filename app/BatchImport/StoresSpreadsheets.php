<?php

namespace App\BatchImport;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mime\MimeTypes;

/**
 * Accept and store Base 64 encoded Spreadsheet data.
 */
trait StoresSpreadsheets
{
    /**
     * Total number of imported rows.
     *
     * @var int
     */
    protected $imported = 0;

    /**
     * Spreadsheet rows that were rejected by validation.
     *
     * @var array
     */
    protected $rejected = [];

    /**
     * Spreadsheet rows that are duplicates of existing entities.
     *
     * @var array
     */
    protected $duplicates = [];

    /**
     * Organisation Ids of duplicates that can be imported.
     *
     * @var array
     */
    protected $ignoreDuplicateIds = [];

    /**
     * Import a base64 encode spreadsheet.
     */
    public function processSpreadsheet(string $spreadsheet): void
    {
        $filePath = $this->storeBase64FileString($spreadsheet, 'batch-upload');

        if (!Storage::disk('local')->exists($filePath) || !is_readable(Storage::disk('local')->path($filePath))) {
            throw new FileNotFoundException($filePath);
        }

        $this->rejected = $this->validateSpreadsheet($filePath);

        if (!count($this->rejected)) {
            try {
                $this->imported = $this->importSpreadsheet($filePath);
            } catch (\App\Exceptions\DuplicateContentException $e) {
                Storage::disk('local')->delete($filePath);
                $this->imported = 0;
            } catch (\Exception $e) {
                Storage::disk('local')->delete($filePath);

                abort(500, $e->getMessage() . $e->getTraceAsString());
            }
        }

        Storage::disk('local')->delete($filePath);
    }

    /**
     * Store a Base 64 encoded data string.
     *
     * @throws Illuminate\Validation\ValidationException
     */
    protected function storeBase64FileString(string $file_data, string $path): string
    {
        preg_match('/^data:(application\/[a-z\-\.]+);base64,(.*)/', $file_data, $matches);
        if (count($matches) < 3) {
            throw ValidationException::withMessages(['spreadsheet' => 'Invalid Base64 Excel data']);
        }
        if (!$file_blob = base64_decode(trim($matches[2]), true)) {
            throw ValidationException::withMessages(['spreadsheet' => 'Invalid Base64 Excel data']);
        }

        return $this->storeBinaryUpload($file_blob, $path, $matches[1]);
    }

    /**
     * Store a binary file blob and update the models properties.
     */
    protected function storeBinaryUpload(string $blob, string $path, string $mime_type = null, string $ext = null): string
    {
        $path = empty($path) ? '' : trim($path, '/') . '/';
        $mime_type = $mime_type ?? $this->getFileStringMimeType($blob);
        $ext = $ext ?? $this->guessFileExtension($mime_type);
        $filename = md5($blob) . '.' . $ext;
        Storage::disk('local')->put($path . $filename, $blob);

        return $path . $filename;
    }

    /**
     * Get the mime type of a binary file string.
     *
     * @var string
     */
    protected function getFileStringMimeType(string $file_str): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $file_str);
        finfo_close($finfo);

        return $mime_type;
    }

    /**
     * Guess the extension for a file from it's mime-type.
     */
    protected function guessFileExtension(string $mime_type): string
    {
        return (new MimeTypes())->getExtensions($mime_type)[0] ?? null;
    }
}
