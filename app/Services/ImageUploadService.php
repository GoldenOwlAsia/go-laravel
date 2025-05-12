<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    public function upload(UploadedFile $file, string $folder = 'uploads', ?int $width = null, ?int $height = null): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "$folder/$filename";

        $image = Image::make($file->getRealPath());

        if ($width || $height) {
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        Storage::disk('s3')->put($path, (string) $image->encode());

        return Storage::url($path);
    }
}
