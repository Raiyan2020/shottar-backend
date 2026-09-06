<?php

namespace Tests\Unit;

use App\Services\RowOrderService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * موديل خفيف للاختبار — بيمثل أي جدول عنده order_by ونطاق ترتيب.
 * الـ scope_id بيقلّد subject_id / lesson_section_id في الجداول الحقيقية.
 */
class OrderableStub extends Model
{
    protected $table = 'order_stubs';

    public $timestamps = false;

    protected $guarded = [];
}

class RowOrderServiceTest extends TestCase
{
    protected RowOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite_testing']);
        config(['database.connections.sqlite_testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::create('order_stubs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('scope_id')->default(1);
            $table->string('name')->nullable();
            $table->integer('order_by')->default(0);
        });

        $this->service = new RowOrderService();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_stubs');

        parent::tearDown();
    }

    /** @return \Closure(): \Illuminate\Database\Eloquent\Builder */
    protected function scope(int $scopeId = 1): \Closure
    {
        return fn () => OrderableStub::query()->where('scope_id', $scopeId);
    }

    protected function seedRows(array $names, int $scopeId = 1): void
    {
        foreach ($names as $index => $name) {
            OrderableStub::create([
                'name' => $name,
                'scope_id' => $scopeId,
                'order_by' => $index + 1,
            ]);
        }
    }

    protected function namesInOrder(int $scopeId = 1): array
    {
        return OrderableStub::where('scope_id', $scopeId)
            ->orderBy('order_by')->orderBy('id')
            ->pluck('name')->all();
    }

    protected function find(string $name): OrderableStub
    {
        return OrderableStub::where('name', $name)->firstOrFail();
    }

    // ── 1. تحريك لأعلى مركز واحد بالظبط ─────────────────────────
    public function test_move_up_shifts_the_row_exactly_one_position(): void
    {
        $this->seedRows(['A', 'B', 'C', 'D']);

        $this->assertTrue($this->service->move($this->find('C'), $this->scope(), 'up'));

        $this->assertSame(['A', 'C', 'B', 'D'], $this->namesInOrder());
    }

    // ── 2. تحريك لأسفل مركز واحد بالظبط ─────────────────────────
    public function test_move_down_shifts_the_row_exactly_one_position(): void
    {
        $this->seedRows(['A', 'C', 'B', 'D']);

        $this->assertTrue($this->service->move($this->find('B'), $this->scope(), 'down'));

        $this->assertSame(['A', 'C', 'D', 'B'], $this->namesInOrder());
    }

    // ── 3. أول صف مينفعش يطلع فوق ───────────────────────────────
    public function test_first_row_cannot_move_up(): void
    {
        $this->seedRows(['A', 'B', 'C']);

        $this->assertFalse($this->service->move($this->find('A'), $this->scope(), 'up'));
        $this->assertSame(['A', 'B', 'C'], $this->namesInOrder());
    }

    // ── 4. آخر صف مينفعش ينزل تحت ───────────────────────────────
    public function test_last_row_cannot_move_down(): void
    {
        $this->seedRows(['A', 'B', 'C']);

        $this->assertFalse($this->service->move($this->find('C'), $this->scope(), 'down'));
        $this->assertSame(['A', 'B', 'C'], $this->namesInOrder());
    }

    // ── 5. الترتيب يفضل فريد ومتتالي ────────────────────────────
    public function test_order_values_stay_unique_and_contiguous(): void
    {
        // الحالة الواقعية: صفوف قديمة كلها order_by = 0
        foreach (['A', 'B', 'C', 'D'] as $name) {
            OrderableStub::create(['name' => $name, 'scope_id' => 1, 'order_by' => 0]);
        }

        $this->service->move($this->find('D'), $this->scope(), 'up');

        $values = OrderableStub::where('scope_id', 1)->orderBy('order_by')->pluck('order_by')->all();

        $this->assertSame([1, 2, 3, 4], $values, 'order_by لازم يبقى 1..N من غير تكرار');
        $this->assertSame(['A', 'B', 'D', 'C'], $this->namesInOrder());
    }

    // ── 6. النطاق محترم: التحريك مبيلمسش نطاق تاني ──────────────
    public function test_moving_never_touches_another_scope(): void
    {
        $this->seedRows(['A1', 'A2'], scopeId: 1);
        $this->seedRows(['B1', 'B2'], scopeId: 2);

        $this->service->move($this->find('A2'), $this->scope(1), 'down');

        // A2 آخر صف في نطاقه، فمفيش حركة — و B1 ما اتلمسش
        $this->assertSame(['A1', 'A2'], $this->namesInOrder(1));
        $this->assertSame(['B1', 'B2'], $this->namesInOrder(2));
        $this->assertSame(1, (int) $this->find('B1')->order_by);
    }

    // ── 7. المراكز العامة مستقلة عن الـ pagination ──────────────
    public function test_positions_are_global_not_per_page(): void
    {
        $names = [];
        for ($i = 1; $i <= 45; $i++) {
            $names[] = 'Row'.$i;
        }
        $this->seedRows($names);

        $map = $this->service->positionMap($this->scope());

        $this->assertSame(45, $map['total']);
        $this->assertSame(21, $map['positions'][$this->find('Row21')->id]);

        // أول صف في الصفحة التانية (المركز 21) يطلع للمركز 20
        $this->service->move($this->find('Row21'), $this->scope(), 'up');

        $map = $this->service->positionMap($this->scope());
        $this->assertSame(20, $map['positions'][$this->find('Row21')->id]);
        $this->assertSame(21, $map['positions'][$this->find('Row20')->id]);
    }

    // ── 8/9. السحب مع بحث/صفحة مبيعيدش ترقيم القائمة كلها ───────
    public function test_dragging_filtered_rows_keeps_their_global_slots(): void
    {
        $this->seedRows(['A', 'B', 'C', 'D', 'E']);

        // المستخدم شايف نتيجة بحث فيها C و E بس (المركزين 3 و 5)
        // وسحب E فوق C.
        $this->service->applyVisibleOrder($this->scope(), [
            $this->find('E')->id,
            $this->find('C')->id,
        ]);

        // المراكز 3 و 5 هما نفسهم، بس اتبدلوا بين C و E.
        // A و B و D ما اتحركوش.
        $this->assertSame(['A', 'B', 'E', 'D', 'C'], $this->namesInOrder());
        $this->assertSame(1, (int) $this->find('A')->order_by);
        $this->assertSame(2, (int) $this->find('B')->order_by);
        $this->assertSame(4, (int) $this->find('D')->order_by);
    }

    public function test_dragging_on_a_later_page_does_not_move_rows_to_the_top(): void
    {
        $names = [];
        for ($i = 1; $i <= 25; $i++) {
            $names[] = 'Row'.$i;
        }
        $this->seedRows($names);

        // الصفحة التانية: الصفوف 21..25، والمستخدم عكس أول اتنين.
        $visible = [
            $this->find('Row22')->id,
            $this->find('Row21')->id,
            $this->find('Row23')->id,
            $this->find('Row24')->id,
            $this->find('Row25')->id,
        ];

        $this->service->applyVisibleOrder($this->scope(), $visible);

        $map = $this->service->positionMap($this->scope());

        // لازم يفضلوا في المراكز 21..25 مش يقفزوا لـ 1..5
        $this->assertSame(21, $map['positions'][$this->find('Row22')->id]);
        $this->assertSame(22, $map['positions'][$this->find('Row21')->id]);
        $this->assertSame(1, $map['positions'][$this->find('Row1')->id], 'الصفحة الأولى ما تتأثرش');
    }

    // ── 10. السحب العادي (كل الصفوف ظاهرة) لسه شغال ─────────────
    public function test_dragging_the_full_visible_list_still_reorders(): void
    {
        $this->seedRows(['A', 'B', 'C']);

        $this->service->applyVisibleOrder($this->scope(), [
            $this->find('C')->id,
            $this->find('A')->id,
            $this->find('B')->id,
        ]);

        $this->assertSame(['C', 'A', 'B'], $this->namesInOrder());
    }

    public function test_drag_payload_with_a_foreign_id_is_ignored(): void
    {
        $this->seedRows(['A', 'B'], scopeId: 1);
        $this->seedRows(['X'], scopeId: 2);

        $this->service->applyVisibleOrder($this->scope(1), [
            $this->find('X')->id,   // مش من النطاق ده
            $this->find('A')->id,
        ]);

        $this->assertSame(['A', 'B'], $this->namesInOrder(1));
        $this->assertSame(['X'], $this->namesInOrder(2));
    }
}
