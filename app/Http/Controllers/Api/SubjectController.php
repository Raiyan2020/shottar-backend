<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectDetailResource;
use App\Http\Resources\SubjectResource;
use App\Models\CourseMaterial;
use App\Models\Grade;
use App\Models\Order;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    //index
    public function index(Request $request)
    {
       //semester_id grade_id
        $user = auth()->user();
        $search = $request->input('search');
        $semesterId = $user->semester_id ?? $request->input('semester_id');
        $gradeId = $user->grade_id ?? $request->input('grade_id');

        if (!$gradeId || !$semesterId) {
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
                }
            }
        }


        if (!$semesterId || !$gradeId) {
            return sendError('Please provide at least one of semester_id or grade_id.');
        }



        // Query to get subjects based on semester and grade

        $subjectQuery = Subject::with(['courseMaterials','grade','semester'])
            ->where('status', 1)
            ->where('grade_id', $gradeId)
            ->where(function ($q) use ($semesterId) {
                $q->where('semester_id', $semesterId)
                    ->orWhereHas('semesters', function ($sq) use ($semesterId) {
                        $sq->where('semesters.id', $semesterId);
                    });
            });

        if ($search) {
            $subjectQuery->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%$search%")
                    ->orWhere('name_en', 'like', "%$search%");
            });
        }

        $allSubjects = $subjectQuery->get();

        $purchasedSubjectIds = Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->with('items')
            ->get()
            ->pluck('items')
            ->flatten()
            ->pluck('subject_id')
            ->unique();

        $otherSubjects = $allSubjects->whereNotIn('id', $purchasedSubjectIds);
        //all_materials_price جيبه من grade
        $grade = Grade::find($gradeId);

        $totalPrice = number_format((float) $otherSubjects->sum('price'), 2, '.', '');

        if ($otherSubjects->isNotEmpty()) {
            if ($totalPrice > $grade->all_materials_price) {
                $allMaterialsPrice = number_format((float) $grade->all_materials_price, 2, '.', '');
            }else {
                $allMaterialsPrice = $totalPrice;
            }
        } else {
            $allMaterialsPrice = '0.00';
        }

        // Add the total price to the response
        $response = [
            'total_price' => $allMaterialsPrice,
            'subjects' => SubjectResource::collection($otherSubjects),
        ];

        return sendResponse($response);

    }

    public function purchasedSubjects(Request $request)
    {
        $user = auth()->user();

        $orders = Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->with('items.subject')
            ->get();


        if ($orders->isEmpty()) {
            return sendError('ليس لديك أي اشتراك');
        }

        $purchasedSubjects = $orders->flatMap(function ($order) {
            return $order->items->pluck('subject')->filter();
        })->unique('id');

        if ($purchasedSubjects->isEmpty()) {
            return sendError('لا يوجد مواد مرتبطة بالطلبات.');
        }
        $gradeIds    = $purchasedSubjects->pluck('grade_id')->unique();
        $semesterIds = $purchasedSubjects->pluck('semester_id')->unique();

        // جلب جميع المواد التي تنتمي لنفس الصفوف والفصول
        $allSubjects = Subject::with('courseMaterials')
            ->whereIn('grade_id', $gradeIds)
            ->where(function ($q) use ($semesterIds) {
                $q->whereIn('semester_id', $semesterIds)
                    ->orWhereHas('semesters', function ($sq) use ($semesterIds) {
                        $sq->whereIn('semesters.id', $semesterIds);
                    });
            })
            ->get();

        $filteredSubjects = $allSubjects->whereIn('id', $purchasedSubjects->pluck('id'));


        return sendResponse(SubjectResource::collection($filteredSubjects));
    }

    //show
    public function details($id)
    {

        $subject = Subject::with(['courseMaterials.section', 'orders'])->find($id);


        if (!$subject) {
            return sendError('Subject not found.');
        }
        return sendResponse(new SubjectDetailResource($subject));
    }

    public function videoDetails($id)
    {
        $lesson =CourseMaterial::find($id);

        if (!$lesson || !$lesson->video) {
            return sendError('Video not found.');
        }

        $details = vimeo_video_details($lesson->video);

        return sendResponse([
            'lesson_id' => $lesson->id,
            'video'     => $lesson->video,
            'video_details' => $details
        ]);
    }



}
