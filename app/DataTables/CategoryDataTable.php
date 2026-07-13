<?php

namespace App\DataTables;

use App\Models\Category;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CategoryDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($category) {
                return view('components.datatable.actions', [
                    'id' => $category->id,
                    'routeEdit' => 'admin.categories.edit',
                    'routeDelete' => 'admin.categories.destroy',
                    'name' => $category->name_ar,
                ]);
            })
            //image
            ->addColumn('image', function ($category) {
                return '<img src="' . asset($category->image) . '" alt="' . $category->name_en . '" class="img-thumbnail" style="width: 50px; height: 50px;">';
            })

            ->addColumn('filter_group', function ($category) {
                return $category->filterGroup ? $category->filterGroup->name_en : __('dataTable.no_filter_group');
            })
            ->rawColumns(['image', 'action', 'status', 'filter_group']);
    }

    public function query(Category $model)
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
            Column::make('image')->title(__('dataTable.image')),
            Column::make('name_en')->title(__('dataTable.name')),
            Column::make('status')->title(__('dataTable.status')),
            Column::make('filter_group')->title(__('filter')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'categories_' . date('YmdHis');
    }
}
