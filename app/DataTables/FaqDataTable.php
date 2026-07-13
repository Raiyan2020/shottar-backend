<?php

namespace App\DataTables;

use App\Models\Faq;
use Illuminate\Support\Facades\App;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class FaqDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($faq) {
                return view('components.datatable.actions', [
                    'id' => $faq->id,
                    'routeEdit' => 'admin.faqs.edit',
                    'routeDelete' => 'admin.faqs.destroy',
                    'name' => $faq->title_ar,
                ]);
            })
            ->editColumn('status', function ($faq) {
                return view('components.datatable.status-toggle', [
                    'id' => $faq->id,
                    'status' => $faq->status,
                    'url' => route('admin.faqs.toggleStatus', $faq->id),
                ]);
            })
            ->editColumn('question', function ($faq) {
                return App::isLocale('ar') ? $faq->question_ar : $faq->question_en;
            })
            ->editColumn('answer', function ($faq) {
                return App::isLocale('ar') ? $faq->answer_ar : $faq->answer_en;
            })
            ->rawColumns(['action', 'status']);
    }

    public function query(Faq $model)
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
            Column::make('question')->title(__('dataTable.question')),
            Column::make('answer')->title(__('dataTable.answer')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'faqs_' . date('YmdHis');
    }
}
