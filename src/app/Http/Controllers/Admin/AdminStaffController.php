<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧
     */
    public function list()
    {
        // 一般ユーザーのみ取得
        $staff = User::where('role', 'user')
            ->withCount(['attendances' => function($query) {
                $query->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
            }])
            ->get();

        return view('admin.admin-staff-list', compact('staff'));
    }

    /**
     * スタッフ別勤怠一覧
     */
    public function staffAttendance(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        // 月の指定（デフォルトは今月）
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetDate = Carbon::create($year, $month, 1);

        // 指定月の勤怠データを取得
        $attendances = Attendance::where('user_id', $id)
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

        return view('admin.admin-staff-attendance', compact('staff', 'dates', 'targetDate'));
    }

    /**
     * CSV出力
     */
    public function exportCsv(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetDate = Carbon::create($year, $month, 1);

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $filename = sprintf('%s_%s年%s月_勤怠.csv', $staff->name, $year, $month);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($attendances, $targetDate, $year, $month) {
            $file = fopen('php://output', 'w');

            // BOM追加（Excel対応）
            fputs($file, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($file, ['日付', '曜日', '出勤', '退勤', '休憩時間', '勤務時間']);

            $daysInMonth = $targetDate->daysInMonth;
            $weekDays = ['日', '月', '火', '水', '木', '金', '土'];

            // 月の全日付ループ
            for ($day = 1; $day <= $daysInMonth; $day++) {

                $date = Carbon::create($year, $month, $day);

                $attendance = $attendances->first(function ($attendance) use ($date) {
                    return $attendance->date->isSameDay($date);
                });

                fputcsv($file, [
                    $date->format('Y/m/d'),
                    $weekDays[$date->dayOfWeek],
                    $attendance && $attendance->start_time
                        ? Carbon::parse($attendance->start_time)->format('H:i')
                        : '',
                    $attendance && $attendance->end_time
                        ? Carbon::parse($attendance->end_time)->format('H:i')
                        : '',
                    $attendance ? $attendance->break_time : '',
                    $attendance ? $attendance->work_time : '',
                ]);
            }

            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
