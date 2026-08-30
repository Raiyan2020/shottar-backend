<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * §9 — وضع مراجعة آبل.
     *
     * وهو التطبيق تحت المراجعة، مراجع آبل لازم ميشوفش أي وسيلة دفع غير الشراء
     * داخل التطبيق (الدفع الخارجي للمحتوى الرقمي = رفض مضمون).
     *
     * `is_reviewer` بيتحسب **على السيرفر** من هيدرز `device-type` + `app-version`
     * مقارنة بإعداد "إصدار iOS تحت مراجعة آبل" في لوحة التحكم. قبل كده كان
     * بيتاخد من الطلب نفسه (`?is_reviewer=1`) وده أي حد كان يقدر يبعته.
     *
     * أول ما آبل توافق: الأدمن يمسح الحقل أو يحط النسخة اللي بعدها، ومستخدمي
     * نفس النسخة يرجعوا يشوفوا كل الوسائل على طول.
     *
     * ⚠️ الوسيلة اللي بتترجع أثناء المراجعة محددة في إعداد `review_payment_slug`
     * (الافتراضي `cash`). لازم تطابق اللي التطبيق بيفلتر عليه، وإلا القايمة
     * بتطلع فاضية عند المراجع.
     */
    public function __invoke(Request $request)
    {
        $isReviewer = app_in_apple_review(
            $request->header('device-type'),
            $request->header('app-version')
        );

        $query = PaymentMethod::query();

        if ($isReviewer) {
            // وسيلة واحدة بس أثناء المراجعة. الـ slug بيتحدد من الإعدادات
            // (review_payment_slug) عشان يتغيّر من غير نشر كود لو لزم.
            // من غير شرط status عشان لو حد قفلها بالغلط المراجع ميلاقيش
            // قايمة فاضية (= رفض مؤكد).
            $slug = trim((string) setting('review_payment_slug', PaymentMethod::SLUG_CASH))
                ?: PaymentMethod::SLUG_CASH;

            $query->where('slug', $slug);
        } else {
            $query->where('status', 1);
        }

        $payments = $query->get();

        // is_reviewer لازم يكون **top-level** جنب data مش جواه
        return response()->json([
            'status' => true,
            'is_reviewer' => $isReviewer,
            'data' => PaymentMethodResource::collection($payments),
        ], 200);
    }
}
