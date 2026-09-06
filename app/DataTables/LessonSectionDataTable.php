<?php

namespace App\DataTables;

use App\Models\LessonSection;
use App\Services\RowOrderService;
use App\Models\Subject;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class LessonSectionDataTable extends DataTable
{
    protected string $statusRoute = '.sections.toggleStatus';

    /** نطاق ترتيب الوحدات: وحدات نفس المادة بس. */
    protected function orderScope(): \Closure
    {
        $subject = request()->route('subject');

        return fn () => LessonSection::query()->where('subject_id', $subject->id);
    }

    public function dataTable($query): EloquentDataTable
    {
        $order = app(RowOrderService::class)->positionMap($this->orderScope());
        $subject = request()->route('subject');
        $prefix = panelPrefix();

        return (new EloquentDataTable($query))
            ->addColumn('reorder', function ($section) use ($order, $subject, $prefix) {
                return view('dashboard.partials._reorder-cell', [
                    'moveUrl' => route($prefix.'.sections.move', [$subject->id, $section->id]),
                    'position' => $order['positions'][$section->id] ?? 1,
                    'total' => $order['total'],
                ])->render();
            })
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
                            'icon' => 'bi bi-play-btn-fill',
                            'btn' => 'btn btn-primary',
                            'showLabel' => true,
                        ],
                        [
                            'route' => route(panelPrefix().'.subjects.materials.index', [
                                'subject' =>$section->subject_id,
                                'type' => 'note',
                                'section' => $section->id
                            ]),
                            'title' => __('general.notes'),
                            'icon' => 'bi bi-file-earmark-text-fill',
                            'btn' => 'btn btn-success',
                            'showLabel' => true,
                        ],
                        [
                            'route' => route(panelPrefix().'.subjects.sections.challenges.index', [
                                'subject' => $section->subject_id,
                                'section' => $section->id,
                            ]),
                            'title' => __('general.Challenges'),
                            'icon' => 'bi bi-trophy-fill',
                            'btn' => 'btn btn-warning',
                            'showLabel' => true,
                        ]


                    ];
                }else{
                    $extraActions = [
                    [
                        'route' => route(panelPrefix().'.subjects.sections.challenges.index', [
                            'subject' => $section->subject_id,
                            'section' => $section->id,
                        ]),
                        'title' => __('general.Challenges'),
                        'icon' => 'bi bi-trophy-fill',
                        'btn' => 'btn btn-warning',
                        'showLabel' => true,
                    ],
                    ];

                }
                return view('components.datatable.actions', [
                    'id' => $section->id,
                    'subjectId' => $section->subject_id, // مهم لتمريره إلى المسارات
                    'nameUrl' =>'section',
                    'routeEdit' => panelPrefix().'.subjects.sections.edit',
                    'routeDelete' => panelPrefix().'.subjects.sections.destroy',
                    'showEditLabel' => true,
                    'editTitle' => __('Edit'),

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
                $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

                return $section->subject?->{$nameColumn} ?? '-';
            })
            ->rawColumns(['action', 'status', 'reorder']);
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
            ->addTableClass('table table-hover')
            ->parameters([
                // Keep the order returned by the query (order_by). DataTables'
                // default ID sorting used to undo the drag-and-drop order.
                'order' => [],
                // Reordering must include the complete list, not only one page.
                'paging' => false,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('reorder')->title('')->exportable(false)->printable(false)
                ->searchable(false)->orderable(false)->addClass('reorder-col'),
            Column::make('id')->title(__('dataTable.id')),
            localeNameColumn(),
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
