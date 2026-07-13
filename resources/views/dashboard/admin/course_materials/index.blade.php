<x-datatable :dataTable="$dataTable" :title="__('general.lesson_sections')">
    <x-slot:css>
        <style>
            #datatable tbody tr {
                cursor: move;
            }
        </style>
    </x-slot:css>
    <x-slot:header>
        <a href="{{ route(panelPrefix().'.subjects.materials.create',[$subject->id,$type,'section' => $sectionId]) }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
        @if(auth('admin')->user()->hasRole('admin'))
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @else
            <a href="{{ route(panelPrefix().'.subjects.sections.index',$subject->id) }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @endif

    </x-slot:header>

    <x-slot:script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            function initSortable() {
                const tableBody = document.querySelector('#datatable tbody');
                if (!tableBody || tableBody.dataset.sortableApplied) return;

                // منع التهيئة المكررة
                tableBody.dataset.sortableApplied = true;

                Sortable.create(tableBody, {
                    animation: 150,
                    handle: null, // كامل الصف قابل للسحب
                    onEnd: function () {
                        let order = [];
                        document.querySelectorAll('#datatable tbody tr').forEach((row, index) => {
                            order.push({
                                id: row.id,
                                order_by: index + 1
                            });
                        });

                        fetch('{{ route(panelPrefix().'.materials.reorder', [$type,$sectionId ?? null]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order })
                        }).then(res => res.json())
                            .then(res => {
                                if (res.status === 'success') {
                                    console.log('تم تحديث الترتيب بنجاح');
                                } else {
                                    console.error('فشل في التحديث');
                                }
                            });
                    }
                });
            }

            // تأكد من التفعيل بعد تحميل DataTable
            $(document).ready(function () {
                initSortable();
            });

            // إعادة التفعيل بعد كل إعادة رسم للجدول
            $(document).on('draw.dt', function () {
                initSortable();
            });
        </script>

    </x-slot:script>
</x-datatable>
