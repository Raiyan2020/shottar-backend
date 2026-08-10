<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::with(['user', 'paymentMethod', 'items.subject'])->latest('id')->get()->map(function ($order) {
            return [
                'ID' => $order->id,
                'User' => $order->user?->name ?? '-',
                'Phone' => $order->user?->phone ?? '-',
                'Package' => $order->packageName('en'),
                'Subjects' => $order->subjectsLabel('en'),
                'Total' => $order->total,
                'Discount' => $order->discount,
                'Payment Method' => $order->paymentMethod?->name_en ?? '-',
                'Status' => $order->status,
                'Created At' => optional($order->created_at)->format('Y-m-d H:i'),
                'Expires At' => optional($order->expires_at)->format('Y-m-d') ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Phone',
            'Package',
            'Subjects',
            'Total',
            'Discount',
            'Payment Method',
            'Status',
            'Created At',
            'Expires At',
        ];
    }
}
