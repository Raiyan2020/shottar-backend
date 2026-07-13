<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subject;
use App\Services\MyFatoorahService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    //store order
        public function store(StoreOrderRequest $request)
        {
            $data = $request->validated();
            $user = auth()->user();
            $data['user_id'] = auth()->id();
    //        $data['expires_at'] = now()->addDays(30); // Set expiration date to 30 days from now
            $data['status'] = 'pending'; // Default status
            $subjectIds = $request->input('items', []);
            $order = Order::create($data);

            if ($request->has('items')) {
                $items = collect($subjectIds)->map(function ($id) {
                    $subject = Subject::find($id);
                    return [
                        'subject_id' => $id,
                        'price' => $subject->price ?? 0  ,
                    ];
                });

                $order->items()->createMany($items->toArray());
            }

            $total = $order->total;


            // Check if a coupon code is provided
            if ($request->has('coupon_code')) {
                $coupon = Coupon::where('code', $request->input('coupon_code'))
                    ->where('status', '1')
                    ->first();

//                if ($coupon && !$coupon->isExpired()) {
//
//                    // Apply the coupon discount
//                    if ($coupon->type === 'percent') {
//                        $discount = ($total * $coupon->value) / 100;
//                    } else {
//                        $discount = $coupon->value;
//                    }
//
//
//                }
                $order->coupon_id = $coupon->id ?? null;
                $order->discount = $discount ?? 0;
//                $total = $order->total - ($order->discount ?? 0);
//                $order->total = $total;
                $order->save();
            }



//            return $total;
            //if total is 0 then set status to paid
            if ($total <= 0) {
                $order->status = 'paid';
                $order->save();
                return sendResponse([
                    'success' => true,
                    'message' => 'تم إنشاء الطلب بنجاح.',
                    'order_id' => $order->id,
                    'total' => 0,
                    'payment_url' => null
//                    'total' => round($total, 3)   ,
                ]);
            }


            try {
                $paymentUrl = (new PaymentService($user, $order))->createInvoice();
            } catch (\Exception $e) {
                $order->delete();
                return sendError($e->getMessage());
            }
            return sendResponse([
                'success' => true,
                'message' => 'تم إنشاء الطلب ورابط الدفع بنجاح.',
                'payment_url' => $paymentUrl,
                'order_id' => $order->id,
                'total' => round($total, 3),
            ]);

        }

    //checkCoupon
    public function checkCoupon(Request $request)
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

        if ($coupon->isExpired()) {
            return sendError($lang === 'ar' ? 'القسيمة منتهية الصلاحية.' : 'Coupon has expired.');
        }

        return sendResponse(
            $coupon,
            $lang === 'ar' ? 'القسيمة صالحة' : 'Coupon retrieved successfully.'
        );
    }

    //paymentSuccess
    public function paymentSuccess(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);
        $order->status = 'paid';
        $order->payment_reference = $request->paymentId ?? null;
        $order->save();

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
