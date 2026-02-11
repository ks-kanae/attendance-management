<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;

class CorrectionRequestController extends Controller
{
    public function list(Request $request)
    {
        $user = Auth::user();

        // タブの選択（デフォルトは承認待ち）
        $tab = $request->input('tab', 'pending');

        // 修正申請を取得
        $query = AttendanceCorrection::with(['attendance', 'user'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // タブによる絞り込み
        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'approved') {
            $query->whereIn('status', ['approved', 'rejected']);
        }

        $corrections = $query->get();

        return view('correction-request-list', compact('corrections', 'tab'));
    }
}
