<?php

namespace Tests\Unit;

use App\DataTables\SubjectDataTable;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectImageUrlTest extends TestCase
{
    private string $tempImagePath = 'images/test-subject-cover.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put($this->tempImagePath, 'fake-image');
    }

    public function test_datatable_image_html_uses_storage_url(): void
    {
        $html = SubjectDataTable::imageHtml($this->tempImagePath);

        $this->assertStringContainsString('/storage/images/test-subject-cover.jpg', $html);
        $this->assertStringNotContainsString('images//', $html);
    }

    public function test_datatable_image_html_normalizes_double_slash_paths(): void
    {
        $html = SubjectDataTable::imageHtml('images//' . basename($this->tempImagePath));

        $this->assertStringContainsString('/storage/images/test-subject-cover.jpg', $html);
        $this->assertStringNotContainsString('images//', $html);
    }

    public function test_datatable_image_html_is_empty_when_image_is_missing(): void
    {
        $this->assertSame('', SubjectDataTable::imageHtml(null));
        $this->assertSame('', SubjectDataTable::imageHtml(''));
        $this->assertSame('', SubjectDataTable::imageHtml('images/does-not-exist.jpg'));
    }

    public function test_subject_resource_returns_storage_url_for_image(): void
    {
        Storage::disk('public')->put('images/math.jpg', 'fake');

        $subject = new Subject([
            'name_ar' => 'رياضيات',
            'name_en' => 'Math',
            'image' => 'images//math.jpg',
            'price' => 100,
            'duration' => null,
        ]);
        $subject->setRelation('courseMaterials', new Collection());
        $subject->setRelation('teachers', new Collection());

        $payload = (new SubjectResource($subject))->toArray(Request::create('/'));

        $this->assertStringContainsString('/storage/images/math.jpg', $payload['image']);
        $this->assertStringNotContainsString('images//', $payload['image']);
    }
}
