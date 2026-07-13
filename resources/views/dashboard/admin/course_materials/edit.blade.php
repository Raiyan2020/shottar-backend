@extends('dashboard.layouts.master')
@section('title', $type == 'lesson' ? __('general.Update Lesson') : __('general.Update Note'))
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
                        <h4 class="card-title">{{ $type == 'lesson' ? __('general.Update Lesson') : __('general.Update Note') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route(panelPrefix().'.subjects.materials.update', [$subject->id, $material->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                            <input type="hidden" name="type" value="{{ $type }}">

                            <div class="row">

                                <!-- Name Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input type="text" name="name_ar" id="name_ar"
                                               value="{{ old('name_ar', $material->name_ar) }}"
                                               class="form-control form-control-sm @error('name_ar') is-invalid @enderror" required>
                                        @error('name_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Name English -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input type="text" name="name_en" id="name_en"
                                               value="{{ old('name_en', $material->name_en) }}"
                                               class="form-control form-control-sm @error('name_en') is-invalid @enderror" required>
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Lesson Section Select -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="lesson_section_id">{{ __('general.lesson_sections') }}</label>

                                        @if(auth('admin')->check() && auth('admin')->user()->hasRole('teacher'))
                                            {{-- إذا المستخدم مدرس نخليها مقفلة --}}
                                            <select name="lesson_section_id" id="lesson_section_id" class="form-control form-control-sm" disabled>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}"
                                                        {{ (old('lesson_section_id', $material->lesson_section_id) == $section->id) ? 'selected' : '' }}>
                                                        {{ $section->name_ar }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            {{-- نخزن القيمة في hidden input --}}
                                            <input type="hidden" name="lesson_section_id" value="{{ old('lesson_section_id', $material->lesson_section_id) }}">
                                        @else
                                            {{-- الوضع الطبيعي (أدمن أو غير مدرس) --}}
                                            <select name="lesson_section_id" id="lesson_section_id"
                                                    class="form-control form-control-sm @error('lesson_section_id') is-invalid @enderror" required>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}"
                                                        {{ (old('lesson_section_id', $material->lesson_section_id) == $section->id) ? 'selected' : '' }}>
                                                        {{ $section->name_ar }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error('lesson_section_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>


                                <!-- Video or File -->
                                @if($type == 'lesson')
                                    <!-- Video File Upload -->
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="video" class="col-form-label-sm">{{ __('general.Video File') }}</label>
                                            <input type="file" name="video" id="video"
                                                   class="form-control form-control-sm @error('video') is-invalid @enderror">
                                            @error('video')
                                            <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @else
                                    <!-- File Upload -->
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="file" class="col-form-label-sm">{{ __('general.Upload File') }}</label>
                                            <input type="file" name="file" id="file"
                                                   class="form-control form-control-sm @error('file') is-invalid @enderror">
                                            @error('file')
                                            <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Status -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="status" class="col-form-label-sm">{{ __('general.Status') }}</label>
                                        <select name="status" id="status"
                                                class="form-control form-control-sm @error('status') is-invalid @enderror" required>
                                            <option value="1" {{ old('status', $material->status) == '1' ? 'selected' : '' }}>{{ __('general.Active') }}</option>
                                            <option value="0" {{ old('status', $material->status) == '0' ? 'selected' : '' }}>{{ __('general.Inactive') }}</option>
                                        </select>
                                        @error('status')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="status">{{__('general.is_free')}}</label>
                                        <select
                                            name="is_free"
                                            id="is_free"
                                            class="form-control form-control-sm @error('is_free') is-invalid @else {{ old('is_free') ? 'is-valid' : '' }} @enderror"
                                            required
                                        >
                                            <option value="1"  {{ old('is_free', $material->is_free) == '1' ? 'selected' : '' }}>{{ __('general.Active') }}</option>
                                            <option value="0" {{ old('is_free', $material->is_free) == '0' ? 'selected' : '' }}>{{ __('general.Inactive') }}</option>
                                        </select>
                                        @error('status')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                        <a href="{{ route('admin.subjects.materials.index', [$subject->id,$type]) }}" class="btn btn-secondary">{{ __('general.cancel') }}</a>
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
