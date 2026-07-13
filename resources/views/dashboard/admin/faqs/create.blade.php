@extends('dashboard.layouts.master')
@section('title', __('general.Add FAQ'))
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
                        <h4 class="card-title">{{ __('general.Add FAQ') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.faqs.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <!-- Question Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="question_ar" class="col-form-label-sm">{{ __('general.question_ar') }}</label>
                                        <input type="text" name="question_ar" id="question_ar" value="{{ old('question_ar') }}"
                                               class="form-control form-control-sm @error('question_ar') is-invalid @enderror" required>
                                        @error('question_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Question English -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="question_en" class="col-form-label-sm">{{ __('general.question_en') }}</label>
                                        <input type="text" name="question_en" id="question_en" value="{{ old('question_en') }}"
                                               class="form-control form-control-sm @error('question_en') is-invalid @enderror" required>
                                        @error('question_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Answer Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="answer_ar" class="col-form-label-sm">{{ __('general.answer_ar') }}</label>
                                        <textarea name="answer_ar" id="answer_ar"
                                                  class="form-control form-control-sm @error('answer_ar') is-invalid @enderror"
                                                  rows="3" required>{{ old('answer_ar') }}</textarea>
                                        @error('answer_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Answer English -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="answer_en" class="col-form-label-sm">{{ __('general.answer_en') }}</label>
                                        <textarea name="answer_en" id="answer_en"
                                                  class="form-control form-control-sm @error('answer_en') is-invalid @enderror"
                                                  rows="3" required>{{ old('answer_en') }}</textarea>
                                        @error('answer_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Save') }}</button>
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
