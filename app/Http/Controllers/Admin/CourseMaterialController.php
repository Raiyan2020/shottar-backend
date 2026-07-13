<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CourseMaterialDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseMaterialRequest;
use App\Jobs\UploadVideoToVimeoJob;
use App\Models\CourseMaterial;
use App\Models\LessonSection;
use App\Models\Subject;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Traits\HasStatusToggle;
use App\Services\VimeoService;
use Illuminate\Support\Facades\Http;
use Vimeo\Vimeo;

class CourseMaterialController extends Controller
{
    use HasStatusToggle , ImageTrait;

    protected ?Vimeo $client = null;

    protected function vimeo(): Vimeo
    {
        if ($this->client) {
            return $this->client;
        }

        $clientId = config('services.vimeo.client_id');
        $clientSecret = config('services.vimeo.client_secret');
        $accessToken = config('services.vimeo.access_token');

        if (! is_string($clientId) || $clientId === '' ||
            ! is_string($clientSecret) || $clientSecret === '' ||
            ! is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException(
                'Vimeo credentials are missing. Set VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, and VIMEO_ACCESS_TOKEN in your .env file.'
            );
        }

        return $this->client = new Vimeo($clientId, $clientSecret, $accessToken);
    }

    public function index(CourseMaterialDataTable $dataTable, Subject $subject,$type)
    {
        //type lesson or note
        $type = $type ?? 'lesson'; // Default to 'lesson' if not provided
        $sectionId = request()->get('section'); // Get section ID from query parameters

        return $dataTable->with('subject', $subject)->render('dashboard.admin.course_materials.index', [
            'subject' => $subject,
            'type' =>$type,
            'sectionId' => $sectionId, // Pass section ID to the view
        ]);
    }

