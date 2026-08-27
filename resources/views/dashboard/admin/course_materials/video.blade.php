<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <title>{{ __('Direct Vimeo Upload') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="{{ asset('dashboard/cdn/tus.min.js') }}"></script>
    <script>
        // احتياطي لو الملف المحلي مش موجود
        if (typeof tus === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/tus-js-client@4.3.1/dist/tus.min.js"><\/script>');
        }
    </script>

</head>
<body>

<h2>{{ __('Upload Video Directly to Vimeo') }}</h2>

<input type="file" id="videoFile" accept="video/*">
<input type="text" id="videoTitle" placeholder="{{ __('Video title') }}">
<button id="uploadBtn">{{ __('Upload Video') }}</button>
<div id="progress">{{ __('Progress') }}: 0%</div>

<script>
    const fileInput   = document.querySelector('#videoFile');
    const uploadButton= document.querySelector('#uploadBtn');
    const videoTitle  = document.querySelector('#videoTitle');
    const progressDiv = document.querySelector('#progress');
    const translations = @json([
        'selectFile' => __('general.Please select a video file'),
        'enterTitle' => __('Please enter a video title'),
        'linkFailed' => __('Failed to get Vimeo upload link'),
        'uploadFailed' => __('Upload failed'),
        'progress' => __('Progress'),
        'saving' => __('Upload finished! Saving...'),
        'saved' => __('Video uploaded and saved successfully!'),
        'completed' => __('Upload and save completed!'),
    ]);

    uploadButton.addEventListener('click', async () => {
        const file = fileInput.files[0];
        if (!file) return alert(translations.selectFile);
        if (!videoTitle.value) return alert(translations.enterTitle);

        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        // 1) اطلب رابط الرفع TUS الجاهز من السيرفر (يجب أن ينشئ فيديو على Vimeo ويعيد upload_link + uri)
        const res  = await fetch('/vimeo-upload-url', { method: 'POST',
            headers:{'Content-Type':'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ size: file.size, name: videoTitle.value }) });
        const data = await res.json();

        // تأكد أننا نستلم الرابط الصحيح من Vimeo: upload_link (أحيانًا يسمّى upload_url عندك)
        const uploadUrl = data.upload_link || data.upload_url;
        if (!uploadUrl || !data.video_uri) return alert(translations.linkFailed);

        // 2) استخدم uploadUrl وليس endpoint
        const upload = new tus.Upload(file, {
            uploadUrl,               // ← المهم
            // لا تضع endpoint هنا
            chunkSize: 2 * 1024 * 1024, // 2MB
            // Vimeo بيرجّع 412 لما الـ Upload-Offset مش متطابق. الافتراضي في tus
            // إن أي 4xx خطأ نهائي، فالرفع كان بيموت في النص. الإعادة بتعمل HEAD
            // وتقرأ الـ offset الحقيقي وتكمّل من عنده.
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
                alert(translations.uploadFailed + ': ' + (error?.message || error));
            },
            onProgress(bytesUploaded, bytesTotal) {
                const pct = ((bytesUploaded / bytesTotal) * 100).toFixed(2);
                progressDiv.textContent = `${translations.progress}: ${pct}%`;
            },
            async onSuccess() {
                progressDiv.textContent = translations.saving;

                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const save = await fetch('/vimeo-save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ video_uri: data.video_uri, title: videoTitle.value })
                });

                const result = await save.json();
                console.log(result);
                alert(translations.saved);
                progressDiv.textContent = translations.completed;
            }
        });

        upload.start();
    });
</script>


</body>
</html>
