<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Http\Resources\SemesterResource;
use App\Models\Grade;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function __invoke(Request $request)
    {
        $semesters = Semester::where('status', 1)
//            ->orderBy('order_by', 'asc')
            ->get();

        return sendResponse(SemesterResource::collection($semesters));

    }
}
