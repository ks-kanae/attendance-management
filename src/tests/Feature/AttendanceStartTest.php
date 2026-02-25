<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceStartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 0, 0));

        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create();

        // 2. 画面に「出勤」ボタンが表示されていることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 3. 出勤の処理を行う
        $this->actingAs($user)->post('/attendance/start');

        // DBに記録されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        // ステータスが「出勤中」になることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 18, 0, 0));

        // 1. ステータスが退勤済であるユーザーにログインする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤務ボタンが表示されないことを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('出勤');

        Carbon::setTestNow();
    }

    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 25, 9, 15, 0));

        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create();

        // 2. 出勤の処理を行う
        $this->actingAs($user)->post('/attendance/start');

        // 3. 勤怠一覧画面から出勤の日付を確認する
        $response = $this->actingAs($user)->get('/attendance/list?year=2026&month=2');

        $response->assertStatus(200);

        // 出勤時刻(H:i形式)が表示されていることを確認
        $response->assertSee('09:15');

        Carbon::setTestNow();
    }
}
