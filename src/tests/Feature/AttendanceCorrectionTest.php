<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendance($user)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->post(
            route('attendance.correct', $attendance->id),
            [
                'corrected_start_time' => '19:00',
                'corrected_end_time' => '18:00',
                'reason' => 'テスト',
            ]
        );

        $response->assertSessionHasErrors([
            'corrected_start_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->post(
            route('attendance.correct', $attendance->id),
            [
                'corrected_start_time' => '09:00',
                'corrected_end_time' => '18:00',
                'reason' => 'テスト',
                'break_corrections' => [
                    ['start_time' => '19:00', 'end_time' => '19:30']
                ]
            ]
        );

        $response->assertSessionHasErrors([
            'break_corrections.0.start_time' => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->post(
            route('attendance.correct', $attendance->id),
            [
                'corrected_start_time' => '09:00',
                'corrected_end_time' => '18:00',
                'reason' => 'テスト',
                'break_corrections' => [
                    ['start_time' => '12:00', 'end_time' => '19:00']
                ]
            ]
        );

        $response->assertSessionHasErrors([
            'break_corrections.0.end_time' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->post(
            route('attendance.correct', $attendance->id),
            [
                'corrected_start_time' => '09:00',
                'corrected_end_time' => '18:00',
                'reason' => '',
            ]
        );

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください'
        ]);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_修正申請処理が実行される()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = $this->createAttendance($user);

        $this->actingAs($user)->post(
            route('attendance.correct', $attendance->id),
            [
                'corrected_start_time' => '09:30',
                'corrected_end_time' => '18:30',
                'reason' => '修正理由',
            ]
        );

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_承認待ちにログインユーザーが行った申請が全て表示されていること()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = $this->createAttendance($user);

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => '09:30',
            'corrected_end_time' => '18:30',
            'reason' => '修正理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('correction.user.list', ['tab' => 'pending']));

        $response->assertSee('承認待ち');
        $response->assertSee('修正理由');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = $this->createAttendance($user);

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => '09:30',
            'corrected_end_time' => '18:30',
            'reason' => '承認済テスト',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->get(route('correction.user.list', ['tab' => 'approved']));

        $response->assertSee('承認済み');
        $response->assertSee('承認済テスト');
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = $this->createAttendance($user);

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => '09:30',
            'corrected_end_time' => '18:30',
            'reason' => '詳細テスト',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
