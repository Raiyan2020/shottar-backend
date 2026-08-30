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
     */
    public function __invoke(Request $request)
    {
        $isReviewer = app_in_apple_review(
            $request->header('device-type'),
            $request->header('app-version')
        );

        $query = PaymentMethod::query();

        if ($isReviewer) {
            // الشراء داخل التطبيق بس — ومن غير شرط status عشان لو حد قفلها
            // بالغلط المراجع ميلاقيش قايمة فاضية (= رفض مؤكد).
            $query->where('slug', PaymentMethod::SLUG_APPLE_IAP);
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
