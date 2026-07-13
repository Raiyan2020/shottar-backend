<?php

namespace App\DataTables;

use App\Models\Coupon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CouponsDataTable extends DataTable
{


    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($coupon) {
                return view('components.datatable.actions', [
                    'id' => $coupon->id,
                    'routeEdit' => 'admin.coupons.edit',
                    'routeDelete' => 'admin.coupons.destroy',
                    'name' => $coupon->code,
                ]);
            })
            ->editColumn('status', function ($coupon) {
                return view('components.datatable.status-toggle', [
                    'id' => $coupon->id,
                    'status' => $coupon->status,
                    'url' => route('admin.coupons.toggleStatus', $coupon->id),
                ]);
            })
            ->editColumn('discount_type', function ($coupon) {
                return $coupon->discount_type === 'percent' ? __('Percentage') : __('Fixed Amount');
            })
            ->editColumn('expires_at', function ($coupon) {
                return $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : __('No Expiry');
            })
            ->rawColumns(['action', 'status']);
    }

    public function query(Coupon $model)
    {
        return $model->newQuery();
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->addTableClass('table table-hover');
    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('code')->title(__('dataTable.code')),
            Column::make('type')->title(__('dataTable.discount_type')),
            Column::make('value')->title(__('dataTable.discount_value')),
            Column::make('expires_at')->title(__('dataTable.expires_at')),
            //used_count
            Column::make('used_count')->title(__('dataTable.used_count')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'coupons_' . date('YmdHis');
    }
}
