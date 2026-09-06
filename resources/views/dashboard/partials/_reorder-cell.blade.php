{{--
    خلية التحكم في الترتيب — بتتحط كأول عمود في أي جدول قابل لإعادة الترتيب.

    [ ⠿ ] [ ↑ ] [ ↓ ]

    المسكة (⠿) هي الوحيدة اللي بتبدأ السحب (SortableJS متظبط على
    handle: '.js-drag-handle')، فأي لمسة على باقي الصف أو على أزرار
    التعديل/الحذف بتفضل ضغطة عادية على شاشة اللمس.

    $position / $total مراكز عامة جاية من السيرفر — مش ترتيب الصف في الصفحة
    الحالية — عشان أزرار الحافة تبقى صح مع الـ pagination والبحث.

    @param string $moveUrl   رابط النقل
    @param int    $position  المركز العام (يبدأ من 1)
    @param int    $total     إجمالي الصفوف في نفس نطاق الترتيب
--}}
<div class="reorder-controls" data-move-url="{{ $moveUrl }}" data-position="{{ $position }}" data-total="{{ $total }}">
    <span class="reorder-handle js-drag-handle" role="button" tabindex="-1"
          title="{{ __('general.Drag to reorder') }}"
          aria-label="{{ __('general.Drag to reorder') }}">
        <i class="bi bi-grip-vertical" aria-hidden="true"></i>
    </span>

    <button type="button" class="btn btn-sm btn-outline-secondary reorder-btn js-move-up"
            title="{{ __('general.Move up') }}" aria-label="{{ __('general.Move up') }}"
            @disabled($position <= 1)>
        <i class="bi bi-arrow-up" aria-hidden="true"></i>
    </button>

    <button type="button" class="btn btn-sm btn-outline-secondary reorder-btn js-move-down"
            title="{{ __('general.Move down') }}" aria-label="{{ __('general.Move down') }}"
            @disabled($position >= $total)>
        <i class="bi bi-arrow-down" aria-hidden="true"></i>
    </button>
</div>
