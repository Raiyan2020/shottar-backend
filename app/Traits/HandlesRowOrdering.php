<?php

namespace App\Traits;

use App\Services\RowOrderService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الجزء المشترك بين كل الكونترولرز اللي عندها ترتيب صفوف بالأسهم.
 *
 * الصلاحيات والتحقق من ملكية الصف بيفضلوا مسؤولية الكونترولر نفسه — الترايت
 * ده بياخد الصف والنطاق بعد ما الكونترولر يتأكد منهم.
 */
trait HandlesRowOrdering
{
    /**
     * @param  Closure(): \Illuminate\Database\Eloquent\Builder  $scope
     */
    protected function moveRow(Request $request, Model $model, Closure $scope): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $moved = app(RowOrderService::class)->move($model, $scope, $data['direction']);

        $map = app(RowOrderService::class)->positionMap($scope);

        return response()->json([
            'status' => 'success',
            'moved' => $moved,
            'position' => $map['positions'][$model->getKey()] ?? null,
            'total' => $map['total'],
        ]);
    }
}
