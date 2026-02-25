<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_管理者ユーザーが全一般ユーザーの氏名メールアドレスを確認できる()
    {
        $this->loginAdmin();

        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        $response = $this->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee($user1->email);
        $response->assertSee($user2->name);
        $response->assertSee($user2->email);
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::create(2025, 1, 10),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '通常勤務',
        ]);

        $response = $this->get(route('admin.staff.attendance', [
            'id' => $user->id,
            'year' => 2025,
            'month' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('01/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->get(route('admin.staff.attendance', [
            'id' => $user->id,
            'year' => 2025,
            'month' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee('2025/02');

        $previousMonthResponse = $this->get(route('admin.staff.attendance', [
            'id' => $user->id,
            'year' => 2025,
            'month' => 1,
        ]));

        $previousMonthResponse->assertSee('2025/01');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->get(route('admin.staff.attendance', [
            'id' => $user->id,
            'year' => 2025,
            'month' => 1,
        ]));

        $response->assertSee('2025/01');

        $nextMonthResponse = $this->get(route('admin.staff.attendance', [
            'id' => $user->id,
            'year' => 2025,
            'month' => 2,
        ]));

        $nextMonthResponse->assertSee('2025/02');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $this->loginAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response = $this->get(route('admin.attendance.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
