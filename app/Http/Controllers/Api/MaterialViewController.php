<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMaterialViewRequest;
use App\Models\MaterialView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialViewController extends Controller
{
    
    public function store(StoreMaterialViewRequest  $request)
    {
        $userId = auth()->id();
        $data = $request->validated();

        $courseMaterialId  = (int) $data['course_material_id'];
        $incrementSeconds  = max(0, (int) ($data['duration_watched'] ?? 0)); // لا نسمح بالسالب
        $viewedAt          = $data['viewed_at'] ?? now();

        $materialView = MaterialView::updateOrCreate(
            [
                'user_id'            => $userId,
                'course_material_id' => $courseMaterialId,
            ],
            [
                'viewed_at'        => $viewedAt,
                'duration_watched' => DB::raw('COALESCE(duration_watched,0) + '.$incrementSeconds),
            ]
        );
        $materialView->refresh();

        return sendResponse($materialView, 'Material view recorded successfully.');

    }

}
