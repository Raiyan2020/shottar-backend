
<x-datatable :dataTable="$dataTable" :title="__('general.Core Subjects')">
    <x-slot:header>
        <a href="{{ route('admin.core-subjects.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
    </x-slot:header>
</x-datatable>
