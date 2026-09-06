<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * ترتيب الصفوف على مستوى قاعدة البيانات (order_by) داخل نطاق محدد.
 *
 * الطريقة القديمة كانت الواجهة تبعت ترتيب الصفوف الظاهرة وتعيد ترقيمها من 1،
 * وده بيكسر الترتيب العام لما يكون فيه pagination أو بحث — الصف رقم 21 كان
 * بيرجع 1 لمجرد إنه أول صف في الصفحة التانية. هنا السيرفر هو اللي بيحدد
 * الجار الحقيقي في الترتيب العام ويبدّل معاه.
 *
 * مفتاح الترتيب دايمًا: order_by ASC ثم id ASC (نفس ترتيب الاستعلامات الحالية،
 * والـ id بيضمن نتيجة ثابتة لو حصل تعادل).
 */
class RowOrderService
{
    public const DIRECTION_UP = 'up';

    public const DIRECTION_DOWN = 'down';

    /**
     * ينقل صف مركز واحد داخل نطاقه.
     *
     * @param  Closure(): Builder  $scope  بيرجّع استعلام جديد لنطاق الترتيب
     * @return bool false لو الصف على الحافة (أول/آخر) فمفيش حركة
     */
    public function move(Model $model, Closure $scope, string $direction): bool
    {
        $direction = $direction === self::DIRECTION_UP ? self::DIRECTION_UP : self::DIRECTION_DOWN;

        return DB::transaction(function () use ($model, $scope, $direction) {
            // الترقيم بيتظبط الأول (1..N) عشان الحركة تبقى تبديل بسيط ومضمون،
            // ولإصلاح الصفوف القديمة اللي order_by بتاعها 0 أو متكرر.
            $this->normalize($scope);

            $model->refresh();

            $current = (int) $model->getAttribute('order_by');
            $target = $direction === self::DIRECTION_UP ? $current - 1 : $current + 1;

            if ($target < 1) {
                return false;
            }

            $neighbour = $scope()->where('order_by', $target)->first();

            if (! $neighbour) {
                return false;
            }

            // تبديل المركزين. بنستخدم query builder مباشرة عشان منعتمدش على
            // fillable الموديلات المختلفة.
            $scope()->whereKey($neighbour->getKey())->toBase()->update(['order_by' => $current]);
            $scope()->whereKey($model->getKey())->toBase()->update(['order_by' => $target]);

            return true;
        });
    }

    /**
     * بيطبّق ترتيب الصفوف الظاهرة (السحب والإفلات) من غير ما يكسر الترتيب العام.
     *
     * الطريقة القديمة كانت بتدي الصفوف الظاهرة المراكز 1..N، فلو المستخدم كان
     * في الصفحة التانية أو عامل بحث، الصفوف دي كانت بتقفز لأول القائمة والباقي
     * يتلخبط. هنا بناخد المراكز العامة اللي الصفوف دي واقفة فيها فعلاً
     * (مثلاً 21, 22, 23) وبنعيد توزيعها على نفس الصفوف بالترتيب الجديد —
     * فمجموعة المراكز ما بتتغيرش، اللي بيتغير هو مين في أنهي مركز.
     *
     * @param  Closure(): Builder  $scope
     * @param  array<int, int|string>  $ids  ids بترتيب ظهورها بعد السحب
     */
    public function applyVisibleOrder(Closure $scope, array $ids): void
    {
        $ids = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));

        if (count($ids) < 2) {
            return;
        }

        DB::transaction(function () use ($scope, $ids) {
            $this->normalize($scope);

            $map = $this->positionMap($scope)['positions'];

            // المراكز اللي الصفوف دي شاغلاها حاليًا، مرتبة تصاعديًا.
            $slots = [];
            foreach ($ids as $id) {
                if (isset($map[$id])) {
                    $slots[] = $map[$id];
                }
            }

            sort($slots);

            if (count($slots) !== count($ids)) {
                // في id مش تابع للنطاق ده — منعملش حاجة بدل ما نخرب الترتيب.
                return;
            }

            foreach ($ids as $index => $id) {
                if ((int) $map[$id] === (int) $slots[$index]) {
                    continue;
                }

                $scope()->whereKey($id)->toBase()->update(['order_by' => $slots[$index]]);
            }
        });
    }

    /**
     * يضمن إن order_by داخل النطاق أرقام متتالية فريدة من 1 لـ N.
     *
     * @param  Closure(): Builder  $scope
     */
    public function normalize(Closure $scope): void
    {
        $rows = $this->orderedKeys($scope);

        $needsFix = false;
        foreach ($rows as $index => $row) {
            if ((int) $row->order_by !== $index + 1) {
                $needsFix = true;
                break;
            }
        }

        if (! $needsFix) {
            return;
        }

        foreach ($rows as $index => $row) {
            if ((int) $row->order_by === $index + 1) {
                continue;
            }

            $scope()->whereKey($row->getKey())->toBase()->update(['order_by' => $index + 1]);
        }
    }

    /**
     * خريطة المراكز العامة داخل النطاق: [id => position] مع الإجمالي.
     *
     * بتتنادى مرة واحدة عند رسم الجدول، فالمراكز بتفضل صحيحة مهما كانت
     * الصفحة الحالية أو نتيجة البحث.
     *
     * @param  Closure(): Builder  $scope
     * @return array{positions: array<int|string, int>, total: int}
     */
    public function positionMap(Closure $scope): array
    {
        $positions = [];

        foreach ($this->orderedKeys($scope) as $index => $row) {
            $positions[$row->getKey()] = $index + 1;
        }

        return ['positions' => $positions, 'total' => count($positions)];
    }

    /**
     * @param  Closure(): Builder  $scope
     * @return \Illuminate\Support\Collection<int, Model>
     */
    protected function orderedKeys(Closure $scope): \Illuminate\Support\Collection
    {
        $query = $scope();
        $table = $query->getModel()->getTable();
        $key = $query->getModel()->getKeyName();

        return $query
            ->reorder()
            ->orderBy($table.'.order_by')
            ->orderBy($table.'.'.$key)
            ->get([$table.'.'.$key, $table.'.order_by'])
            ->values();
    }
}
