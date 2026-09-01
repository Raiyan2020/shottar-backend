@php
    $prefix = auth('admin')->user()->hasRole('admin') ? 'admin' : 'teacher';
@endphp
<x-datatable :dataTable="$dataTable" :title="__('general.lesson_sections')">
    <x-slot:css>
        <style>
            #datatable tbody tr {
                cursor: move;
            }
        </style>
    </x-slot:css>
    <x-slot:header>
        <a href="{{ route($prefix.'.subjects.sections.create', $subject->id) }}"
           type="button"
           class="btn btn-primary waves-effect waves-light">
            {{ __('dataTable.add') }}
        </a>
        @if(auth('admin')->user()->hasRole('admin'))
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @endif
    </x-slot:header>

    <x-slot:script>
        {{-- مستضافة محليًا: الاعتماد على CDN خارجي وقت التشغيل فشل قبل كده على
             شبكات بعض المدرّسين (نفس مشكلة tus في رفع الفيديو). --}}
        <script src="{{ asset('dashboard/cdn/Sortable.min.js') }}"></script>
        <script>
            // احتياطي لو الملف المحلي مش موجود
            if (typeof Sortable === 'undefined') {
                document.write('<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"><\/script>');
            }
        </script>
        <script>
            function initSortable() {
                const tableBody = document.querySelector('#datatable tbody');
                if (!tableBody || tableBody.dataset.sortableApplied) return;

                // منع التهيئة المكررة
                tableBody.dataset.sortableApplied = true;

                Sortable.create(tableBody, {
                    animation: 150,

                    // الصف كله قابل للسحب، بس الأزرار والروابط مستثناة — من غير كده
                    // SortableJS بيخطف الـ touchstart على التابلت ويعتبر أي لمسة بداية
                    // سحب، فأزرار Edit/Lessons/Notes مكانتش بتضغط خالص (كانت شغالة
                    // على الديسكتوب لأن الماوس بيفرّق بين الضغط والسحب).
                    filter: 'a, button, input, select, textarea, label, .btn',

                    // مهم: من غير دي SortableJS بيعمل preventDefault على العناصر
                    // المستثناة وبيمنع الضغطة نفسها.
                    preventOnFilter: false,

                    // على اللمس بس: لازم ضغطة مستمرة 200ms قبل ما السحب يبدأ،
                    // فاللمسة السريعة تفضل ضغطة عادية.
                    delay: 200,
                    delayOnTouchOnly: true,

                    // حركة أقل من 8px متبدأش سحب (اهتزاز الصوابع الطبيعي)
                    touchStartThreshold: 8,
                    onEnd: async function () {
                        let order = [];
                        document.querySelectorAll('#datatable tbody tr').forEach((row, index) => {
                            order.push({
                                id: row.id,
                                order_by: index + 1
                            });
                        });

                        try {
                            const response = await fetch('{{ route($prefix.'.sections.reorder', $subject->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ order })
                            });

                            if (!response.ok) throw new Error('Unable to save section order');
                        } catch (error) {
                            // Restore the persisted order if saving failed.
                            window.LaravelDataTables['datatable'].ajax.reload(null, false);
                            console.error(error);
                        }
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
