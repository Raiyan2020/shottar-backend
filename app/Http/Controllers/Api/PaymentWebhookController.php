<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPaymentReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * ترتيب الحقول المطلوب من MyFatoorah لبناء توقيع حدث PAYMENT_STATUS_CHANGED
     * (Webhook V2). الترتيب ده حرفي من مستنداتهم ولازم يفضل زي ما هو.
     *
     * @see https://docs.myfatoorah.com/docs/webhook-v2-payment-status-data-model
     */
    protected const SIGNATURE_FIELDS = [
        'Invoice.Id',
        'Invoice.Status',
        'Transaction.Status',
        'Transaction.PaymentId',
        'Invoice.ExternalIdentifier',
    ];

    /**
     * Webhook من MyFatoorah (يتسجل من لوحة التحكم بتاعتهم - لازم يكون Webhook V2
     * عشان شكل التوقيع اللي بنتحقق منه هنا موثّق ومتأكد منه). مقصود يكون طبقة
     * تأكيد إضافية جنب callback/success و callback/error الموجودين — من غير
     * ما نلمسهم أو نغيّر سلوكهم.
     *
     * التحقق هنا بيتم على مرحلتين مستقلتين، مش بديل عن بعض:
     * 1) توقيع الـ webhook نفسه (MyFatoorah-Signature header) بمفتاح الـ
     *    secret بتاع الحساب — عشان نتأكد إن الطلب فعلاً جاي من MyFatoorah
     *    ومحدش زوّره أو غيّر فيه وهو ماشي.
     * 2) حتى لو التوقيع سليم، إحنا لسه بنسأل MyFatoorah بنفسنا عن طريق
     *    GetPaymentStatus (في OrderPaymentReconciler) قبل ما نصلّح حالة أي
     *    أوردر — دفاع إضافي في حالة تسريب الـ secret أو أي مفاجأة تانية.
     */
    public function myFatoorah(Request $request, OrderPaymentReconciler $reconciler)
    {
        $payload = $request->all();

        Log::info('Shottar MyFatoorah webhook received', ['payload' => $payload]);

        if (! $this->hasValidSignature($request, $payload)) {
            return response()->json(['received' => true]);
        }

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

    /**
     * بيتحقق من MyFatoorah-Signature header باستخدام HMAC-SHA256 مع الـ
     * webhook secret بتاع الحساب، بالظبط زي ما موثّق عندهم:
     * 1) رتّب الحقول زي SIGNATURE_FIELDS، والقيمة الفاضية بدل null.
     * 2) كوّن نص واحد "Key=Value,Key2=Value2".
     * 3) HMAC-SHA256 بالـ secret (UTF-8) في binary mode.
     * 4) base64 encode، وقارن بالتوقيع الجاي في الـ header (hash_equals).
     *
     * لو الـ secret لسه مش متظبط في .env (قبل ما يتفعّل من بورتال
     * MyFatoorah)، بنسيب الطلب يعدي (مع تحذير في اللوج) عشان النظام يفضل
     * شغال بطبقة GetPaymentStatus لحد ما يتظبط. بعد ما يتحط الـ secret،
     * أي توقيع غلط أو ناقص بيترفض على طول.
     */
    protected function hasValidSignature(Request $request, array $payload): bool
    {
        $secret = config('services.myfatoorah.webhook_secret');

        if (! $secret) {
            Log::warning('Shottar MyFatoorah webhook: MY_FATOORAH_WEBHOOK_SECRET not configured, skipping signature verification');

            return true;
        }

        $signature = $request->header('MyFatoorah-Signature');

        if (! $signature) {
            Log::warning('Shottar MyFatoorah webhook: missing MyFatoorah-Signature header, rejecting', ['payload' => $payload]);

            return false;
        }

        $data = $payload['Data'] ?? [];

        $orderedString = collect(self::SIGNATURE_FIELDS)
            ->map(fn (string $field) => $field . '=' . (Arr::get($data, $field) ?? ''))
            ->implode(',');

        $expected = base64_encode(hash_hmac('sha256', $orderedString, $secret, true));

        if (! hash_equals($expected, $signature)) {
            Log::warning('Shottar MyFatoorah webhook: signature mismatch, rejecting', [
                'payload' => $payload,
            ]);

            return false;
        }

        return true;
    }
}
