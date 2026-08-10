<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait ImageTrait
{
    public function getImageAttribute($value)
    {
        if ($value) {
            return getimg($value);
        } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return asset('storage/images/default.jpg');
    }

    public function setImageAttribute($value, $directory = 'images')
    {
        if (is_file($value)) {
            $this->attributes['image'] = uploader($value, $directory);
        } else {
            $this->attributes['image'] = $value;
        }
    }

    public function uploadImage($folder, $image)
    {
        /** @var UploadedFile $image */
        $filename = $image->hashName();
        $path = normalize_public_path('images/' . $filename);

        Storage::disk('public')->putFileAs('images', $image, $filename);

        // Remove mistaken legacy copy under app/public/images if present.
        $legacy = public_path($path);
        if (is_file($legacy)) {
            @unlink($legacy);
        }

        return $path;
    }

    public function uploadImagePost($folder, $image)
    {
        $image->store('/', $folder);
        $filename = $image->hashName();

        return 'images/' . $folder . '/' . $filename;
    }

    public function uploadImageVideo($folder, $image)
    {
        $image->store('/', $folder);
        $filename = $image->hashName();

        return 'videos/' . $filename;
    }

    public function uploadImageFront($folder, $image)
    {
        $image->store('/', $folder);

        return $image->hashName();
    }

    public function deleteImage($imagePath)
    {
        $imagePath = normalize_public_path($imagePath);

        if (! $imagePath) {
            return;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        $legacy = public_path($imagePath);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }
}
