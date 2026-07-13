<?php

namespace App\DataTables;

use App\Models\Order;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class OrderDataTable extends DataTable
{
    protected string $statusRoute = 'admin.orders.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('user', fn($order) => $order->user?->name ?? '-')
            ->addColumn('payment_method', function($order) {
                if (!$order->paymentMethod ) {
                    if ($order->total >= $order->discount) {
                        return __('dataTable.free');
                    }
                    return '-';
                }
                return $order->paymentMethod?->name_en ?? '-';
            })
            ->editColumn('status', function ($order) {
                if ($order->status === 'paid') {
                    return '<span class="badge bg-success">'.__('dataTable.paid').'</span>';
                } else {
                    return '<span class="badge bg-danger">'.__('dataTable.unpaid').'</span>';
                }
            })

            ->addColumn('action', function ($order) {
                return view('components.datatable.actions', [
                    'id' => $order->id,
//                    'routeEdit' => 'admin.orders.edit',
                    'routeView' => route('admin.orders.show', $order->id), // رابط عرض الطلب
                    'routeDelete' => 'admin.orders.destroy',
                    'name' => $order->id,
                ]);
            })
            ->rawColumns(['status', 'action']);
    }

    public function query(Order $model)
    {
        return $model->newQuery()->with(['user', 'paymentMethod']);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->addTableClass('table table-hover')
            ->buttons([
                Button::make('excel')
                    ->text('Excel') // النص الأساسي
                    ->className('btn btn-success btn-sm'),
                Button::make('print')
                    ->text('Print')
                    ->className('btn btn-primary btn-sm'),
            ]);
//            ->parameters([
//                'initComplete' => 'function(settings, json) {
//                // بعد إنشاء الجدول، ضع أيقونات
//                $(".buttons-excel").html("<i class=\'fas fa-file-excel\'></i> Excel");
//                $(".buttons-print").html("<i class=\'fas fa-print\'></i> Print");
//            }',
//                'dom' => 'Bfrtip', // هذا يضمن ظهور الأزرار
//                'responsive' => true,
//            ]);

    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('user')->title(__('dataTable.user')),
            Column::make('total')->title(__('dataTable.total')),
            Column::make('discount')->title(__('dataTable.discount')),
            Column::make('payment_method')->title(__('dataTable.payment_method')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'orders_' . date('YmdHis');
    }
}
