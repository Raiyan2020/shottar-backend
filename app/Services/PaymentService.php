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

        if (PaymentMethod::isOffline($paymentMethod->slug)) {
            throw new \InvalidArgumentException('Offline payment methods do not use an external gateway.');
        }

        if ($paymentMethod->slug === PaymentMethod::SLUG_APPLE_IAP) {
            throw new \InvalidArgumentException('Apple IAP is handled via the verify endpoint, not MyFatoorah.');
        }

        $paymentMethodId = PaymentMethod::ALL_METHODS[$paymentMethod->slug] ?? 1;

        // MyFatoorah بيرفض CustomerMobile لو أطول من 11 حرف، وكود الدولة بيتبعت
        // في حقل منفصل. كنا بنبعت الرقم كامل (201068286020 = 12 رقم) فكل فاتورة
        // كانت بتترفض بـ "must be a string with a maximum length of 11".
        [$countryCode, $mobile] = $this->splitCustomerMobile();

        $invoiceData = [
            'InvoiceValue' => round($this->order->total, 3),
            'PaymentMethodId' => $paymentMethodId,
            'CustomerName' => $this->user->name ?? 'User' . $this->user->id,
            'CustomerEmail' => $this->user->email ?? 'info@shottar.com',
            'CallBackUrl' => route('ordersSuccess', ['order_id' => $this->order->id]),
            'ErrorUrl' => route('ordersError', ['order_id' => $this->order->id]),
            'CustomerReference' => $this->order->id,
            'Language' => app()->getLocale(),
            'DisplayCurrencyIso' => 'KWD',
        ];

        if ($mobile !== '') {
            $invoiceData['CustomerMobile'] = $mobile;

            if ($countryCode !== '') {
                $invoiceData['MobileCountryCode'] = '+' . $countryCode;
            }
        }

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

    /**
     * يفصل رقم العميل لكود دولة + رقم محلي بحد أقصى 11 رقم زي ما MyFatoorah عايزة.
     *
     * @return array{0: string, 1: string} [كود الدولة بأرقام بس، الرقم المحلي]
     */
    protected function splitCustomerMobile(): array
    {
        $countryCode = preg_replace('/\D+/', '', (string) $this->user->country_code);
        $mobile = preg_replace('/\D+/', '', (string) $this->user->phone_not_code);

        // لو phone_not_code فاضي (مستخدمين قدام)، نشيل كود الدولة من الرقم الكامل.
        if ($mobile === '') {
            $digits = preg_replace('/\D+/', '', (string) $this->user->phone);

            $mobile = ($countryCode !== '' && str_starts_with($digits, $countryCode))
                ? substr($digits, strlen($countryCode))
                : $digits;
        }

        // لو لسه مش قادرين نطلّع رقم مظبوط (مستخدم من غير country_code مثلاً)،
        // منبعتش الرقم خالص. CustomerMobile اختياري عند MyFatoorah، فالفاتورة
        // هتتعمل عادي — أحسن من إننا نبعت رقم مقصوص غلط وترفض الفاتورة كلها.
        if ($mobile === '' || strlen($mobile) > 11) {
            return ['', ''];
        }

        return [$countryCode, $mobile];
    }
}
