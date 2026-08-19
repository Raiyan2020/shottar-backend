<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subject;
use App\Services\MyFatoorahService;
use App\Services\OrderPricingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * §11 — السيرفر هو اللي بيحسب المبلغ.
     *
     * التطبيق لسه يقدر يبعت `total` (للتوافق) بس السيرفر بيتجاهله ويستخدم
     * حسابه هو، ويرجّع التفصيل: subtotal / discount / total.
     * لو `ORDER_STRICT_TOTAL=true` بيرفض أي فرق بدل ما يقبله بصمت.
     */
    public function store(StoreOrderRequest $request, OrderPricingService $pricing)
    {
        $lang = $request->header('lang') === 'ar' ? 'ar' : 'en';
        $user = auth()->user();
        $subjectIds = array_values(array_unique(array_map('intval', $request->input('items', []))));
        $isAllMaterials = (bool) $request->input('is_all_materials', false);
        $couponCode = $request->input('coupon_code');

        $quote = $pricing->quote($subjectIds, $isAllMaterials, $couponCode, $lang);

        if ($quote['items']->isEmpty()) {
            return sendError($lang === 'ar' ? 'لا توجد مواد صالحة في الطلب.' : 'No valid subjects in the order.');
        }

        // كوبون مبعوت وغير صالح → نرفض بصراحة بدل ما الطالب يتحاسب كامل من غير
        // ما يعرف إن الكوبون مش شغّال.
        if ($quote['coupon_error']) {
            return sendError($quote['coupon_error']);
        }

        $clientTotal = $request->filled('total') ? (float) $request->input('total') : null;
        $serverTotal = $quote['total'];

        if (! $pricing->matches($clientTotal, $serverTotal)) {
            Log::warning('Shottar order total mismatch', [
                'user_id' => $user->id,
                'client_total' => $clientTotal,
                'server_total' => $serverTotal,
                'subtotal' => $quote['subtotal'],
                'bundle_discount' => $quote['bundle_discount'],
                'coupon_discount' => $quote['coupon_discount'],
                'items' => $subjectIds,
                'is_all_materials' => $isAllMaterials,
                'coupon_code' => $couponCode,
                'strict' => (bool) config('services.orders.strict_total'),
            ]);

            if (config('services.orders.strict_total')) {
                return sendError(
                    $lang === 'ar'
                        ? 'المبلغ المرسل لا يطابق حساب النظام. يرجى تحديث السلة والمحاولة مرة أخرى.'
                        : 'The submitted total does not match the server calculation. Please refresh your cart and try again.',
                    $this->breakdown($quote),
                    422
                );
            }
        }

        $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));

        if ($serverTotal > 0) {
            if (! $paymentMethod || ! $paymentMethod->status) {
                return sendError($lang === 'ar' ? 'طريقة الدفع غير صالحة.' : 'Invalid payment method.');
            }

            if ($paymentMethod->slug === PaymentMethod::SLUG_APPLE_IAP) {
                return sendError(
                    $lang === 'ar'
                        ? 'استخدم مسار Apple In-App Purchase للشراء على iOS.'
                        : 'Use the Apple In-App Purchase flow on iOS.'
                );
            }
        }

        // الطلب وبنوده بيتعملوا في transaction واحدة بالمبلغ المحسوب على السيرفر.
        $order = DB::transaction(function () use ($request, $user, $quote, $serverTotal, $isAllMaterials) {
            $order = Order::create([
                'user_id' => $user->id,
                'payment_method_id' => $request->input('payment_method_id'),
                'is_all_materials' => $isAllMaterials,
                'status' => 'pending',
                'total' => $serverTotal,
                'discount' => $quote['discount'],
                'discount_amount' => $quote['discount'],
                'coupon_id' => $quote['coupon']?->id,
            ]);

            $order->items()->createMany(
                $quote['items']->map(fn ($subject) => [
                    'subject_id' => $subject->id,
                    'price' => (float) $subject->price,
                ])->all()
            );

            return $order;
        });

        // مبلغ صفر (باقة مجانية أو خصم 100%) → الطلب مدفوع على طول.
        if ($serverTotal <= 0) {
            $order->status = 'paid';
            $order->save();
            $this->markCouponUsed($order);

            return sendResponse(array_merge([
                'success' => true,
                'order_id' => $order->id,
                'payment_url' => null,
                'payment_status' => $order->status,
            ], $this->breakdown($quote)), $lang === 'ar' ? 'تم إنشاء الطلب بنجاح.' : 'Order created successfully.');
        }

        if (PaymentMethod::isOffline($paymentMethod->slug)) {
            return sendResponse(array_merge([
                'success' => true,
                'order_id' => $order->id,
                'payment_url' => null,
                'payment_status' => $order->status,
                'payment_method' => $paymentMethod->slug,
            ], $this->breakdown($quote)), $lang === 'ar'
                ? 'تم إنشاء الطلب. يرجى إتمام الدفع نقدًا لتفعيل المواد.'
                : 'Order created. Please complete cash payment to activate your subjects.');
        }

        try {
            $paymentUrl = (new PaymentService($user, $order))->createInvoice();
        } catch (\Exception $e) {
            Log::error('Shottar order invoice failed', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'total' => $serverTotal,
                'payment_method' => $paymentMethod->slug,
                'error' => $e->getMessage(),
            ]);

            $order->delete();

            return sendError($e->getMessage());
        }

        return sendResponse(array_merge([
            'success' => true,
            'order_id' => $order->id,
            'payment_url' => $paymentUrl,
            'payment_status' => $order->status,
            'payment_method' => $paymentMethod->slug,
        ], $this->breakdown($quote)), $lang === 'ar'
            ? 'تم إنشاء الطلب ورابط الدفع بنجاح.'
            : 'Order and payment link created successfully.');
    }

    /**
     * تفصيل المبلغ اللي التطبيق بيعرضه — الأرقام دي هي المرجع.
     */
    protected function breakdown(array $quote): array
    {
        return [
            'subtotal' => $quote['subtotal'],
            'bundle_discount' => $quote['bundle_discount'],
            'coupon_discount' => $quote['coupon_discount'],
            'discount' => $quote['discount'],
            'total' => $quote['total'],
            'coupon_code' => $quote['coupon_code'],
            'currency' => 'KWD',
        ];
    }

    /**
     * عدّاد استخدام الكوبون بيزيد وقت ما الطلب يتدفع فعلًا، مش وقت إنشائه،
     * عشان محاولة دفع فاشلة متحرقش الكوبون.
     */
    protected function markCouponUsed(Order $order): void
    {
        if (! $order->coupon_id) {
            return;
        }

        Coupon::where('id', $order->coupon_id)->increment('used_count');
    }

    //checkCoupon
    public function checkCoupon(Request $request, OrderPricingService $pricing)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);
        $lang = $request->header('lang', 'ar');

        $coupon= Coupon::where('code', $data['code'])
            ->where('status', '1')
            ->first();

        if (!$coupon) {
            return sendError($lang === 'ar' ? 'القسيمة غير موجودة.' : 'Coupon not found.');
        }

        if ($coupon->isNotYetActive()) {
            return sendError($lang === 'ar' ? 'القسيمة غير مفعّلة بعد.' : 'Coupon is not active yet.');
        }

        if ($coupon->isExpired()) {
            return sendError($lang === 'ar' ? 'القسيمة منتهية الصلاحية.' : 'Coupon has expired.');
        }

        // §11 — بنرجّع كمان حساب السيرفر لنفس السلة لو التطبيق بعت `items`،
        // عشان الرقم اللي بيتعرض على الشاشة يبقى هو نفس الرقم اللي الطلب
        // هيتحاسب بيه. شكل `data` هو هو (كائن الكوبون) + حقول زيادة.
        $payload = $coupon->toArray();
        $payload['usage_limit_reached'] = $coupon->usage_limit !== null
            && (int) $coupon->usage_limit > 0
            && (int) $coupon->used_count >= (int) $coupon->usage_limit;

        if ($payload['usage_limit_reached']) {
            return sendError($lang === 'ar' ? 'تم استنفاد عدد استخدامات القسيمة.' : 'Coupon usage limit reached.');
        }

        if ($request->filled('items')) {
            $items = $request->input('items');

            if (is_string($items)) {
                $items = explode(',', $items);
            }

            $quote = $pricing->quote(
                array_values(array_unique(array_map('intval', (array) $items))),
                (bool) $request->input('is_all_materials', false),
                $coupon->code,
                $lang === 'ar' ? 'ar' : 'en'
            );

            $payload = array_merge($payload, [
                'subtotal' => $quote['subtotal'],
                'bundle_discount' => $quote['bundle_discount'],
                'coupon_discount' => $quote['coupon_discount'],
                'discount' => $quote['discount'],
                'total' => $quote['total'],
                'currency' => 'KWD',
            ]);
        }

        return sendResponse(
            $payload,
            $lang === 'ar' ? 'القسيمة صالحة' : 'Coupon retrieved successfully.'
        );
    }

    //paymentSuccess
    public function paymentSuccess(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);

        // idempotent: لو الكولباك اتنادى مرتين، عدّاد الكوبون ميزيدش مرتين.
        $wasPaid = $order->isPaid();

        $order->status = 'paid';
        $order->payment_reference = $request->paymentId ?? null;
        $order->save();

        if (! $wasPaid) {
            $this->markCouponUsed($order);
        }

        echo 'success';
    }
    //paymentError
    public function paymentError(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);
        $order->status = 'failed';
        $order->payment_reference = $request->paymentId ?? null;
        $order->save();

        echo 'error';
    }

}
