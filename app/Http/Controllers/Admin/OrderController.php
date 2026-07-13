<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OrderDataTable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function index(OrderDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.orders.index');
    }

    public function show(Order $order)
    {
        $order->load('user', 'items.subject');
        return view('dashboard.admin.orders.show', compact('order'));
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['status' => true]);
    }

    public function exportExcel()
    {
        return Excel::download(new OrdersExport, 'orders.xlsx');
    }
}
