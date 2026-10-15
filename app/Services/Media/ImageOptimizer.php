<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;

/**
 * Makes an uploaded photo web-sized before it is stored.
 *
 * A phone photo is 4000+ px wide and 3-10 MB; a room card shows it at about 400 px. Stored as it is, every guest
 * downloads the full file and the page crawls. Here a photo is scaled down to MAX_EDGE px on its longest side and
 * saved again at a lower quality, which usually brings it under 400 KB with no visible difference.
 *
 *  - The picture is turned the way the camera meant it (EXIF orientation) before re-saving, because saving drops
 *    the EXIF tag and a sideways photo would stay sideways.
 *  - PNG and WebP keep their transparency. GIFs and anything unreadable are left exactly as uploaded.
 *  - If the PHP "gd" extension is missing, or the result would not be smaller, the original is stored untouched.
 */
class ImageOptimizer
{
    public const MAX_EDGE = 1600;
    public const JPEG_QUALITY = 82;
    public const WEBP_QUALITY = 82;

    /** A file this small is already fine: not worth re-encoding */
    private const SKIP_UNDER_BYTES = 300 * 1024;

    /**
     * @return array{contents: string, extension: string, mime: string}|null  null = store the original as it is
     */
    public function optimize(UploadedFile $file): ?array
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;

        if (!$info) {
            return null;
        }

        [$width, $height, $type] = $info;

        $supported = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (!isset($supported[$type])) {
            return null;
        }

        $orientation = $this->orientation($path, $type);
        $tooBig      = max($width, $height) > self::MAX_EDGE;

        if (!$tooBig && $orientation === 1 && $file->getSize() < self::SKIP_UNDER_BYTES) {
            return null;
        }

        // A big photo is large once it is unpacked into memory (width x height x 4 bytes)
        $previousLimit = ini_get('memory_limit');
        @ini_set('memory_limit', '768M');

        try {
            $image = @imagecreatefromstring((string) file_get_contents($path));
            if (!$image) {
                return null;
            }

            $image = $this->turn($image, $orientation);

            $w = imagesx($image);
            $h = imagesy($image);

            if (max($w, $h) > self::MAX_EDGE) {
                $scale = self::MAX_EDGE / max($w, $h);
                $small = imagescale($image, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)), IMG_BICUBIC_FIXED);
                if ($small) {
                    imagedestroy($image);
                    $image = $small;
                }
            }

            $contents = $this->encode($image, $type);
            imagedestroy($image);

            // Never make a photo bigger. (A turned photo is kept even if it is not smaller: it must not stay sideways.)
            if ($contents === null || ($orientation === 1 && strlen($contents) >= $file->getSize())) {
                return null;
            }

            return [
                'contents'  => $contents,
                'extension' => $supported[$type],
                'mime'      => image_type_to_mime_type($type),
            ];
        } catch (\Throwable) {
            return null;
        } finally {
            @ini_set('memory_limit', (string) $previousLimit);
        }
    }

    /** EXIF orientation (1 = upright). Only JPEGs carry it, and only when the "exif" extension is there. */
    private function orientation(string $path, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($path);

        return (int) ($exif['Orientation'] ?? 1) ?: 1;
    }

    private function turn(\GdImage $image, int $orientation): \GdImage
    {
        $result = match ($orientation) {
            2       => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3       => imagerotate($image, 180, 0),
            4       => $this->flip($image, IMG_FLIP_VERTICAL),
            5       => $this->flip(imagerotate($image, -90, 0), IMG_FLIP_HORIZONTAL),
            6       => imagerotate($image, -90, 0),
            7       => $this->flip(imagerotate($image, 90, 0), IMG_FLIP_HORIZONTAL),
            8       => imagerotate($image, 90, 0),
            default => $image,
        };

        return $result ?: $image;
    }

    private function flip(\GdImage|false $image, int $mode): \GdImage|false
    {
        if ($image) {
            imageflip($image, $mode);
        }

        return $image;
    }

    private function encode(\GdImage $image, int $type): ?string
    {
        ob_start();

        try {
            $ok = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($image, null, self::JPEG_QUALITY),
                IMAGETYPE_WEBP => $this->keepAlpha($image) && imagewebp($image, null, self::WEBP_QUALITY),
                IMAGETYPE_PNG  => $this->keepAlpha($image) && imagepng($image, null, 6),
                default        => false,
            };

            $data = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        return $ok && $data !== '' ? $data : null;
    }

    private function keepAlpha(\GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return true;
    }
}
