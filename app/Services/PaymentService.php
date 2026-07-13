<?php
namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;
use App\Services\MyFatoorahService;

class PaymentService
{
    protected $user;
    protected $order;

    public function __construct($user, Order $order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    public function createInvoice()
    {
        $paymentMethod = PaymentMethod::find($this->order->payment_method_id);

        if (!$paymentMethod) {
            throw new \Exception('طريقة الدفع غير موجودة.');
        }

        $paymentMethodId = PaymentMethod::ALL_METHODS[$paymentMethod->slug] ?? 1;

        $phone = preg_replace('/[^0-9]/', '', $this->user->phone);

        $invoiceData = [
            'InvoiceValue' => round($this->order->total, 3),
            'PaymentMethodId' => $paymentMethodId,
            'CustomerName' => $this->user->name ?? 'User' . $this->user->id,
            'CustomerEmail' => $this->user->email ?? 'info@shottar.com',
            'CustomerMobile' => $phone,
            'CallBackUrl' => route('ordersSuccess', ['order_id' => $this->order->id]),
            'ErrorUrl' => route('ordersError', ['order_id' => $this->order->id]),
            'CustomerReference' => $this->order->id,
            'Language' => app()->getLocale(),
            'DisplayCurrencyIso' => 'KWD',
        ];

        try {
            return app(MyFatoorahService::class)->executePayment($invoiceData, $this->order->id);
        } catch (\Throwable $e) {
            Log::error('MyFatoorah payment error', [
                'message' => $e->getMessage(),
                'order_id' => $this->order->id,
                'invoiceData' => $invoiceData,
            ]);

            throw new \Exception('فشل إنشاء الفاتورة. الرجاء المحاولة لاحقًا.');
        }
    }
}
