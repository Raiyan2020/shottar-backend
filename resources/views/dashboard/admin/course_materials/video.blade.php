<!DOCTYPE html>
<html>
<head>
    <title>Direct Vimeo Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.jsdelivr.net/npm/tus-js-client/dist/tus.min.js"></script>

</head>
<body>

<h2>Upload Video Directly to Vimeo</h2>

<input type="file" id="videoFile" accept="video/*">
<input type="text" id="videoTitle" placeholder="Video title">
<button id="uploadBtn">Upload Video</button>
<div id="progress">Progress: 0%</div>

<script>
    const fileInput   = document.querySelector('#videoFile');
    const uploadButton= document.querySelector('#uploadBtn');
    const videoTitle  = document.querySelector('#videoTitle');
    const progressDiv = document.querySelector('#progress');

    uploadButton.addEventListener('click', async () => {
        const file = fileInput.files[0];
        if (!file) return alert('Please select a video file');
        if (!videoTitle.value) return alert('Please enter a video title');

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
        if (!uploadUrl || !data.video_uri) return alert('Failed to get Vimeo upload link');

        // 2) استخدم uploadUrl وليس endpoint
        const upload = new tus.Upload(file, {
            uploadUrl,               // ← المهم
            // لا تضع endpoint هنا
            chunkSize: 2 * 1024 * 1024, // 2MB
            retryDelays: [0, 1000, 3000, 5000],
            metadata: { filename: file.name, filetype: file.type },
            removeFingerprintOnSuccess: true,
            onError(error) {
                console.error(error);
                alert('Upload failed: ' + (error?.message || error));
            },
            onProgress(bytesUploaded, bytesTotal) {
                const pct = ((bytesUploaded / bytesTotal) * 100).toFixed(2);
                progressDiv.textContent = `Progress: ${pct}%`;
            },
            async onSuccess() {
                progressDiv.textContent = 'Upload finished! Saving...';

                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const save = await fetch('/vimeo-save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ video_uri: data.video_uri, title: videoTitle.value })
                });

                const result = await save.json();
                console.log(result);
                alert('Video uploaded and saved successfully!');
                progressDiv.textContent = 'Upload and save completed!';
            }
        });

        upload.start();
    });
</script>


</body>
</html>
