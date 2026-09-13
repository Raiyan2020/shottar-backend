<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * منطق مشترك لتأكيد/تصليح حالة الطلب من MyFatoorah — بيستخدمه الـ webhook
 * وendpoint الـ check-payment (OrderController@checkPaymentStatus). مقصود إنه
 * منفصل تمامًا عن OrderController@paymentSuccess/@paymentError الموجودين، من
 * غير ما يمسّهم، فهو طبقة إضافية للتأكد بس، مش بديل ليهم.
 */
class OrderPaymentReconciler
{
    public function __construct(protected MyFatoorahService $myFatoorah)
    {
    }

    /**
     * بيسأل MyFatoorah عن حالة الطلب فعليًا (مش بيصدّق أي بيانات جاية من بره)
     * وبيصلّح حالته لو لسه pending. بيرجّع null لو الطلب already paid/failed
     * (يعني معملش حاجة)، أو الحالة الجديدة لو غيّرها.
     */
    public function reconcile(Order $order, string $source): ?string
    {
        // فحص سريع من غير lock — لو الطلب already paid/failed مفيش داعي حتى
        // نسأل MyFatoorah أو نمسك transaction.
        if ($order->status !== 'pending') {
            return null;
        }

        // بنفضّل نسأل بـ InvoiceId الحقيقي (فريد ومضمون من MyFatoorah) لو
        // متخزّن على الأوردر. CustomerReference نص حر ممكن يتكرر مع فواتير
        // تجار تانيين في sandbox المشترك — حصل فعلاً في الاختبار: order_id="3"
        // اتلاقى matched مع فاتورة حد تاني خالص كانت Paid. من غير InvoiceId
        // منسيبش الأوردر يتصلّح خالص لحد ما نتأكد إضافي (شوف تحت).
        if ($order->myfatoorah_invoice_id) {
            $status = $this->myFatoorah->getPaymentStatus($order->myfatoorah_invoice_id, 'InvoiceId');
        } else {
            $status = $this->myFatoorah->getPaymentStatus((string) $order->id, 'CustomerReference');

            // تأكيد إضافي وإحنا مضطرين نستخدم CustomerReference (أوردرات قديمة
            // اتعملت قبل ما نبدأ نخزّن invoice_id): لازم قيمة الفاتورة تقارب
            // مبلغ الأوردر، وإلا نعتبرها مش نفس الفاتورة ونتجاهلها كإجراء أمان.
            if (($status['is_paid'] ?? false) || ($status['is_failed'] ?? false)) {
                $invoiceValue = (float) ($status['invoice_value'] ?? 0);

                if (abs($invoiceValue - (float) $order->total) > 0.01) {
                    Log::warning('Shottar reconcile: CustomerReference matched but InvoiceValue mismatch — likely a collision with another merchant\'s invoice, ignoring', [
                        'order_id' => $order->id,
                        'order_total' => $order->total,
                        'invoice_value' => $invoiceValue,
                        'source' => $source,
                    ]);

                    return null;
                }
            }
        }

        if (! ($status['is_paid'] ?? false) && ! ($status['is_failed'] ?? false)) {
            return null;
        }

        // لحظة الكتابة بس بتتم جوه lock + إعادة فحص الحالة، عشان لو الـ
        // webhook والـ cron (أو الاتنين) جم في نفس اللحظة على نفس الأوردر،
        // واحد بس هو اللي يكتب ويزود الكوبون؛ التاني هيلاقي الحالة اتغيرت
        // وهيطلع من غير ما يعمل حاجة.
        return DB::transaction(function () use ($order, $status, $source) {
            $locked = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'pending') {
                return null;
            }

            if ($status['is_paid'] ?? false) {
                $this->markPaid($locked, $status['invoice_id'] ?? null, $source);

                return 'paid';
            }

            $this->markFailed($locked, $status['invoice_id'] ?? null, $source);

            return 'failed';
        });
    }

    protected function markPaid(Order $order, ?string $paymentReference, string $source): void
    {
        $order->status = 'paid';

        if ($paymentReference) {
            $order->payment_reference = $paymentReference;
        }

        $order->save();

        if ($order->coupon_id) {
            Coupon::where('id', $order->coupon_id)->increment('used_count');
        }

        Log::info('Shottar order reconciled to paid', ['order_id' => $order->id, 'source' => $source]);
    }

    protected function markFailed(Order $order, ?string $paymentReference, string $source): void
    {
        $order->status = 'failed';

        if ($paymentReference) {
            $order->payment_reference = $paymentReference;
        }

        $order->save();

        Log::info('Shottar order reconciled to failed', ['order_id' => $order->id, 'source' => $source]);
    }
}
