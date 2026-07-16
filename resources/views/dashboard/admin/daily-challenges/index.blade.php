
<x-datatable :dataTable="$dataTable" :title="__('general.Daily Challenges')">
    <x-slot:header>
        <a href="{{ route('admin.daily-challenges.create') }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
    </x-slot:header>
</x-datatable>
