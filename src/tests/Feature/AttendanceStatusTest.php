<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤務外の場合、勤怠ステータスが正しく表示される()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create();

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 3. 画面に表示されているステータスを確認する
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /** @test */
    public function 出勤中の場合、勤怠ステータスが正しく表示される()
    {
        // 1. ステータスが出勤中のユーザーにログインする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 3. ステータス確認
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合、勤怠ステータスが正しく表示される()
    {
        // 1. ステータスが休憩中のユーザーにログインする
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        // 休憩開始のみ（end_timeなし）
        WorkBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => null,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 3. ステータス確認
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 退勤済の場合、勤怠ステータスが正しく表示される()
    {
        // 1. ステータスが退勤済のユーザーにログインする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 3. ステータス確認
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
