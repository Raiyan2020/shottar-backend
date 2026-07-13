@extends('dashboard.layouts.master')
@section('title', __('general.Update Lesson Section'))
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
                        <h4 class="card-title">{{ __('general.Update Lesson Section') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route(panelPrefix().'.subjects.sections.update', [$subject->id, $section->id]) }}" method="post">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                                <!-- Name Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="name_ar">{{__('general.Name in Arabic')}}</label>
                                        <input
                                            value="{{ old('name_ar', $section->name_ar) }}"
                                            name="name_ar"
                                            type="text"
                                            id="name_ar"
                                            class="form-control form-control-sm @error('name_ar') is-invalid @enderror"
                                            placeholder="{{__('general.Name in Arabic')}}"
                                            required
                                        />
                                        @error('name_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Name English -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="name_en">{{__('general.Name in English')}}</label>
                                        <input
                                            value="{{ old('name_en', $section->name_en) }}"
                                            name="name_en"
                                            type="text"
                                            id="name_en"
                                            class="form-control form-control-sm @error('name_en') is-invalid @enderror"
                                            placeholder="{{__('general.Name in English')}}"
                                            required
                                        />
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Challenge Duration -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="challenge_duration">{{ __('general.Challenge Duration (minutes)') }}</label>
                                        <input
                                            value="{{ old('challenge_duration', $section->challenge_duration) }}"
                                            name="challenge_duration"
                                            type="number"
                                            id="challenge_duration"
                                            min="1"
                                            class="form-control form-control-sm @error('challenge_duration') is-invalid @enderror"
                                            placeholder="{{ __('general.Enter challenge duration in minutes') }}"
                                        />
                                        @error('challenge_duration')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Challenge Active -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm d-block">{{ __('general.Activate Challenge') }}</label>
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                name="challenge_active"
                                                id="challenge_active"
                                                class="form-check-input"
                                                value="1"
                                                {{ old('challenge_active', $section->challenge_active) ? 'checked' : '' }}
                                            />
                                            <label class="form-check-label" for="challenge_active">{{ __('general.Enable') }}</label>
                                        </div>
                                        @error('challenge_active')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12 mt-2">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
@endsection
