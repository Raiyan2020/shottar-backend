<x-datatable :dataTable="$dataTable" :title="__('general.exams')">
    <x-slot:header>
        <a href="{{ route(panelPrefix().'.subjects.exams.create', $subject->id) }}"
           class="btn btn-primary waves-effect waves-light">
            {{ __('dataTable.add') }}
        </a>
        @if(auth('admin')->user()->hasRole('admin'))
        <a href="{{ route('admin.subjects.index') }}"
           class="btn btn-secondary waves-effect waves-light">
            {{ __('general.back') }}
        </a>
        @endif
    </x-slot:header>

    <x-slot:script>
        @include('dashboard.partials._reorder-script', [
            'reorderUrl' => route(panelPrefix().'.subjects.exams.reorder', $subject->id),
        ])
    </x-slot:script>
</x-datatable>
