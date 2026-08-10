@if($url = image_url($photo ?? null))
    <img class="datatable_img" alt="" src="{{ $url }}">
@endif
