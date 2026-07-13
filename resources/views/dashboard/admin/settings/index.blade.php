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
                                        <div class="{{ (str_contains($x->key_id, 'image') || $x->key_id == 'blog_video' || str_contains($x->key_id, 'desc')) ? 'col-md-12' : 'col-md-6' }} col-12">
                                            <div class="form-group">
                                                <label class="col-form-label-sm" for="setting{{$x->id}}">{{App::getLocale() == 'en' ? $x->title_en : $x->title_ar }}</label>
                                                @if(str_contains($x->key_id, 'desc'))
                                                    <textarea
                                                        name="{{ $x->key_id }}"
                                                        data-length="1000"
                                                        class="form-control char-textarea form-control-sm"
                                                        id="setting{{$x->id}}"
                                                        rows="3"
                                                        placeholder="set....">{{ $x->value }}</textarea>

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
