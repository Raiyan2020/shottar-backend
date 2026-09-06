{{--
    ترتيب الدروس/الملاحظات داخل وحدة.

    السلوك المشترك (السحب من المسكة + أسهم فوق/تحت) اتنقل لـ
    dashboard.partials._reorder-script عشان الكود ميتكررش في تلات جداول.
    الملف ده فاضل مسؤول عن حاجة واحدة: الترتيب متاح بس لما تكون فيه وحدة
    محددة — من غير وحدة مفيش نطاق ترتيب واضح أصلاً.
--}}
@if(filled($sectionId))
    @include('dashboard.partials._reorder-script', [
        'reorderUrl' => route($reorderRouteName, [
            'type' => $type,
            'section' => $sectionId,
        ]),
    ])
@endif
