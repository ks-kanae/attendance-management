<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_自分が行った勤怠情報が全て表示されている()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 勤怠情報が登録されたユーザーにログインする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-05-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-05-02',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 2. 勤怠一覧ページを開く
        $response = $this->actingAs($user)
            ->get(route('user.attendance.list', ['year' => 2024, 'month' => 5]));

        // 3. 自分の勤怠情報がすべて表示されていることを確認する
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 15));

        // 1. ユーザーにログインをする
        $user = User::factory()->create();

        // 2. 勤怠一覧ページを開く
        $response = $this->actingAs($user)
            ->get(route('user.attendance.list'));

        // 現在の月が表示されている
        $response->assertSee('2024/05');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-04-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠一覧ページを開く → 3. 「前月」ボタンを押す（4月表示）
        $response = $this->actingAs($user)
            ->get(route('user.attendance.list', ['year' => 2024, 'month' => 4]));

        // 前月の情報が表示されている
        $response->assertSee('2024/04');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_翌月を押下した時に表示月の前月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-06-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠一覧ページを開く → 3. 「翌月」ボタンを押す（6月表示）
        $response = $this->actingAs($user)
            ->get(route('user.attendance.list', ['year' => 2024, 'month' => 6]));

        // 翌月の情報が表示されている
        $response->assertSee('2024/06');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        Carbon::setTestNow(Carbon::create(2024, 5, 1));

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2024-05-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 2. 勤怠一覧ページを開く
        $this->actingAs($user)
            ->get(route('user.attendance.list', ['year' => 2024, 'month' => 5]))
            ->assertSee('詳細');

        // 3. 「詳細」ボタンを押下する
        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance->id));

        // その日の勤怠詳細画面に遷移する
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
