@extends('dashboard.layouts.master')
@section('title', __('general.Order Details'))
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
            <h4 class="card-title">{{ __('general.Order Details') }} #{{ $order->id }}</h4>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">{{ __('general.Back') }}</a>
        </div>

        <div class="card-body">
            <p><strong>{{ __('dataTable.user') }}:</strong> {{ $order->user->name }}</p>
            <p><strong>{{ __('dataTable.total') }}:</strong> {{ $order->total }}</p>
            <p><strong>{{ __('dataTable.discount') }}:</strong> {{ $order->discount }}</p>
            <p><strong>{{ __('dataTable.payment_method') }}:</strong> {{ $order->paymentMethod?->name ?? __('dataTable.free') }}</p>
            <p><strong>{{ __('dataTable.status') }}:</strong> {{ $order->status ? __('general.Active') : __('general.Inactive') }}</p>

            <h5 class="mt-4">{{ __('general.Items') }}</h5>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>{{ __('general.Product') }}</th>
                    <th>{{ __('general.Price') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->subject->name_en ?? '-' }}</td>
                        <td>{{ $item->price }}</td>
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
