<?php

// app/Exports/OrdersExport.php
namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::with(['user','paymentMethod'])->get()->map(function($order) {
            return [
                'ID' => $order->id,
                'User' => $order->user?->name ?? '-',
                'Total' => $order->total,
                'Discount' => $order->discount,
                'Payment Method' => $order->paymentMethod?->name_en ?? '-',
                'Status' => $order->status === 'paid' ? 'Paid' : 'Unpaid',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Total',
            'Discount',
            'Payment Method',
            'Status',
        ];
    }
}
