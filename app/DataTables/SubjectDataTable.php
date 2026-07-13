<?php

namespace App\DataTables;

use App\Models\Subject;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class SubjectDataTable extends DataTable
{
    protected string $statusRoute = 'admin.subjects.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($subject) {
                return view('components.datatable.actions', [
                    'id' => $subject->id,
                    'routeEdit' => 'admin.subjects.edit',
                    'routeDelete' => 'admin.subjects.destroy',
                    'name' => $subject->name_ar,
                    'extraActions' => [
                        [
                            'route' => route('admin.subjects.sections.index', $subject->id),
                            'btn' => 'btn btn-warning',
                            'icon' => 'bi bi-folder-plus',
                            'title' => __('general.sections'), // أيقونة الأقسام
                        ],
                        [
                            'route' => route('admin.subjects.materials.index', ['subject' => $subject->id, 'type' => 'lesson']),
                            'title' => __('general.lessons'),
                            'icon' => 'bi bi-play-circle', // أيقونة الدروس
                            'btn' => 'btn btn-primary',
                        ],
                        [
                            'route' => route('admin.subjects.materials.index', ['subject' => $subject->id, 'type' => 'note']),
                            'title' => __('general.notes'),
                            'icon' => 'bi bi-journal-text', // أيقونة المذكرات
                            'btn' => 'btn btn-success',
                        ],

                    ]

                ]);
            })
            ->editColumn('status', function ($model) {
                return view('components.datatable.status-toggle', [
                    'id' => $model->id,
                    'status' => $model->status,
                    'url' => route($this->statusRoute, $model->id),
                ]);
            })
            ->editColumn('image', function ($subject) {
                return $subject->image
                    ? '<img src="' . asset('storage/' . $subject->image) . '" style="width:50px;height:50px;" class="img-thumbnail" />'
                    : '';
            })
//            ->editColumn('grade_id', function ($subject) {
//                return $subject->grade ? $subject->grade->name_ar : '-';
//            })
//            ->editColumn('study_type_id', function ($subject) {
//                return $subject->studyType ? $subject->studyType->name_ar : '-';
//            })
//            ->editColumn('semester_id', function ($subject) {
//                return $subject->semester ? $subject->semester->name_ar : '-';
//            })
            ->addColumn('details', function ($subject) {
                $grade = $subject->grade ? $subject->grade->name_en : '-';
//                $studyType = $subject->studyType ? $subject->studyType->name_en : '-';
                $semester = $subject->semester ? $subject->semester->name_en : '-';

                return "<strong>Grade:</strong> {$grade}<br>"
//                    ."<strong>Study Type:</strong> {$studyType}<br>"
                    ."<strong>Semester:</strong> {$semester}";
            })
            ->rawColumns(['action', 'status', 'image','details'])

            ->filterColumn('name_ar', function ($query, $keyword) {
                $query->where('name_ar', 'like', "%{$keyword}%");
            })
            ->filterColumn('name_en', function ($query, $keyword) {
                $query->where('name_en', 'like', "%{$keyword}%");
            })
            ->filterColumn('price', function ($query, $keyword) {
                $query->where('price', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->where('status', 'like', "%{$keyword}%");
            })
            ->filterColumn('grade_id', function ($query, $keyword) {
                $query->whereHas('grade', function ($q) use ($keyword) {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                      ->orWhere('name_en', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('study_type_id', function ($query, $keyword) {
                $query->whereHas('studyType', function ($q) use ($keyword) {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                      ->orWhere('name_en', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('semester_id', function ($query, $keyword) {
                $query->whereHas('semester', function ($q) use ($keyword) {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                      ->orWhere('name_en', 'like', "%{$keyword}%");
                });
            });



    }

    public function query(Subject $model)
    {
        return $model->with(['grade', 'studyType', 'semester'])->newQuery();
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->addTableClass('table table-hover datatable--bold');

    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('name_ar')->title(__('dataTable.name_ar')),
            Column::make('name_en')->title(__('dataTable.name_en')),
            Column::computed('details')->title(__('general.Details'))->exportable(false)->printable(false),
//            Column::make('grade_id')->title(__('general.Grade')),
//            Column::make('study_type_id')->title(__('general.Study Type')),
//            Column::make('semester_id')->title(__('general.Semester')),
            Column::make('price')->title(__('general.Price')),
//            Column::make('duration')->title(__('general.Duration')),
//            Column::make('image')->title(__('dataTable.image')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'subjects_' . date('YmdHis');
    }
}
