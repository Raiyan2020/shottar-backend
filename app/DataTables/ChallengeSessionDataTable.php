<?php

namespace App\DataTables;

use App\Models\ChallengeUserSession;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class ChallengeSessionDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('user_name', fn($row) => $row->user->name ?? '-')
            ->addColumn('started_at', fn($row) => $row->started_at ? $row->started_at->format('Y-m-d H:i') : '-')
            ->addColumn('ended_at', fn($row) => $row->ended_at ? $row->ended_at->format('Y-m-d H:i') : '-')
            ->addColumn('score', fn($row) => '<span class="badge bg-primary">' . ($row->score ? round($row->score, 2) : 0) . '%</span>')
            ->addColumn('action', function ($session) {
                return view('components.datatable.actions', [
                    'id' => $session->id,
                    'name' => $session->user->name ?? '-',
                    'extraActions' => [
                        [
                            'route' => route('admin.challenge.sessions.show', [
                                'id' => $session->id
                            ]),
                            'btn' => 'btn btn-outline-info',
                            'title' => __('general.View'),
                            'icon' => 'fa fa-eye'
                        ],
                        // ممكن تضيف إجراءات إضافية لو حبيت مستقبلاً
                    ]
                ]);
            })
            ->rawColumns(['score', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(ChallengeUserSession $model)
    {
        return $model->newQuery()
            ->with('user')
//            ->where('subject_id', $this->subject_id)
            ->latest();
    }

    /**
     * Optional HTML builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('sessions-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters([
                'language' => ['url' => url('/frontEnd/datatables/ar.json')],
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'user_name', 'title' => __('Student')],
            ['data' => 'started_at', 'title' => __('Start Date')],
            ['data' => 'ended_at', 'title' => __('End Date')],
            ['data' => 'score', 'title' => __('Score')],
            ['data' => 'action', 'title' => __('Actions'), 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'ChallengeSessions_' . date('YmdHis');
    }
}
