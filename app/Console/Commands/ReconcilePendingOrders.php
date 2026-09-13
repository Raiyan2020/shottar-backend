<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderPaymentReconciler;
use Illuminate\Console\Command;

class ReconcilePendingOrders extends Command
{
    /**
     * طبقة تأكيد إضافية جنب الـ webhook و callback/success — لو الاتنين فشلوا
     * (العميل قفل المتصفح والـ webhook اتأخر/اترفض)، الأمر ده بيسأل MyFatoorah
     * مباشرة عن أي طلب لسه pending وبعدّى عليه وقت كافي، ويصلّح حالته.
     */
    protected $signature = 'orders:reconcile-pending {--minutes=10 : Only check orders older than this}';

    protected $description = 'Reconcile pending MyFatoorah orders whose payment redirect/webhook never arrived';

    public function handle(OrderPaymentReconciler $reconciler): int
    {
        $minutes = (int) $this->option('minutes');

        $orders = Order::where('status', 'pending')
            ->whereNotNull('payment_method_id')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        $this->info("Checking {$orders->count()} pending order(s)...");

        foreach ($orders as $order) {
            $result = $reconciler->reconcile($order, 'cron');

            if ($result) {
                $this->line("Order #{$order->id}: marked {$result}");
            }
        }

        return self::SUCCESS;
    }
}
