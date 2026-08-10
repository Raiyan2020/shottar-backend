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
    public function test_datatable_image_html_uses_public_images_asset_without_storage_prefix(): void
    {
        $path = 'images/subject-cover.jpg';
        $html = SubjectDataTable::imageHtml($path);

        $this->assertStringContainsString('src="' . asset($path) . '"', $html);
        $this->assertStringContainsString('/images/subject-cover.jpg', $html);
        $this->assertStringNotContainsString('/storage/images/', $html);
    }

    public function test_datatable_image_html_is_empty_when_image_is_missing(): void
    {
        $this->assertSame('', SubjectDataTable::imageHtml(null));
        $this->assertSame('', SubjectDataTable::imageHtml(''));
    }

    public function test_subject_resource_returns_asset_url_for_image(): void
    {
        $path = 'images/math.jpg';

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

        $this->assertSame(asset($path), $payload['image']);
        $this->assertStringContainsString('/images/math.jpg', $payload['image']);
        $this->assertStringNotContainsString('/storage/', $payload['image']);
    }

    public function test_edit_view_uses_same_asset_path_as_datatable(): void
    {
        $path = 'images/edit-preview.jpg';
        $editSrc = asset($path);
        $datatableHtml = SubjectDataTable::imageHtml($path);

        $this->assertStringContainsString('/images/edit-preview.jpg', $editSrc);
        $this->assertStringContainsString('src="' . $editSrc . '"', $datatableHtml);
        $this->assertStringNotContainsString('/storage/', $datatableHtml);
    }
}
