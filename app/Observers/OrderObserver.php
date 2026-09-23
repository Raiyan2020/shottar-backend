<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\UserNotifier;

/**
 * بيبعت إشعار "معاملات" لما أي أوردر يوصل لحالة paid — مهما كان مصدر
 * الدفع (MyFatoorah callback, reconciler cron, أوردر بقيمة صفر, Apple IAP).
 * ده بيغطي كل نقاط تحويل الحالة من غير ما نكرر النداء في كل واحدة منها.
 */
class OrderObserver
{
    public function __construct(private readonly UserNotifier $notifier) {}

    public function created(Order $order): void
    {
        $this->notifyIfPaid($order);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            $this->notifyIfPaid($order);
        }
    }

    private function notifyIfPaid(Order $order): void
    {
        if ($order->status !== 'paid') {
            return;
        }

        $user = $order->user;

        if (! $user) {
            return;
        }

        $this->notifier->notify(
            $user,
            'transaction',
            'تم تأكيد اشتراكك بنجاح',
            'تم الدفع وتفعيل المواد اللي اشتركت فيها. بالتوفيق في مذاكرتك!',
            'Your subscription is confirmed',
            'Payment received and your subjects are now active. Good luck studying!',
            $order->id,
        );
    }
}
