<?php

namespace App\Rules;

use App\Models\IosBundleProduct;
use App\Models\Order;
use App\Models\Subject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IosProductIdRule implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreSubjectId = null,
        protected ?int $ignoreBundleGradeId = null,
        protected ?int $ignoreBundleSemesterId = null,
        protected ?string $lockedOriginal = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            if ($this->lockedOriginal) {
                $fail(__('general.ios_product_id_locked'));
            }

            return;
        }

        if (! is_string($value)) {
            $fail(__('general.ios_product_id_invalid'));

            return;
        }

        if (strlen($value) > 100) {
            $fail(__('general.ios_product_id_max'));

            return;
        }

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*(\.[A-Za-z0-9][A-Za-z0-9_-]*)+$/', $value)) {
            $fail(__('general.ios_product_id_invalid'));

            return;
        }

        if ($this->lockedOriginal && $value !== $this->lockedOriginal) {
            $fail(__('general.ios_product_id_locked'));

            return;
        }

        $subjectTaken = Subject::query()
            ->where('ios_product_id', $value)
            ->when($this->ignoreSubjectId, fn ($q) => $q->where('id', '!=', $this->ignoreSubjectId))
            ->exists();

        if ($subjectTaken) {
            $fail(__('general.ios_product_id_taken'));

            return;
        }

        $bundleQuery = IosBundleProduct::query()->where('ios_product_id', $value);
        if ($this->ignoreBundleGradeId && $this->ignoreBundleSemesterId) {
            $bundleQuery->where(function ($q) {
                $q->where('grade_id', '!=', $this->ignoreBundleGradeId)
                    ->orWhere('semester_id', '!=', $this->ignoreBundleSemesterId);
            });
        }

        if ($bundleQuery->exists()) {
            $fail(__('general.ios_product_id_taken'));
        }
    }

    public static function isLocked(?string $productId): bool
    {
        if (! $productId) {
            return false;
        }

        return Order::where('apple_product_id', $productId)->exists();
    }
}
