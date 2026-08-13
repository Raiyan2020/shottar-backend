@extends('dashboard.layouts.master')
@section('title', __('general.Add Core Subject'))
@section('css')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
@endsection

@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('general.Add Core Subject') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.core-subjects.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar') }}"
                                               class="form-control form-control-sm @error('name_ar') is-invalid @enderror" required>
                                        @error('name_ar')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input type="text" name="name_en" id="name_en" value="{{ old('name_en') }}"
                                               class="form-control form-control-sm @error('name_en') is-invalid @enderror">
                                        @error('name_en')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="image" class="col-form-label-sm">{{ __('general.Image') }}</label>
                                        <input type="file" name="image" id="image" accept="image/*"
                                               class="form-control form-control-sm @error('image') is-invalid @enderror" required>
                                        @error('image')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status">{{ __('dataTable.status') }}</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">{{ __('general.Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
