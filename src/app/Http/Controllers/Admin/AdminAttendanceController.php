<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
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
