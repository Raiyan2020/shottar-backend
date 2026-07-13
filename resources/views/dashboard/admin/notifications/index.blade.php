
<x-datatable :dataTable="$dataTable" :title="__('notifications')">
    <x-slot:header>
        <a href="{{ route('admin.notifications.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
    </x-slot:header>
</x-datatable>
