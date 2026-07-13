
<x-datatable :dataTable="$dataTable" :title="__('general.orders')">
    <x-slot:header>
    <a href="{{ route('admin.orders.exportExcel') }}" class="btn btn-success">
        <i class="fas fa-file-excel"></i> Excel
    </a>
    </x-slot:header>
</x-datatable>

