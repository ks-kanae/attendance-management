<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        if (Auth::attempt($credentials)) {
            // ログイン成功後、管理者かチェック
            if (Auth::user()->isAdmin()) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.attendance.list'));
            }

            // 一般ユーザーの場合はログアウト
            Auth::logout();
            return back()->withErrors([
                'email' => '管理者権限がありません。',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が正しくありません。',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
