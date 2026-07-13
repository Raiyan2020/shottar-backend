<?php

namespace App\DataTables;

use App\Models\CourseMaterial;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CourseMaterialDataTable extends DataTable
{
    protected string $statusRoute = '.subjects.materials.toggleStatus';
    protected string $isFreeRoute = '.subjects.materials.toggleIsFree';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->setRowId('id') // <-- مهم جدًا لتحديد ID للصف
            ->setRowAttr([
                'class' => 'sortable-row', // لسهولة استهدافه من الجافاسكربت
            ])
            ->addColumn('action', function ($material) {

                //action array    if (request()->route('type') == 'lesson') {
                $action = [      'id' => $material->id,
                    'subjectId' => $material->subject_id,  // لتمرير معرّف المادة إلى الروت
                    'nameUrl' =>'material',
//                    'routeEdit' => panelPrefix().'.subjects.materials.edit',
                    'routeDelete' => panelPrefix().'.subjects.materials.destroy',
                    'name' => $material->name_ar,];
                if (request()->route('type') != 'lesson') {
                    $action['routeEdit'] = panelPrefix().'.subjects.materials.edit';
                }
                if ($material->type == 'note' && $material->file) {
                    $action['routeView'] = asset($material->file);
                    $action['viewTarget'] = '_blank';
                }

                return view('components.datatable.actions',$action );
            })
            ->editColumn('status', function ($material) {
                return view('components.datatable.status-toggle', [
                    'id' => $material->id,
                    'status' => $material->status,
                    'name' => 'status',
                    'url' => route(panelPrefix().$this->statusRoute, [$material->id]),
                ]);
            })
            //url
            ->editColumn('url', function ($material) {
                if ($material->video) {
                    $details = vimeo_video_details($material->video);

//                    if ($details && isset($details['embed_html'])) {
//                        // نعرض الـ iframe مباشرة
//                        return $details['embed_html'];
//                    }

                    // fallback لو الـ API ما رجعت embed_html
                    return '<a href="' . $material->video . '" target="_blank">' . __('general.View') . '</a>';
                }
                return __('general.NoUrl');
            })
            //is_free
//            ->editColumn('is_free', function ($material) {
//                //html
//                if ($material->is_free) {
//                    return '<span class="badge bg-success">' . __('general.Yes') . '</span>';
//                } else {
//                    return '<span class="badge bg-danger">' . __('general.No') . '</span>';
//                }
//            })
            ->editColumn('is_free', function ($material) {
                return view('components.datatable.status-toggle', [
                    'id' => $material->id,
                    'status' => $material->is_free,
                    'name' => 'is_free',
                    'url' => route(panelPrefix().$this->isFreeRoute, [$material->id]),
                ]);
            })

            ->editColumn('type', function ($material) {
                return $material->type == 'lesson' ? __('general.Lesson') : __('general.Note');
            })
            ->rawColumns(['action', 'status','is_free', 'type','url']);
    }

    public function query(CourseMaterial $model)
    {
        $prefix = auth('admin')->user()->hasRole('admin') ? 'admin' : 'teacher';

        $subject = request()->route('subject') ?? null;
        $type = request()->route('type'); // استلام قيمة الـ type من request (lesson أو note مثلاً)
        $sectionId = request()->get('section');

        if ($prefix == 'admin'){
            $query = $model->newQuery()->where('subject_id', $subject->id)
//                ->where('lesson_section_id',$sectionId)
                ->where('type', $type)
                ->orderBy('order_by');
        }else{
            $query = $model->newQuery()->where('subject_id', $subject->id)
                ->where('lesson_section_id',$sectionId)
                ->where('type', $type)
                ->orderBy('order_by');
        }




        return $query->with('subject','section');
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
        $columns = [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('name_ar')->title(__('dataTable.name_ar')),
            Column::make('name_en')->title(__('dataTable.name_en')),
//            Column::make('lesson_section')->title(__('general.lesson_sections')),
        ];
        if (request()->route('type') == 'lesson') {
//            $columns[] = Column::make('duration')->title(__('general.Duration'));
            //url
            $columns[] = Column::make('url')->title(__('general.url'));
            $columns[] = Column::make('upload_status')->title(__('general.upload_status'));

        }
        $columns[] = Column::make('type')->title(__('dataTable.type'));
        //is_free

        $columns[] = Column::make('is_free')->title(__('general.is_free'));
        $columns[] = Column::make('status')->title(__('dataTable.status'));
        //upload_status
        $columns[] = Column::computed('action')
            ->title(__('dataTable.action'))
            ->exportable(false)
            ->printable(false);
        return $columns;
    }

    protected function filename(): string
    {
        return 'course_materials_' . date('YmdHis');
    }
}
