<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/** Stores uploads on the public disk; raster images are resized and re-encoded as WebP without metadata. */
final class Images
{
    private const MAX_DIMENSION = 2000;

    public static function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            try {
                $image = (new ImageManager(new Driver))->read($file->getRealPath())->scaleDown(self::MAX_DIMENSION, self::MAX_DIMENSION);
                $path = trim($directory, '/').'/'.Str::uuid().'.webp';
                Storage::disk('media')->put($path, (string) $image->toWebp(82));

                return $path;
            } catch (\Throwable $e) {
                Log::warning('Image optimisation failed, storing original', ['error' => $e->getMessage()]);
            }
        }

        return $file->store(trim($directory, '/'), 'media');
    }

    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk('media')->url($path) : null;
    }
}
