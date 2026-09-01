
<x-datatable :dataTable="$dataTable" :title="__('general.grade')">
    <x-slot:css>
        <style>
            #datatable tbody tr {
                cursor: move;
            }
        </style>
    </x-slot:css>
    <x-slot:header>
        <a href="{{ route('admin.grades.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
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

                    // ده مش "حد أدنى للسحب" — ده حد "لو الصباع اتحرك أكتر منه *أثناء*
                    // الـ 200ms delay، السحب بيتلغي ويتحول لسكرول". 8px صغيرة جدًا:
                    // أي سحب حقيقي بيبدأ يتحرك فورًا مع اللمس، فكان بيتلغي قبل ما
                    // يبدأ خالص على شاشة اللمس (ده اللي كان بيمنع السحب لفوق تحديدًا،
                    // لأن مفيش سكرول بديل يحصل هناك فيبان إن حاجة مش بتتحرك خالص).
                    touchStartThreshold: 25,
                    onEnd: function () {
                        let order = [];
                        document.querySelectorAll('#datatable tbody tr').forEach((row, index) => {
                            order.push({
                                id: row.id,
                                order_by: index + 1
                            });
                        });

                        fetch('{{ route('admin.grades.reorder') }}', {
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

