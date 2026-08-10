<?php

namespace Tests\Unit;

use App\DataTables\SubjectDataTable;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SubjectImageUrlTest extends TestCase
{
    private string $tempImagePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir(public_path('images'))) {
            mkdir(public_path('images'), 0755, true);
        }

        $this->tempImagePath = 'images/test-subject-cover.jpg';
        file_put_contents(public_path($this->tempImagePath), 'fake-image');
    }

    protected function tearDown(): void
    {
        if (is_file(public_path($this->tempImagePath))) {
            unlink(public_path($this->tempImagePath));
        }

        parent::tearDown();
    }

    public function test_datatable_image_html_uses_public_images_asset_without_storage_prefix(): void
    {
        $html = SubjectDataTable::imageHtml($this->tempImagePath);

        $this->assertStringContainsString('src="' . asset($this->tempImagePath) . '"', $html);
        $this->assertStringContainsString('/images/test-subject-cover.jpg', $html);
        $this->assertStringNotContainsString('/storage/images/', $html);
        $this->assertStringNotContainsString('images//', $html);
    }

    public function test_datatable_image_html_normalizes_double_slash_paths(): void
    {
        $html = SubjectDataTable::imageHtml('images//' . basename($this->tempImagePath));

        $this->assertStringContainsString('/images/test-subject-cover.jpg', $html);
        $this->assertStringNotContainsString('images//', $html);
    }

    public function test_datatable_image_html_is_empty_when_image_is_missing(): void
    {
        $this->assertSame('', SubjectDataTable::imageHtml(null));
        $this->assertSame('', SubjectDataTable::imageHtml(''));
        $this->assertSame('', SubjectDataTable::imageHtml('images/does-not-exist.jpg'));
    }

    public function test_subject_resource_returns_asset_url_for_image(): void
    {
        $path = 'images//math.jpg';

        $subject = new Subject([
            'name_ar' => 'رياضيات',
            'name_en' => 'Math',
            'image' => $path,
            'price' => 100,
            'duration' => null,
        ]);
        $subject->setRelation('courseMaterials', new Collection());
        $subject->setRelation('teachers', new Collection());

        $payload = (new SubjectResource($subject))->toArray(Request::create('/'));

        $this->assertSame(asset('images/math.jpg'), $payload['image']);
        $this->assertStringContainsString('/images/math.jpg', $payload['image']);
        $this->assertStringNotContainsString('images//', $payload['image']);
    }

    public function test_edit_view_uses_same_asset_path_as_datatable(): void
    {
        $editSrc = image_url($this->tempImagePath);
        $datatableHtml = SubjectDataTable::imageHtml($this->tempImagePath);

        $this->assertSame(asset($this->tempImagePath), $editSrc);
        $this->assertStringContainsString('src="' . $editSrc . '"', $datatableHtml);
    }
}
