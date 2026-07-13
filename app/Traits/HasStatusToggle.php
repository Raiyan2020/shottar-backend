<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HasStatusToggle
{
    public function toggleStatu($modelClass, $id): JsonResponse
    {
        try {
            $model = $modelClass::findOrFail($id);
            $model->status = !$model->status;
            $model->save();
            $statusText = $model->status ? 'مفعل' : 'غير مفعل';
            return response()->json(['status' => true,
                'newStatus' => $model->status,
                'message' => "تم تحديث الحالة إلى: $statusText",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'لم يتم العثور على العنصر'], 404);
        }
    }
    //is free
    public function toggleIsFree($modelClass, $id): JsonResponse
    {
        try {
            $model = $modelClass::findOrFail($id);
            $model->is_free = !$model->is_free;
            $model->save();
            $statusText = $model->is_free ? 'مجاني' : 'غير مجاني';
            return response()->json(['status' => true,
                'newStatus' => $model->is_free,
                'message' => "تم تحديث الحالة إلى: $statusText",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'لم يتم العثور على العنصر'], 404);
        }
    }
}
