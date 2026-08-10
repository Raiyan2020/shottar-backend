@extends('dashboard.layouts.master')
@section('title', __('general.Subscription Details'))
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
                        <h4 class="card-title mb-0">{{ __('general.Subscription Details') }} #{{ $order->id }}</h4>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">{{ __('general.Back') }}</a>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ __('dataTable.user') }}:</strong> {{ $order->user?->name ?? '-' }}</p>
                                <p><strong>{{ __('dataTable.phone') }}:</strong> {{ $order->user?->phone ?? '-' }}</p>
                                <p><strong>{{ __('dataTable.package') }}:</strong> {{ $order->packageName() }}</p>
                                <p><strong>{{ __('dataTable.status') }}:</strong>
                                    @if($order->status === 'paid')
                                        <span class="badge bg-success">{{ __('dataTable.paid') }}</span>
                                    @elseif($order->status === 'pending')
                                        <span class="badge bg-warning">{{ __('dataTable.pending') }}</span>
                                    @elseif($order->status === 'failed')
                                        <span class="badge bg-danger">{{ __('dataTable.failed') }}</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="badge bg-secondary">{{ __('dataTable.cancelled') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('dataTable.unpaid') }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>{{ __('dataTable.total') }}:</strong> {{ $order->total }}</p>
                                <p><strong>{{ __('dataTable.discount') }}:</strong> {{ $order->discount ?? 0 }}</p>
                                <p><strong>{{ __('dataTable.payment_method') }}:</strong>
                                    @php
                                        $method = $order->paymentMethod;
                                        $methodName = $method
                                            ? (app()->getLocale() === 'ar' ? ($method->name_ar ?? $method->name_en) : ($method->name_en ?? $method->name_ar))
                                            : null;
                                    @endphp
                                    {{ $methodName ?? __('dataTable.free') }}
                                </p>
                                <p><strong>{{ __('dataTable.created_at') }}:</strong> {{ optional($order->created_at)->format('Y-m-d H:i') }}</p>
                                <p><strong>{{ __('dataTable.expires_at') }}:</strong> {{ optional($order->expires_at)->format('Y-m-d') ?? '-' }}</p>
                                @if($order->payment_reference)
                                    <p><strong>{{ __('general.Payment ID') }}:</strong> {{ $order->payment_reference }}</p>
                                @endif
                            </div>
                        </div>

                        <h5 class="mt-4">{{ __('general.Items') }}</h5>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('general.Product') }}</th>
                                <th>{{ __('general.Price') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($order->items as $item)
                                <tr>
                                    <td>
                                        {{ app()->getLocale() === 'ar'
                                            ? ($item->subject->name_ar ?? $item->subject->name_en ?? '-')
                                            : ($item->subject->name_en ?? $item->subject->name_ar ?? '-') }}
                                    </td>
                                    <td>{{ $item->price }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">{{ __('dataTable.No records available') }}</td>
                                </tr>
                            @endforelse
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
