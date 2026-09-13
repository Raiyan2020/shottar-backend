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
        $type = request()->route('type');
        $subject = request()->route('subject');
        $prefix = panelPrefix();

        // كل درس يتحرك داخل وحدته فقط. في صفحة "كل الدروس" نحسب خريطة
        // مستقلة لكل وحدة حتى لا يبدّل السهم درسًا مع درس من وحدة ثانية.
        $orders = CourseMaterial::query()
            ->where('subject_id', $subject->id)
            ->where('type', $type)
            ->orderBy('order_by')
            ->orderBy('id')
            ->get(['id', 'lesson_section_id'])
            ->groupBy(fn ($material) => $material->lesson_section_id === null ? 'none' : (string) $material->lesson_section_id)
            ->map(function ($materials) {
                return [
                    'positions' => $materials->values()->mapWithKeys(
                        fn ($material, $index) => [$material->id => $index + 1]
                    )->all(),
                    'total' => $materials->count(),
                ];
            });

        return (new EloquentDataTable($query))
            ->addColumn('reorder', function ($material) use ($orders, $type, $subject, $prefix) {
                $sectionKey = $material->lesson_section_id === null
                    ? 'none'
                    : (string) $material->lesson_section_id;
                $order = $orders->get($sectionKey, ['positions' => [], 'total' => 1]);

                return view('dashboard.partials._reorder-cell', [
                    'moveUrl' => route($prefix.'.materials.move', [
                        'type' => $type,
                        'section' => $sectionKey,
                        'material' => $material->id,
                        'subject' => $subject->id,
                    ]),
                    'position' => $order['positions'][$material->id] ?? 1,
                    'total' => $order['total'],
                ])->render();
            })
            ->setRowId('id') // <-- مهم جدًا لتحديد ID للصف
            ->setRowAttr([
                'class' => 'sortable-row', // لسهولة استهدافه من الجافاسكربت
            ])
            ->addColumn('action', function ($material) {

                //action array    if (request()->route('type') == 'lesson') {
                $action = [      'id' => $material->id,
                    'subjectId' => $material->subject_id,  // لتمرير معرّف المادة إلى الروت
                    'nameUrl' =>'material',
                    'routeEdit' => panelPrefix().'.subjects.materials.edit',
                    'routeDelete' => panelPrefix().'.subjects.materials.destroy',
                    'showEditLabel' => true,
                    'editTitle' => __('Edit'),
                    'name' => $material->name_ar,
                ];
                if ($material->type == 'note' && $material->file) {
                    $action['routeView'] = stored_file_url($material->file);
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
            ->rawColumns(['action', 'status','is_free', 'type','url','reorder']);
    }

    public function query(CourseMaterial $model)
    {
        $subject = request()->route('subject') ?? null;
        $type = request()->route('type'); // استلام قيمة الـ type من request (lesson أو note مثلاً)
        $sectionId = request()->get('section');

        return $model->newQuery()
            ->where('subject_id', $subject->id)
            ->where('type', $type)
            ->when(filled($sectionId), fn ($query) => $query->where('lesson_section_id', $sectionId))
            ->orderBy('order_by')
            ->orderBy('id')
            ->with('subject', 'section');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addTableClass('table table-hover')
            ->parameters([
                'lengthMenu' => [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
                'pageLength' => 20,
                // الترتيب الافتراضي بالـ id كان بيلغي ترتيب order_by الجاي من
                // الاستعلام. [] معناها سيب ترتيب الاستعلام زي ما هو.
                'order' => [],
            ]);

    }

    public function getColumns(): array
    {
        $columns = [
            Column::computed('reorder')->title('')->exportable(false)->printable(false)
                ->searchable(false)->orderable(false)->addClass('reorder-col'),
            Column::make('id')->title(__('dataTable.id')),
            localeNameColumn(),
//            Column::make('lesson_section')->title(__('general.lesson_sections')),
        ];
        if (request()->route('type') == 'lesson') {
//            $columns[] = Column::make('duration')->title(__('general.Duration'));
            // 'url' مش عمود حقيقي في الجدول (القيمة محسوبة في editColumn من
            // عمود video)، فلازم يبقى computed مش make() وإلا الـ DataTables
            // بيحاول يعمل WHERE على عمود اسمه url مش موجود أصلاً ويطلع SQL
            // error عند البحث.
            $columns[] = Column::computed('url')->title(__('general.url'))
                ->exportable(false)->printable(false)->searchable(false)->orderable(false);
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
