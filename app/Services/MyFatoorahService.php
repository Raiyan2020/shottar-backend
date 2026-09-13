<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MyFatoorahService
{
    public function executePayment(array $data, $orderId): string
    {
        $response = Http::withToken(config('services.myfatoorah.token'))
            ->withHeaders([
                'accept' => 'application/json',
                'Content-type' => 'application/json',
            ])
            ->post(config('services.myfatoorah.base_url') . '/ExecutePayment', $data)
            ->json();

        if (!($response['IsSuccess'] ?? false)) {
            Log::error('MyFatoorah ExecutePayment Failed', [
                'order_id' => $orderId,
                'request_data' => $data,
                'response' => $response,
            ]);

            throw ValidationException::withMessages([
                'payment' => 'حدث خطأ أثناء إنشاء الفاتورة، الرجاء المحاولة لاحقًا.',
            ]);
        }

        return $response['Data']['PaymentURL'];

// this when you want to user webhook
//        return [
//            'payment_url' => $response['Data']['PaymentURL'],
//            'invoice_id'  => $response['Data']['InvoiceId'],
//        ];

    }

    /**
     * بيسأل MyFatoorah فعليًا عن حالة الفاتورة بدل ما نصدّق الـ redirect لوحده.
     * $keyType: PaymentId (من paymentId اللي راجع في الكولباك) أو CustomerReference (order_id).
     */
    public function getPaymentStatus(string $key, string $keyType = 'PaymentId'): array
    {
        $response = Http::withToken(config('services.myfatoorah.token'))
            ->withHeaders([
                'accept' => 'application/json',
                'Content-type' => 'application/json',
            ])
            ->post(config('services.myfatoorah.base_url') . '/GetPaymentStatus', [
                'Key' => $key,
                'KeyType' => $keyType,
            ])
            ->json();

        if (!($response['IsSuccess'] ?? false)) {
            Log::error('MyFatoorah GetPaymentStatus Failed', [
                'key' => $key,
                'key_type' => $keyType,
                'response' => $response,
            ]);

            return ['is_paid' => false, 'raw' => $response];
        }

        $invoiceStatus = $response['Data']['InvoiceStatus'] ?? null;

        return [
            'is_paid' => $invoiceStatus === 'Paid',
            'is_failed' => in_array($invoiceStatus, ['Failed', 'Expired', 'Canceled'], true),
            'invoice_status' => $invoiceStatus,
            'invoice_id' => $response['Data']['InvoiceId'] ?? null,
            'invoice_value' => $response['Data']['InvoiceValue'] ?? null,
            'raw' => $response,
        ];
    }
}
