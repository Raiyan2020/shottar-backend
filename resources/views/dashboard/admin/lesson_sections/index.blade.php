@php
    $prefix = auth('admin')->user()->hasRole('admin') ? 'admin' : 'teacher';
@endphp
<x-datatable :dataTable="$dataTable" :title="__('general.lesson_sections')">
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
        @include('dashboard.partials._reorder-script', [
            'reorderUrl' => route($prefix.'.sections.reorder', $subject->id),
        ])
    </x-slot:script>
</x-datatable>
