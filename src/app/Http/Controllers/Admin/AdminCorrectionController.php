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

        $corrections = $query->orderBy('created_at', 'desc')->get();

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
        $correction = AttendanceCorrection::with(['attendance', 'breakCorrections'])->findOrFail($id);
        $attendance = $correction->attendance;

        DB::transaction(function () use ($correction, $attendance) {

            // 出退勤反映
            $attendance->update([
                'start_time' => $correction->corrected_start_time,
                'end_time'   => $correction->corrected_end_time,
            ]);

            // 既存休憩を削除
            $attendance->breaks()->delete();

            // 申請された休憩を登録
            foreach ($correction->breakCorrections as $break) {

                if (empty($break->corrected_start_time) && empty($break->corrected_end_time)) {
                    continue;
                }

                $attendance->breaks()->create([
                    'start_time' => $break->corrected_start_time,
                    'end_time'   => $break->corrected_end_time,
                ]);
            }

            // 承認
            $correction->update([
                'status' => 'approved',
                'approved_by' => auth('admin')->id(),
            ]);
        });

        return redirect()->route('admin.correction.list');
    }

    /**
     * 承認処理
     */
    public function updateApproval(Request $request, $id)
    {
        $correction = AttendanceCorrection::whereHas('user', function($query) {
                $query->where('role', 'user');
            })
            ->findOrFail($id);

        // 承認処理
        $correction->update([
            'status' => 'approved',
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
        ]);

        // Ajax リクエストの場合
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '承認しました',
            ]);
        }

        return redirect()->route('admin.correction.list')->with('success', '承認しました');
    }
}
