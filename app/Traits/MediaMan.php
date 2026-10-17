<?php

namespace App\Traits;

use App\Services\Media\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait MediaMan
{
    /**
     * Store an uploaded file.
     *
     * Existing calls continue using the public disk:
     * $this->storeFile($file, 'room');
     *
     * Private chat upload:
     * $this->storeFile($file, 'chat-attachments/1', 'local');
     */
    public function storeFile(
        mixed $file,
        string $path,
        string $disk = 'public'
    ): mixed {
        if (!$file instanceof UploadedFile) {
            return $file;
        }

        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'bin'
        );

        // Room and property photos are scaled down and recompressed first (a phone photo is several MB)
        $optimized = in_array(trim($path, '/'), ['room', 'properties'], true)
            ? app(ImageOptimizer::class)->optimize($file)
            : null;

        if ($optimized) {
            $extension = $optimized['extension'];
        }

        $fileName = sprintf(
            '%s-%s.%s',
            now()->format('Y-m-d-His'),
            Str::lower(Str::random(20)),
            $extension
        );

        $path = trim($path, '/');

        if (!Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }

        if ($optimized) {
            Storage::disk($disk)->put($path . '/' . $fileName, $optimized['contents']);
        } else {
            Storage::disk($disk)->putFileAs(
                $path,
                $file,
                $fileName
            );
        }

        return [
            'name' => $fileName,
            'path' => $path,
            'disk' => $disk,
            'size' => $optimized ? strlen($optimized['contents']) : $file->getSize(),
            'mime' => $optimized['mime']
                ?? $file->getMimeType()
                ?: $file->getClientMimeType()
                    ?: 'application/octet-stream',
            'original_name' =>
                $file->getClientOriginalName(),
        ];
    }

    public function deleteFile(
        string $file,
        string $path,
        string $disk = 'public'
    ): void {
        $fullPath = trim($path, '/')
            . '/'
            . ltrim($file, '/');

        if (Storage::disk($disk)->exists($fullPath)) {
            Storage::disk($disk)->delete($fullPath);
        }
    }

    public function deleteStoredFile(
        string $fullPath,
        string $disk = 'public'
    ): void {
        if (Storage::disk($disk)->exists($fullPath)) {
            Storage::disk($disk)->delete($fullPath);
        }
    }
}
