<?php

namespace App\DataTables;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\App;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TeachersDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($teacher) {
                // مثال: منع حذف مستخدم محدد (اختياري)
                if ($teacher->id == 1) {
                    return view('components.datatable.actions', [
                        'id'        => $teacher->id,
                        'routeEdit' => 'admin.teachers.edit',
                        // الاسم موحّد (ما في name_ar/name_en على Admin)
                        'name'      => $teacher->name,
                    ]);
                }

                return view('components.datatable.actions', [
                    'id'          => $teacher->id,
                    'routeEdit'   => 'admin.teachers.edit',
                    'routeDelete' => 'admin.teachers.destroy',
                    'name'        => $teacher->name,
                ]);
            })
            ->addColumn('image', function ($teacher) {
                return view('components.datatable.image', ['photo' => $teacher->image]);
            })
            ->addColumn('created_at', function ($teacher) {
                return $teacher->created_at?->format('Y-m-d H:i');
            })
            ->addColumn('subjects_count', function ($teacher) {
                return (int) ($teacher->teaching_subjects_count ?? 0);
            })
            ->addColumn('subscribers_count', function ($teacher) {
                return (int) ($teacher->subscribers_count ?? 0);
            })
            ->rawColumns(['action', 'image'])
            ->filterColumn('created_at', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('created_at', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('created_at', function ($query, $order) {
                $query->orderBy('created_at', $order);
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Admin $model): QueryBuilder
    {
        return $model->newQuery()
            ->role('teacher')
            ->withCount('teachingSubjects')
            ->select('admins.*')
            ->selectSub(function ($query) {
                $query->from('teacher_subjects as ts')
                    ->join('order_items as oi', 'oi.subject_id', '=', 'ts.subject_id')
                    ->join('orders as o', 'o.id', '=', 'oi.order_id')
                    ->whereColumn('ts.teacher_id', 'admins.id')
                    ->where('o.status', 'paid')
                    ->selectRaw('COUNT(DISTINCT o.user_id)');
            }, 'subscribers_count');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->language([
                'search'      => __('dataTable.Search'),
                'lengthMenu'  => __('dataTable.Show').' _MENU_ '.__('dataTable.Entries'),
                'zeroRecords' => __('dataTable.No matching records found'),
                'info'        => __('dataTable.Showing').' _START_ '.__('dataTable.to').' _END_ '.__('dataTable.of').' _TOTAL_ '.__('dataTable.entries'),
                'infoEmpty'   => __('dataTable.No records available'),
                'infoFiltered'=> __('dataTable.filtered from').' _MAX_ '.__('dataTable.total records'),
                'paginate'    => [
                    'first'    => __('dataTable.First'),
                    'last'     => __('dataTable.Last'),
                    'next'     => __('dataTable.Next'),
                    'previous' => __('dataTable.Previous'),
                ],
            ])
            ->lengthMenu([[5,10,25,50,100,500],[5,10,25,50,100,500]])
            ->addTableClass('table rounded rounded-3 table-hover border');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')
                ->title(__('dataTable.id'))
                ->addClass('text-center align-middle'),

            Column::make('image')
                ->title(__('dataTable.image'))
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center align-middle')
                ->orderable(false)
                ->searchable(false),

            Column::make('email')
                ->title(__('dataTable.email'))
                ->addClass('text-center align-middle'),

            Column::make('name')
                ->title(__('dataTable.name'))
                ->addClass('text-center align-middle'),

            Column::make('subjects_count')
                ->title(__('general.subjects_count'))
                ->addClass('text-center align-middle')
                ->orderable(false)
                ->searchable(false),

            Column::make('subscribers_count')
                ->title(__('general.subscribers_count'))
                ->addClass('text-center align-middle')
                ->orderable(false)
                ->searchable(false),

            Column::make('created_at')
                ->title(__('dataTable.created_at'))
                ->addClass('text-center align-middle'),

            Column::computed('action')
                ->title(__('dataTable.action'))
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center align-middle')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'teachers_' . date('YmdHis');
    }
}
