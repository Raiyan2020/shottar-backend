{{--
    ترتيب الدروس/الملاحظات داخل وحدة.

    السلوك المشترك (السحب من المسكة + أسهم فوق/تحت) اتنقل لـ
    dashboard.partials._reorder-script عشان الكود ميتكررش في تلات جداول.
    السحب متاح عند فتح وحدة محددة فقط، أما أسهم كل صف فتظل متاحة في صفحة كل
    الدروس وتحرّك الدرس داخل وحدته من غير خلط الوحدات ببعض.
--}}
@include('dashboard.partials._reorder-script', [
    'reorderUrl' => filled($sectionId)
        ? route($reorderRouteName, [
            'type' => $type,
            'section' => $sectionId,
            'subject' => $subject->id,
        ])
        : null,
])
