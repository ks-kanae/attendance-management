<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
   use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 管理者ユーザーにログインする
        $admin = Admin::factory()->create();
        $user1 = User::factory()->create(['role' => 'user', 'name' => '山田太郎']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => '佐藤花子']);

        Attendance::create([
            'user_id' => $user1->id,
            'date' => '2024-05-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'date' => '2024-05-01',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 2. 勤怠一覧画面を開く
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list'));

        // その日の全ユーザーの勤怠情報が正確な値になっている
        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('佐藤花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_遷移した際に現在の日付が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 管理者ユーザーにログインする
        $admin = Admin::factory()->create();

        // 2. 勤怠一覧画面を開く
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list'));

        // 勤怠一覧画面にその日の日付が表示されている
        $response->assertSee('2024年5月1日');
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_前日を押下した時に前の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 2));

        // 1. 管理者ユーザーにログインする
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['role' => 'user', 'name' => '前日テスト']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-05-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠一覧画面を開く → 3. 「前日」ボタンを押す
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list', [
                'date' => '2024-05-01'
            ]));

        // 前日の日付の勤怠情報が表示される
        $response->assertSee('2024年5月1日');
        $response->assertSee('前日テスト');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_翌日を押下した時に次の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 管理者ユーザーにログインする
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['role' => 'user', 'name' => '翌日テスト']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-05-02',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠一覧画面を開く → 3. 「翌日」ボタンを押す
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list', [
                'date' => '2024-05-02'
            ]));

        // 翌日の日付の勤怠情報が表示される
        $response->assertSee('2024年5月2日');
        $response->assertSee('翌日テスト');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
