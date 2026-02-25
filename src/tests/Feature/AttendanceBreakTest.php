<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 0));

        // 出勤中ユーザーを作成
        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
        ]);

        // 休憩入ボタンが表示されている
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        // 休憩処理
        $this->actingAs($user)->post('/attendance/break-start');

        // DB確認
        $this->assertDatabaseCount('breaks', 1);

        // ステータスが休憩中になる
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /** @test */
    public function 休憩は一日に何回でもできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 0));

        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
        ]);

        // 1回目
        $this->actingAs($user)->post('/attendance/break-start');
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 30));
        $this->actingAs($user)->post('/attendance/break-end');

        // 再度 休憩入ボタンが表示される
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 10, 0));

        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
        ]);

        // 休憩開始
        $this->actingAs($user)->post('/attendance/break-start');

        // 休憩戻処理
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 10, 30));
        $this->actingAs($user)->post('/attendance/break-end');

        // ステータスが出勤中になる
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /** @test */
    public function 休憩戻は一日に何回でもできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 0));

        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
        ]);

        // 1回目
        $this->actingAs($user)->post('/attendance/break-start');
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 30));
        $this->actingAs($user)->post('/attendance/break-end');

        // 2回目 休憩入
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 10, 0));
        $this->actingAs($user)->post('/attendance/break-start');

        // 休憩戻ボタンが表示される
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    /** @test */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 0));

        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
        ]);

        // 休憩開始
        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 30));
        $this->actingAs($user)->post('/attendance/break-end');

        // 一覧画面確認
        $response = $this->actingAs($user)
            ->get('/attendance/list?year=2026&month=2');

        // 30分休憩 → 0:30 表示確認
        $response->assertSee('0:30');

        Carbon::setTestNow();
    }
}
