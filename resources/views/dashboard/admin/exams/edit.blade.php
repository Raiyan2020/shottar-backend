@extends('dashboard.layouts.master')
@section('title', __('general.Update Exam'))
@section('css')
    <style>
        .vimeo-progress { height: 10px; }
        .vimeo-status   { font-size: 12px; color: #6c757d; }
    </style>
@endsection

@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">{{ __('general.Update Exam') }} — {{ app()->isLocale('ar') ? $subject->name_ar : $subject->name_en }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="form" action="{{ route(panelPrefix().'.subjects.exams.update', [$subject->id, $exam->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                            <div class="row">
                                <!-- الوحدة: اختياري — لو مختارتش، المرفق بيبقى على مستوى المادة كلها -->
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="lesson_section_id" class="col-form-label-sm">{{ __('general.unit') }}</label>
                                        <select name="lesson_section_id" id="lesson_section_id"
                                                class="form-control form-control-sm @error('lesson_section_id') is-invalid @enderror">
                                            <option value="">{{ __('general.Whole subject (no unit)') }}</option>
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}" {{ (string) old('lesson_section_id', $exam->lesson_section_id) === (string) $section->id ? 'selected' : '' }}>
                                                    {{ app()->getLocale() === 'en' ? $section->name_en : $section->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('lesson_section_id')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_ar" class="col-form-label-sm">{{ __('general.Name in Arabic') }}</label>
                                        <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar', $exam->name_ar) }}"
                                               class="form-control form-control-sm @error('name_ar') is-invalid @enderror" required>
                                        @error('name_ar')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="name_en" class="col-form-label-sm">{{ __('general.Name in English') }}</label>
                                        <input type="text" name="name_en" id="name_en" value="{{ old('name_en', $exam->name_en) }}"
                                               class="form-control form-control-sm @error('name_en') is-invalid @enderror" required>
                                        @error('name_en')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="file" class="col-form-label-sm">{{ __('general.PDF') }}</label>
                                        <input type="file" name="file" id="file" accept="application/pdf"
                                               class="form-control form-control-sm @error('file') is-invalid @enderror">
                                        @if($exam->file)
                                            <a href="{{ stored_file_url($exam->file) }}" target="_blank" class="d-block mt-1">
                                                {{ __('general.Current') }} PDF
                                            </a>
                                        @endif
                                        @error('file')
                                        <span class="col-form-label-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 col-12">
                                    <div class="form-group">
                                        <label for="is_free" class="col-form-label-sm">{{ __('general.is_free') }}</label>
                                        <select name="is_free" id="is_free" class="form-control form-control-sm">
                                            <option value="0" @selected(old('is_free', (int) $exam->is_free) == 0)>{{ __('general.No') }}</option>
                                            <option value="1" @selected(old('is_free', (int) $exam->is_free) == 1)>{{ __('general.Yes') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12">
                                    <div class="form-group">
                                        <label for="status" class="col-form-label-sm">{{ __('general.Status') }}</label>
                                        <select name="status" id="status" class="form-control form-control-sm">
                                            <option value="1" @selected(old('status', (int) $exam->status) == 1)>{{ __('general.Active') }}</option>
                                            <option value="0" @selected(old('status', (int) $exam->status) == 0)>{{ __('general.Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- فيديو حل الاختبار — اختياري، بيتضاف في أي وقت بعد إنشاء الاختبار --}}
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label class="col-form-label-sm d-block">{{ __('general.Solution Video') }}</label>

                                        @if($exam->video)
                                            <a href="{{ $exam->video }}" target="_blank" class="d-block mb-2">
                                                {{ __('general.Current') }} {{ __('general.Solution Video') }}
                                                ({{ __('general.Status') }}: {{ $exam->upload_status ?? '-' }})
                                            </a>
                                        @endif

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

                                        <input type="hidden" name="vimeo_uri" id="vimeo_uri" value="{{ old('vimeo_uri') }}">
                                        <input type="hidden" name="video" id="video_url" value="{{ old('video') }}">

                                        @error('video')
                                        <span class="col-form-label-sm text-danger d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">{{ __('general.Update') }}</button>
                                    <a href="{{ route(panelPrefix().'.subjects.exams.index', $subject->id) }}" class="btn btn-secondary">{{ __('general.Back') }}</a>
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
    <script src="{{ asset('dashboard/cdn/tus.min.js') }}"></script>
    <script>
        (function () {
            const TUS_SOURCES = [
                @json(asset('dashboard/cdn/tus.min.js')),
                'https://cdn.jsdelivr.net/npm/tus-js-client@4.3.1/dist/tus.min.js',
            ];

            function loadScript(src) {
                return new Promise((resolve, reject) => {
                    const el = document.createElement('script');
                    el.src = src;
                    el.onload = resolve;
                    el.onerror = () => reject(new Error(src));
                    document.head.appendChild(el);
                });
            }

            async function ensureTus() {
                if (typeof tus !== 'undefined') return true;

                for (const src of TUS_SOURCES) {
                    try {
                        await loadScript(src);
                        if (typeof tus !== 'undefined') return true;
                    } catch (e) {
                        console.warn('tus load failed:', src);
                    }
                }

                return false;
            }

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
            const formEl      = document.querySelector('form.form');

            if (!uploadBtn) return;

            let upload = null;
            let pending = null; // { key, uploadUrl, videoUri }

            function fileKey(f){ return [f.name, f.size, f.lastModified].join('|'); }

            function setBusy(b){
                uploadBtn.disabled = b;
                cancelBtn.disabled = !b;
            }
            function setProgress(pct){
                barEl.style.width = pct + '%';
                progTxt.textContent = pct + '%';
            }
            function setStatus(msg){ statusTxt.textContent = msg || ''; }
            function toMb(bytes){ return (bytes / (1024 * 1024)).toFixed(1); }

            uploadBtn.addEventListener('click', async () => {
                const file = fileInput ? fileInput.files?.[0] : null;
                if (!file) { alert(@json(__('general.Please select a video file'))); return; }

                const niceName = (nameEn?.value || nameAr?.value || file.name).trim().slice(0,200);

                if (file.size <= 0) { alert('Invalid file'); return; }

                setBusy(true); setProgress(0); setStatus(@json(__('general.Initializing...')));

                if (! await ensureTus()) {
                    setStatus(@json(__('general.Upload library failed to load. Check your internet connection and refresh the page.')));
                    setBusy(false);
                    return;
                }

                try {
                    let uploadUrl, videoUri;

                    if (pending && pending.key === fileKey(file)) {
                        uploadUrl = pending.uploadUrl;
                        videoUri  = pending.videoUri;
                        setStatus(@json(__('general.Resuming upload...')));
                    } else {
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

                        uploadUrl = data.upload_link;
                        videoUri  = data.video_uri;
                        pending   = { key: fileKey(file), uploadUrl, videoUri };
                    }

                    upload = new tus.Upload(file, {
                        uploadUrl,
                        chunkSize: 2 * 1024 * 1024,
                        retryDelays: [0, 1000, 3000, 5000, 10000, 15000, 30000],
                        onShouldRetry(error) {
                            const status = error?.originalResponse?.getStatus?.() ?? 0;
                            return status === 0
                                || status === 409 || status === 412 || status === 423
                                || status === 429 || status >= 500;
                        },
                        metadata: { filename: file.name, filetype: file.type },
                        storeFingerprintForResuming: false,
                        onError(error) {
                            console.error(error);
                            setStatus(@json(__('general.Upload failed. Press Upload again to resume from where it stopped.'))
                                + ' (' + (error?.message || error) + ')');
                            setBusy(false);
                        },
                        onProgress(bytesUploaded, bytesTotal) {
                            const pct = Math.min(100, Math.max(0, ((bytesUploaded / bytesTotal) * 100))).toFixed(0);
                            setProgress(pct);
                            setStatus(@json(__('general.Uploaded')) + ` ${toMb(bytesUploaded)} / ${toMb(bytesTotal)} MB`);
                        },
                        async onSuccess() {
                            setProgress(100);
                            setStatus(@json(__('general.Upload finished!')));
                            pending = null;

                            vimeoUriInp.value = videoUri;
                            videoUrlInp.value = 'https://vimeo.com' + videoUri;

                            // الفيديو هنا اختياري — مش زي الدرس، فبنسيب المدرّس
                            // يضغط "تحديث" بنفسه بدل الحفظ التلقائي.
                            setBusy(false);
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
                    pending = null;
                    setStatus(@json(__('general.Upload canceled')));
                    setBusy(false);
                    setProgress(0);
                }
            });
        })();
    </script>
@endsection
