@extends('dashboard.layouts.master')
@section('title', __('general.Add Coupon'))
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
                        <h4 class="card-title">{{ __('general.Add Coupon') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.coupons.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <!-- Coupon Code -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="code" class="col-form-label-sm">{{ __('general.code') }}</label>
                                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                                               class="form-control form-control-sm @error('code') is-invalid @enderror" required>
                                        @error('code')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Discount Type -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_type" class="col-form-label-sm">{{ __('general.discount_type') }}</label>
                                        <select name="type" id="discount_type"
                                                class="form-control form-control-sm @error('discount_type') is-invalid @enderror" required>
                                            <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>
                                                {{ __('general.percentage') }}
                                            </option>
                                            <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>
                                                {{ __('general.fixed_amount') }}
                                            </option>
                                        </select>
                                        @error('discount_type')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Discount Value -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_value" class="col-form-label-sm">{{ __('general.discount_value') }}</label>
                                        <input type="number" step="0.01" name="value" id="discount_value" value="{{ old('discount_value') }}"
                                               class="form-control form-control-sm @error('discount_value') is-invalid @enderror" required>
                                        @error('discount_value')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Expiry Date -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="expires_at" class="col-form-label-sm">{{ __('general.expires_at') }}</label>
                                        <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                                               class="form-control form-control-sm @error('expires_at') is-invalid @enderror">
                                        @error('expires_at')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="status" class="col-form-label-sm">{{ __('general.status') }}</label>
                                        <select name="status" id="status"
                                                class="form-control form-control-sm @error('status') is-invalid @enderror" required>
                                            <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>{{ __('general.active') }}</option>
                                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>{{ __('general.inactive') }}</option>
                                        </select>
                                        @error('status')
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
