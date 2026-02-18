<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;

class CorrectionRequestController extends Controller
{
    public function list(Request $request)
    {
        $tab = $request->input('tab', 'pending');

        if (Auth::guard('admin')->check()) {
            // 管理者用の申請一覧
            $query = AttendanceCorrection::with(['attendance', 'user'])
                ->whereHas('user', function($q) {
                    $q->where('role', 'user');
                })
                ->orderBy('created_at', 'desc');

            if ($tab === 'pending') {
                $query->where('status', 'pending');
            } elseif ($tab === 'approved') {
                $query->whereIn('status', ['approved', 'rejected']);
            }

            $corrections = $query->get();

            return view('admin.admin-correction-list', compact('corrections', 'tab'));
        } else {
            // 一般ユーザー用の申請一覧
            $user = Auth::user();
            $query = AttendanceCorrection::with(['attendance', 'user'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($tab === 'pending') {
                $query->where('status', 'pending');
            } elseif ($tab === 'approved') {
                $query->whereIn('status', ['approved', 'rejected']);
            }

            $corrections = $query->get();

            return view('correction-request-list', compact('corrections', 'tab'));
        }
    }
}
