<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ExamDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Models\Exam;
use App\Models\Subject;
use App\Services\RowOrderService;
use App\Traits\HandlesRowOrdering;
use App\Traits\HasStatusToggle;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    use HasStatusToggle, ImageTrait, HandlesRowOrdering {
        HasStatusToggle::toggleIsFree as toggleModelIsFree;
    }

    public function index(ExamDataTable $dataTable, Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        return $dataTable
            ->with('subject', $subject)
            ->render('dashboard.admin.exams.index', compact('subject'));
    }

    public function create(Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        $sections = $subject->lessonSections()->get();

        return view('dashboard.admin.exams.create', compact('subject', 'sections'));
    }

    public function store(ExamRequest $request, Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        $data = $request->validated();
        unset($data['file'], $data['video'], $data['vimeo_uri']);

        $data['subject_id'] = $subject->id;
        $data['status'] = $request->boolean('status', true);
        $data['is_free'] = $request->boolean('is_free', false);
        $data['uploaded_by'] = auth('admin')->id();
        $data['order_by'] = ((int) $subject->exams()->max('order_by')) + 1;

        if ($request->hasFile('file')) {
            $data['file'] = $this->uploadPdf($request->file('file'), 'exams');
        }

        Exam::create($data);

        return redirect()
            ->route(panelPrefix().'.subjects.exams.index', $subject->id)
            ->with('success', __('general.Exam created successfully'));
    }

    public function edit(Subject $subject, Exam $exam)
    {
        $this->authorizeTeacherSubject($subject);
        abort_unless($exam->subject_id === $subject->id, 404);

        $sections = $subject->lessonSections()->get();

        return view('dashboard.admin.exams.edit', compact('subject', 'exam', 'sections'));
    }

    public function update(ExamRequest $request, Subject $subject, Exam $exam)
    {
        $this->authorizeTeacherSubject($subject);
        abort_unless($exam->subject_id === $subject->id, 404);

        $data = $request->validated();
        unset($data['file'], $data['video'], $data['vimeo_uri']);

        $data['status'] = $request->boolean('status', $exam->status);
        $data['is_free'] = $request->boolean('is_free', $exam->is_free);

        if ($request->hasFile('file')) {
            $this->deleteImage($exam->file);
            $data['file'] = $this->uploadPdf($request->file('file'), 'exams');
        }

        // فيديو حل الاختبار — بيتبعت من صفحة التعديل عبر رفع Vimeo TUS، ممكن
        // يحصل في أي وقت بعد إنشاء الاختبار (مش وقت الرفع الأول للـ PDF).
        if ($request->filled('vimeo_uri')) {
            $data['vimeo_uri'] = $request->input('vimeo_uri');
            $data['video'] = $request->input('video');
            $data['upload_status'] = 'processing';

            try {
                $resp = Http::withToken(config('services.vimeo.access_token'))
                    ->withHeaders(['Accept' => 'application/vnd.vimeo.*+json;version=3.4'])
                    ->get('https://api.vimeo.com'.$request->vimeo_uri);

                if ($resp->successful()) {
                    $body = $resp->json();

                    if (isset($body['link'])) {
                        $data['video'] = $body['link'];
                        $data['upload_status'] = 'done';
                    }
                } else {
                    Log::error('Failed to fetch Vimeo info for exam solution video', [
                        'status' => $resp->status(),
                        'body' => $resp->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to fetch Vimeo link for exam solution video: '.$e->getMessage());
            }
        }

        $exam->update($data);

        return redirect()
            ->route(panelPrefix().'.subjects.exams.index', $subject->id)
            ->with('success', __('general.Exam updated successfully'));
    }

    public function destroy(Subject $subject, Exam $exam)
    {
        $this->authorizeTeacherSubject($subject);
        abort_unless($exam->subject_id === $subject->id, 404);

        $this->deleteImage($exam->file);
        $exam->delete();

        return response()->json('success');
    }

    public function sort(Request $request, Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer', 'distinct'],
        ]);

        $examIds = collect($data['order'])->pluck('id');
        abort_unless(
            Exam::query()->where('subject_id', $subject->id)->whereIn('id', $examIds)->count() === $examIds->count(),
            422,
            __('Invalid exam order.')
        );

        app(RowOrderService::class)->applyVisibleOrder(
            fn () => Exam::query()->where('subject_id', $subject->id),
            $examIds->all()
        );

        return response()->json(['status' => 'success']);
    }

    public function move(Request $request, Subject $subject, Exam $exam)
    {
        $this->authorizeTeacherSubject($subject);
        abort_unless($exam->subject_id === $subject->id, 404);

        return $this->moveRow(
            $request,
            $exam,
            fn () => Exam::query()->where('subject_id', $subject->id)
        );
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Exam::class, $id);
    }

    public function toggleIsFree($id)
    {
        return $this->toggleModelIsFree(Exam::class, $id);
    }

    private function authorizeTeacherSubject(Subject $subject): void
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('teacher')) {
            abort_unless($subject->teachers()->whereKey($user->id)->exists(), 403);
        }
    }
}
