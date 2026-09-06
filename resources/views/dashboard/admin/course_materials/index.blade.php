<x-datatable :dataTable="$dataTable" :title="__('general.lesson_sections')">
    <x-slot:header>
        <a href="{{ route(panelPrefix().'.subjects.materials.create',[$subject->id,$type,'section' => $sectionId]) }}" type="button" class="btn btn-primary waves-effect waves-light">{{__('dataTable.add')}}</a>
        @if(auth('admin')->user()->hasRole('admin'))
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @else
            <a href="{{ route(panelPrefix().'.subjects.sections.index',$subject->id) }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @endif

    </x-slot:header>

    <x-slot:script>
        @include('dashboard.admin.course_materials._sorting', [
            'reorderRouteName' => panelPrefix().'.materials.reorder',
        ])

    </x-slot:script>
</x-datatable>
