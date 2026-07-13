
<x-datatable :dataTable="$dataTable" :title="__('general.Study Types')">
    <x-slot:header>
        <a href="{{ route('admin.study-types.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
    </x-slot:header>
</x-datatable>
