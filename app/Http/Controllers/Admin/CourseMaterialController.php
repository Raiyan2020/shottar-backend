<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CourseMaterialDataTable;
use App\Http\Controllers\Controller;
use App\Jobs\SyncVimeoDurationJob;
use App\Http\Requests\CourseMaterialRequest;
use App\Jobs\UploadVideoToVimeoJob;
use App\Models\CourseMaterial;
use App\Models\LessonSection;
use App\Models\Notification;
use App\Models\Subject;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Traits\HandlesRowOrdering;
use App\Traits\HasStatusToggle;
use App\Services\FirebaseNotificationService;
use App\Services\RowOrderService;
use App\Services\VimeoService;
use Illuminate\Support\Facades\Http;
use Vimeo\Vimeo;

class CourseMaterialController extends Controller
{
    use HasStatusToggle , ImageTrait, HandlesRowOrdering;

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
        $this->authorizeTeacherSubject($subject);

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

                        // ⚠️ Vimeo لسه بيرمّز الفيديو في اللحظة دي، فالـ duration
                        // بيرجع 0. بنحفظه بس لو رقم حقيقي، والباقي بيتظبط من
                        // SyncVimeoDurationJob بعد ما الترميز يخلص.
                        if (! empty($body['duration'])) {
                            $data['duration'] = (int) $body['duration'];
                            $data['duration_text'] = gmdate('H:i:s', (int) $body['duration']);
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

            // status مش دايمًا موجود في $data (بيعتمد على الـ default بتاع
            // العمود لما الأدمن ميبعتوش)، فـ create() بيسيبه null في نسخة
            // الذاكرة رغم إن الداتابيز فعلاً خزّنت true. refresh() بيجيب
            // الحالة الحقيقية عشان أي شرط بعد كده (زي إشعار الدرس الجديد)
            // ما يتخدعش بقيمة null فاضية.
            $material->refresh();

            // لو المدة لسه مش جاهزة (Vimeo بيرمّز)، نجيبها لاحقًا بدل ما تفضل صفر.
            if ($material->type === 'lesson' && empty($material->duration) && ! empty($material->video)) {
                SyncVimeoDurationJob::dispatch($material->id)->delay(now()->addMinutes(2));
            }

            // إشعار "تعليمية" لكل المشتركين في المادة لما درس جديد ومفعّل
            // يتضاف. اتحط في try مستقل عشان فشل الإشعار (مثلاً Firebase
            // واقع) ميوقّعش الصفحة بـ"فشل الحفظ" رغم إن الدرس اتسجّل فعلاً.
            if ($material->type === 'lesson' && $material->status) {
                try {
                    $this->notifySubjectSubscribers($subject, $material);
                } catch (\Throwable $notifyError) {
                    \Log::error('Educational notification failed: '.$notifyError->getMessage());
                }
            }

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

    /**
     * بيبعت إشعار "تعليمية" لكل المستخدمين اللي مشتركين في المادة (أوردر
     * status=paid يحتوي عليها)، لما درس جديد ومفعّل يتضاف. بنعمل insert
     * جماعي للصفوف وبوش واحد multicast بدل ما نلف على كل مستخدم بنداء
     * منفصل لـ Firebase — العدد ممكن يبقى كبير حسب شعبية المادة.
     */
    private function notifySubjectSubscribers(Subject $subject, CourseMaterial $material): void
    {
        $userIds = $subject->orders()->where('status', 'paid')->pluck('user_id')->unique()->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $users = \App\Models\User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'device_token', 'notification_enabled']);

        $titleAr = 'درس جديد في '.$subject->name_ar;
        $bodyAr = 'تم إضافة درس جديد: '.$material->name_ar;
        $titleEn = 'New lesson in '.($subject->name_en ?: $subject->name_ar);
        $bodyEn = 'A new lesson has been added: '.($material->name_en ?: $material->name_ar);

        $now = now();
        $rows = $users->map(fn ($u) => [
            'user_id' => $u->id,
            'title' => $titleAr,
            'title_en' => $titleEn,
            'body' => $bodyAr,
            'body_en' => $bodyEn,
            'type' => 'user',
            'category' => 'educational',
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notification::insert($rows);

        $tokens = $users->where('notification_enabled', true)
            ->pluck('device_token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens !== []) {
            app(FirebaseNotificationService::class)->sendNotification($tokens, $titleAr, $bodyAr, [
                'type' => 'user',
                'category' => 'educational',
            ]);
        }
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
                    // من غير كده الفيديو بياخد privacy الحساب الافتراضي (عادة
                    // private)، فالرابط بيرجع "couldn't find that page" لأي
                    // حد مش مسجّل دخول على نفس حساب Vimeo — حتى بعد ما نحفظ
                    // الرابط الصحيح من الـ API.
                    'privacy' => [
                        'view' => 'unlisted',
                    ],
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

    public function sort(Request $request, $type, $sectionId)
    {
        $data = $request->validate([
            'subject' => ['required', 'integer', 'exists:subjects,id'],
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer', 'distinct'],
            'order.*.order_by' => ['required', 'integer'],
        ]);

        $subject = Subject::findOrFail($data['subject']);
        $this->authorizeTeacherSubject($subject);
        $scope = $this->materialOrderScope($subject, $type, $sectionId);

        $materialIds = collect($data['order'])->pluck('id');
        abort_unless(
            $scope()->whereIn('id', $materialIds)->count() === $materialIds->count(),
            422,
            __('Invalid material order.')
        );

        // 🔒 النطاق بيمنع التعديل على مواد بره المادة/النوع/الوحدة الحالية،
        // وبيحافظ على المراكز العامة بدل ما يرقّم الصفوف الظاهرة من 1.
        app(RowOrderService::class)->applyVisibleOrder(
            $scope,
            collect($data['order'])->pluck('id')->all()
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * نقل درس/ملاحظة مركز واحد داخل نفس الوحدة ونفس النوع.
     *
     * نطاق الترتيب هو نفس نطاق sort() بالظبط: lesson_section_id + type.
     */
    public function move(Request $request, $type, $sectionId, CourseMaterial $material)
    {
        $data = $request->validate([
            'subject' => ['required', 'integer', 'exists:subjects,id'],
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $subject = Subject::findOrFail($data['subject']);
        $this->authorizeTeacherSubject($subject);
        $scope = $this->materialOrderScope($subject, $type, $sectionId);

        abort_unless($scope()->whereKey($material->id)->exists(), 404);

        return $this->moveRow(
            $request,
            $material,
            $scope
        );
    }

    private function materialOrderScope(Subject $subject, string $type, $sectionId): \Closure
    {
        return function () use ($subject, $type, $sectionId) {
            return CourseMaterial::query()
                ->where('subject_id', $subject->id)
                ->where('type', $type)
                ->when(
                    $sectionId === 'none',
                    fn ($query) => $query->whereNull('lesson_section_id'),
                    fn ($query) => $query->where('lesson_section_id', $sectionId)
                );
        };
    }

    private function authorizeTeacherSubject(Subject $subject): void
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('teacher')) {
            abort_unless($subject->teachers()->whereKey($user->id)->exists(), 403);
        }
    }



}
