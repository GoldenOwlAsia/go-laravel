<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

class ImageUploadService
{
    public function upload(UploadedFile $file, string $folder = 'uploads', ?int $width = null, ?int $height = null): string
    {
        $disk = Storage::disk('s3');
        $path = $folder.'/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        $image = Image::read($file->getRealPath());

        if ($width || $height) {
            $image->scaleDown($width, $height);
        }

        if (! $disk->put($path, (string) $image->encode())) {
            throw new RuntimeException("Failed to upload image to [{$path}].");
        }

        return $disk->url($path);
    }
}
