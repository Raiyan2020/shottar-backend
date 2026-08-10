<?php

namespace App\DataTables;

use App\Models\Order;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class OrderDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        $lang = app()->getLocale();

        return (new EloquentDataTable($query))
            ->addColumn('user', fn ($order) => $order->user?->name ?? '-')
            ->addColumn('phone', fn ($order) => $order->user?->phone ?? '-')
            ->addColumn('package', fn ($order) => $order->packageName($lang))
            ->addColumn('subjects', fn ($order) => $order->subjectsLabel($lang))
            ->addColumn('payment_method', function ($order) {
                if (! $order->paymentMethod) {
                    if ((float) $order->total <= (float) ($order->discount ?? 0)) {
                        return __('dataTable.free');
                    }

                    return '-';
                }

                return app()->getLocale() === 'ar'
                    ? ($order->paymentMethod->name_ar ?? $order->paymentMethod->name_en ?? '-')
                    : ($order->paymentMethod->name_en ?? $order->paymentMethod->name_ar ?? '-');
            })
            ->editColumn('status', function ($order) {
                return match ($order->status) {
                    'paid' => '<span class="badge bg-success">'.__('dataTable.paid').'</span>',
                    'pending' => '<span class="badge bg-warning">'.__('dataTable.pending').'</span>',
                    'failed' => '<span class="badge bg-danger">'.__('dataTable.failed').'</span>',
                    'cancelled' => '<span class="badge bg-secondary">'.__('dataTable.cancelled').'</span>',
                    default => '<span class="badge bg-danger">'.__('dataTable.unpaid').'</span>',
                };
            })
            ->editColumn('created_at', fn ($order) => optional($order->created_at)->format('Y-m-d H:i'))
            ->editColumn('expires_at', fn ($order) => optional($order->expires_at)->format('Y-m-d') ?? '-')
            ->addColumn('action', function ($order) {
                return view('components.datatable.actions', [
                    'id' => $order->id,
                    'routeView' => route('admin.orders.show', $order->id),
                    'routeDelete' => 'admin.orders.destroy',
                    'name' => $order->id,
                ]);
            })
            ->rawColumns(['status', 'action']);
    }

    public function query(Order $model)
    {
        return $model->newQuery()->with(['user', 'paymentMethod', 'items.subject']);
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
                    ->text('Excel')
                    ->className('btn btn-success btn-sm'),
                Button::make('print')
                    ->text('Print')
                    ->className('btn btn-primary btn-sm'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('user')->title(__('dataTable.user')),
            Column::make('phone')->title(__('dataTable.phone')),
            Column::computed('package')->title(__('dataTable.package'))->orderable(false)->searchable(false),
            Column::computed('subjects')->title(__('dataTable.subjects'))->orderable(false)->searchable(false),
            Column::make('total')->title(__('dataTable.total')),
            Column::make('discount')->title(__('dataTable.discount')),
            Column::make('payment_method')->title(__('dataTable.payment_method')),
            Column::make('status')->title(__('dataTable.status')),
            Column::make('created_at')->title(__('dataTable.created_at')),
            Column::make('expires_at')->title(__('dataTable.expires_at')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'orders_' . date('YmdHis');
    }
}
