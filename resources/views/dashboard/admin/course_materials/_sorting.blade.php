@if(filled($sectionId))
    @php
        $reorderUrl = route($reorderRouteName, [
            'type' => $type,
            'section' => $sectionId,
        ]);
    @endphp
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
                onEnd: function () {
                    const order = [];
                    document.querySelectorAll('#datatable tbody tr').forEach((row, index) => {
                        order.push({
                            id: row.id,
                            order_by: index + 1
                        });
                    });

                    fetch(@json($reorderUrl), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token())
                        },
                        body: JSON.stringify({ order })
                    }).then(response => {
                        if (!response.ok) throw new Error('Unable to save material order');
                        return response.json();
                    }).then(response => {
                        if (response.status !== 'success') {
                            throw new Error('Unable to save material order');
                        }
                    }).catch(error => {
                        window.LaravelDataTables['datatable'].ajax.reload(null, false);
                        console.error(error);
                    });
                }
            });
        }

        $(document).ready(initSortable);
        $(document).on('draw.dt', initSortable);
    </script>
@endif
