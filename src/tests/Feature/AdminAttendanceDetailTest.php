<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin()
    {
        $admin = Admin::factory()->create();

        // ← ここが超重要
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '通常勤務',
        ]);

        $response = $this->get(route('admin.attendance.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('通常勤務');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->from(route('admin.attendance.detail', $attendance->id))->post(route('admin.attendance.update', $attendance->id), [
            'start_time' => '19:00',
            'end_time' => '18:00',
            'reason' => 'テスト',
        ]);

        $response->assertSessionHasErrors();
        $this->followRedirects($response)
        ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', $attendance->id))->post(route('admin.attendance.update', $attendance->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => 'テスト',
            'breaks' => [
                ['start_time' => '19:00', 'end_time' => '19:30'],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->followRedirects($response)
        ->assertSee('休憩時間が不適切な値です');
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', $attendance->id))->post(route('admin.attendance.update', $attendance->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => 'テスト',
            'breaks' => [
                ['start_time' => '17:00', 'end_time' => '19:30'],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->followRedirects($response)
        ->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response = $this->from(route('admin.attendance.detail', $attendance->id))->post(route('admin.attendance.update', $attendance->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors();
        $this->followRedirects($response)
        ->assertSee('備考を記入してください');
    }
}
