<?php

namespace App\Actions\Exams;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a generated exam document from the private disk, or nothing while it is still being
 * prepared in the background.
 */
class DownloadExamDocumentAction
{
    public function execute(string $path, string $filename): ?StreamedResponse
    {
        $disk = Storage::disk('local');

        return $disk->exists($path) ? $disk->download($path, $filename) : null;
    }
}
