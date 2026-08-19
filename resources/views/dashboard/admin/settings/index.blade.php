@extends('dashboard.layouts.master')
@section('title', __('general.General Settings') )
@section('css')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
@endsection
@section('content')

    <section id="multiple-column-form">
        <form class="form" action="{{ route('admin.settings.update') }}" method="post" enctype="multipart/form-data">

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                            </div>
                            <div class="card-body">
                                @csrf
                                <div class="row">
                                    @foreach (\App\Models\Setting::where('is_object',1)->get() as $key => $x)
                                        @php
                                            // الشروط وسياسة الخصوصية نص طويل بأسطر — لازم textarea
                                            $isLongText = \Illuminate\Support\Str::contains($x->key_id, ['terms', 'privacy', 'policy', 'desc']);
                                        @endphp
                                        <div class="{{ ($isLongText || str_contains($x->key_id, 'image') || $x->key_id == 'blog_video') ? 'col-md-12' : 'col-md-6' }} col-12">
                                            <div class="form-group">
                                                <label class="col-form-label-sm" for="setting{{$x->id}}">{{App::getLocale() == 'en' ? $x->title_en : $x->title_ar }}</label>
                                                @if($isLongText)
                                                    {{-- §9: النص بيتحفظ بأسطره الأصلية. كان input type=text
                                                         فالمتصفح كان بيبعت النص في سطر واحد وكل الـ \n بتتلخبط. --}}
                                                    <textarea
                                                        name="{{ $x->key_id }}"
                                                        class="form-control form-control-sm"
                                                        id="setting{{$x->id}}"
                                                        rows="{{ str_contains($x->key_id, 'desc') ? 3 : 20 }}"
                                                        dir="auto"
                                                        style="white-space: pre-wrap; font-family: inherit;"
                                                        placeholder="set....">{{ $x->value }}</textarea>
                                                    @if(!str_contains($x->key_id, 'desc'))
                                                        <small class="text-muted d-block mt-1">
                                                            {{ __('general.Line breaks are preserved exactly as typed.') }}
                                                            ({{ __('general.Characters') }}: {{ mb_strlen((string) $x->value) }})
                                                        </small>
                                                    @endif

                                                @elseif(str_contains($x->key_id, 'image'))
                                                    <div class="custom-file">
                                                        <input
                                                            name="{{$x->key_id}}"
                                                            type="file"
                                                            multiple
                                                            class="custom-file-input"
                                                            id="imageFile{{$x->id}}"
                                                        />
                                                        <label class="custom-file-label" for="imageFile{{$x->id}}">{{__('general.Choose files')}}</label>
                                                    </div>
                                                @elseif($x->key_id == 'forced_update_android' || $x->key_id == 'forced_update_ios' || $x->key_id == 'force_close' )

                                                        <select   name="{{$x->key_id}}" class="form-control form-control-sm" >
                                                            <option @if($x->value  == '1') selected="selected" @endif value="1">{{__('Yes')}}</option>
                                                            <option @if($x->value == '0') selected="selected" @endif value="0">{{__('No')}}</option>
                                                        </select>
                                                @else
                                                    <input
                                                        name="{{ $x->key_id }}"
                                                        value="@if(isset($x->value)){{$x->value}}@endif"
                                                        type="text"
                                                        id="setting{{$x->id}}"
                                                        class="form-control form-control-sm"
                                                        placeholder="john"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <button type="submit" class="btn btn-primary mr-1">{{__('general.Submit')}}</button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">

            </div>
        </form>
    </section>

@endsection
@section('js')
    <script>
        document.querySelectorAll('.custom-file-input').forEach(input => {
            input.addEventListener('change', function (e) {
                var fileNames = [];
                // Check if multiple files are selected
                if (e.target.files.length > 1) {
                    for (let i = 0; i < e.target.files.length; i++) {
                        fileNames.push(e.target.files[i].name);
                    }
                    e.target.nextElementSibling.textContent = fileNames.join(', ');
                } else {
                    var fileName = e.target.files[0] ? e.target.files[0].name : '{{__('general.Choose file')}}';
                    e.target.nextElementSibling.textContent = fileName;
                }
            });
        });
    </script>
@stop
