<?php

namespace App\DataTables;

use App\Models\LessonSection;
use App\Models\Subject;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class LessonSectionDataTable extends DataTable
{
    protected string $statusRoute = '.sections.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->setRowId('id') // <-- مهم جدًا لتحديد ID للصف
            ->setRowAttr([
                'class' => 'sortable-row', // لسهولة استهدافه من الجافاسكربت
            ])
            ->addColumn('action', function ($section) {

                if (auth('admin')->check() && auth('admin')->user()->hasRole('teacher')) {
                    $extraActions = [

                        [
                            'route' => route(panelPrefix().'.subjects.materials.index', [
                                'subject' => $section->subject_id,
                                'type' => 'lesson',
                                'section' => $section->id
                            ]),
                            'title' => __('general.lessons'),
                            'icon' => 'bi bi-play-circle', // أيقونة الدروس
                            'btn' => 'btn btn-primary',
                        ],
                        [
                            'route' => route(panelPrefix().'.subjects.materials.index', [
                                'subject' =>$section->subject_id,
                                'type' => 'note',
                                'section' => $section->id
                            ]),
                            'title' => __('general.notes'),
                            'icon' => 'bi bi-journal-text', // أيقونة المذكرات
                            'btn' => 'btn btn-success',
                        ],
                        [
                            'route' => route(panelPrefix().'.subjects.sections.challenges.index', [
                                'subject' => $section->subject_id,
                                'section' => $section->id,
                            ]),
                            'title' => __('challenges'),
                            'icon' => 'bi bi-question-circle', // أيقونة التحديات
                            'btn' => 'btn btn-warning',
                        ]


                    ];
                }else{
                    $extraActions = [
                    [
                        'route' => route(panelPrefix().'.subjects.sections.challenges.index', [
                            'subject' => $section->subject_id,
                            'section' => $section->id,
                        ]),
                        'title' => __('challenges'),
                        'icon' => 'bi bi-question-circle', // أيقونة التحديات
                        'btn' => 'btn btn-warning',
                    ],
                    ];

                }
                return view('components.datatable.actions', [
                    'id' => $section->id,
                    'subjectId' => $section->subject_id, // مهم لتمريره إلى المسارات
                    'nameUrl' =>'section',
                    'routeEdit' => panelPrefix().'.subjects.sections.edit',
                    'routeDelete' => panelPrefix().'.subjects.sections.destroy',

                    'name' => $section->name_ar,

                    'extraActions' => $extraActions ?? [],
                ]);
            })
            ->editColumn('status', function ($section) {
                return view('components.datatable.status-toggle', [
                    'id' => $section->id,
                    'status' => $section->status,
                    'url' => route(panelPrefix().$this->statusRoute, [$section->id]),
                ]);
            })
            ->addColumn('subject', function ($section) {
                return $section->subject?->name_ar ?? '-';
            })
            ->rawColumns(['action', 'status']);
    }

    public function query(LessonSection $model)
    {
        $subject = request()->route('subject');
        return $model->newQuery()
            ->where('subject_id', $subject->id)
            ->with('subject')

            ->orderBy('order_by');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->addTableClass('table table-hover')
            ->parameters([
                'lengthMenu' => [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
                'pageLength' => 20,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('name_ar')->title(__('dataTable.name_ar')),
            Column::make('name_en')->title(__('dataTable.name_en')),
            Column::make('subject')->title(__('general.subject')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')
                ->title(__('dataTable.action'))
                ->exportable(false)
                ->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'lesson_sections_' . date('YmdHis');
    }
}
