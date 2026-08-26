@extends('dashboard.layouts.master')
@section('title', __('general.Update Exam'))
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
                        <h4 class="card-title mb-0">{{ __('general.Update Exam') }} — {{ $subject->name_ar }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.subjects.exams.update', [$subject->id, $exam->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                            <div class="row">
                                <!-- الوحدة: اختياري — لو مختارتش، المرفق بيبقى على مستوى المادة كلها -->
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="lesson_section_id" class="col-form-label-sm">{{ __('general.unit') }}</label>
                                        <select name="lesson_section_id" id="lesson_section_id"
                                                class="form-control form-control-sm @error('lesson_section_id') is-invalid @enderror">
                                            <option value="">{{ __('general.Whole subject (no unit)') }}</option>
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}" {{ (string) old('lesson_section_id', $exam->lesson_section_id) === (string) $section->id ? 'selected' : '' }}>
                                                    {{ app()->getLocale() === 'en' ? $section->name_en : $section->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('lesson_section_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar', $exam->name_ar) }}"
                                               class="form-control form-control-sm @error('name_ar') is-invalid @enderror" required>
                                        @error('name_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input type="text" name="name_en" id="name_en" value="{{ old('name_en', $exam->name_en) }}"
                                               class="form-control form-control-sm @error('name_en') is-invalid @enderror" required>
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="file" class="col-form-label-sm">{{ __('general.PDF') }}</label>
                                        <input type="file" name="file" id="file" accept="application/pdf"
                                               class="form-control form-control-sm @error('file') is-invalid @enderror">
                                        @if($exam->file)
                                            <a href="{{ stored_file_url($exam->file) }}" target="_blank" class="d-block mt-1">
                                                {{ __('general.Current') }} PDF
                                            </a>
                                        @endif
                                        @error('file')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 col-12">
                                    <div class="form-group">
                                        <label for="is_free" class="col-form-label-sm">{{ __('general.is_free') }}</label>
                                        <select name="is_free" id="is_free" class="form-control form-control-sm">
                                            <option value="0" @selected(old('is_free', (int) $exam->is_free) == 0)>{{ __('general.No') }}</option>
                                            <option value="1" @selected(old('is_free', (int) $exam->is_free) == 1)>{{ __('general.Yes') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12">
                                    <div class="form-group">
                                        <label for="status" class="col-form-label-sm">{{ __('general.Status') }}</label>
                                        <select name="status" id="status" class="form-control form-control-sm">
                                            <option value="1" @selected(old('status', (int) $exam->status) == 1)>{{ __('general.Active') }}</option>
                                            <option value="0" @selected(old('status', (int) $exam->status) == 0)>{{ __('general.Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                    <a href="{{ route('admin.subjects.exams.index', $subject->id) }}" class="btn btn-secondary">{{ __('general.Back') }}</a>
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
