<?php

namespace Tests\Unit;

use App\Traits\ImageTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTraitTest extends TestCase
{
    private object $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->uploader = new class {
            use ImageTrait;
        };
    }

    public function test_upload_image_stores_file_on_public_disk_and_returns_relative_path(): void
    {
        $file = UploadedFile::fake()->image('subject.jpg');

        $path = $this->uploader->uploadImage('admin', $file);

        $this->assertMatchesRegularExpression('#^images/[^/]+\.jpg$#', $path);
        $this->assertStringNotContainsString('//', $path);
        Storage::disk('public')->assertExists($path);

        $url = image_url($path);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/images/', $url);
        $this->assertStringNotContainsString('/images//', $url);

        $this->uploader->deleteImage($path);
        Storage::disk('public')->assertMissing($path);
    }
}
