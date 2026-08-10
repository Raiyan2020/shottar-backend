<?php

namespace Tests\Unit;

use App\Traits\ImageTrait;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageTraitTest extends TestCase
{
    private object $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploader = new class {
            use ImageTrait;
        };

        if (! is_dir(public_path('images'))) {
            mkdir(public_path('images'), 0755, true);
        }
    }

    public function test_upload_image_stores_file_under_public_images_and_returns_relative_path(): void
    {
        $file = UploadedFile::fake()->image('subject.jpg');

        $path = $this->uploader->uploadImage('admin', $file);

        $this->assertMatchesRegularExpression('#^images/[^/]+\.jpg$#', $path);
        $this->assertStringNotContainsString('//', $path);
        $this->assertStringNotContainsString('storage/', $path);
        $this->assertFileExists(public_path($path));

        $this->uploader->deleteImage($path);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_uploaded_image_is_reachable_via_asset_url_not_storage_url(): void
    {
        $file = UploadedFile::fake()->image('cover.png');
        $path = $this->uploader->uploadImage('admin', $file);

        $correctUrl = asset($path);
        $brokenUrl = asset('storage/' . $path);

        $this->assertStringContainsString('/images/', $correctUrl);
        $this->assertStringNotContainsString('/storage/images/', $correctUrl);
        $this->assertNotSame($correctUrl, $brokenUrl);
        $this->assertFileExists(public_path(parse_url($correctUrl, PHP_URL_PATH)));

        $this->uploader->deleteImage($path);
    }
}
