<?php

namespace App\DataTables;

use App\Models\Exam;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExamDataTable extends DataTable
{
    protected string $statusRoute = 'admin.subjects.exams.toggleStatus';
    protected string $isFreeRoute = 'admin.subjects.exams.toggleIsFree';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (Exam $exam) {
                $action = [
                    'id' => $exam->id,
                    'subjectId' => $exam->subject_id,
                    'nameUrl' => 'exam',
                    'routeEdit' => 'admin.subjects.exams.edit',
                    'routeDelete' => 'admin.subjects.exams.destroy',
                    'name' => $exam->name_ar,
                ];

                if ($exam->file) {
                    $action['routeView'] = stored_file_url($exam->file);
                    $action['viewTarget'] = '_blank';
                }

                return view('components.datatable.actions', $action);
            })
            ->addColumn('unit', function (Exam $exam) {
                return optional($exam->section)->{'name_'.app()->getLocale()}
                    ?? optional($exam->section)->{app()->isLocale('ar') ? 'name_ar' : 'name_en'}
                    ?? '-';
            })
            ->filterColumn('unit', function ($query, $keyword) {
                $query->whereHas('section', function ($q) use ($keyword) {
                    $q->where('name_ar', 'like', '%'.$keyword.'%')
                        ->orWhere('name_en', 'like', '%'.$keyword.'%');
                });
            })
            ->editColumn('status', function (Exam $exam) {
                return view('components.datatable.status-toggle', [
                    'id' => $exam->id,
                    'status' => $exam->status,
                    'name' => 'status',
                    'url' => route($this->statusRoute, $exam->id),
                ]);
            })
            ->editColumn('is_free', function (Exam $exam) {
                return view('components.datatable.status-toggle', [
                    'id' => $exam->id,
                    'status' => $exam->is_free,
                    'name' => 'is_free',
                    'url' => route($this->isFreeRoute, $exam->id),
                ]);
            })
            ->editColumn('file', function (Exam $exam) {
                if (! $exam->file) {
                    return '-';
                }

                $url = stored_file_url($exam->file);

                return '<a href="' . e($url) . '" target="_blank" class="btn btn-sm btn-outline-primary">PDF</a>';
            })
            ->rawColumns(['action', 'status', 'is_free', 'file']);
    }

    public function query(Exam $model)
    {
        $subject = request()->route('subject');

        return $model->newQuery()
            ->with('section')          // عشان عمود الوحدة ميعملش N+1
            ->where('subject_id', $subject->id)
            ->orderBy('order_by')
            ->orderByDesc('id');
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
            Column::make('name_ar')->title(__('dataTable.name_ar')),
            Column::make('name_en')->title(__('dataTable.name_en')),
            Column::make('unit')->title(__('general.unit'))->orderable(false),
            Column::make('file')->title(__('general.PDF'))->orderable(false)->searchable(false),
            Column::make('is_free')->title(__('general.is_free')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'exams_' . date('YmdHis');
    }
}