    public function create(Subject $subject, Request $request)
    {
        $type = $request->route('type'); // Get type from route, default to 'lesson'
        $sections = $subject->lessonSections()->get();
        $sectionId = $request->get('section') ; // Get section ID from query parameters

        return view('dashboard.admin.course_materials.create', compact('subject', 'sections', 'type','sectionId'));
    }
    public function store(CourseMaterialRequest $request, Subject $subject /*, VimeoService $vimeoService */)
    {
        $data = $request->validated();

        try {
            // 1) ملفات عادية (notes أو مرفقات)
            if ($request->hasFile('file')) {
                $data['file'] = $this->uploadImage('admin', $request->file('file'));
            }

            // 2) فيديو عبر Vimeo TUS (من الواجهة)
            if ($request->filled('vimeo_uri')) {
                // نبني الرابط العام
                $data['video']     = $request->input('video');
                $data['vimeo_uri'] = $request->input('vimeo_uri');
                $data['upload_status'] = 'processing'; // لأن Vimeo بيرمز بعد الرفع
            } else {
                // إن ما وصل vimeo_uri (وطبعاً ما بدنا نرفع ملف فيديو هنا)، خلّي الحالة pending
                $data['upload_status'] = $data['upload_status'] ?? 'pending';
            }

            $data['uploaded_by'] = auth('admin')->id();
            if ($request->filled('vimeo_uri')) {
                try {
                    $resp = Http::withToken(config('services.vimeo.access_token'))
                        ->withHeaders(['Accept' => 'application/vnd.vimeo.*+json;version=3.4'])
                        ->get("https://api.vimeo.com" . $request->vimeo_uri);
//                    return $resp->json();
                    if ($resp->successful()) {
                        $body = $resp->json();

                        if (isset($body['link'])) {
                            // الرابط العام الصحيح (https://vimeo.com/{id}/{hash})
                            $data['video'] = $body['link'];
                        }

                        if (isset($body['duration'])) {
                            $data['duration'] = $body['duration'];
                            $data['duration_text'] = gmdate('H:i:s', $body['duration']);
                        }
                    } else {
                        \Log::error('Failed to fetch Vimeo info', [
                            'status' => $resp->status(),
                            'body'   => $resp->body()
                        ]);
                    }

                } catch (\Throwable $e) {
                    \Log::error("Failed to fetch Vimeo link: " . $e->getMessage());
                }
            }

            // أنشئ السجل
            $maxOrder = $subject->courseMaterials()->max('order_by');
            $data['order_by'] = $maxOrder ? $maxOrder + 1 : 1;

            $material = $subject->courseMaterials()->create($data);

            return redirect()
                ->route(panelPrefix().'.subjects.materials.index', [
                    $subject->id,
                        $data['type'] ?? 'lesson',
                    'section' => $request->get('section'),
                ])
                ->with('success', __('Created Successfully'));

        } catch (\Throwable $e) {
            \Log::error('CourseMaterial store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors([
                'general' => 'فشل الحفظ: ' . $e->getMessage()
            ]);
        }
    }


    public function edit(Subject $subject, CourseMaterial $material)
    {
        $type = $material->type;
        $sections = $subject->lessonSections()->get();

        return view('dashboard.admin.course_materials.edit', compact('subject', 'material', 'sections','type'));
    }

    public function update(CourseMaterialRequest $request, Subject $subject, CourseMaterial $material)
    {
        $data = $request->validated();

        if ($request->has('file')) {
            $data['file'] = $this->uploadImage('admin', $request->file);
        }
        if ($request->has('video')) {
            $videoPath = $this->uploadImage('admin', $request->video);
            $data['video'] = $videoPath;

            $ffprobe = \FFMpeg\FFProbe::create();
            $absolutePath = public_path($videoPath);

            $durationInSeconds = (int) $ffprobe
                ->format($absolutePath)
                ->get('duration');

            $data['duration'] = $durationInSeconds;
            $data['duration_text'] = gmdate("H:i:s", $durationInSeconds);
        }

        $material->update($data);

        return redirect()->route(panelPrefix().'.subjects.materials.index', [$subject->id,$data['type'] ?? 'lesson'])
            ->with('success', __('Updated Successfully'));
    }

    public function destroy(Subject $subject, CourseMaterial $material)
    {
        $material->delete();

        return response()->json(['status' => true]);
    }

    public function toggleStatus($materialId)
    {
        return $this->toggleStatu(CourseMaterial::class, $materialId);
    }
    //is free
    public function toggleIsFrees($materialId)
    {
        return $this->toggleIsFree(CourseMaterial::class, $materialId);
    }


    public function getUploadUrl(Request $request)
    {
        $data = $request->validate([
            'size' => 'required|integer|min:1',     // حجم الملف بالبايت
            'name' => 'nullable|string|max:200',    // اسم الفيديو الاختياري
        ]);

        try {
            $resp = $this->vimeo()->request(
                '/me/videos',
                [
                    'upload' => [
                        'approach' => 'tus',
                        'size'     => (int) $data['size'],
                    ],
                    'name' => $data['name'] ?? 'Untitled',
                ],
                'POST',
                [ 'Accept' => 'application/vnd.vimeo.*+json;version=3.4' ]
            );

            // تحقّق من الرد
            $body = $resp['body'] ?? [];
            $uploadLink = $body['upload']['upload_link'] ?? null;
            $videoUri   = $body['uri'] ?? null;

            if (!$uploadLink || !$videoUri) {
                return response()->json([
                    'error'   => 'Vimeo API did not return a valid upload link',
                    'details' => $resp,
                ], 502);
            }

            // نرجّع المفتاحين للتوافق مع الواجهة
            return response()->json([
                'upload_link' => $uploadLink,           // لـ tus.Upload(uploadUrl)
                'upload_url'  => $uploadLink,           // لو واجهتك تقرأ upload_url
                'video_uri'   => $videoUri,             // مثل /videos/123456789
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Exception while requesting Vimeo upload URL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //stor

    public function sort(Request $request, $type,$sectionId)
    {

        foreach ($request->order as $item) {
            CourseMaterial::where('id', $item['id'])
                ->where('lesson_section_id', $sectionId) // 🔒 تأكد أنه لن يعدل إلا على أقسام هذه المادة
                    ->where('type',$type)
                ->update(['order_by' => $item['order_by']]);
        }
        return response()->json(['status' => 'success']);
    }



}
