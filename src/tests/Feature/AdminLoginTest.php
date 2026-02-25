<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        // 管理者を登録
        Admin::create([
            'name' => '管理者テスト',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // メールアドレス未入力でログイン
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /** @test */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        // 管理者を登録
        Admin::create([
            'name' => '管理者テスト',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // パスワード未入力でログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /** @test */
    public function 登録内容と一致しない場合、バリデーションメッセージが表示される()
    {
        // 管理者を登録
        Admin::create([
            'name' => '管理者テスト',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 登録情報と異なるメールアドレスでログイン
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
