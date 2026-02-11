<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // 勤怠状態を判定
        $status = $this->determineStatus($attendance);

        return view('attendance', compact('attendance', 'status'));
    }

    private function determineStatus($attendance)
    {
        if (!$attendance) {
            return 'not_working'; // 勤務外
        }

        if ($attendance->end_time) {
            return 'finished'; // 退勤済
        }

        // 最後の休憩レコードを確認
        $lastBreak = $attendance->breaks()->latest()->first();

        if ($lastBreak && !$lastBreak->end_time) {
            return 'on_break'; // 休憩中
        }

        return 'working'; // 出勤中
    }

    public function start(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 既に出勤していないかチェック
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return redirect()->route('attendance')->with('error', '既に出勤しています');
        }

        // 出勤記録を作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => Carbon::now(),
        ]);

        return redirect()->route('attendance');
    }

    public function end(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')->with('error', '出勤記録がありません');
        }

        if ($attendance->end_time) {
            return redirect()->route('attendance')->with('error', '既に退勤しています');
        }

        $attendance->update([
            'end_time' => Carbon::now(),
        ]);

        return redirect()->route('attendance');
    }

    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')->with('error', '出勤記録がありません');
        }

        // 休憩開始
        $attendance->breaks()->create([
            'start_time' => Carbon::now(),
        ]);

        return redirect()->route('attendance');
    }

    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')->with('error', '出勤記録がありません');
        }

        // 最後の休憩を取得
        $lastBreak = $attendance->breaks()->whereNull('end_time')->latest()->first();

        if (!$lastBreak) {
            return redirect()->route('attendance')->with('error', '休憩開始の記録がありません');
        }

        $lastBreak->update([
            'end_time' => Carbon::now(),
        ]);

        return redirect()->route('attendance');
    }

    /**
     * 勤怠一覧
     */
    public function list(Request $request)
    {
        $user = Auth::user();

        // 月の指定（デフォルトは今月）
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetDate = Carbon::create($year, $month, 1);

        // 指定月の勤怠データを取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        // 月の全日付を生成
        $daysInMonth = $targetDate->daysInMonth;
        $dates = collect();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $attendance = $attendances->first(function ($attendance) use ($date) {
            return $attendance->date->isSameDay($date);
            });

            $dates->push([
                'date' => $date,
                'attendance' => $attendance,
                'total_work_time' => $attendance ? $this->calculateWorkTime($attendance) : null,
                'total_break_time' => $attendance ? $this->calculateBreakTime($attendance) : null,
            ]);
        }

        return view('attendance-list', compact('dates', 'targetDate'));
    }

    /**
     * 勤怠詳細
     */
    public function detail($id)
    {
        $user = Auth::user();
        $attendance = Attendance::with(['breaks', 'latestCorrection.breakCorrections'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // 修正申請中かどうか
        $hasPendingCorrection = $attendance->latestCorrection && $attendance->latestCorrection->isPending();

        return view('attendance-detail', compact('attendance', 'hasPendingCorrection'));
    }

    /**
     * 修正申請
     */
    public function correct(Request $request, $id)
    {
        $request->validate([
            'corrected_start_time' => 'required',
            'corrected_end_time' => 'required',
            'reason' => 'required|string|max:500',
            'break_corrections' => 'array',
            'break_corrections.*.start_time' => 'nullable',
            'break_corrections.*.end_time' => 'nullable',
        ]);

        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)->findOrFail($id);

        // 修正申請を作成
        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'corrected_start_time' => $request->corrected_start_time,
            'corrected_end_time' => $request->corrected_end_time,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // 休憩の修正申請
        if ($request->has('break_corrections')) {
            foreach ($request->break_corrections as $index => $breakData) {
                if ($breakData['start_time'] || $breakData['end_time']) {
                    $correction->breakCorrections()->create([
                        'break_number' => $index + 1,
                        'corrected_start_time' => $breakData['start_time'],
                        'corrected_end_time' => $breakData['end_time'],
                    ]);
                }
            }
        }

        return redirect()->route('attendance.detail', $id)->with('success', '修正申請を送信しました');
    }

    /**
     * 勤務時間を計算
     */
    private function calculateWorkTime($attendance)
    {
        if (!$attendance->start_time || !$attendance->end_time) {
            return null;
        }

        $start = Carbon::parse($attendance->start_time);
        $end = Carbon::parse($attendance->end_time);
        $workMinutes = $start->diffInMinutes($end);

        // 休憩時間を引く
        $breakMinutes = $this->calculateBreakTime($attendance, true);
        $totalMinutes = $workMinutes - $breakMinutes;

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 休憩時間を計算
     */
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
