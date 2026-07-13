@extends('dashboard.layouts.master')
@section('title', __('general.Edit Grade') )
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
                        <h4 class="card-title">{{ __('general.Edit Grade') }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route('admin.grades.update', $grade->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <!-- Name Arabic -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar">{{ __('general.Name in Arabic') }}</label>
                                        <input
                                            value="{{ old('name_ar', $grade->name_ar) }}"
                                            name="name_ar"
                                            type="text"
                                            id="name_ar"
                                            class="form-control @error('name_ar') is-invalid @enderror"
                                            required
                                        />
                                        @error('name_ar')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Name English -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en">{{ __('general.Name in English') }}</label>
                                        <input
                                            value="{{ old('name_en', $grade->name_en) }}"
                                            name="name_en"
                                            type="text"
                                            id="name_en"
                                            class="form-control @error('name_en') is-invalid @enderror"
                                            required
                                        />
                                        @error('name_en')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="all_materials_price">{{ __('general.All Materials Price') }}</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="all_materials_price"
                                            id="all_materials_price"
                                            value="{{ old('all_materials_price', $grade->all_materials_price) }}"
                                            class="form-control form-control-sm @error('all_materials_price') is-invalid @enderror"
                                        >
                                        @error('all_materials_price')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- Icon Number -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="icon_number">{{ __('general.icon_number') }}</label>
                                        <input
                                            type="number"
                                            name="icon_number"
                                            id="icon_number"
                                            value="{{ old('icon_number', $grade->icon_number) }}"
                                            class="form-control @error('icon_number') is-invalid @enderror"
                                        >
                                        @error('icon_number')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="status">{{ __('general.Status') }}</label>
                                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="1" {{ old('status', $grade->status) == 1 ? 'selected' : '' }}>{{ __('general.Active') }}</option>
                                            <option value="0" {{ old('status', $grade->status) == 0 ? 'selected' : '' }}>{{ __('general.Inactive') }}</option>
                                        </select>
                                        @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Discounts -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_2_materials">{{ __('general.Discount for 2 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_2_materials" id="discount_2_materials"
                                               value="{{ old('discount_2_materials', $grade->discount_2_materials) }}"
                                               class="form-control @error('discount_2_materials') is-invalid @enderror">
                                        @error('discount_2_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_3_materials">{{ __('general.Discount for 3 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_3_materials" id="discount_3_materials"
                                               value="{{ old('discount_3_materials', $grade->discount_3_materials) }}"
                                               class="form-control @error('discount_3_materials') is-invalid @enderror">
                                        @error('discount_3_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_4_materials">{{ __('general.Discount for 4 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_4_materials" id="discount_4_materials"
                                               value="{{ old('discount_4_materials', $grade->discount_4_materials) }}"
                                               class="form-control @error('discount_4_materials') is-invalid @enderror">
                                        @error('discount_4_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_5_materials">{{ __('general.Discount for 5 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_5_materials" id="discount_5_materials"
                                               value="{{ old('discount_5_materials', $grade->discount_5_materials) }}"
                                               class="form-control @error('discount_5_materials') is-invalid @enderror">
                                        @error('discount_5_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_6_materials">{{ __('general.Discount for 6 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_6_materials" id="discount_6_materials"
                                               value="{{ old('discount_6_materials', $grade->discount_6_materials) }}"
                                               class="form-control @error('discount_6_materials') is-invalid @enderror">
                                        @error('discount_6_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_7_materials">{{ __('general.Discount for 7 Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_7_materials" id="discount_7_materials"
                                               value="{{ old('discount_7_materials', $grade->discount_7_materials) }}"
                                               class="form-control @error('discount_7_materials') is-invalid @enderror">
                                        @error('discount_7_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="discount_all_materials">{{ __('general.Discount for All Materials (%)') }}</label>
                                        <input type="number" step="0.01" name="discount_all_materials" id="discount_all_materials"
                                               value="{{ old('discount_all_materials', $grade->discount_all_materials) }}"
                                               class="form-control @error('discount_all_materials') is-invalid @enderror">
                                        @error('discount_all_materials')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
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
