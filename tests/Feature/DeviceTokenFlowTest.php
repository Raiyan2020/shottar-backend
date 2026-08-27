<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * اختبار تدفق device_token بالكامل من ناحية الباك إند.
 *
 * الهدف: التأكد إن مهما كانت حالة المستخدم (لوجين، إعادة لوجين بعد خروج،
 * تحديث توكن وحده، نقل جهاز لمستخدم تاني) التوكن بيتخزن وبيتحدّث دايمًا.
 *
 * الاختبار معزول تمامًا: SQLite في الذاكرة وجداول مصنوعة يدويًا لأن بعض
 * الميجيشنز فيها أوامر MySQL-specific.
 */
class DeviceTokenFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // نطرد أي طلب HTTP خارجي (واتساب OTP... إلخ)
        Http::fake();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            // قيم شكلية عشان مسار WhatsApp ما يرميش RuntimeException في الريجستر
            'services.wawp.instance_id' => 'test-instance',
            'services.wawp.access_token' => 'test-token',
        ]);

        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            // نفس تعريف 2014_10_12_000000_create_users_table.php بالظبط
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->enum('status', ['1', '2', '3'])->default('1');
            $table->string('activation_code')->nullable();
            $table->integer('resend_code_count')->default(0);
            $table->text('device_token')->nullable();
            $table->string('device_type')->nullable();
            $table->string('country_code')->nullable();
            $table->string('phone_not_code')->nullable();
            $table->string('language')->default('ar');
            $table->boolean('notification_enabled')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // نفس تعريف ميجراشن sanctum
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // جدول مصغّر كفاية للعلاقات اللي بتتقري في رد اللوجين (UserResource
        // بيقرا باقة المستخدم عبر orders)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    protected function createUser(string $phone, ?string $token = null, ?string $type = null): User
    {
        return User::create([
            'name' => 'تستر',
            'phone' => $phone,
            'password' => bcrypt('secret'),
            'status' => '2',
            'device_token' => $token,
            'device_type' => $type,
        ]);
    }

    /** لوجين بإرسال device_token → لازم يتخزن زي ما هو مع النوع */
    public function test_login_stores_ios_device_token(): void
    {
        $this->createUser('+96560000001');

        $this->postJson('/api/login', [
            'country_code' => '+965',
            'phone' => '60000001',
            'device_token' => 'ios-token-from-fcm',
            'device_type' => 'ios',
        ])->assertOk();

        $this->assertSame(
            'ios-token-from-fcm',
            User::where('phone', '+96560000001')->value('device_token')
        );
        $this->assertSame(
            'ios',
            User::where('phone', '+96560000001')->value('device_type')
        );
    }

    /** سيناريو الآيفون: لوجين جديد بعد خروج بتوكن مختلف → التحديث لازم يحصل */
    public function test_relogin_after_logout_updates_the_token(): void
    {
        $this->createUser('+96560000002', 'old-ios-token');

        // التطبيق الجديد بيطلّع FCM token مختلف، فبعتّه مع اللوجين
        $this->postJson('/api/login', [
            'country_code' => '+965',
            'phone' => '60000002',
            'device_token' => 'fresh-ios-token-after-reinstall',
            'device_type' => 'ios',
        ])->assertOk();

        $user = User::where('phone', '+96560000002')->first();

        $this->assertSame('fresh-ios-token-after-reinstall', $user->refresh()->device_token);
        $this->assertNotSame('old-ios-token', $user->device_token);
        $this->assertSame('ios', $user->device_type);
    }

    /**
     * لوجين من غير device_token → بالتصميم بنسيب التوكن القديم مكانه
     * (التطبيق ساعات بيعمل لوجين قبل ما يجهّز توكن FCM، ومسح القديم
     * كان هيوقف الإشعارات من غير سبب).
     */
    public function test_login_without_device_token_keeps_previous_one(): void
    {
        $this->createUser('+96560000003', 'kept-ios-token');

        $this->postJson('/api/login', [
            'country_code' => '+965',
            'phone' => '60000003',
            'device_type' => 'ios',
        ])->assertOk();

        $this->assertSame(
            'kept-ios-token',
            User::where('phone', '+96560000003')->value('device_token')
        );
    }

    /** ريجستر جديد بتوكن آيفون → لازم يتخزن من أول لحظة */
    public function test_register_stores_device_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'مستخدم جديد',
            'country_code' => '+965',
            'phone' => '60000004',
            'device_token' => 'brand-new-ios-token',
            'device_type' => 'ios',
        ]);

        $this->assertSame(
            'brand-new-ios-token',
            User::where('phone', '+96560000004')->value('device_token')
        );
    }

    /** مسار /device-token المخصص (onTokenRefresh من غير لوجين) بحدّث التوكن */
    public function test_device_token_endpoint_updates_token_while_logged_in(): void
    {
        $user = $this->createUser('+96560000005', 'older-ios-token');
        $bearer = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/device-token', [
            'device_token' => str_repeat('a', 165), // طول APNs/FCM الواقعي
            'device_type' => 'ios',
        ], ['Authorization' => 'Bearer '.$bearer])->assertOk();

        $this->assertSame(str_repeat('a', 165), $user->refresh()->device_token);
    }

    /**
     * لو نفس التوكن بدأ يجي لمستخدم تاني (جهاز انتقل لحد)، بيتفك
     * من القديم تلقائيًا عشان الإشعارات تروح صاحبها الحالي بس.
     */
    public function test_same_token_on_another_user_moves_ownership(): void
    {
        $this->createUser('+96560000006', 'shared-ios-token');

        $other = $this->createUser('+96560000007', null);
        $bearer = $other->createToken('auth')->plainTextToken;

        $this->postJson('/api/device-token', [
            'device_token' => 'shared-ios-token',
            'device_type' => 'ios',
        ], ['Authorization' => 'Bearer '.$bearer])->assertOk();

        $this->assertNull(User::where('phone', '+96560000006')->value('device_token'));
        $this->assertSame('shared-ios-token', $other->refresh()->device_token);
    }

    /** اللوج أوت بحذف توكن الصلاحية بس — مش بيمسح device_token من الداتابيز */
    public function test_logout_does_not_clear_device_token(): void
    {
        $user = $this->createUser('+96560000008', 'token-survives-logout', 'ios');
        $bearer = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => 'Bearer '.$bearer])
            ->assertOk();

        $user->refresh();
        $this->assertSame('token-survives-logout', $user->device_token);
        $this->assertSame('ios', $user->device_type);

        // التوكن الفعلي بتاع الصلاحية اتشال زي المتوقع
        $this->assertSame(0, $user->tokens()->count());
    }
}
