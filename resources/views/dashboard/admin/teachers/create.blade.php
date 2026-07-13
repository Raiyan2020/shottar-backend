@extends('dashboard.layouts.master')
@section('title', __('general.Add Teacher') )

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">{{ __('general.Add Teacher') }}</h4>
                    </div>

                    <div class="card-body">
                        <form class="form" action="{{ route('admin.teachers.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                {{-- الاسم --}}
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="name">{{ __('general.Name in english') }}</label>
                                        <input value="{{ old('name') }}" name="name" type="text" id="name"
                                               class="form-control form-control-sm @error('name') is-invalid @else {{ old('name') ? 'is-valid' : '' }} @enderror"
                                               placeholder="John" required />
                                        @error('name') <span class="col-form-label-sm text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- الإيميل --}}
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="email">{{ __('general.Email') }}</label>
                                        <input value="{{ old('email') }}" type="email" id="email" name="email"
                                               class="form-control form-control-sm @error('email') is-invalid @else {{ old('email') ? 'is-valid' : '' }} @enderror"
                                               placeholder="john@example.com" required />
                                        @error('email') <span class="col-form-label-sm text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- كلمة المرور + تأكيد --}}
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="password">{{ __('general.Password') }}</label>
                                        <input name="password" type="password" id="password"
                                               class="form-control form-control-sm @error('password') is-invalid @else {{ old('password') ? 'is-valid' : '' }} @enderror"
                                               placeholder="••••••••••••" required />
                                        @error('password') <span class="col-form-label-sm text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="password_confirmation">{{ __('general.Password confirmation') }}</label>
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                               class="form-control form-control-sm" placeholder="••••••••••••" />
                                    </div>
                                </div>

                                {{-- الصورة (اختياري) --}}
                                <div class="col-lg-12 col-md-12">
                                    <div class="form-group">
                                        <label for="customFile">{{ __('general.Image') }}</label>
                                        <div class="custom-file">
                                            <input name="photo" type="file"
                                                   class="custom-file-input @error('photo') is-invalid @else {{ old('photo') ? 'is-valid' : '' }} @enderror"
                                                   id="customFile" />
                                            <label class="custom-file-label" for="customFile">{{ __('general.Choose file') }}</label>
                                        </div>
                                        @error('photo') <span class="col-form-label-sm text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- إسناد المواد للمعلّم --}}
                                <div class="col-12">
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">{{ __('general.Assign Subjects to Teacher') }}</h5>
                                        <div class="d-flex gap-1">
                                            <button type="button" id="select-all" class="btn btn-outline-secondary btn-sm">{{ __('general.Select All') }}</button>
                                            <button type="button" id="clear-all" class="btn btn-outline-secondary btn-sm">{{ __('general.Clear') }}</button>
                                        </div>
                                    </div>

                                    <select id="subject_ids" name="subject_ids[]" class="form-control" multiple data-placeholder="{{ __('general.Select Subjects') }}">
                                        @foreach($subjects as $s)
                                            <option value="{{ $s->id }}" {{ in_array($s->id, old('subject_ids', [])) ? 'selected' : '' }}>
                                                {{ $s->name_en . '(' . $s->grade?->name_en . ')'.'('.$s->semester?->name_en . ')' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subject_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="modal-footer w-100">
                                    <div class="form-group col-md-12">
                                        <button type="submit" class="btn btn-primary mr-1">{{ __('general.Submit') }}</button>
                                    </div>
                                </div>
                            </div> {{-- row --}}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
    <script>
        (function () {
            // ملف الصورة
            const fileInput = document.getElementById('customFile');
            if (fileInput) {
                fileInput.addEventListener('change', function (e) {
                    const fileName = e.target.files[0] ? e.target.files[0].name : '{{ __('general.Choose file') }}';
                    e.target.nextElementSibling.textContent = fileName;
                });
            }

            // Select2
            const $select = $('#subject_ids');
            if ($select.length) {
                $select.select2({ width: '100%', placeholder: $select.data('placeholder'), allowClear: true });

                document.getElementById('select-all').addEventListener('click', function(){
                    const all = Array.from($select.find('option')).map(o => o.value);
                    $select.val(all).trigger('change');
                });
                document.getElementById('clear-all').addEventListener('click', function(){
                    $select.val(null).trigger('change');
                });
            }
        })();
    </script>
@endsection
