<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AdminCorrectionController extends Controller
{
    public function list(Request $request)
    {
        // タブ取得（URL ?tab=pending とか）
        $tab = $request->query('tab', 'pending');

        // ステータスで絞り込み
        $query = AttendanceCorrection::with(['attendance.user']);

        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'approved') {
            $query->where('status', 'approved');
        }

        $corrections = $query->orderBy('created_at', 'asc')->get();

        return view('admin.admin-correction-list', compact('corrections', 'tab'));
    }

    /**
 * 承認詳細画面
 */
    public function show($id)
    {
        $correction = AttendanceCorrection::with([
            'attendance.user',
            'breakCorrections'
        ])->findOrFail($id);

        return view('admin.admin-correction-approve', compact('correction'));
    }

    /**
     * 承認画面表示
     */
    public function approve(Request $request, $id)
    {
        $correction = AttendanceCorrection::with('breakCorrections', 'attendance.breaks')
        ->findOrFail($id);

    if ($correction->status === 'approved') {
        return response()->json(['status' => 'error', 'message' => '既に承認済みです']);
    }

    DB::transaction(function () use ($correction) {

        $correction->update([
            'status' => 'approved',
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
        ]);

        $attendance = $correction->attendance;

        // ===== 勤怠時間更新 =====
        $attendance->update([
            'start_time' => $correction->corrected_start_time,
            'end_time'   => $correction->corrected_end_time,
            'reason'     => $correction->reason,
        ]);

        // ===== ここが重要 =====
        // 既存の休憩を全削除
        $attendance->breaks()->delete();

        // 修正休憩を再作成
        foreach ($correction->breakCorrections as $break) {
            if ($break->corrected_start_time && $break->corrected_end_time) {
                $attendance->breaks()->create([
                    'start_time' => $break->corrected_start_time,
                    'end_time'   => $break->corrected_end_time,
                ]);
            }
        }
    });

    return response()->json(['status' => 'success']);
    }
}
