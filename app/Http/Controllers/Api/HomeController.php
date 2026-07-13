<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Order;
use App\Models\Subject;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //index
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        $gradeId = null;
        $semesterId = null;
        $purchasedSubjectIds = collect();

        // ✅ محاولة الحصول على آخر طلب مدفوع
        $lastOrder = Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->latest()
            ->with('items.subject')
            ->first();

        if ($lastOrder && $lastOrder->items->isNotEmpty()) {
            $firstSubject = optional($lastOrder->items->first())->subject;

            if ($firstSubject) {
                $gradeId = $firstSubject->grade_id;
                $semesterId = $firstSubject->semester_id;
                $purchasedSubjectIds = $lastOrder->items->pluck('subject_id')->unique();
            }
        }

        // ✅ fallback: استخدام الصف والفصل من الريكوست في حال لم يكن هناك طلب مدفوع
        if (!$gradeId || !$semesterId) {
            $gradeId = $request->input('grade_id');
            $semesterId = $request->input('semester_id');
        }

        // ✅ التحقق النهائي: هل يوجد صف وفصل لتصفية المواد؟
        if (!$gradeId || !$semesterId) {
            return sendError( 'الرجاء تحديد الصف والفصل الدراسي.');
        }

        // ✅ جلب جميع المواد لهذا الصف والفصل
        $subjectQuery = Subject::with(['courseMaterials'])
            ->where('grade_id', $gradeId)
            ->where('semester_id', $semesterId);

        // ✅ تطبيق فلتر البحث إن وُجد
        if ($search) {
            $subjectQuery->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%$search%")
                    ->orWhere('name_en', 'like', "%$search%");
            });
        }

        $allSubjects = $subjectQuery->get();

        // ✅ تقسيم المواد إلى مشتراة وغير مشتراة
        $purchasedSubjects = $allSubjects->whereIn('id', $purchasedSubjectIds);
        $otherSubjects = $allSubjects->whereNotIn('id', $purchasedSubjectIds);

        // ✅ إرسال الرد النهائي
        $response = [
            'other_subjects' => SubjectResource::collection($otherSubjects),
            'purchased_subjects' => SubjectResource::collection($purchasedSubjects),
        ];

        return sendResponse($response);
    }

}
