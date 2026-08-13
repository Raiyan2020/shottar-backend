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
                            'icon' => 'bi bi-journal-text',
                            'btn' => 'btn btn-success',
                        ],
                        [
                            'route' => route('admin.subjects.exams.index', $subject->id),
                            'title' => __('general.exams'),
                            'icon' => 'bi bi-file-earmark-pdf',
                            'btn' => 'btn btn-danger',
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
            ->editColumn('name_ar', function ($subject) {
                $image = $subject->coreSubject?->image ?: $subject->image;
                $html = self::imageHtml($image);
                if ($html !== '') {
                    return $html;
                }

                return e($subject->name_ar ?: '-');
            })
            ->addColumn('details', function ($subject) {
                $grade = $subject->grade ? $subject->grade->name_en : '-';
                $semesters = $subject->semesters->isNotEmpty()
                    ? $subject->semesters->pluck('name_en')->implode(', ')
                    : ($subject->semester?->name_en ?? '-');

                return "<strong>Grade:</strong> {$grade}<br>"
                    ."<strong>Semester:</strong> {$semesters}";
            })
            ->addColumn('subscribers_count', function ($subject) {
                return (int) ($subject->subscribers_count ?? 0);
            })
            ->rawColumns(['action', 'status', 'name_ar', 'details'])

            ->filterColumn('name_ar', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                        ->orWhere('name_en', 'like', "%{$keyword}%")
                        ->orWhereHas('coreSubject', function ($cq) use ($keyword) {
                            $cq->where('name_ar', 'like', "%{$keyword}%")
                                ->orWhere('name_en', 'like', "%{$keyword}%");
                        });
                });
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
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('semesters', function ($sq) use ($keyword) {
                        $sq->where('name_ar', 'like', "%{$keyword}%")
                            ->orWhere('name_en', 'like', "%{$keyword}%");
                    })->orWhereHas('semester', function ($sq) use ($keyword) {
                        $sq->where('name_ar', 'like', "%{$keyword}%")
                            ->orWhere('name_en', 'like', "%{$keyword}%");
                    });
                });
            });



    }

    public function query(Subject $model)
    {
        return $model->newQuery()
            ->with(['grade', 'studyType', 'semester', 'semesters', 'coreSubject'])
            ->select('subjects.*')
            ->selectSub(function ($query) {
                $query->from('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('order_items.subject_id', 'subjects.id')
                    ->where('orders.status', 'paid')
                    ->selectRaw('COUNT(DISTINCT orders.user_id)');
            }, 'subscribers_count');
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
            Column::make('name_ar')->title(__('dataTable.image'))->orderable(false),
            Column::make('name_en')->title(__('dataTable.name_en')),
            Column::computed('details')->title(__('general.Details'))->exportable(false)->printable(false),
            Column::make('price')->title(__('general.Price')),
            Column::make('subscribers_count')->title(__('general.subscribers_count'))->orderable(true)->searchable(false),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    /**
     * Subject images are served via /storage/images/... (public disk symlink).
     */
    public static function imageHtml(?string $image): string
    {
        $path = normalize_public_path($image);
        $url = image_url($path);

        if (! $url || ! $path) {
            return '';
        }

        $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            || \Illuminate\Support\Facades\Storage::disk('public')->exists('images/' . basename($path))
            || is_file(public_path($path))
            || is_file(public_path('images/' . basename($path)));

        if (! $exists && ! preg_match('#^https?://#i', $path)) {
            return '';
        }

        return '<img src="' . e($url) . '" style="width:50px;height:50px;" class="img-thumbnail" alt="" />';
    }

    protected function filename(): string
    {
        return 'subjects_' . date('YmdHis');
    }
}
