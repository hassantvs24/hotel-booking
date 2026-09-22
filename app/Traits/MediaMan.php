<?php

namespace App\Traits;

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

        Storage::disk($disk)->putFileAs(
            $path,
            $file,
            $fileName
        );

        return [
            'name' => $fileName,
            'path' => $path,
            'disk' => $disk,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType()
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
