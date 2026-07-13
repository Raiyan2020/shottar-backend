
<x-datatable :dataTable="$dataTable" :title="__('challenges')">
    <x-slot:header>
        <a href="{{ route(panelPrefix().'.subjects.sections.challenges.create', [$lessonSection->subject_id, $lessonSection->id]) }}"
           type="button"
           class="btn btn-primary waves-effect waves-light">
            {{ __('dataTable.add') }}
        </a>
        @if(auth('admin')->user()->hasRole('admin'))
            <a href="{{ route(panelPrefix().'.subjects.sections.index',[$lessonSection->subject_id]) }}" class="btn btn-secondary waves-effect waves-light">{{__('general.back')}}</a>
        @endif
    </x-slot:header>
</x-datatable>
