<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkBreak;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function list(Request $request)
    {
        // 日付指定（デフォルトは今日）
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        // 指定日の全ユーザーの勤怠データを取得（一般ユーザーのみ）
        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('date', $date)
            ->whereHas('user', function($query) {
                $query->where('role', 'user');
            })
            ->get();

        // 一般ユーザーのみ取得
        $users = User::where('role', 'user')->get();

        $attendanceData = $users->map(function ($user) use ($attendances, $date) {
            $attendance = $attendances->firstWhere('user_id', $user->id);

            return [
                'user' => $user,
                'attendance' => $attendance,
                'total_work_time' => $attendance ? $this->calculateWorkTime($attendance) : null,
                'total_break_time' => $attendance ? $this->calculateBreakTime($attendance) : null,
            ];
        });

        return view('admin.admin-attendance-list', compact('attendanceData', 'date'));
    }

    /**
     * 勤怠詳細
     */
    public function detail($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])
            ->whereHas('user', function($query) {
                $query->where('role', 'user');
            })
            ->findOrFail($id);

        return view('admin.admin-attendance-detail', compact('attendance'));
    }

    /**
     * 勤怠更新
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required',
            'end_time' => 'required',
            'breaks' => 'array',
            'breaks.*.start_time' => 'nullable',
            'breaks.*.end_time' => 'nullable',
        ], [
            'start_time.required' => '出勤時刻を入力してください',
            'end_time.required' => '退勤時刻を入力してください',
        ]);

        $attendance = Attendance::whereHas('user', function($query) {
                $query->where('role', 'user');
            })
            ->findOrFail($id);

        // 勤怠時間を更新
        $attendance->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        // ===== ここから休憩を「削除せず」更新 =====
        $existingBreaks = $attendance->breaks->values();

        if ($request->has('breaks')) {

            foreach ($request->breaks as $index => $breakData) {

                $start = $breakData['start_time'] ?? null;
                $end   = $breakData['end_time'] ?? null;

                $existing = $existingBreaks->get($index);

                // 入力がある場合
                if ($start && $end) {

                    if ($existing) {
                        // 既存更新
                        $existing->update([
                            'start_time' => $start,
                            'end_time' => $end,
                        ]);
                    } else {
                        // 新規作成
                        WorkBreak::create([
                            'attendance_id' => $attendance->id,
                            'start_time' => $start,
                            'end_time' => $end,
                        ]);
                    }

                } else {
                    // 空欄なら既存を削除
                    if ($existing) {
                        $existing->delete();
                    }
                }
            }
        }
        // ===== ここまで =====

        return redirect()->route('admin.attendance.detail', $id)->with('success', '勤怠情報を更新しました');
    }


    private function calculateWorkTime($attendance)
    {
        if (!$attendance->start_time || !$attendance->end_time) {
            return null;
        }

        $start = Carbon::parse($attendance->start_time);
        $end = Carbon::parse($attendance->end_time);
        $workMinutes = $start->diffInMinutes($end);

        $breakMinutes = $this->calculateBreakTime($attendance, true);
        $totalMinutes = $workMinutes - $breakMinutes;

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    private function calculateBreakTime($attendance, $returnMinutes = false)
    {
        $totalMinutes = 0;

        foreach ($attendance->breaks as $break) {
            if ($break->start_time && $break->end_time) {
                $start = Carbon::parse($break->start_time);
                $end = Carbon::parse($break->end_time);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        if ($returnMinutes) {
            return $totalMinutes;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
