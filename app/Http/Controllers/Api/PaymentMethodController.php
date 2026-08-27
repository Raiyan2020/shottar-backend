<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{

    public function __invoke(Request $request)
    {

        $payments = PaymentMethod::where('status', 1)
            // is_reviewer=true -> وسيلة الدفع كاش فقط، false أو بدون إرسال -> كل الوسائل
            ->when($request->boolean('is_reviewer'), function ($query) {
                return $query->where('slug', PaymentMethod::SLUG_CASH);
            })
            ->get();

        return sendResponse(PaymentMethodResource::collection($payments));

    }
}
