<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceCorrectionRequest;
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
        $status = $attendance ? $attendance->status : 'not_working';


        return view('attendance', compact('attendance', 'status'));
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
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
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
                'total_work_time' => $attendance ? $attendance->work_time : null,
                'total_break_time' => $attendance ? $attendance->break_time : null,
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
    public function correct(AttendanceCorrectionRequest $request, $id)
    {
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

}
