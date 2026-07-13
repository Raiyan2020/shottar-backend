<?php

namespace App\DataTables;

use App\Models\ChallengeQuestion;
use Yajra\DataTables\Services\DataTable;

class ChallengeQuestionDataTable extends DataTable
{



    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($challenge) {
                return view('components.datatable.actions', [
                    'id' => $challenge->id,
                    'name' => $challenge->title_ar,
                    'extraActions' => [
                        [
                            'route' => route(panelPrefix().'.subjects.sections.challenges.edit', [
                                'subject' => $challenge->section->subject_id,
                                'section' => $challenge->lesson_section_id,
                                'challenge' => $challenge->id
                            ]),
                            'btn' => 'btn btn-info',
                            'title' => __('Edit'),
                            'icon' => 'bi bi-pencil-fill'
                        ],
                        [
                            'route' => route(panelPrefix().'.subjects.sections.challenges.destroy', [
                                'subject' => $challenge->section->subject_id,
                                'section' => $challenge->lesson_section_id,
                                'challenge' => $challenge->id
                            ]),
                            'btn' => 'btn btn-danger delete-btn',
                            'title' => __('Delete'),
                            'icon' => 'bi bi-trash-fill'
                        ]
                    ]
                ]);
            });

    }

    public function query(ChallengeQuestion $model)
    {
        return $model->newQuery()
            ->where('lesson_section_id', $this->lesson_section_id)
            ->with('section')
            ->select('challenge_questions.*');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['title' => __('dataTable.action')]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'id', 'title' => '#'],
            ['data' => 'title_ar', 'title' => 'title AR'],
            ['data' => 'title_en', 'title' => 'title EN'],
        ];
    }
}
