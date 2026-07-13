@extends('dashboard.layouts.master')
@section('title', $type == 'lesson' ? __('general.Add Lesson') : __('general.Add Note'))

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('dashboard/app-assets/css/components.css') }}">
    <style>
        .vimeo-progress { height: 10px; }
        .vimeo-status   { font-size: 12px; color: #6c757d; }
    </style>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title mb-0">
                            {{ $type == 'lesson' ? __('general.Add Lesson') : __('general.Add Note') }}
                        </h4>
                    </div>

                    <div class="card-body">
                        <form class="form" action="{{ route(panelPrefix().'.subjects.materials.store', [$subject->id, 'type' => $type , 'section' => $sectionId]) }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                            <input type="hidden" name="type" value="{{ $type }}">

                            <div class="row">
                                <!-- Arabic Name -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar') }}"
                                               class="form-control form-control-sm @error('name_ar') is-invalid @enderror" required>
                                        @error('name_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- English Name -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input type="text" name="name_en" id="name_en" value="{{ old('name_en') }}"
                                               class="form-control form-control-sm @error('name_en') is-invalid @enderror" required>
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Section -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="lesson_section_id">{{ __('general.lesson_sections') }}</label>

                                        @if(auth('admin')->check() && auth('admin')->user()->hasRole('teacher'))
                                            <select name="lesson_section_id" id="lesson_section_id" class="form-control form-control-sm" disabled>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}" {{ request('section') == $section->id ? 'selected' : '' }}>
                                                        {{ $section->name_ar }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="lesson_section_id" value="{{ request('section') }}">
                                        @else
                                            <select name="lesson_section_id" id="lesson_section_id" class="form-control form-control-sm @error('lesson_section_id') is-invalid @enderror" required>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}" {{ old('lesson_section_id') == $section->id ? 'selected' : '' }}>
                                                        {{ $section->name_ar }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error('lesson_section_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- is_free -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm" for="is_free">{{ __('general.is_free') }}</label>
                                        <select name="is_free" id="is_free"
                                                class="form-control form-control-sm @error('is_free') is-invalid @else {{ old('is_free') ? 'is-valid' : '' }} @enderror"
                                                required>
                                            <option value="1" {{ old('is_free') === '1' ? 'selected' : '' }}>{{ __('general.Active') }}</option>
                                            <option value="0" {{ old('is_free') === '0' ? 'selected' : '' }}>{{ __('general.Inactive') }}</option>
                                        </select>
                                        @error('is_free')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                @if($type == 'lesson')
                                    {{-- Vimeo Direct Upload (TUS) --}}
                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label class="col-form-label-sm d-block">{{ __('general.Video File') }}</label>

                                            <div class="mb-2">
                                                <input type="file" id="vimeo_file" accept="video/*" class="form-control form-control-sm">
                                            </div>

                                            <div class="d-flex gap-1 mb-2">
                                                <button type="button" id="vimeo_upload_btn" class="btn btn-info btn-sm">
                                                    {{ __('general.Upload to video') }}
                                                </button>
                                                <button type="button" id="vimeo_cancel_btn" class="btn btn-outline-danger btn-sm" disabled>
                                                    {{ __('general.cancel') }}
                                                </button>
                                            </div>

                                            <div class="mb-1">
                                                <div class="progress vimeo-progress">
                                                    <div id="vimeo_bar" class="progress-bar" role="progressbar" style="width:0%;"></div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1 vimeo-status">
                                                    <small id="vimeo_progress">0%</small>
                                                    <small id="vimeo_status"></small>
                                                </div>
                                            </div>

                                            {{-- hidden inputs get filled after successful upload --}}
                                            <input type="hidden" name="vimeo_uri" id="vimeo_uri" value="{{ old('vimeo_uri') }}">
                                            <input type="hidden" name="video" id="video_url" value="{{ old('video') }}">

                                            @error('video')
                                            <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @else
                                    <!-- Regular File Upload (Notes) -->
                                    <div class="col-md-12 col-12">
                                        <div class="form-group">
                                            <label for="file" class="col-form-label-sm">{{ __('general.Upload File') }}</label>
                                            <input type="file" name="file" id="file"
                                                   class="form-control form-control-sm @error('file') is-invalid @enderror">
                                            @error('file')
                                            <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Submit -->
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <button type="submit" class="btn btn-primary">{{ __('general.Save') }}</button>
                                    </div>
                                </div>

                            </div> <!-- /.row -->
                        </form>
                    </div> <!-- /.card-body -->
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/tus-js-client/dist/tus.min.js"></script>
    <script>
        (function () {
            const fileInput   = document.getElementById('vimeo_file');
            const uploadBtn   = document.getElementById('vimeo_upload_btn');
            const cancelBtn   = document.getElementById('vimeo_cancel_btn');
            const barEl       = document.getElementById('vimeo_bar');
            const progTxt     = document.getElementById('vimeo_progress');
            const statusTxt   = document.getElementById('vimeo_status');
            const vimeoUriInp = document.getElementById('vimeo_uri');
            const videoUrlInp = document.getElementById('video_url');
            const nameAr      = document.getElementById('name_ar');
            const nameEn      = document.getElementById('name_en');

            let upload = null;

            function setBusy(b){
                uploadBtn.disabled = b;
                cancelBtn.disabled = !b;
            }
            function setProgress(pct){
                barEl.style.width = pct + '%';
                progTxt.textContent = pct + '%';
            }
            function setStatus(msg){ statusTxt.textContent = msg || ''; }

            uploadBtn.addEventListener('click', async () => {
                const file = fileInput ? fileInput.files?.[0] : null;
                if (!file) { alert(@json(__('general.Please select a video file'))); return; }

                // اسم الفيديو (يُرسل لعرضه في Vimeo)
                const niceName = (nameEn?.value || nameAr?.value || file.name).trim().slice(0,200);

                // تحقق مبدئي من الحجم/النوع (اختياري)
                const allowedTypes = ['video/mp4','video/quicktime','video/x-msvideo','video/mpeg'];
                if (file.size <= 0) { alert('Invalid file'); return; }
                // if (!allowedTypes.includes(file.type)) { alert('Unsupported video type'); return; }

                setBusy(true); setProgress(0); setStatus(@json(__('general.Initializing...')));

                try {
                    // ملاحظة: نستخدم API بدون CSRF لتجنب 419
                    // const initRes = await fetch('/vimeo-upload-url', {
                    //     method: 'POST',
                    //     headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    //     body: JSON.stringify({ size: file.size, name: niceName })
                    // });

                    // === إذا حاب تستخدم web.php بدلاً من api.php ===
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    const initRes = await fetch('/vimeo-upload-url', {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                      },
                      body: JSON.stringify({ size: file.size, name: niceName })
                    });

                    const data = await initRes.json();
                    if (!initRes.ok || !data.upload_link || !data.video_uri) {
                        console.error(data);
                        throw new Error('Failed to get Vimeo upload link');
                    }

                    const uploadUrl = data.upload_link; // PATCH endpoint (TUS)
                    const videoUri  = data.video_uri;   // /videos/{id}

                    upload = new tus.Upload(file, {
                        uploadUrl,
                        chunkSize: 2 * 1024 * 1024, // 2MB
                        retryDelays: [0, 1000, 3000, 5000],
                        metadata: { filename: file.name, filetype: file.type },
                        removeFingerprintOnSuccess: true,
                        onError(error) {
                            console.error(error);
                            setStatus('Upload failed: ' + (error?.message || error));
                            setBusy(false);
                        },
                        onProgress(bytesUploaded, bytesTotal) {
                            const pct = Math.min(100, Math.max(0, ((bytesUploaded / bytesTotal) * 100))).toFixed(0);
                            setProgress(pct);
                            setStatus(@json(__('general.Uploaded')) + ` ${bytesUploaded} / ${bytesTotal} bytes`);
                        },
                        async onSuccess() {
                            setProgress(100);
                                setStatus(@json(__('general.Upload finished!')));

                            // عبّي الحقول المخفية — هي اللي راح نحفظها مع باقي بيانات الدرس
                            vimeoUriInp.value = videoUri;
                            videoUrlInp.value = 'https://vimeo.com' + videoUri;

                            // خيار: تقديم حفظ تلقائي بعد الرفع
                            document.querySelector('form.form')?.submit();

                            // setBusy(false);
                        }
                    });

                    upload.start();

                } catch (e) {
                    console.error(e);
                    setStatus('Error: ' + (e?.message || e));
                    setBusy(false);
                }
            });

            cancelBtn.addEventListener('click', () => {
                if (upload) {
                    try { upload.abort(); } catch(e){}
                    upload = null;
                    setStatus(@json(__('general.Upload canceled')));
                    setBusy(false);
                    setProgress(0);
                }
            });
        })();
    </script>
@endsection
