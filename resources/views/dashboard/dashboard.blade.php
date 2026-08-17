@extends('dashboard.layouts.master')

@section('title', __('general.analytics'))
@section('css')
    {{--    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">--}}
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/colors.css') }}">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        #statistics-card .row:first-child {
            align-items: stretch;
        }
        #statistics-card .row:first-child > [class*="col-"] {
            display: flex;
        }
        #statistics-card .stat-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 210px;
            margin-bottom: 1.5rem;
        }
        #statistics-card .stat-card .card-header {
            min-height: 88px;
            flex: 0 0 auto;
        }
        #statistics-card .stat-card .card-text {
            margin-bottom: 0;
            min-height: 2.5em;
            line-height: 1.25;
        }
        #statistics-card .stat-chart {
            height: 100px;
            margin-top: auto;
        }
        #statistics-card .stat-chart .apexcharts-canvas {
            height: 100px !important;
        }
    </style>
@endsection

@section('content')
    <section id="statistics-card">
        <div class="row">
            @php
                $colClass = auth('admin')->user()->hasRole('admin') ? 'col-lg-3 col-sm-6 col-12' : 'col-lg-4 col-sm-6 col-12';
            @endphp

            <div class="{{ $colClass }}">
                <div class="card stat-card">
                    <div class="card-header align-items-start pb-0">
                        <div>
                            <h2 class="font-weight-bolder">{{ $usersCount }}</h2>
                            <p class="card-text">{{ __('general.Number Users') }}</p>
                        </div>
                        <div class="avatar bg-light-primary" style="padding: 0.27rem;">
                            <div class="avatar-content">
                                <i data-feather="users" class="font-medium-5"></i>
                            </div>
                        </div>
                    </div>
                    <div id="line-area-chart-5" class="stat-chart"></div>
                </div>
            </div>

            <div class="{{ $colClass }}">
                <div class="card stat-card">
                    <div class="card-header align-items-start pb-0">
                        <div>
                            <h2 class="font-weight-bolder">{{ $subjectsCount }}</h2>
                            <p class="card-text">{{ __('Number Subjects') }}</p>
                        </div>
                        <div class="avatar bg-light-success" style="padding: 0.27rem;">
                            <div class="avatar-content">
                                <i data-feather="book-open" class="font-medium-5"></i>
                            </div>
                        </div>
                    </div>
                    <div id="line-area-chart-6" class="stat-chart"></div>
                </div>
            </div>

            <div class="{{ $colClass }}">
                <div class="card stat-card">
                    <div class="card-header align-items-start pb-0">
                        <div>
                            <h2 class="font-weight-bolder">{{ $courseMaterialsCount }}</h2>
                            <p class="card-text">{{ __('Number Course Materials') }}</p>
                        </div>
                        <div class="avatar bg-light-info" style="padding: 0.27rem;">
                            <div class="avatar-content">
                                <i data-feather="file-text" class="font-medium-5"></i>
                            </div>
                        </div>
                    </div>
                    <div id="line-area-chart-7" class="stat-chart"></div>
                </div>
            </div>

            @if(auth('admin')->user()->hasRole('admin'))
                <div class="{{ $colClass }}">
                    <div class="card stat-card">
                        <div class="card-header align-items-start pb-0">
                            <div>
                                <h2 class="font-weight-bolder">{{ $totalAmount }}</h2>
                                <p class="card-text">{{ __('total Amount') }}</p>
                            </div>
                            <div class="avatar bg-light-danger" style="padding: 0.27rem;">
                                <div class="avatar-content">
                                    <i data-feather="dollar-sign" class="font-medium-5"></i>
                                </div>
                            </div>
                        </div>
                        <div id="line-area-chart-3" class="stat-chart"></div>
                    </div>
                </div>
            @endif

        </div>
        <div class="row">
            <div class=" col-12">
                <div class="card">
                    <div class="card-header bg-transparent pd-b-0 pd-t-20 bd-b-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mb-0">{{__('general.number of views videos')}}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! $lineChart->render() !!}
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{--    <script src="{{asset('dashboard/app-assets/vendors/js/vendors.min.js')}}"></script>--}}
    <script src="{{asset('dashboard/app-assets/js/scripts/cards/card-statistics.js')}}"></script>
    <script src="{{asset('dashboard/app-assets/vendors/js/charts/apexcharts.min.js')}}"></script>
    <script src="{{asset('dashboard/app-assets/js/core/app-menu.js')}}"></script>
    <script src="{{asset('dashboard/app-assets/js/core/app.js')}}"></script>
    <script>
        feather.replace();
    </script>

@stop
