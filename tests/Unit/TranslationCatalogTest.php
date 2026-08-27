<?php

namespace Tests\Unit;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationCatalogTest extends TestCase
{
    public static function phpCatalogs(): array
    {
        return collect(glob(dirname(__DIR__, 2).'/resources/lang/en/*.php'))
            ->mapWithKeys(fn (string $path) => [basename($path) => [basename($path)]])
            ->all();
    }

    #[DataProvider('phpCatalogs')]
    public function test_arabic_and_english_catalogs_have_the_same_keys(string $filename): void
    {
        $english = Arr::dot(require resource_path("lang/en/{$filename}"));
        $arabic = Arr::dot(require resource_path("lang/ar/{$filename}"));

        $this->assertSame([], array_keys(array_diff_key($english, $arabic)), "Arabic is missing keys from {$filename}");
        $this->assertSame([], array_keys(array_diff_key($arabic, $english)), "English is missing keys from {$filename}");
    }

    public function test_json_catalogs_have_the_same_keys(): void
    {
        $english = json_decode(file_get_contents(resource_path('lang/en.json')), true, flags: JSON_THROW_ON_ERROR);
        $arabic = json_decode(file_get_contents(resource_path('lang/ar.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([], array_keys(array_diff_key($english, $arabic)));
        $this->assertSame([], array_keys(array_diff_key($arabic, $english)));
    }
}
