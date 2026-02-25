<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use Carbon\Carbon;

class CorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create();

        AttendanceCorrection::factory()->count(3)->create([
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'status' => 'pending',
        ]);

        AttendanceCorrection::factory()->count(2)->create([
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.correction.list', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $this->assertEquals(3, $response->viewData('corrections')->count());
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = Admin::factory()->create();

        AttendanceCorrection::factory()->count(4)->create([
            'status' => 'approved',
        ]);

        AttendanceCorrection::factory()->count(2)->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.correction.list', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $this->assertEquals(4, $response->viewData('corrections')->count());
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => '09:00',
            'corrected_end_time' => '18:00',
            'reason' => '打刻ミス',
        ]);

        BreakCorrection::factory()->create([
            'attendance_correction_id' => $correction->id,
            'break_number' => 1,
            'corrected_start_time' => '12:00',
            'corrected_end_time' => '13:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.correction.show', $correction->id));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('打刻ミス');
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => '09:00',
            'corrected_end_time' => '18:00',
            'status' => 'pending',
        ]);

        BreakCorrection::factory()->create([
            'attendance_correction_id' => $correction->id,
            'break_number' => 1,
            'corrected_start_time' => '12:00',
            'corrected_end_time' => '13:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.correction.approve', $correction->id));

        $response->assertRedirect(route('admin.correction.list'));

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    }
}
