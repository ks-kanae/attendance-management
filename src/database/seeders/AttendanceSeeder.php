<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use App\Models\Admin;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者アカウント作成
        Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // テストユーザー5人を作成
        $users = [
            ['name' => '山田太郎', 'email' => 'yamada@example.com'],
            ['name' => '佐藤花子', 'email' => 'sato@example.com'],
            ['name' => '田中一郎', 'email' => 'tanaka@example.com'],
            ['name' => '鈴木美咲', 'email' => 'suzuki@example.com'],
            ['name' => '高橋健太', 'email' => 'takahashi@example.com'],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password'),
                'role' => 'user', // 一般ユーザー
            ]);

            // 過去30日分の勤怠データを作成
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);

                // 土日はスキップ（約70%の確率で）
                if ($date->isWeekend() && rand(1, 10) <= 7) {
                    continue;
                }

                // ランダムに欠勤（約10%の確率で）
                if (rand(1, 10) == 1) {
                    continue;
                }

                // 出勤時刻（8:00〜10:00の間）
                $startHour = rand(8, 9);
                $startMinute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
                $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);

                // 退勤時刻（17:00〜19:00の間）
                $endHour = rand(17, 19);
                $endMinute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
                $endTime = sprintf('%02d:%02d:00', $endHour, $endMinute);

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'start_time' => sprintf('%02d:%02d:00', rand(8, 9), [0,15,30,45][array_rand([0,15,30,45])]),
                    'end_time'   => sprintf('%02d:%02d:00', rand(17, 19), [0,15,30,45][array_rand([0,15,30,45])]),
                ]);

                // 休憩1（必須）
                WorkBreak::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => '12:00:00',
                    'end_time'   => '13:00:00',
                ]);

                // 休憩2（50%）
                if (rand(0, 1)) {
                    WorkBreak::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => '15:00:00',
                        'end_time'   => '15:15:00',
                    ]);
                }
            }
        }

        /** =========================
         * 修正申請データ
         * ========================= */
        User::where('role', 'user')->each(function ($user) {

            $attendances = Attendance::where('user_id', $user->id)
                ->latest('date')
                ->take(10)
                ->get();

            if ($attendances->isEmpty()) return;

            $reasons = [
                '打刻忘れのため',
                '電車遅延のため',
                '客先訪問のため',
                '体調不良のため',
            ];

            $targets = $attendances->shuffle();

            /** -------------------------
             * 勤務時間修正（1〜2件）
             * ------------------------- */
            $targets->take(rand(1, 2))->each(function ($attendance) use ($user, $reasons) {
                AttendanceCorrection::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'corrected_start_time' => '09:00:00',
                    'corrected_end_time'   => '18:00:00',
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => 'pending',
                ]);
            });

            /** -------------------------
             * 休憩修正（1〜2件）
             * ------------------------- */
            $targets->slice(2)->take(rand(1, 2))->each(function ($attendance) use ($user, $reasons) {

                if ($attendance->breaks->count() === 0) return;

                $correction = AttendanceCorrection::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'corrected_start_time' => $attendance->start_time,
                    'corrected_end_time'   => $attendance->end_time,
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => 'pending',
                ]);

                $breakCount = min(rand(1, 2), $attendance->breaks->count());

                foreach (range(1, $breakCount) as $i) {

                    if ($i === 1) {
                        $start = '12:00:00';
                        $end   = '13:00:00';
                    } else {
                        $start = '15:00:00';
                        $end   = '15:15:00';
                    }

                    BreakCorrection::create([
                        'attendance_correction_id' => $correction->id,
                        'break_number' => $i,
                        'corrected_start_time' => $start,
                        'corrected_end_time'   => $end,
                    ]);
                }
            });
        });
    }
}
