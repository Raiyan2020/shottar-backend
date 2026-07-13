@extends('dashboard.layouts.master')
@section('title', __('general.Update Subject'))
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
                        <h4 class="card-title">{{ __('general.Update Subject') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.subjects.update', $subject->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <!-- Name Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input
                                            type="text"
                                            name="name_ar"
                                            id="name_ar"
                                            value="{{ old('name_ar', $subject->name_ar) }}"
                                            class="form-control form-control-sm @error('name_ar') is-invalid @enderror"
                                            placeholder="Name in Arabic"
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
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input
                                            type="text"
                                            name="name_en"
                                            id="name_en"
                                            value="{{ old('name_en', $subject->name_en) }}"
                                            class="form-control form-control-sm @error('name_en') is-invalid @enderror"
                                            placeholder="Name in English"
                                            required
                                        />
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Grade Select -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="grade_id" class="col-form-label-sm">{{ __('general.Grade') }}</label>
                                        <select
                                            name="grade_id"
                                            id="grade_id"
                                            class="form-control form-control-sm @error('grade_id') is-invalid @enderror"
                                            required
                                        >
                                            <option value="">{{ __('general.Select Grade') }}</option>
                                            @foreach($grades as $grade)
                                                <option value="{{ $grade->id }}" {{ old('grade_id', $subject->grade_id) == $grade->id ? 'selected' : '' }}>
                                                    {{ $grade->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('grade_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Study Type Select -->
{{--                                <div class="col-md-6 col-12">--}}
{{--                                    <div class="form-group">--}}
{{--                                        <label for="study_type_id" class="col-form-label-sm">{{ __('general.Study Type') }}</label>--}}
{{--                                        <select--}}
{{--                                            name="study_type_id"--}}
{{--                                            id="study_type_id"--}}
{{--                                            class="form-control form-control-sm @error('study_type_id') is-invalid @enderror"--}}
{{--                                        >--}}
{{--                                            <option value="">{{ __('general.Select Study Type') }}</option>--}}
{{--                                            @foreach($studyTypes as $studyType)--}}
{{--                                                <option value="{{ $studyType->id }}" {{ old('study_type_id', $subject->study_type_id) == $studyType->id ? 'selected' : '' }}>--}}
{{--                                                    {{ $studyType->name_ar }}--}}
{{--                                                </option>--}}
{{--                                            @endforeach--}}
{{--                                        </select>--}}
{{--                                        @error('study_type_id')--}}
{{--                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>--}}
{{--                                        @enderror--}}
{{--                                    </div>--}}
{{--                                </div>--}}

                                <!-- Semester Select -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="semester_id" class="col-form-label-sm">{{ __('general.Semester') }}</label>
                                        <select
                                            name="semester_id"
                                            id="semester_id"
                                            class="form-control form-control-sm @error('semester_id') is-invalid @enderror"
                                            required
                                        >
                                            <option value="">{{ __('general.Select Semester') }}</option>
                                            @foreach($semesters as $semester)
                                                <option value="{{ $semester->id }}" {{ old('semester_id', $subject->semester_id) == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('semester_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="price" class="col-form-label-sm">{{ __('general.Price') }}</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="price"
                                            id="price"
                                            value="{{ old('price', $subject->price) }}"
                                            class="form-control form-control-sm @error('price') is-invalid @enderror"
                                            placeholder="Price"
                                            required
                                        />
                                        @error('price')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="image" class="col-form-label-sm">{{ __('general.Image') }}</label>
                                        <input
                                            type="file"
                                            name="image"
                                            id="image"
                                            class="form-control form-control-sm @error('image') is-invalid @enderror"
                                        />
                                        @if($subject->image)
                                            <img src="{{ asset( $subject->image) }}" width="80" class="mt-1" alt="Subject Image">
                                        @endif
                                        @error('image')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>


                                <!-- Submit Button -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">{{ __('general.cancel') }}</a>
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
