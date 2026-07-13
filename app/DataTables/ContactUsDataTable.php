<?php

namespace App\DataTables;

use App\Models\ContactUs;
use Illuminate\Support\Facades\App;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class ContactUsDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($contactUs) {
                return view('components.datatable.actions', [
                    'id' => $contactUs->id,
                    'routeDelete' => 'admin.contact-us.destroy',
                    'name' =>  $contactUs->name,
                ]);
            })
            //user
            ->addColumn('user', function ($contactUs) {
                return $contactUs->user ? $contactUs->user->name : '-';
            })

            ->rawColumns(['action']);

    }

    public function query(ContactUs $model)
    {
        return $model->newQuery()->with('user');
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
            Column::make('user')->title(__('dataTable.user')),
            Column::make('name')->title(__('dataTable.name')),
            Column::make('phone')->title(__('dataTable.phone')),
            Column::make('message')->title(__('dataTable.message')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'cities_' . date('YmdHis');
    }
}

