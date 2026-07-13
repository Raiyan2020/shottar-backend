<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqsResource;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __invoke(Request $request)
    {
        $grades = Grade::where('status', 1)
            ->orderBy('order_by', 'asc')
            ->get();

        return sendResponse(GradeResource::collection($grades));

    }
}
