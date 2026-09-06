
<x-datatable :dataTable="$dataTable" :title="__('general.grade')">
    <x-slot:header>
        <a href="{{ route('admin.grades.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
    </x-slot:header>

    <x-slot:script>
        @include('dashboard.partials._reorder-script', [
            'reorderUrl' => route('admin.grades.reorder'),
        ])
    </x-slot:script>
</x-datatable>

