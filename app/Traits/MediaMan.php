<?php


namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait MediaMan
{

    /**
     * @param $file
     * @param $path
     * @return mixed
     */
    public function storeFile($file, $path) :mixed
    {
        // if not file
        if (!$file instanceof UploadedFile) {
            return $file;
        }

        // generate unique name for file
        $currentDate = now();
        $slugify = Str::slug($currentDate, '-') . '-' . uniqid();
        $imageName = $slugify . '.' . $file->getClientOriginalExtension();

        // Check if Path exist or not
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }

        // Store File to Disk
        Storage::disk('public')->putFileAs($path . '/', $file, $imageName);
        return [
            'name' => $imageName,
            'path' => $path,
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
        ];
    }

    /**
     * @param $file
     * @param $path
     * @return void
     */
    public function deleteFile($file, $path) :void
    {
        if (Storage::disk('public')->exists($path . '/' . $file)) {
            Storage::disk('public')->delete($path . '/' . $file);
        }
    }

}
