<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseMaterial;
use App\Models\Exam;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * §6 — تنزيل ملفات PDF (المذكرات والاختبارات) من خلال الـ API.
 *
 * قبل كده الملفات كانت بتتقدّم من /storage مباشرة، فالسيرفر كان يقدر يرجّع
 * صفحة HTML بكود 200 لو الملف مش موجود — والتطبيق بيخزّن الـ HTML ده كأنه PDF
 * ويفتح شاشة بيضا بعد كده. المسار ده بيضمن:
 *
 *   - `Content-Type: application/pdf` صح
 *   - `Content-Length` صح (عشان أي تنزيل ناقص يبان)
 *   - كود حالة حقيقي: 404 JSON لو الملف مش موجود، 403 لو مش مشترك
 *   - مفيش redirects لاستضافة تانية
 *
 * `type` بيفرّق بين المصدرين لأن `notes[].id` و `exams[].id` تسلسلين مستقلين
 * (جدولين مختلفين: course_materials و exams) فالـ id لوحده مش مُعرِّف كافي.
 */
class MaterialFileController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        $lang = $request->header('lang') === 'en' ? 'en' : 'ar';

        [$record, $subjectId] = match ($type) {
            'note' => $this->resolveNote($id),
            'exam' => $this->resolveExam($id),
            default => [null, null],
        };

        if (! $record) {
            return sendError(
                $lang === 'ar' ? 'الملف غير موجود.' : 'File not found.',
                [],
                404
            );
        }

        if (! $record->is_free && ! $this->hasAccess($request->user()?->id, $subjectId)) {
            return sendError(
                $lang === 'ar' ? 'هذا الملف متاح للمشتركين فقط.' : 'This file is available to subscribers only.',
                [],
                403
            );
        }

        $absolutePath = $this->resolveAbsolutePath($record->file);

        if (! $absolutePath) {
            return sendError(
                $lang === 'ar' ? 'الملف غير موجود على السيرفر.' : 'The file is missing on the server.',
                [],
                404
            );
        }

        $fileName = $this->downloadName($record, $lang);

        $response = response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) filesize($absolutePath),
            // الاسم العربي لازم يتكوّد RFC 5987، مع بديل ASCII للعملاء القديمة
            'Content-Disposition' => sprintf(
                'inline; filename="%s"; filename*=UTF-8\'\'%s',
                $this->asciiName($record, $fileName),
                rawurlencode($fileName)
            ),
            'Cache-Control' => 'private, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ]);

        if ($response instanceof BinaryFileResponse) {
            $response->setAutoLastModified();
        }

        return $response;
    }

    /**
     * @return array{0: ?CourseMaterial, 1: ?int}
     */
    protected function resolveNote(int $id): array
    {
        $note = CourseMaterial::where('id', $id)
            ->where('type', 'note')
            ->where('status', 1)
            ->first();

        return [$note, $note?->subject_id];
    }

    /**
     * @return array{0: ?Exam, 1: ?int}
     */
    protected function resolveExam(int $id): array
    {
        $exam = Exam::where('id', $id)->where('status', 1)->first();

        return [$exam, $exam?->subject_id];
    }

    protected function hasAccess(?int $userId, ?int $subjectId): bool
    {
        if (! $userId || ! $subjectId) {
            return false;
        }

        return Order::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('subject_id', $subjectId))
            ->exists();
    }

    /**
     * المسار المخزّن ممكن يكون على قرص public أو في public/ القديمة أو URL كامل.
     */
    protected function resolveAbsolutePath(?string $storedPath): ?string
    {
        $path = normalize_public_path($storedPath);

        if (! $path || preg_match('#^https?://#i', $path)) {
            return null;
        }

        $relative = ltrim(preg_replace('#^storage/#', '', $path), '/');

        foreach ([Storage::disk('public')->path($relative), public_path($relative), public_path($path)] as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function downloadName($record, string $lang): string
    {
        $name = $lang === 'en'
            ? ($record->name_en ?: $record->name_ar)
            : ($record->name_ar ?: $record->name_en);

        $name = preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', (string) $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name)) ?: 'file';

        return str_ends_with(strtolower($name), '.pdf') ? $name : $name . '.pdf';
    }

    /**
     * بديل ASCII لاسم الملف (اللي فيه حروف عربية) لهيدر Content-Disposition.
     */
    protected function asciiName($record, string $fallbackName): string
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $record->name_en);
        $ascii = trim((string) $ascii, '-');

        if ($ascii === '' || $ascii === '.pdf') {
            $ascii = 'file-' . $record->id;
        }

        return str_ends_with(strtolower($ascii), '.pdf') ? $ascii : $ascii . '.pdf';
    }
}
