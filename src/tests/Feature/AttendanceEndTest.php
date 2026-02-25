<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_退勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 18, 0, 0));

        // 1. ステータスが勤務中のユーザーにログインする
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => Carbon::create(2024, 1, 1, 9, 0, 0),
        ]);

        // 2. 画面に「退勤」ボタンが表示されていることを確認する
        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertStatus(200);
        $response->assertSee('退勤');

        // 3. 退勤の処理を行う
        $response = $this->post(route('attendance.end'));

        $response->assertRedirect(route('attendance'));

        // DB確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'end_time' => '18:00:00',
        ]);

        // 処理後に画面上に表示されるステータスが「退勤済」になる
        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertSee('退勤済');
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 9, 0, 0));

        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create();

        // 2. 出勤と退勤の処理を行う
        $this->actingAs($user)->post(route('attendance.start'));

        Carbon::setTestNow(Carbon::create(2024, 1, 1, 18, 0, 0));
        $this->actingAs($user)->post(route('attendance.end'));

        // 3. 勤怠一覧画面から退勤の日付を確認する
        $response = $this->actingAs($user)->get(route('user.attendance.list', [
            'year' => 2024,
            'month' => 1,
        ]));

        $response->assertStatus(200);

        // 退勤時刻が正確に表示されていること
        $response->assertSee('18:00');
    }
}
