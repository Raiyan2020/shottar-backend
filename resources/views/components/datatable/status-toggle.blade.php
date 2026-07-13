@php
$names = $name ?? 'status';
@endphp
<span class="badge {{ $status ? 'bg-success' : 'bg-danger' }} toggle-status"
      data-id="{{ $id }}"
      data-url="{{ $url }}"
      style="cursor: pointer;">
    @if($names == 'is_free')
        {{ $status ? __('general.Yes') : __('general.No') }}
    @else
        {{ $status ? __('dataTable.active') : __('dataTable.inactive') }}
    @endif
</span>

