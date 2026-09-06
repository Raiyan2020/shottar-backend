{{--
    سلوك إعادة الترتيب المشترك لكل جداول الداشبورد.

    كان الكود ده متكرر بالحرف في تلات ملفات (grades / lesson_sections /
    course_materials) وكل واحد بينحرف عن التاني مع الوقت. بقى ملف واحد.

    @param string|null $reorderUrl  رابط حفظ ترتيب السحب (اختياري)
--}}

{{-- مستضافة محليًا: الاعتماد على CDN خارجي وقت التشغيل فشل قبل كده على
     شبكات بعض المدرّسين (نفس مشكلة tus في رفع الفيديو). --}}
<script src="{{ asset('dashboard/cdn/Sortable.min.js') }}"></script>
<script>
    // احتياطي لو الملف المحلي مش موجود
    if (typeof Sortable === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"><\/script>');
    }
</script>

<style>
    .reorder-controls {
        display: flex;
        align-items: center;
        gap: .25rem;
        width: max-content;
    }

    /* المسكة هي الوحيدة اللي عليها cursor السحب، والصف نفسه رجع cursor عادي. */
    .reorder-handle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        /* مساحة لمس مريحة على التابلت من غير ما تكبر الصف. */
        min-width: 2rem;
        min-height: 2rem;
        cursor: grab;
        color: #6c757d;
        border-radius: .25rem;
        /* بيمنع المتصفح ياخد اللمسة كسكرول وهو ماسك المسكة. مطبّقة على
           المسكة بس — باقي الصف بيفضل بيتسكرول عادي. */
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    .reorder-handle:hover { background-color: rgba(0, 0, 0, .05); }
    .reorder-handle:active { cursor: grabbing; }

    .reorder-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        min-height: 2rem;
        padding: 0;
        line-height: 1;
    }

    .reorder-btn:disabled { opacity: .4; cursor: not-allowed; }

    .reorder-row-busy { opacity: .55; pointer-events: none; }

    .sortable-ghost { opacity: .4; }
</style>

<script>
    (function () {
        const CSRF = @json(csrf_token());
        const REORDER_URL = @json($reorderUrl ?? null);

        // بيمنع طلبين على نفس الصف في نفس الوقت (ضغط سريع مكرر على السهم).
        let pending = false;

        function tableBody() {
            return document.querySelector('#datatable tbody');
        }

        function toast(icon, title) {
            if (typeof Swal === 'undefined') {
                if (icon === 'error') { alert(title); }
                return;
            }
            Swal.fire({
                toast: true, position: 'top-end', icon: icon, title: title,
                showConfirmButton: false, timer: 2500, timerProgressBar: true,
                background: '#ffffff', color: '#3e2f1c',
                customClass: {
                    popup: 'swal2-gold-shadow',
                    icon: 'swal2-gold-icon',
                    timerProgressBar: 'swal2-gold-progress'
                }
            });
        }

        function reloadTable() {
            if (window.LaravelDataTables && window.LaravelDataTables['datatable']) {
                window.LaravelDataTables['datatable'].ajax.reload(null, false);
            } else {
                window.location.reload();
            }
        }

        // ───────────────────────────── سهم فوق / تحت
        // الحركة بتتحسب على السيرفر: بيدوّر على الجار الحقيقي في الترتيب العام
        // ويبدّل معاه. الواجهة مش بتحسب مراكز ولا بتبدّل صفوف في الـ DOM، عشان
        // الـ pagination والبحث ميأثروش على الترتيب المحفوظ.
        async function move(button, direction) {
            if (pending || button.disabled) return;

            const controls = button.closest('.reorder-controls');
            const row = button.closest('tr');
            if (!controls || !controls.dataset.moveUrl) return;

            pending = true;
            row.classList.add('reorder-row-busy');

            try {
                const response = await fetch(controls.dataset.moveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({ direction: direction })
                });

                if (!response.ok) throw new Error('move failed: ' + response.status);

                const data = await response.json();
                if (data.status !== 'success') throw new Error('move rejected');

                // السيرفر هو مصدر الحقيقة — بنعيد تحميل الجدول عشان المراكز
                // وحالة الأزرار تتحسب من جديد.
                reloadTable();
            } catch (error) {
                console.error(error);
                toast('error', @json(__('general.Could not change the order. Please try again.')));
                reloadTable();
            } finally {
                pending = false;
                row.classList.remove('reorder-row-busy');
            }
        }

        // مفوَّض على الـ body عشان يفضل شغال بعد ما DataTables يعيد رسم الصفوف.
        document.addEventListener('click', function (event) {
            const up = event.target.closest('.js-move-up');
            if (up) { event.preventDefault(); move(up, 'up'); return; }

            const down = event.target.closest('.js-move-down');
            if (down) { event.preventDefault(); move(down, 'down'); }
        });

        // ───────────────────────────── السحب والإفلات
        function initSortable() {
            const body = tableBody();
            if (!body || body.dataset.sortableApplied || !REORDER_URL) return;
            if (typeof Sortable === 'undefined') return;

            body.dataset.sortableApplied = true;

            Sortable.create(body, {
                animation: 150,

                // ده هو الإصلاح الأساسي: السحب بيبدأ من المسكة بس، مش من الصف.
                // قبل كده الصف كله كان مصدر سحب، وعلى شاشة اللمس كانت أي لمسة
                // على زرار Edit/Delete تتحسب بداية سحب. المحاولات القديمة
                // (filter + delay + touchStartThreshold) كانت بتوازن بين
                // الضغطة والسحب بالتخمين وفضلت بتفشل في الاتجاهين.
                handle: '.js-drag-handle',

                ghostClass: 'sortable-ghost',

                onEnd: function () {
                    const order = [];
                    document.querySelectorAll('#datatable tbody tr').forEach(function (row, index) {
                        order.push({ id: row.id, order_by: index + 1 });
                    });

                    fetch(REORDER_URL, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ order: order })
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Unable to save order');
                        return response.json();
                    }).then(function (data) {
                        if (data.status !== 'success' && data.status !== true) {
                            throw new Error('Unable to save order');
                        }
                        // بنعيد التحميل عشان مراكز الأسهم وحالة الحواف تتحدّث.
                        reloadTable();
                    }).catch(function (error) {
                        console.error(error);
                        toast('error', @json(__('general.Could not change the order. Please try again.')));
                        reloadTable();
                    });
                }
            });
        }

        $(document).ready(initSortable);
        $(document).on('draw.dt', function () {
            // DataTables بيستبدل الـ tbody، فالعلامة بتضيع ولازم نعيد التفعيل.
            const body = tableBody();
            if (body && !body.dataset.sortableApplied) initSortable();
        });
    })();
</script>
