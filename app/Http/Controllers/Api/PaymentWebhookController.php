<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Webhook من MyFatoorah (يتسجل من لوحة التحكم بتاعتهم). مقصود يكون طبقة
     * تأكيد إضافية جنب callback/success و callback/error الموجودين — من غير
     * ما نلمسهم أو نغيّر سلوكهم. الشكل الدقيق للـ payload بيختلف حسب إعداد
     * الحساب، فبدل ما نصدّق أي حقل جاي فيه (order id, status..)، إحنا بس
     * بناخد منه إشارة "في حاجة اتغيرت لأوردر كذا" وبعدين بنتأكد بنفسنا من
     * MyFatoorah عن طريق GetPaymentStatus بتوكن السيرفر بتاعنا. فحتى لو حد
     * زوّر طلب على الراوت ده، مش هيقدر يخلي أوردر يبقى paid إلا لو فعلاً
     * متأكد كده عند MyFatoorah.
     */
    public function myFatoorah(Request $request, OrderPaymentReconciler $reconciler)
    {
        $payload = $request->all();

        Log::info('Shottar MyFatoorah webhook received', ['payload' => $payload]);

        $orderId = $payload['Data']['CustomerReference']
            ?? $payload['CustomerReference']
            ?? $payload['Data']['InvoiceReference']
            ?? $payload['InvoiceReference']
            ?? null;

        if (! $orderId || ! ctype_digit((string) $orderId)) {
            Log::warning('Shottar MyFatoorah webhook missing/invalid order reference', ['payload' => $payload]);

            return response()->json(['received' => true]);
        }

        $order = Order::find((int) $orderId);

        if (! $order) {
            Log::warning('Shottar MyFatoorah webhook order not found', ['order_id' => $orderId]);

            return response()->json(['received' => true]);
        }

        $reconciler->reconcile($order, 'webhook');

        return response()->json(['received' => true]);
    }
}
