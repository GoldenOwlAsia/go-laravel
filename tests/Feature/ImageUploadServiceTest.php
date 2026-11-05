<?php

namespace Tests\Feature;

use App\Services\ImageUploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ImageUploadServiceTest extends TestCase
{
    public function test_upload_writes_the_image_to_the_s3_disk_and_returns_its_url(): void
    {
        Storage::fake('s3');

        $url = app(ImageUploadService::class)->upload(
            UploadedFile::fake()->image('avatar.jpg', 400, 400),
            'avatars',
            100,
            100,
        );

        $files = Storage::disk('s3')->allFiles('avatars');

        $this->assertCount(1, $files);
        $this->assertStringStartsWith('avatars/', $files[0]);
        $this->assertGreaterThan(0, Storage::disk('s3')->size($files[0]));
        $this->assertStringEndsWith($files[0], $url);
    }

    public function test_upload_throws_when_the_disk_rejects_the_write(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('url')->never();

        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

        $this->expectException(RuntimeException::class);

        app(ImageUploadService::class)->upload(
            UploadedFile::fake()->image('avatar.jpg', 400, 400),
        );
    }
}
