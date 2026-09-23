@extends('dashboard.layouts.master')
@section('title', __('general.Edit Daily Challenge'))
@section('css')
@endsection

@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('general.Edit Daily Challenge') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.daily-challenges.update', $dailyChallenge->id) }}" method="post">
                            @csrf
                            @method('PUT')
                            @include('dashboard.admin.daily-challenges._form', ['dailyChallenge' => $dailyChallenge])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
@endsection
