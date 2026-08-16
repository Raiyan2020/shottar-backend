<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppleIapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppleIapController extends Controller
{
    public function verify(Request $request, AppleIapService $iap)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|string|max:191',
            'receipt' => 'required|string',
            'transaction_id' => 'nullable|string|max:191',
            'source' => 'nullable|string|in:purchase,restore',
        ]);

        if ($validator->fails()) {
            return sendResponse([
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ]);
        }

        try {
            $result = $iap->verifyAndGrant(
                $request->user(),
                $request->input('product_id'),
                $request->input('receipt'),
                $request->input('transaction_id'),
                $request->input('source', 'purchase')
            );
        } catch (\Throwable $e) {
            Log::error('Apple IAP verify failed', [
                'user_id' => $request->user()?->id,
                'product_id' => $request->input('product_id'),
                'transaction_id' => $request->input('transaction_id'),
                'error' => $e->getMessage(),
            ]);

            return sendResponse([
                'success' => false,
                'message' => 'تعذر التحقق من عملية الشراء',
            ]);
        }

        return sendResponse($result);
    }

    public function notifications(Request $request, AppleIapService $iap)
    {
        $signedPayload = $request->input('signedPayload');
        if (! is_string($signedPayload) || $signedPayload === '') {
            return response()->json(['status' => false], 400);
        }

        try {
            $iap->handleNotification($signedPayload);
        } catch (\Throwable $e) {
            Log::error('Apple IAP notification failed', ['error' => $e->getMessage()]);

            return response()->json(['status' => false], 400);
        }

        return response()->json(['status' => true]);
    }
}
