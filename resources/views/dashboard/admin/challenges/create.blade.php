@extends('dashboard.layouts.master')

@section('title', __('general.Add Challenge'))

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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">{{ __('general.Add Challenge') }}</h4>
                        <a href="{{ route(panelPrefix().'.subjects.sections.challenges.index', [$lessonSection->subject_id, $lessonSection->id]) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> {{ __('general.Back') }}
                        </a>
                    </div>

                    <div class="card-body">
                        <form class="form"
                              action="{{ route(panelPrefix().'.subjects.sections.challenges.store', [$lessonSection->subject_id, $lessonSection->id]) }}"
                              method="POST">
                            @csrf

                            <div class="row">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">{{ __('general.Question') }}</h5>
                                </div>
                                {{-- Title Arabic --}}
                                <div class="col-md-6 col-12 mb-2">

                                    <div class="form-group">
                                        <label for="title_ar" class="col-form-label-sm">
                                            {{ __('general.title_ar') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               id="title_ar"
                                               name="title_ar"
                                               value="{{ old('title_ar') }}"
                                               class="form-control form-control-sm @error('title_ar') is-invalid @enderror"
                                               placeholder="{{ __('general.title_ar') }}" required>
                                        @error('title_ar')
                                        <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Title English --}}
                                <div class="col-md-6 col-12 mb-2">
                                    <div class="form-group">
                                        <label for="title_en" class="col-form-label-sm">
                                            {{ __('general.title_en') }}
                                        </label>
                                        <input type="text"
                                               id="title_en"
                                               name="title_en"
                                               value="{{ old('title_en') }}"
                                               class="form-control form-control-sm @error('title_en') is-invalid @enderror"
                                               placeholder="{{ __('general.title_en') }}">
                                        @error('title_en')
                                        <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Answers Section --}}
                                <div class="col-12 mt-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">{{ __('general.answers') }}</h5>
                                        <button type="button" id="addAnswer" class="btn btn-sm btn-success">
                                            <i class="fa fa-plus"></i> {{ __('general.add_answer') }}
                                        </button>
                                    </div>

                                    <div id="answersContainer" class="border rounded p-2 bg-light">
                                        {{-- إجابة افتراضية واحدة --}}
                                        <div class="answer-item row align-items-center mb-2">
                                            <div class="col-md-4 mb-1">
                                                <input type="text" name="answers[0][title_ar]" class="form-control form-control-sm"
                                                       placeholder="{{ __('general.title_ar') }}" required>
                                            </div>
                                            <div class="col-md-4 mb-1">
                                                <input type="text" name="answers[0][title_en]" class="form-control form-control-sm"
                                                       placeholder="{{ __('general.title_en') }}">
                                            </div>
                                            <div class="col-md-2 mb-1 text-center">
                                                <input type="radio" name="correct_answer" value="0" class="form-check-input" checked>
                                                <label class="form-check-label small">{{ __('general.correct') }}</label>
                                            </div>
                                            <div class="col-md-2 text-center mb-1">
                                                <button type="button" class="btn btn-sm btn-danger removeAnswer">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="col-12 mt-1">
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let answerIndex = 1; // الإجابة الأولى موجودة مسبقاً في HTML

            const container = document.getElementById('answersContainer');

            // إضافة إجابة جديدة
            document.getElementById('addAnswer').addEventListener('click', function () {
                const newAnswer = document.createElement('div');
                newAnswer.classList.add('answer-item', 'row', 'align-items-center', 'mb-2');

                newAnswer.innerHTML = `
            <div class="col-md-4 mb-1">
                <input type="text" name="answers[${answerIndex}][title_ar]" class="form-control form-control-sm"
                       placeholder="{{ __('general.title_ar') }}" required>
            </div>
            <div class="col-md-4 mb-1">
                <input type="text" name="answers[${answerIndex}][title_en]" class="form-control form-control-sm"
                       placeholder="{{ __('general.title_en') }}">
            </div>
            <div class="col-md-2 mb-1 text-center">
                <input type="radio" name="correct_answer" value="${answerIndex}" class="form-check-input" id="correct-${answerIndex}">
                <label for="correct-${answerIndex}" class="form-check-label small ">{{ __('general.correct') }}</label>

            </div>
            <div class="col-md-2 text-center mb-1">
                <button type="button" class="btn btn-sm btn-danger removeAnswer">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        `;

                container.appendChild(newAnswer);
                answerIndex++;
            });

            // حذف إجابة
            container.addEventListener('click', function (e) {
                if (e.target.closest('.removeAnswer')) {
                    e.target.closest('.answer-item').remove();
                }
            });
        });
    </script>

@endsection
