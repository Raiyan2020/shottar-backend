
@extends('dashboard.layouts.master')
@section('title', __('general.Update User') )
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
                        <h4 class="card-title">{{__('general.Update User')}} </h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.users.update', $user->id) }}" method="post" enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            <div class="row">
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="status">{{__('general.Status')}}</label>
                                        <select
                                            name="status"
                                            id="status"
                                            class="form-control form-control-sm @error('status') is-invalid @else {{ old('status') ? 'is-valid' : '' }} @enderror"
                                            required
                                        >
                                            <option value="1" {{ old('status', $user->status ?? '') == 1 ? 'selected' : '' }}>{{ __('general.Active') }}</option>
                                            <option value="3" {{ old('status', $user->status ?? '') == 3 ? 'selected' : '' }}>{{ __('general.Inactive') }}</option>
                                        </select>
                                        @error('status')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title mb-0">{{ __('general.customer_subscriptions') }}</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('dataTable.id') }}</th>
                                <th>{{ __('dataTable.package') }}</th>
                                <th>{{ __('dataTable.subjects') }}</th>
                                <th>{{ __('dataTable.total') }}</th>
                                <th>{{ __('dataTable.status') }}</th>
                                <th>{{ __('dataTable.created_at') }}</th>
                                <th>{{ __('dataTable.expires_at') }}</th>
                                <th>{{ __('dataTable.action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($user->orders as $order)
                                <tr>
                                    <td>{{ $order->id }}</td>
                                    <td>{{ $order->packageName() }}</td>
                                    <td>{{ $order->subjectsLabel() }}</td>
                                    <td>{{ $order->total }}</td>
                                    <td>
                                        @if($order->status === 'paid')
                                            <span class="badge bg-success">{{ __('dataTable.paid') }}</span>
                                        @elseif($order->status === 'pending')
                                            <span class="badge bg-warning">{{ __('dataTable.pending') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('dataTable.unpaid') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ optional($order->expires_at)->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-info">
                                            {{ __('general.Show order') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('dataTable.No records available') }}</td>
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
