@extends('dashboard.layouts.master')
@section('title', __('general.Send Notification'))
@section('css')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css"/>
@endsection

@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('Send Notification') }}</h4>
                    </div>
                    <div class="card-body">

                        <form class="form" action="{{ route('admin.notifications.store') }}" method="post">
                            @csrf

                            <div class="row">

                                <!-- Notification Title -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm">{{ __('title') }}</label>
                                        <input type="text" name="title" class="form-control form-control-sm" required>
                                    </div>
                                </div>

                                <!-- Notification Body -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm">{{ __('body') }}</label>
                                        <textarea name="body" class="form-control form-control-sm" rows="2" required></textarea>
                                    </div>
                                </div>

                                <!-- Send Type -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm">إرسال إلى</label>
                                        <select id="send_type" name="send_type" class="form-control form-control-sm" required>
                                            <option value="all">جميع المستخدمين</option>
                                            <option value="one">مستخدم واحد</option>
                                            <option value="group">مجموعة مستخدمين</option>

                                        </select>
                                    </div>
                                </div>

                                <!-- Select One User -->
                                <div class="col-md-6 col-12 send_one_box d-none">
                                    <div class="form-group">
                                        <label class="col-form-label-sm">اختر المستخدم</label>
                                        <select name="user_id" class="form-control form-control-sm select2_single_user">
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->phone }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Select Multiple Users -->
                                <div class="col-md-6 col-12 send_group_box d-none">
                                    <div class="form-group">
                                        <label class="col-form-label-sm">اختر المستخدمين</label>
                                        <select name="users[]" multiple class="form-control form-control-sm select2_multi_users">
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->phone }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mt-1">{{ __('Send') }}</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        // Select2
        $('.select2_single_user').select2();
        $('.select2_multi_users').select2();

        // Show/Hide Fields Based on Send Type
        $('#send_type').on('change', function () {
            let type = $(this).val();

            if (type === 'one') {
                $('.send_one_box').removeClass('d-none');
                $('.send_group_box').addClass('d-none');
            }
            else if (type === 'group') {
                $('.send_group_box').removeClass('d-none');
                $('.send_one_box').addClass('d-none');
            }
            else {
                $('.send_one_box, .send_group_box').addClass('d-none');
            }
        });
    </script>
@endsection
