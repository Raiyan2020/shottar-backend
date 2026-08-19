<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * §11 — حساب مبلغ الطلب على السيرفر.
 *
 * قبل كده الخصم كان بيتحسب في التطبيق وبيتبعت جوه `total`، والباك كان بيقبل
 * الرقم زي ما هو (وكود تطبيق الكوبون كان معمول comment أصلًا فـ `discount`
 * كان بيتخزن صفر دايمًا). النتيجة: الخصم بيتبعت مرتين بشكلين مختلفين ومحدش
 * بيتحقق من حاجة.
 *
 * السيرفر بقى هو مصدر الحقيقة، وبيرجّع التفصيل كامل: subtotal / discount / total.
 *
 * ترتيب الحساب:
 *   1) subtotal = مجموع أسعار المواد المختارة (من الداتابيز، مش من الطلب)
 *   2) خصم الباقة:
 *      - باقة شاملة  → السعر بيبقى grades.all_materials_price (لو أقل من المجموع)
 *      - عدد مواد N  → نسبة grades.discount_N_materials
 *   3) خصم الكوبون على المتبقّي (مع احترام max_discount)
 *   4) total = المتبقّي، مقرّب لـ 3 خانات (الدينار الكويتي)
 */
class OrderPricingService
{
    public const SCALE = 3;

    /**
     * @param  array<int>  $subjectIds
     * @return array{
     *     subtotal: float, bundle_discount: float, coupon_discount: float,
     *     discount: float, total: float, coupon: ?Coupon, coupon_code: ?string,
     *     coupon_error: ?string, items: Collection, is_all_materials: bool
     * }
     */
    public function quote(
        array $subjectIds,
        bool $isAllMaterials = false,
        ?string $couponCode = null,
        string $lang = 'ar'
    ): array {
        $subjects = Subject::whereIn('id', $subjectIds)->get(['id', 'price', 'grade_id']);

        $subtotal = $this->round((float) $subjects->sum(fn ($s) => (float) $s->price));

        $bundleDiscount = $this->round($this->bundleDiscount($subjects, $subtotal, $isAllMaterials));
        $afterBundle = $this->round(max(0, $subtotal - $bundleDiscount));

        [$coupon, $couponDiscount, $couponError] = $this->couponDiscount($couponCode, $afterBundle, $lang);
        $couponDiscount = $this->round($couponDiscount);

        $total = $this->round(max(0, $afterBundle - $couponDiscount));

        return [
            'subtotal' => $subtotal,
            'bundle_discount' => $bundleDiscount,
            'coupon_discount' => $couponDiscount,
            'discount' => $this->round($bundleDiscount + $couponDiscount),
            'total' => $total,
            'coupon' => $coupon,
            'coupon_code' => $coupon?->code,
            'coupon_error' => $couponError,
            'items' => $subjects,
            'is_all_materials' => $isAllMaterials,
        ];
    }

    /**
     * خصم الباقة من إعدادات الصف.
     */
    protected function bundleDiscount(Collection $subjects, float $subtotal, bool $isAllMaterials): float
    {
        if ($subjects->isEmpty() || $subtotal <= 0) {
            return 0.0;
        }

        $grade = Grade::find($subjects->first()->grade_id);

        if (! $grade) {
            return 0.0;
        }

        if ($isAllMaterials) {
            // نفس منطق SubjectController::index و AppleIapService: سعر الباقة
            // الشاملة هو all_materials_price لو أقل من مجموع الأسعار.
            $bundlePrice = (float) ($grade->all_materials_price ?? 0);

            if ($bundlePrice > 0 && $bundlePrice < $subtotal) {
                return $subtotal - $bundlePrice;
            }

            // مفيش سعر باقة مضبوط → نستخدم نسبة discount_all_materials
            return $subtotal * ((float) ($grade->discount_all_materials ?? 0)) / 100;
        }

        $percent = (float) ($grade->getDiscount($subjects->count()) ?? 0);

        return $subtotal * $percent / 100;
    }

    /**
     * @return array{0: ?Coupon, 1: float, 2: ?string}  [الكوبون, الخصم, رسالة الخطأ]
     */
    protected function couponDiscount(?string $code, float $amount, string $lang): array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return [null, 0.0, null];
        }

        $coupon = Coupon::where('code', $code)->where('status', '1')->first();

        if (! $coupon) {
            return [null, 0.0, $lang === 'ar' ? 'القسيمة غير موجودة.' : 'Coupon not found.'];
        }

        if ($coupon->isNotYetActive()) {
            return [null, 0.0, $lang === 'ar' ? 'القسيمة غير مفعّلة بعد.' : 'Coupon is not active yet.'];
        }

        if ($coupon->isExpired()) {
            return [null, 0.0, $lang === 'ar' ? 'القسيمة منتهية الصلاحية.' : 'Coupon has expired.'];
        }

        if ($coupon->usage_limit !== null && $coupon->usage_limit > 0
            && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            return [null, 0.0, $lang === 'ar' ? 'تم استنفاد عدد استخدامات القسيمة.' : 'Coupon usage limit reached.'];
        }

        $discount = $coupon->type === 'percent'
            ? $amount * ((float) $coupon->value) / 100
            : (float) $coupon->value;

        if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        // الخصم ميزيدش عن المبلغ نفسه
        $discount = min($discount, $amount);

        return [$coupon, max(0.0, $discount), null];
    }

    /**
     * تقريب لـ 3 خانات — بيقطع مشكلة الـ float زي 4.950000000000001.
     */
    public function round(float $value): float
    {
        return round($value, self::SCALE);
    }

    /**
     * هل المبلغ اللي بعته التطبيق مطابق لحساب السيرفر؟
     */
    public function matches(?float $clientTotal, float $serverTotal): bool
    {
        if ($clientTotal === null) {
            return true;
        }

        $tolerance = (float) config('services.orders.total_tolerance', 0.001);

        return abs($clientTotal - $serverTotal) <= $tolerance;
    }
}
