<x-datatable :dataTable="$dataTable" :title="__('general.exams')">
    <x-slot:header>
        <a href="{{ route('admin.subjects.exams.create', $subject->id) }}"
           class="btn btn-primary waves-effect waves-light">
            {{ __('dataTable.add') }}
        </a>
        <a href="{{ route('admin.subjects.index') }}"
           class="btn btn-secondary waves-effect waves-light">
            {{ __('general.back') }}
        </a>
    </x-slot:header>
</x-datatable>
