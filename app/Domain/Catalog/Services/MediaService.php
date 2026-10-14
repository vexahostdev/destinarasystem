<?php

namespace App\Domain\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Store and optimize an uploaded image (GD driver, resize max 1600x1200, webp or original).
     *
     * @param UploadedFile $file
     * @param string $directory (e.g. 'destinations/covers' or 'destinations/gallery')
     * @return string Relative path stored on the public disk
     */
    public function storeOptimizedImage(UploadedFile $file, string $directory = 'destinations'): string
    {
        $fileName = Str::uuid() . '.webp';
        $fullPath = $directory . '/' . $fileName;

        // Read and scale down image
        $image = $this->imageManager->read($file->getRealPath());
        $image->scaleDown(width: 1600, height: 1200);

        // Convert to WebP with 82% quality
        $encoded = $image->toWebp(82);

        Storage::disk('public')->put($fullPath, (string) $encoded);

        return $fullPath;
    }

    /**
     * Delete a media file from the public disk.
     */
    public function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
