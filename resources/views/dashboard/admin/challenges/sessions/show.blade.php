@extends('dashboard.layouts.master')
@section('title', __('general.Challenge Session Details') )
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
                        <h4 class="card-title">{{ __('general.Challenge Session Details') }}</h4>
                        <a href="{{ route('admin.challenge.sessions.index') }}" class="btn btn-secondary">{{ __('general.Back') }}</a>
                    </div>


                    <div class="card-body">
                        <div class="mb-3">
                            <p><strong>{{ __('general.User') }}:</strong> {{ $session->user->name }}</p>
                            <p><strong>{{ __('general.Score') }}:</strong> {{ $session->score }}</p>
                            <p><strong>{{ __('general.Started At') }}:</strong> {{ $session->started_at }}</p>
                            <p><strong>{{ __('general.Ended At') }}:</strong> {{ $session->ended_at }}</p>
                        </div>

                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('general.Question') }}</th>
                                <th>{{ __('general.Selected Answer') }}</th>
                                <th>{{ __('general.Correct / Wrong') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($session->answers as $answer)
                                <tr>
                                    <td>{{ $answer->question->title_ar ?? '-' }}</td>
                                    <td>{{ $answer->answers->title_ar ?? '-' }}</td>
                                    <td>
                                        @if($answer->is_correct)
                                            <span class="text-success">{{ __('general.Correct') }}</span>
                                        @else
                                            <span class="text-danger">{{ __('general.Wrong') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
@endsection
