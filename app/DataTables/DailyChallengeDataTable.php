<?php

namespace App\DataTables;

use App\Models\DailyChallenge;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class DailyChallengeDataTable extends DataTable
{
    protected string $statusRoute = 'admin.daily-challenges.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($challenge) {
                return view('components.datatable.actions', [
                    'id' => $challenge->id,
                    'routeEdit' => 'admin.daily-challenges.edit',
                    'routeDelete' => 'admin.daily-challenges.destroy',
                    'name' => $challenge->title_ar,
                ]);
            })
            ->addColumn('grade', function ($challenge) {
                return optional($challenge->grade)->name_ar ?? '-';
            })
            ->addColumn('semester', function ($challenge) {
                return optional($challenge->semester)->name_ar ?? '-';
            })
            ->addColumn('subject', function ($challenge) {
                return optional($challenge->subject)->name_ar ?? '-';
            })
            ->editColumn('challenge_date', function ($challenge) {
                return optional($challenge->challenge_date)->format('Y-m-d');
            })
            ->editColumn('status', function ($model) {
                return view('components.datatable.status-toggle', [
                    'id' => $model->id,
                    'status' => $model->status,
                    'url' => route($this->statusRoute, $model->id),
                ]);
            })
            ->rawColumns(['action', 'status', 'grade', 'semester', 'subject']);
    }

    public function query(DailyChallenge $model)
    {
        return $model->with(['grade', 'semester', 'subject'])->newQuery();
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
            Column::make('title_ar')->title(__('general.Question')),
            Column::computed('subject')->title(__('general.subjects')),
            Column::computed('grade')->title(__('general.Grade')),
            Column::computed('semester')->title(__('general.Semester')),
            Column::make('challenge_date')->title(__('general.challenge_date')),
            Column::make('reward_points')->title(__('general.reward_points')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'daily_challenges_' . date('YmdHis');
    }
}
