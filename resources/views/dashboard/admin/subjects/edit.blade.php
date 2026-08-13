@extends('dashboard.layouts.master')
@section('title', __('general.Update Subject'))
@section('css')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .core-subject-option { display: flex; align-items: center; gap: 10px; }
        .core-subject-option img { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; }
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding-inline-end: 28px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-inline-start: 12px;
            color: inherit;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            width: 28px;
            top: 1px;
            inset-inline-end: 4px;
            inset-inline-start: auto;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            display: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            margin-top: -6px;
            margin-left: -4px;
            border-right: 2px solid #697a8d;
            border-bottom: 2px solid #697a8d;
            transform: rotate(45deg);
            pointer-events: none;
        }
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow::after {
            margin-top: -2px;
            transform: rotate(-135deg);
        }
        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-inline-end: 8px;
        }
    </style>
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
                        <form class="form" action="{{ route('admin.subjects.update', $subject->id) }}" method="post">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="core_subject_id" class="col-form-label-sm">{{ __('general.Core Subject') }}</label>
                                        <select name="core_subject_id" id="core_subject_id"
                                                class="form-control form-control-sm @error('core_subject_id') is-invalid @enderror"
                                                required data-placeholder="{{ __('general.Select Core Subject') }}">
                                            <option value=""></option>
                                            @foreach($coreSubjects as $core)
                                                <option value="{{ $core->id }}"
                                                        data-image="{{ image_url($core->image) }}"
                                                        {{ (string) old('core_subject_id', $subject->core_subject_id) === (string) $core->id ? 'selected' : '' }}>
                                                    {{ $core->localizedName() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('core_subject_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="grade_id" class="col-form-label-sm">{{ __('general.Grade') }}</label>
                                        <select name="grade_id" id="grade_id"
                                                class="form-control form-control-sm @error('grade_id') is-invalid @enderror" required>
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

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        @php
                                            $selectedSemesters = old('semester_ids', $subject->semesters->pluck('id')->toArray() ?: array_filter([$subject->semester_id]));
                                        @endphp
                                        <label class="col-form-label-sm d-block">{{ __('general.Semester') }}</label>
                                        @foreach($semesters as $semester)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="semester_ids[]"
                                                       id="semester_{{ $semester->id }}"
                                                       value="{{ $semester->id }}"
                                                       {{ in_array($semester->id, $selectedSemesters) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="semester_{{ $semester->id }}">{{ $semester->name_ar }}</label>
                                            </div>
                                        @endforeach
                                        @error('semester_ids')
                                        <span class="col-form-label-sm text-danger d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="price" class="col-form-label-sm">{{ __('general.Price') }}</label>
                                        <input type="number" step="0.01" name="price" id="price"
                                               value="{{ old('price', $subject->price) }}"
                                               class="form-control form-control-sm @error('price') is-invalid @enderror" required>
                                        @error('price')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="ios_product_id" class="col-form-label-sm">{{ __('general.ios_product_id') }}</label>
                                        <input type="text" name="ios_product_id" id="ios_product_id"
                                               value="{{ old('ios_product_id', $subject->ios_product_id) }}"
                                               class="form-control form-control-sm @error('ios_product_id') is-invalid @enderror"
                                               placeholder="com.raiyansoft.shottar.subject.{{ $subject->id }}"
                                               dir="ltr" maxlength="100" autocomplete="off" spellcheck="false"
                                               pattern="[A-Za-z0-9][A-Za-z0-9_-]*(\.[A-Za-z0-9][A-Za-z0-9_-]*)+"
                                               title="{{ __('general.ios_product_id_invalid') }}"
                                               @if(!empty($iosProductLocked)) readonly @endif>
                                        @if(!empty($iosProductLocked))
                                            <small class="text-warning d-block">{{ __('general.ios_product_id_locked_hint') }}</small>
                                        @else
                                            <small class="text-muted d-block">{{ __('general.ios_product_id_hint') }}</small>
                                        @endif
                                        @error('ios_product_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">{{ __('general.cancel') }}</a>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
    <script>
        (function () {
            function formatCoreSubject(option) {
                if (!option.id) return option.text;
                var image = $(option.element).data('image');
                var imgHtml = image
                    ? '<img src="' + image + '" alt="">'
                    : '<span style="width:32px;height:32px;display:inline-block;background:#eee;border-radius:4px;"></span>';
                return $('<span class="core-subject-option">' + imgHtml + '<span>' + option.text + '</span></span>');
            }

            $('#core_subject_id').select2({
                width: '100%',
                placeholder: $('#core_subject_id').data('placeholder'),
                allowClear: true,
                templateResult: formatCoreSubject,
                templateSelection: formatCoreSubject,
                escapeMarkup: function (m) { return m; }
            });
        })();
    </script>
@endsection
